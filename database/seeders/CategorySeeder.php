<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Teknologi',
                'description' => 'Diskusi tentang teknologi terbaru, gadget, dan inovasi.',
            ],
            [
                'name' => 'Olahraga',
                'description' => 'Diskusi tentang sepak bola, basket, dan olahraga lainnya.',
            ],
            [
                'name' => 'Umum',
                'description' => 'Diskusi bebas tentang apa saja.',
            ],
        ];

        foreach ($categories as $cat) {
            $category = Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
            ]);

            if ($cat['name'] === 'Teknologi') {
                Category::create([
                    'name' => 'Programming',
                    'slug' => 'programming',
                    'description' => 'Diskusi tentang bahasa pemrograman dan software development.',
                    'parent_id' => $category->id,
                ]);
            }
        }
    }
}
