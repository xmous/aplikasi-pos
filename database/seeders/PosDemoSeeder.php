<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name'=>'Owner Demo','email'=>'owner@example.com','role'=>'owner'],
            ['name'=>'Admin Demo','email'=>'admin@example.com','role'=>'admin'],
            ['name'=>'Gudang Demo','email'=>'gudang@example.com','role'=>'warehouse'],
            ['name'=>'Kasir Demo','email'=>'kasir@example.com','role'=>'cashier'],
        ] as $row) {
            $user = User::query()->firstOrNew(['email'=>$row['email']]);
            $user->forceFill([
                'name'=>$row['name'],
                'password'=>Hash::make('akunPOS!2026'),
                'email_verified_at'=>now(),
                'password_changed_at'=>now(),
                'is_active'=>true,
            ])->save();
            $user->syncRoles([$row['role']]);
        }

        $store=Store::firstOrCreate(
            ['name'=>'Toko Demo Laravel 12'],
            ['phone'=>'081234567890','email'=>'toko@example.com','address'=>'Alamat toko','receipt_footer'=>'Terima kasih sudah berbelanja']
        );
        $warehouse=Warehouse::firstOrCreate(
            ['code'=>'GDG-UTAMA'],
            ['store_id'=>$store->id,'name'=>'Gudang Utama','address'=>$store->address,'is_active'=>true]
        );
        $category=ProductCategory::firstOrCreate(['code'=>'UMUM'],['name'=>'Umum','is_active'=>true]);
        $frozen=ProductCategory::firstOrCreate(['code'=>'FROZEN'],['name'=>'Frozen Food','is_active'=>true]);
        $pcs=Unit::firstOrCreate(['symbol'=>'pcs'],['name'=>'Pieces']);
        $pack=Unit::firstOrCreate(['symbol'=>'pack'],['name'=>'Pack']);
        $dus=Unit::firstOrCreate(['symbol'=>'dus'],['name'=>'Dus']);
        Supplier::firstOrCreate(['code'=>'SUP-001'],['name'=>'Supplier Demo','phone'=>'081111111111','is_active'=>true]);
        Customer::firstOrCreate(['code'=>'CUST-001'],['name'=>'Pelanggan Member','phone'=>'082222222222','credit_limit'=>1000000,'is_active'=>true]);

        $products=[
            ['sku'=>'BRG-001','barcode'=>'899000000001','name'=>'Minuman Contoh','category'=>$category,'buy'=>5000,'sell'=>7500,'expiry'=>false,'batch'=>'GENERAL','expired'=>null],
            ['sku'=>'BRG-002','barcode'=>'899000000002','name'=>'Frozen Food Contoh','category'=>$frozen,'buy'=>15000,'sell'=>22000,'expiry'=>true,'batch'=>'FRZ-2026-01','expired'=>today()->addMonths(6)],
            ['sku'=>'BRG-003','barcode'=>'899000000003','name'=>'Snack Contoh','category'=>$category,'buy'=>8000,'sell'=>12000,'expiry'=>true,'batch'=>'SNK-2026-01','expired'=>today()->addMonths(3)],
        ];

        $stockService=app(StockService::class);
        foreach($products as $row){
            $product=Product::firstOrCreate(['sku'=>$row['sku']],[
                'product_category_id'=>$row['category']->id,'unit_id'=>$pcs->id,'barcode'=>$row['barcode'],
                'name'=>$row['name'],'purchase_price'=>$row['buy'],'average_cost'=>$row['buy'],
                'selling_price'=>$row['sell'],'wholesale_price'=>$row['sell']-1000,'wholesale_min_qty'=>10,
                'minimum_stock'=>5,'track_stock'=>true,'has_expiry'=>$row['expiry'],'is_active'=>true,
            ]);
            $product->conversionUnits()->updateOrCreate(['unit_id'=>$pack->id],[
                'conversion_factor'=>6,'barcode'=>$row['barcode'].'6',
                'selling_price'=>$row['sell']*6,'purchase_price'=>$row['buy']*6,'is_active'=>true,
            ]);
            $product->conversionUnits()->updateOrCreate(['unit_id'=>$dus->id],[
                'conversion_factor'=>24,'barcode'=>$row['barcode'].'24',
                'selling_price'=>$row['sell']*24,'purchase_price'=>$row['buy']*24,'is_active'=>true,
            ]);
            if((float)$product->stocks()->where('warehouse_id',$warehouse->id)->value('quantity')<=0){
                $stockService->increase($warehouse->id,$product,48,'opening_balance',null,'Saldo awal demo',null,$row['batch'],$row['expired']);
            }
        }

        Promotion::firstOrCreate(['code'=>'HEMAT10'],[
            'name'=>'Diskon 10%','type'=>'percent','value'=>10,'minimum_transaction'=>50000,
            'maximum_discount'=>25000,'starts_at'=>now()->subDay(),'ends_at'=>now()->addYear(),'is_active'=>true,
        ]);

        Setting::updateOrCreate(['key'=>'expiry_warning_days'],['value'=>'30']);
        Setting::updateOrCreate(['key'=>'allow_negative_stock'],['value'=>'0']);
    }
}
