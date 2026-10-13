<?php

namespace Database\Seeders;

use App\Models\Bds\BdsBatch;
use App\Models\Bds\BdsTransferRecord;
use App\Models\Bds\BdsTrayRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BdsSampleReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Batch 1000 and 1001 matching BDS Report10.092026_1 to 10
        $batches = [
            [
                'batch_id' => '1000',
                'operational_date' => '2026-09-10',
                'transport_mode' => 'AUTOMATIC',
                'operator_name' => 'CCS_BDS',
                'start_time' => Carbon::parse('2026-09-07 10:00:00'),
                'end_time' => Carbon::parse('2026-09-07 10:16:00'),
                'duration_seconds' => 960,
                'total_bags' => 64,
                'total_weight_kg' => 64.00,
                'throughput_ktg_j' => 240.00,
                'idle_duration_seconds' => 2,
            ],
            [
                'batch_id' => '1001',
                'operational_date' => '2026-09-10',
                'transport_mode' => 'AUTOMATIC',
                'operator_name' => 'CCS_BDS',
                'start_time' => Carbon::parse('2026-09-07 10:30:00'),
                'end_time' => Carbon::parse('2026-09-07 10:46:00'),
                'duration_seconds' => 960,
                'total_bags' => 64,
                'total_weight_kg' => 64.00,
                'throughput_ktg_j' => 240.00,
                'idle_duration_seconds' => 602,
            ],
        ];

        foreach ($batches as $batchData) {
            BdsBatch::updateOrCreate(['batch_id' => $batchData['batch_id']], $batchData);
        }

        // 3. Pallet & Transfer Records for 1000 and 1001
        $transferRecords = [
            // Batch 1000
            [
                'batch_id' => '1000',
                'pallet_id' => '080000001000',
                'transport_id' => '1234',
                'item_code' => 'TIDRKT22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:00:00'),
                'end_time' => Carbon::parse('2026-09-07 10:10:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1000',
                'pallet_id' => '080000001001',
                'transport_id' => '1235',
                'item_code' => 'TIDRKU22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:02:00'),
                'end_time' => Carbon::parse('2026-09-07 10:12:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1000',
                'pallet_id' => '080000001002',
                'transport_id' => '1236',
                'item_code' => 'TIDRKV22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:04:00'),
                'end_time' => Carbon::parse('2026-09-07 10:14:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1000',
                'pallet_id' => '080000001003',
                'transport_id' => '1237',
                'item_code' => 'TIDRKW22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:06:00'),
                'end_time' => Carbon::parse('2026-09-07 10:16:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            // Batch 1001
            [
                'batch_id' => '1001',
                'pallet_id' => '080000002000',
                'transport_id' => '1238',
                'item_code' => 'TIDRKT22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:30:00'),
                'end_time' => Carbon::parse('2026-09-07 10:40:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1001',
                'pallet_id' => '080000002001',
                'transport_id' => '1239',
                'item_code' => 'TIDRKU22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:32:00'),
                'end_time' => Carbon::parse('2026-09-07 10:42:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1001',
                'pallet_id' => '080000002002',
                'transport_id' => '1240',
                'item_code' => 'TIDRKV22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:34:00'),
                'end_time' => Carbon::parse('2026-09-07 10:44:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
            [
                'batch_id' => '1001',
                'pallet_id' => '080000002003',
                'transport_id' => '1241',
                'item_code' => 'TIDRKW22C1P',
                'transport_mode' => 'Automatic',
                'quantity' => 10,
                'start_time' => Carbon::parse('2026-09-07 10:36:00'),
                'end_time' => Carbon::parse('2026-09-07 10:46:00'),
                'duration_seconds' => 600,
                'throughput' => 96.00,
                'weight_kg' => 16.00,
                'source_client' => 'CCS_BDS',
            ],
        ];

        foreach ($transferRecords as $tr) {
            BdsTransferRecord::updateOrCreate(
                [
                    'batch_id' => $tr['batch_id'],
                    'pallet_id' => $tr['pallet_id'],
                    'item_code' => $tr['item_code'],
                ],
                $tr
            );
        }

        // 4. Sample Tray Records for 1000 and 1001
        $trays = [
            // Batch 1000 items (16.00kg per item)
            ['batch_id' => '1000', 'pallet_id' => '080000001000', 'tray_id' => '070000001000', 'item_code' => 'TIDRKT22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1000', 'pallet_id' => '080000001000', 'tray_id' => '070000001001', 'item_code' => 'TIDRKT22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1000', 'pallet_id' => '080000001001', 'tray_id' => '070000001010', 'item_code' => 'TIDRKU22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1000', 'pallet_id' => '080000001001', 'tray_id' => '070000001011', 'item_code' => 'TIDRKU22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1000', 'pallet_id' => '080000001002', 'tray_id' => '070000001020', 'item_code' => 'TIDRKV22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1000', 'pallet_id' => '080000001002', 'tray_id' => '070000001021', 'item_code' => 'TIDRKV22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1000', 'pallet_id' => '080000001003', 'tray_id' => '070000001030', 'item_code' => 'TIDRKW22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1000', 'pallet_id' => '080000001003', 'tray_id' => '070000001031', 'item_code' => 'TIDRKW22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],

            // Batch 1001 items
            ['batch_id' => '1001', 'pallet_id' => '080000002000', 'tray_id' => '070000002000', 'item_code' => 'TIDRKT22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1001', 'pallet_id' => '080000002000', 'tray_id' => '070000002001', 'item_code' => 'TIDRKT22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1001', 'pallet_id' => '080000002001', 'tray_id' => '070000002010', 'item_code' => 'TIDRKU22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1001', 'pallet_id' => '080000002001', 'tray_id' => '070000002011', 'item_code' => 'TIDRKU22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1001', 'pallet_id' => '080000002002', 'tray_id' => '070000002020', 'item_code' => 'TIDRKV22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1001', 'pallet_id' => '080000002002', 'tray_id' => '070000002021', 'item_code' => 'TIDRKV22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
            ['batch_id' => '1001', 'pallet_id' => '080000002003', 'tray_id' => '070000002030', 'item_code' => 'TIDRKW22C1P', 'net_weight_gm' => 1600, 'weight_kg' => 1.60],
            ['batch_id' => '1001', 'pallet_id' => '080000002003', 'tray_id' => '070000002031', 'item_code' => 'TIDRKW22C1P', 'net_weight_gm' => 14400, 'weight_kg' => 14.40],
        ];

        foreach ($trays as $tray) {
            BdsTrayRecord::updateOrCreate(
                [
                    'batch_id' => $tray['batch_id'],
                    'pallet_id' => $tray['pallet_id'],
                    'tray_id' => $tray['tray_id'],
                ],
                array_merge($tray, [
                    'transport_mode' => 'AUTOMATIC',
                    'depalletize_time' => Carbon::parse('2026-09-07 10:20:00'),
                    'feeder_start_time' => Carbon::parse('2026-09-07 10:20:05'),
                    'feeder_end_time' => Carbon::parse('2026-09-07 10:20:10'),
                    'duration_seconds' => 5,
                    'throughput' => 1152.00,
                ])
            );
        }
    }
}
