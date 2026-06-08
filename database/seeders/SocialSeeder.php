<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Seeder;

class SocialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $posts = Post::all();
        $comments = Comment::all();
        $badges = Badge::all();

        if ($users->count() < 2) return;

        // 1. Seed Follows
        foreach ($users as $user) {
            $toFollow = $users->where('id', '!=', $user->id)->random(rand(1, 3));
            foreach ($toFollow as $followed) {
                \App\Models\Follow::firstOrCreate([
                    'follower_id' => $user->id,
                    'following_id' => $followed->id,
                ]);
            }
        }

        // 2. Seed Likes for Posts and Comments
        foreach ($posts->random(10) as $post) {
            foreach ($users->random(rand(1, 4)) as $liker) {
                Like::firstOrCreate([
                    'user_id' => $liker->id,
                    'target_id' => $post->id,
                    'target_type' => 'post',
                ]);
            }
        }

        foreach ($comments->random(10) as $comment) {
            foreach ($users->random(rand(1, 3)) as $liker) {
                Like::firstOrCreate([
                    'user_id' => $liker->id,
                    'target_id' => $comment->id,
                    'target_type' => 'comment',
                ]);
            }
        }

        // 3. Seed some User Badges
        foreach ($users->random(5) as $user) {
            $randomBadge = $badges->random();
            UserBadge::firstOrCreate([
                'user_id' => $user->id,
                'badge_id' => $randomBadge->id,
            ], [
                'earned_at' => now(),
            ]);
        }
    }
}
