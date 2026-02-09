<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banco;

class BancoSeeder extends Seeder
{
    public function run(): void
    {
        $bancos = [
            'Bancolombia',
            'Nequi',
            'Llave',
        ];

        foreach ($bancos as $nombre) {
            Banco::create(['nombre' => $nombre]);
        }
    }
}
