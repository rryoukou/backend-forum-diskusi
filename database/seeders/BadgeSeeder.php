<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            // Reputation Based
            [
                'name' => 'Warga Baru',
                'description' => 'Mencapai 100 reputasi poin.',
                'tier' => 'bronze',
                'condition_type' => 'reputation_points',
                'condition_value' => 100,
            ],
            [
                'name' => 'Kontributor Aktif',
                'description' => 'Mencapai 500 reputasi poin.',
                'tier' => 'silver',
                'condition_type' => 'reputation_points',
                'condition_value' => 500,
            ],
            [
                'name' => 'Pakar Forum',
                'description' => 'Mencapai 1000 reputasi poin.',
                'tier' => 'gold',
                'condition_type' => 'reputation_points',
                'condition_value' => 1000,
            ],
            // Activity Based
            [
                'name' => 'Penulis Produktif',
                'description' => 'Membuat 10 postingan.',
                'tier' => 'bronze',
                'condition_type' => 'posts_count',
                'condition_value' => 10,
            ],
            [
                'name' => 'Jawaban Terpercaya',
                'description' => '5 jawaban diterima sebagai solusi.',
                'tier' => 'silver',
                'condition_type' => 'answers_accepted',
                'condition_value' => 5,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::create($badge);
        }
    }
}
