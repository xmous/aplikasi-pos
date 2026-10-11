<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\CashierShift;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\NumberService;
use App\Services\PromotionService;
use App\Services\LoyaltyPointService;
use App\Services\StockService;
use App\Services\ThermalReceiptService;
use App\Services\UnitConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(
        Request $request,
        StockService $stockService,
        LoyaltyPointService $loyaltyPointService
    ): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()
            ->with(['category', 'unit', 'conversionUnits.unit'])
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term)
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $term))
                        ->orWhereHas('conversionUnits', fn ($unit) => $unit->where('barcode', 'like', $term));
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($warehouses, $stockService): Product {
                $product->sellable_stocks = $warehouses->mapWithKeys(fn (Warehouse $warehouse) => [
                    (string) $warehouse->id => $product->track_stock
                        ? $stockService->sellableQuantity($warehouse->id, $product->id)
                        : null,
                ])->all();
                $product->sellable_stock = $product->sellable_stocks[(string) optional($warehouses->first())->id] ?? null;

                return $product;
            });

        return view('sales.pos', [
            'products' => $products,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => $warehouses,
            'promotions' => Promotion::query()
                ->with('products:id')
                ->currentlyActive()
                ->orderBy('name')
                ->get(),
            'openShift' => CashierShift::query()->where('user_id', auth()->id())->where('status', 'open')->latest('opened_at')->first(),
            'search' => $search,
            'loyalty' => $loyaltyPointService->settings(),
        ]);
    }

    public function store(
        SaleRequest $request,
        StockService $stockService,
        PromotionService $promotionService,
        UnitConversionService $conversionService,
        LoyaltyPointService $loyaltyPointService
    ): RedirectResponse {
        $sale = DB::transaction(function () use (
            $request,
            $stockService,
            $promotionService,
            $conversionService,
            $loyaltyPointService
        ) {
            $shift = CashierShift::query()->where('user_id',auth()->id())->where('status','open')->lockForUpdate()->latest('opened_at')->first();
            if (! $shift) throw ValidationException::withMessages(['shift'=>'Buka shift kasir terlebih dahulu.']);

            $items = collect($request->validated('items'));
            $products = Product::query()->with(['unit','conversionUnits.unit'])->whereIn('id',$items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $prepared = collect();

            foreach ($items as $item) {
                $product = $products->get((int)$item['product_id']);
                if (! $product?->is_active) throw ValidationException::withMessages(['items'=>'Barang tidak aktif atau tidak ditemukan.']);
                $unit = $conversionService->resolve($product, !empty($item['product_unit_id'])?(int)$item['product_unit_id']:null);
                $qty = (float)$item['quantity'];
                $baseQty = $conversionService->toBase($qty,$unit['factor']);
                $price = $unit['product_unit'] ? $unit['selling_price'] : $product->priceFor($baseQty);
                $allowedItemDiscount = $request->user()->can('sales.discount');
                $lineDiscount = $allowedItemDiscount ? min((float)($item['discount'] ?? 0),$qty*$price) : 0;
                $prepared->push([
                    'product_id'=>$product->id,'product'=>$product,'product_unit_id'=>$unit['product_unit']?->id,
                    'unit_id'=>$unit['unit_id'],'unit_symbol'=>$unit['symbol'],'quantity'=>$qty,
                    'conversion_factor'=>$unit['factor'],'base_quantity'=>$baseQty,'unit_price'=>$price,
                    'unit_cost'=>(float)$product->average_cost*$unit['factor'],
                    'discount'=>$lineDiscount,'subtotal'=>max(0,$qty*$price-$lineDiscount),
                ]);
            }

            $subtotal = (float)$prepared->sum('subtotal');
            $promotion = $request->filled('promotion_id') ? Promotion::findOrFail($request->integer('promotion_id')) : null;
            $promoDiscount = $promotionService->calculate($promotion,$prepared,$subtotal);
            $manualDiscount = $request->user()->can('sales.discount')
                ? min(
                    (float) $request->input('manual_discount', 0),
                    max(0, $subtotal - $promoDiscount)
                )
                : 0;

            $customer = $request->filled('customer_id')
                ? Customer::query()
                    ->whereKey($request->integer('customer_id'))
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail()
                : null;

            $beforePointDiscount = max(
                0,
                $subtotal - $promoDiscount - $manualDiscount
            );

            $redemption = $loyaltyPointService->resolveRedemption(
                $customer,
                $beforePointDiscount,
                max(0, $request->integer('redeem_points'))
            );

            $pointSettings = $loyaltyPointService->settings();
            $total = max(
                0,
                $beforePointDiscount - $redemption['discount']
            );

            $potentialPoints = $customer
                ? $loyaltyPointService->calculateEarned($total)
                : 0;

            $payments = collect($request->input('payments',[]))
                ->map(fn($payment)=>[
                    'method'=>$payment['method'] ?? null,'amount'=>(float)($payment['amount'] ?? 0),
                    'reference_number'=>$payment['reference_number'] ?? null,
                ])->filter(fn($payment)=>$payment['amount']>0)->values();

            $received = (float)$payments->sum('amount');

            if ($total > 0 && $received <= 0) {
                throw ValidationException::withMessages([
                    'payments' => 'Belum ada pembayaran. Masukkan minimal satu nominal pembayaran.',
                ]);
            }

            $cashReceived = (float)$payments->where('method','cash')->sum('amount');
            $nonCashReceived = $received-$cashReceived;
            if($nonCashReceived>$total) throw ValidationException::withMessages(['payments'=>'Pembayaran non-tunai tidak boleh melebihi total transaksi.']);
            $change=max(0,$received-$total);
            if($change>$cashReceived) throw ValidationException::withMessages(['payments'=>'Kelebihan pembayaran hanya boleh berasal dari pembayaran tunai.']);

            $appliedPaid = min($received,$total);
            $balance = $total-$appliedPaid;
            if ($balance > 0 && ! $customer) throw ValidationException::withMessages(['customer_id'=>'Pelanggan wajib dipilih untuk transaksi piutang.']);
            if ($balance > 0 && ! $request->filled('due_date')) throw ValidationException::withMessages(['due_date'=>'Tanggal jatuh tempo piutang wajib diisi.']);
            if ($customer && (float)$customer->credit_limit > 0) {
                $existing = (float)$customer->sales()->whereIn('payment_status',['unpaid','partial'])
                    ->selectRaw('COALESCE(SUM(total-paid_amount),0) total')->value('total');
                if ($existing+$balance > (float)$customer->credit_limit) throw ValidationException::withMessages(['customer_id'=>'Limit kredit pelanggan terlampaui.']);
            }

            $warehouse = Warehouse::query()->whereKey($request->integer('warehouse_id'))->where('is_active', true)->firstOrFail();
            $sale = Sale::create([
                'number'=>NumberService::make('INV'),'store_id'=>$warehouse->store_id,'warehouse_id'=>$warehouse->id,
                'cashier_shift_id'=>$shift->id,'customer_id'=>$customer?->id,'cashier_id'=>auth()->id(),
                'promotion_id'=>$promotion?->id,'sold_at'=>now(),'due_date'=>$request->date('due_date'),
                'subtotal'=>$subtotal,'promotion_discount'=>$promoDiscount,'manual_discount'=>$manualDiscount,
                'total'=>$total,'paid_amount'=>$appliedPaid,'change_amount'=>$change,
                'payment_status'=>$this->paymentStatus($appliedPaid,$total),'status'=>'completed','notes'=>$request->input('notes'),
                'points_earned'=>$potentialPoints,
                'points_redeemed'=>$redemption['points'],
                'points_discount'=>$redemption['discount'],
                'points_earn_amount'=>$pointSettings['earn_amount'],
                'points_redeem_value'=>$pointSettings['redeem_value'],
            ]);

            foreach ($prepared as $item) {
                $product = $item['product'];
                $sale->items()->create([
                    'product_id'=>$product->id,'product_unit_id'=>$item['product_unit_id'],'unit_id'=>$item['unit_id'],
                    'product_name'=>$product->name,'sku'=>$product->sku,'quantity'=>$item['quantity'],
                    'conversion_factor'=>$item['conversion_factor'],'base_quantity'=>$item['base_quantity'],
                    'unit_price'=>$item['unit_price'],'unit_cost'=>$item['unit_cost'],
                    'discount'=>$item['discount'],'subtotal'=>$item['subtotal'],
                ]);
                if ($product->track_stock) {
                    $stockService->decreaseFefo($warehouse->id,$product,$item['base_quantity'],'sale',$sale,'Penjualan '.$sale->number,auth()->id());
                }
            }

            if ($appliedPaid > 0) {
                $remainingToApply=$total;$remainingChange=$change;$cashApplied=0;
                foreach($payments as $payment){
                    $available=$payment['amount'];
                    if($payment['method']==='cash' && $remainingChange>0){$changeFromThisRow=min($available,$remainingChange);$available-=$changeFromThisRow;$remainingChange-=$changeFromThisRow;}
                    $amountToApply=min($available,$remainingToApply);
                    if($payment['method']==='cash') $cashApplied+=$amountToApply;
                    if($amountToApply<=0) continue;
                    $sale->payments()->create(['created_by'=>auth()->id(),'method'=>$payment['method'],'amount'=>$amountToApply,'paid_at'=>now(),'reference_number'=>$payment['reference_number'],'notes'=>'Pembayaran transaksi']);
                    $remainingToApply=max(0,$remainingToApply-$amountToApply);
                }
                if($cashApplied>0) $shift->increment('expected_cash',$cashApplied);
            }

            if ($customer && $redemption['points'] > 0) {
                $loyaltyPointService->redeem(
                    $customer,
                    $sale,
                    $redemption['points'],
                    $redemption['discount'],
                    auth()->id()
                );
            }

            if ($sale->payment_status === 'paid') {
                $loyaltyPointService->awardForPaidSale(
                    $sale,
                    auth()->id()
                );
            }

            $sale->refresh();

            AuditService::log('sale.created',$sale,[
                'total'=>$total,
                'balance'=>$balance,
                'points_earned'=>(int)$sale->points_earned,
                'points_redeemed'=>(int)$sale->points_redeemed,
                'points_discount'=>(float)$sale->points_discount,
            ]);
            return $sale;
        },3);

        $message = 'Transaksi berhasil disimpan.';

        if ((int) $sale->points_redeemed > 0) {
            $message .= ' '
                .number_format($sale->points_redeemed, 0, ',', '.')
                .' poin digunakan.';
        }

        if ((int) $sale->points_earned > 0) {
            $message .= $sale->points_awarded_at
                ? ' '.number_format(
                    $sale->points_earned,
                    0,
                    ',',
                    '.'
                ).' poin ditambahkan ke pelanggan.'
                : ' '.number_format(
                    $sale->points_earned,
                    0,
                    ',',
                    '.'
                ).' poin akan masuk setelah transaksi lunas.';
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', $message);
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items.unit','items.productUnit.unit','payments','cashier','customer','promotion','returns']);
        return view('sales.receipt',compact('sale'));
    }

    public function addPayment(
        Sale $sale,
        Request $request,
        LoyaltyPointService $loyaltyPointService
    ): RedirectResponse
    {
        $data = $request->validate(['amount'=>['required','numeric','gt:0'],'method'=>['required','in:cash,qris,transfer,debit,ewallet'],'reference_number'=>['nullable','max:255'],'notes'=>['nullable','string','max:2000']]);
        $awardedPoints = DB::transaction(function () use (
            $sale,
            $data,
            $loyaltyPointService
        ) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $amount = min((float) $data['amount'], $sale->balance);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Piutang sudah lunas.']);
            }

            $shift = null;
            if ($data['method'] === 'cash') {
                $shift = CashierShift::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->latest('opened_at')
                    ->first();

                if (! $shift) {
                    throw ValidationException::withMessages([
                        'method' => 'Buka shift kasir sebelum menerima pembayaran piutang tunai.',
                    ]);
                }
            }

            $sale->payments()->create([
                'created_by' => auth()->id(),
                'method' => $data['method'],
                'amount' => $amount,
                'paid_at' => now(),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = (float) $sale->paid_amount + $amount;
            $sale->update([
                'paid_amount' => $paid,
                'payment_status' => $this->paymentStatus($paid, $sale->net_total),
            ]);

            if ($shift) {
                $shift->increment('expected_cash', $amount);
                CashTransaction::create([
                    'cashier_shift_id' => $shift->id,
                    'created_by' => auth()->id(),
                    'type' => 'in',
                    'amount' => $amount,
                    'category' => 'receivable_payment',
                    'notes' => 'Pembayaran piutang '.$sale->number,
                ]);
            }

            $sale->refresh();

            $awardedPoints = $loyaltyPointService->awardForPaidSale(
                $sale,
                auth()->id()
            );

            AuditService::log('sale.payment_added', $sale, [
                'amount' => $amount,
                'method' => $data['method'],
                'points_awarded' => $awardedPoints,
            ]);

            return $awardedPoints;
        });

        $message = 'Pembayaran piutang berhasil dicatat.';

        if ($awardedPoints > 0) {
            $message .= ' '
                .number_format($awardedPoints, 0, ',', '.')
                .' poin pelanggan telah ditambahkan.';
        }

        return back()->with('success', $message);
    }

    public function thermalText(Sale $sale, ThermalReceiptService $service)
    {
        return response($service->text($sale),200,['Content-Type'=>'text/plain; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$sale->number.'.txt"']);
    }

    private function paymentStatus(float $paid,float $total): string { return $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'); }
}
