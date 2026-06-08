<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModerationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function($q) {
            $q->where('name', 'user');
        })->get();

        $moderators = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['moderator', 'admin']);
        })->get();

        $posts = Post::all();
        $comments = Comment::all();

        if ($users->isEmpty() || $moderators->isEmpty() || $posts->isEmpty()) {
            return;
        }

        // 1. Seed Reports for Posts
        foreach ($posts->random(3) as $post) {
            Report::create([
                'reporter_id' => $users->random()->id,
                'target_id' => $post->id,
                'target_type' => 'post',
                'reason' => 'Spam',
                'description' => 'Ini adalah postingan spam yang berulang kali muncul.',
                'status' => 'pending',
            ]);
        }

        // 2. Seed Reports for Comments
        foreach ($comments->random(3) as $comment) {
            Report::create([
                'reporter_id' => $users->random()->id,
                'target_id' => $comment->id,
                'target_type' => 'comment',
                'reason' => 'Harassment',
                'description' => 'Komentar ini mengandung kata-kata kasar.',
                'status' => 'pending',
            ]);
        }

        // 3. Seed Resolved Reports
        $resolvedPost = $posts->random();
        Report::create([
            'reporter_id' => $users->random()->id,
            'target_id' => $resolvedPost->id,
            'target_type' => 'post',
            'reason' => 'Inappropriate Content',
            'description' => 'Konten tidak pantas.',
            'status' => 'resolved',
            'resolved_by' => $moderators->random()->id,
            'resolved_at' => now(),
        ]);

        // 4. Seed Moderation Logs
        foreach ($users->random(3) as $targetUser) {
            ModerationLog::create([
                'moderator_id' => $moderators->random()->id,
                'target_user_id' => $targetUser->id,
                'action_type' => 'warn_user',
                'reason' => 'Melanggar peraturan komunitas pasal 1.',
                'notes' => 'Peringatan pertama diberikan melalui pesan privat.',
            ]);
        }

        ModerationLog::create([
            'moderator_id' => $moderators->random()->id,
            'target_user_id' => $users->random()->id,
            'action_type' => 'delete_post',
            'reason' => 'Konten mengandung SARA.',
            'notes' => 'Postingan telah dihapus secara permanen.',
        ]);
    }
}
