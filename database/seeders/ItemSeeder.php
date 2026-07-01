<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');

        $items = [
            ['category' => 'Kamera', 'code' => 'CAM-001', 'name' => 'Canon EOS 80D', 'brand' => 'Canon', 'model' => 'EOS 80D', 'daily_price' => 150000, 'stock' => 3],
            ['category' => 'Kamera', 'code' => 'CAM-002', 'name' => 'Sony A6400', 'brand' => 'Sony', 'model' => 'A6400', 'daily_price' => 175000, 'stock' => 2],
            ['category' => 'Kamera', 'code' => 'CAM-003', 'name' => 'Nikon D750', 'brand' => 'Nikon', 'model' => 'D750', 'daily_price' => 200000, 'stock' => 2],
            ['category' => 'Kamera', 'code' => 'CAM-004', 'name' => 'Fujifilm X-T30', 'brand' => 'Fujifilm', 'model' => 'X-T30', 'daily_price' => 160000, 'stock' => 2],
            ['category' => 'Lensa', 'code' => 'LEN-001', 'name' => 'Canon EF 50mm f/1.8', 'brand' => 'Canon', 'model' => 'EF 50mm f/1.8', 'daily_price' => 75000, 'stock' => 4],
            ['category' => 'Lensa', 'code' => 'LEN-002', 'name' => 'Sony 35mm f/1.8', 'brand' => 'Sony', 'model' => '35mm f/1.8', 'daily_price' => 90000, 'stock' => 3],
            ['category' => 'Tripod', 'code' => 'TRI-001', 'name' => 'Tripod Manfrotto', 'brand' => 'Manfrotto', 'model' => null, 'daily_price' => 50000, 'stock' => 5],
            ['category' => 'Lighting', 'code' => 'LGT-001', 'name' => 'Godox SL60W', 'brand' => 'Godox', 'model' => 'SL60W', 'daily_price' => 85000, 'stock' => 3],
            ['category' => 'Microphone', 'code' => 'MIC-001', 'name' => 'Rode VideoMic', 'brand' => 'Rode', 'model' => 'VideoMic', 'daily_price' => 65000, 'stock' => 4],
            ['category' => 'Kamera', 'code' => 'GMB-001', 'name' => 'DJI Ronin SC', 'brand' => 'DJI', 'model' => 'Ronin SC', 'daily_price' => 120000, 'stock' => 2],
        ];

        foreach ($items as $item) {
            Item::updateOrCreate(
                ['code' => $item['code']],
                [
                    'category_id' => $categories[$item['category']],
                    'name' => $item['name'],
                    'brand' => $item['brand'],
                    'model' => $item['model'],
                    'description' => $item['name'].' siap disewa untuk kebutuhan fotografi dan videografi.',
                    'daily_price' => $item['daily_price'],
                    'stock' => $item['stock'],
                    'status' => 'available',
                ],
            );
        }
    }
}
