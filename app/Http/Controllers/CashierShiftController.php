<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Store;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashierShiftController extends Controller
{
    public function index(): View
    {
        $shiftQuery = CashierShift::query()
            ->where('user_id', auth()->id())
            ->withCount('sales')
            ->withSum('sales', 'total');

        return view('shifts.index', [
            'openShift' => (clone $shiftQuery)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first(),
            'shifts' => (clone $shiftQuery)
                ->latest('opened_at')
                ->paginate(20),
            'store' => Store::query()->first(),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'store_id' => ['required', 'exists:stores,id'],
                'opening_cash' => ['required', 'integer', 'min:0', 'max:999999999999'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'opening_cash.integer' => 'Saldo awal hanya boleh berisi angka bulat.',
                'opening_cash.min' => 'Saldo awal tidak boleh kurang dari nol.',
            ]
        );
        DB::transaction(function () use ($data): void {
            \App\Models\User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            if (CashierShift::query()->where('user_id',auth()->id())->where('status','open')->exists()) {
                throw ValidationException::withMessages(['shift'=>'Masih ada shift terbuka.']);
            }
            $shift = CashierShift::create([
                'store_id'=>$data['store_id'],'user_id'=>auth()->id(),'opened_at'=>now(),
                'opening_cash'=>$data['opening_cash'],'expected_cash'=>$data['opening_cash'],'status'=>'open','notes'=>$data['notes'] ?? null,
            ]);
            AuditService::log('shift.opened',$shift);
        }, 3);
        return back()->with('success','Shift kasir dibuka.');
    }

    public function close(Request $request,CashierShift $cashierShift): RedirectResponse
    {
        $data = $request->validate(
            [
                'actual_cash' => ['required', 'integer', 'min:0', 'max:999999999999'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'actual_cash.integer' => 'Kas fisik hanya boleh berisi angka bulat.',
                'actual_cash.min' => 'Kas fisik tidak boleh kurang dari nol.',
            ]
        );
        DB::transaction(function () use ($cashierShift, $data): void {
            $shift = CashierShift::query()->whereKey($cashierShift->id)->lockForUpdate()->firstOrFail();
            abort_unless($shift->user_id===auth()->id() && $shift->status==='open',403);
            $shift->update([
                'closed_at'=>now(),'actual_cash'=>$data['actual_cash'],
                'cash_difference'=>(float)$data['actual_cash']-(float)$shift->expected_cash,
                'status'=>'closed','notes'=>$data['notes'] ?? $shift->notes,
            ]);
            AuditService::log('shift.closed',$shift);
        }, 3);
        return back()->with('success','Shift kasir ditutup.');
    }
}
