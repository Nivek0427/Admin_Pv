<?php

namespace Database\Seeders;

use App\Models\Talla;
use Illuminate\Database\Seeder;

class TallaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(35, 45) as $numero) {
            Talla::firstOrCreate([
                'numero' => $numero,
            ]);
        }
    }
}
