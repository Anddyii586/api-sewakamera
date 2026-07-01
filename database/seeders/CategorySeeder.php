<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kamera', 'Lensa', 'Tripod', 'Lighting', 'Microphone'] as $name) {
            Category::updateOrCreate(
                ['name' => $name],
                [
                    'description' => 'Kategori '.$name.' untuk rental kamera dan peralatan fotografi.',
                    'status' => 'active',
                ],
            );
        }
    }
}
