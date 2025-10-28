<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Women',
                'slug' => 'women-fashion',
                'status' => 'active',
            ],
            [
                'name' => 'Men',
                'slug' => 'men-fashion',
                'status' => 'active',
            ],
            [
                'name' => 'Children',
                'slug' => 'children-fashion',
                'status' => 'active',
            ],
            [
                'name' => 'Jewellery and Accessories',
                'slug' => 'jewellery-and-accessories',
                'status' => 'active',
            ],
            [
                'name' => 'Wedding/Party Shop',
                'slug' => 'wedding-party-shop',
                'status' => 'active',
            ],
            [
                'name' => 'Designer',
                'slug' => 'designer',
                'status' => 'active',
            ],
           
        ]);
    }
}
