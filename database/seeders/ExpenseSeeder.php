<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use Faker\Factory as Faker;

class ExpenseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();


        $expenses = [
            ['note' => 'Gaji Bu Ita', 'amount' => 1600000, 'created_at' => now()],
            ['note' => 'Tab Haji Bu Ita', 'amount' => 500000, 'created_at' => now()],
            ['note' => 'Belanja P Budi 29/8/2023', 'amount' => 224600, 'created_at' => now()],
            ['note' => 'Marno Batako', 'amount' => 600000, 'created_at' => now()],
            ['note' => 'Belanja Besi', 'amount' => 8447500, 'created_at' => now()],
            ['note' => 'Air Minum', 'amount' => 10000, 'created_at' => now()],
            ['note' => 'Bensin P Budi', 'amount' => 37500, 'created_at' => now()],
            ['note' => 'Tukang Kaliurang', 'amount' => 5386800, 'created_at' => now()],
            ['note' => 'Tebang pohon', 'amount' => 400000, 'created_at' => now()],
            ['note' => 'Gaji P Ali', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'SPP Ataya s/d Agustus', 'amount' => 4462500, 'created_at' => now()],
            ['note' => 'Pulsa 3 kantor', 'amount' => 30000, 'created_at' => now()],
            ['note' => 'Avail', 'amount' => 280000, 'created_at' => now()],
            ['note' => 'Parkir Mandiri 2x', 'amount' => 4000, 'created_at' => now()],
            ['note' => 'Gaji Mb Tari', 'amount' => 1500000, 'created_at' => now()],
            ['note' => 'Bangun Indah DP Pilar', 'amount' => 2000000, 'created_at' => now()],
            ['note' => 'Sumur Tirta', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Gaji Mb Erni (TF)', 'amount' => 1500000, 'created_at' => now()],
            ['note' => 'PBB Kaliurang 2023', 'amount' => 209500, 'created_at' => now()],
            ['note' => 'Putra Andalas', 'amount' => 1779500, 'created_at' => now()],
            ['note' => 'Djoyo Baja nota tgl 8/8/23', 'amount' => 10770000, 'created_at' => now()],
            ['note' => 'Djoyo Baja (TF) nota tgl 22/8/23', 'amount' => 2300400, 'created_at' => now()],
            ['note' => 'Belanja P Ali', 'amount' => 3335000, 'created_at' => now()],
            ['note' => 'Daftar sekolah SMA Ata', 'amount' => 450000, 'created_at' => now()],
            ['note' => 'Kas RT Kaliurang', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'Sampah September', 'amount' => 50000, 'created_at' => now()],
            ['note' => 'Gudeg', 'amount' => 36000, 'created_at' => now()],
            ['note' => 'Becak Mb Erni + Tari', 'amount' => 20000, 'created_at' => now()],
            ['note' => 'Bensin P Budi', 'amount' => 25000, 'created_at' => now()],
            ['note' => 'Gaji P Ali', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'Yangti Krapyak', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Tutup Drainase', 'amount' => 330000, 'created_at' => now()],
            ['note' => 'Nopran', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Tukang Kaliurang', 'amount' => 4395050, 'created_at' => now()],
            ['note' => 'Air Minum', 'amount' => 10000, 'created_at' => now()],
            ['note' => 'Putro Pangestu', 'amount' => 850000, 'created_at' => now()],
            ['note' => 'Keset', 'amount' => 1000, 'created_at' => now()],
            ['note' => 'Putra Andalas', 'amount' => 3000000, 'created_at' => now()],
            ['note' => 'Gaji Mb Ninda', 'amount' => 1629200, 'created_at' => now()],
            ['note' => 'Les Ataya (Wakhidin) + admin', 'amount' => 552500, 'created_at' => now()],
            ['note' => 'Konsumsi Cor', 'amount' => 190000, 'created_at' => now()],
            ['note' => 'Semen (Cor) Kaliurang', 'amount' => 1620000, 'created_at' => now()],
            ['note' => 'Karet+plastik', 'amount' => 10000, 'created_at' => now()],
            ['note' => 'UMD (isi klip)', 'amount' => 8000, 'created_at' => now()],
            ['note' => 'Eko Sliro', 'amount' => 1250000, 'created_at' => now()],
            ['note' => 'Kolam Renang', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Gaji P Ali', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'Bensin P Budi', 'amount' => 37500, 'created_at' => now()],
            ['note' => 'Konsumsi Cor Hari 1', 'amount' => 360000, 'created_at' => now()],
            ['note' => 'Marno Batako', 'amount' => 3600000, 'created_at' => now()],
            ['note' => 'Tukang Wonosobo', 'amount' => 5155000, 'created_at' => now()],
            ['note' => 'Tukang Kaliurang', 'amount' => 5142900, 'created_at' => now()],
            ['note' => 'Guyub', 'amount' => 800000, 'created_at' => now()],
            ['note' => 'Belanja P Ali', 'amount' => 3335000, 'created_at' => now()],
            ['note' => 'Daftar sekolah SMA Ata', 'amount' => 450000, 'created_at' => now()],
            ['note' => 'Kas RT Kaliurang', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'Sampah September', 'amount' => 50000, 'created_at' => now()],
            ['note' => 'Gudeg', 'amount' => 36000, 'created_at' => now()],
            ['note' => 'Becak Mb Erni + Tari', 'amount' => 20000, 'created_at' => now()],
            ['note' => 'Bensin P Budi', 'amount' => 25000, 'created_at' => now()],
            ['note' => 'Gaji P Ali', 'amount' => 750000, 'created_at' => now()],
            ['note' => 'Yangti Krapyak', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Tutup Drainase', 'amount' => 330000, 'created_at' => now()],
            ['note' => 'Nopran', 'amount' => 1000000, 'created_at' => now()],
            ['note' => 'Air Minum', 'amount' => 10000, 'created_at' => now()],
            ['note' => 'Keset', 'amount' => 1000, 'created_at' => now()],
            ['note' => 'Gaji Mb Ninda', 'amount' => 1629200, 'created_at' => now()],
            // Add more entries as needed
        ];



        foreach ($expenses as $expense) {
            Expense::create([
                'note' => $expense['note'],
                'amount' => $expense['amount'],
                'created_at' => $faker->dateTimeBetween('2023-08-01', '2023-11-30'),
            ]);
        }
    }
}
