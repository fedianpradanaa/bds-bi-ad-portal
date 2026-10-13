<?php

use App\Models\Bds\BdsSupervisor;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::whereIn('username', [
            'gorainap',
            'bambang.setyo',
            'm.ridwan',
        ])->delete();

        if (class_exists(BdsSupervisor::class)) {
            BdsSupervisor::where('name', 'Gorainap')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for dummy account cleanup
    }
};
