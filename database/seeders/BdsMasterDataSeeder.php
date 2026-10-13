<?php

namespace Database\Seeders;

use App\Models\Bds\BdsDenomination;
use App\Models\Bds\BdsEmission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BdsMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Master Denominasi Uang Kertas Rupiah
        $denominations = [
            ['code' => 'S', 'value' => 1000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'T', 'value' => 2000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'U', 'value' => 5000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'V', 'value' => 10000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'W', 'value' => 20000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'X', 'value' => 50000.00, 'currency_type' => 'BANKNOTE'],
            ['code' => 'Y', 'value' => 100000.00, 'currency_type' => 'BANKNOTE'],
        ];

        foreach ($denominations as $denom) {
            BdsDenomination::updateOrCreate(['code' => $denom['code']], $denom);
        }

        // 2. Master Tahun Emisi Rupiah
        $emissions = [
            ['code' => '20', 'year' => 2020],
            ['code' => '22', 'year' => 2022],
        ];

        foreach ($emissions as $em) {
            BdsEmission::updateOrCreate(['code' => $em['code']], $em);
        }

        // 3. Ensure Operator Role exists
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    }
}
