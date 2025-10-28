<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subcategories')->insert([
            [
                'name' => 'Lengha',
                'slug' => 'lengha',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Shalara',
                'slug' => 'shalara',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Sari',
                'slug' => 'sari',
                'category_id' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'Salwar/Kameez',
                'slug' => 'salwar-kameez',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Abaya/Kaftan',
                'slug' => 'abaya-kaftan',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Open Material',
                'slug' => 'open-material',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Others',
                'slug' => 'others',
                'category_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Sherwani',
                'slug' => 'sherwani',
                'category_id' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Shoes',
                'slug' => 'shoes',
                'category_id' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Others',
                'slug' => 'others',
                'category_id' => 2,
                'status' => 'active'
            ],
           
            [
                'name' => 'Children\'s Clothing',
                'slug' => 'childrens-clothing',
                'category_id' => 3,
                'status' => 'active'
            ],
             [
                'name' => 'Bridal Sets',
                'slug' => 'bridal-sets',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Necklace Sets',
                'slug' => 'necklace-sets',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Earrings',
                'slug' => 'earrings',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Shoes',
                'slug' => 'shoes',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Clutch Bags',
                'slug' => 'clutch-bags',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Others',
                'slug' => 'others',
                'category_id' => 4,
                'status' => 'active'
            ],
            [
                'name' => 'Décor',
                'slug' => 'decor',
                'category_id' => 5,
                'status' => 'active'
            ],
            [
                'name' => 'Others',
                'slug' => 'others',
                'category_id' => 5,
                'status' => 'active'
            ],
            [
                'name' => 'Party Wear',
                'slug' => 'party-wear',
                'category_id' => 5,
                'status' => 'active'
            ],
            [
                'name' => 'Designer',
                'slug' => 'designer',
                'category_id' => 6,
                'status' => 'active'
            ],
           

        ]);
    }
}
