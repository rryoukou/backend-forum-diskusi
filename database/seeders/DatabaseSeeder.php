<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            BadgeSeeder::class,
        ]);

        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $userRole = \App\Models\Role::where('name', 'user')->first();

        // Create a default admin user
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@forum.com',
            'password_hash' => Hash::make('password'),
        ]);
        $admin->roles()->attach($adminRole->id, ['assigned_at' => now()]);

        // Create a default regular user
        $user = User::create([
            'username' => 'user',
            'email' => 'user@forum.com',
            'password_hash' => Hash::make('password'),
        ]);
        $user->roles()->attach($userRole->id, ['assigned_at' => now()]);

        // Create a default moderator user
        $moderatorRole = \App\Models\Role::where('name', 'moderator')->first();
        $mod = User::create([
            'username' => 'moderator',
            'email' => 'mod@forum.com',
            'password_hash' => Hash::make('password'),
        ]);
        $mod->roles()->attach($moderatorRole->id, ['assigned_at' => now()]);

        // Create some more random users
        $users = User::factory(10)->create();
        foreach ($users as $u) {
            $u->roles()->attach($userRole->id, ['assigned_at' => now()]);
        }

        // Create some dummy posts
        $categories = Category::all();
        $allUsers = User::all();

        Post::factory(20)->recycle($allUsers)->recycle($categories)->create()->each(function ($post) use ($allUsers) {
            // Add random tags to each post
            $tags = \App\Models\Tag::inRandomOrder()->limit(rand(1, 3))->get();
            $post->tags()->attach($tags);

            // Add some comments to each post
            \App\Models\Comment::factory(rand(2, 5))->recycle($allUsers)->create([
                'post_id' => $post->id,
            ]);

            // Add some votes to the post
            foreach ($allUsers->random(rand(2, 5)) as $voter) {
                if ($voter->id !== $post->user_id) {
                    \App\Models\Vote::create([
                        'user_id' => $voter->id,
                        'target_id' => $post->id,
                        'target_type' => 'post',
                        'vote_type' => rand(0, 1) ? 'upvote' : 'downvote',
                    ]);
                }
            }
        });

        // Add some bookmarks
        foreach ($allUsers as $u) {
            $randomPosts = Post::inRandomOrder()->limit(rand(1, 3))->get();
            foreach ($randomPosts as $rp) {
                \App\Models\Bookmark::create([
                    'user_id' => $u->id,
                    'post_id' => $rp->id,
                ]);
            }
        }

        // Add some notifications
        foreach ($allUsers as $u) {
            \App\Services\NotificationService::send($u->id, 'welcome', null, 'system', null);
        }

        // Call the additional seeders
        $this->call([
            ModerationSeeder::class,
            SocialSeeder::class,
        ]);
    }
}
