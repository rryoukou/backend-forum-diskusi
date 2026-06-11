<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get profile by username.
     */
    public function show(string $username)
    {
        $user = User::withCount(['posts', 'followers', 'following'])
            ->with(['badges'])
            ->where('username', $username)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Get posts by a specific user.
     */
    public function posts(string $username)
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $posts = $user->posts()
            ->with(['category:id,name', 'tags:id,name'])
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    /**
     * Update authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'avatar_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['bio']);

        if ($request->hasFile('avatar')) {
            // Delete old local avatar if exists
            if ($user->avatar_url && !str_starts_with($user->avatar_url, 'http')) {
                $oldPath = str_replace('/storage/', '', $user->avatar_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = '/storage/' . $path;
        } elseif ($request->has('avatar_url')) {
            $data['avatar_url'] = $request->avatar_url;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Get statistics for the authenticated user (dashboard).
     */
    public function stats()
    {
        $user = auth()->user();

        return response()->json([
            'reputation_points' => $user->reputation_points,
            'posts_count' => $user->posts()->count(),
            'comments_count' => $user->comments()->count(),
            'badges_count' => $user->badges()->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }

    /**
     * Get leaderboard of users by reputation.
     */
    public function leaderboard()
    {
        $users = Cache::remember('leaderboard', now()->addHour(), function () {
            return User::orderBy('reputation_points', 'desc')
                ->limit(10)
                ->get(['id', 'username', 'avatar_url', 'reputation_points', 'level']);
        });

        return response()->json($users);
    }
}
