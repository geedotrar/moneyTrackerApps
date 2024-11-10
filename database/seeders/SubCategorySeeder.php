<?php

namespace Database\Seeders;

use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subCategories = [
            [
                'category_id' => 1,
                'name' => 'subCategory-1',
                'description' => 'desc-1'
            ],
            [
                'category_id' => 2,
                'name' => 'subCategory-2',
                'description' => 'desc-2',
            ],
        ];

        foreach ($subCategories as $subCategory) {
            if (SubCategory::where('name', $subCategory['name'])->doesntExist()) {
                SubCategory::create($subCategory);
            }
        }
    }
}
