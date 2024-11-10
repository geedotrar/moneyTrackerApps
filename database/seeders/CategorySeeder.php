<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catgories = [
            [
                'name' => 'category-1',
                'description' => 'desc-1'
            ],
            [
                'name' => 'category-2',
                'description' => 'desc-2',
            ],
        ];

        foreach ($catgories as $category) {
            if (Category::where('name', $category['name'])->doesntExist()) {
                Category::create($category);
            }
        }
    }
}
