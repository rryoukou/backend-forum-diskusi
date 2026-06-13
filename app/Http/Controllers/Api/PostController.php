<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostEditHistory;
use App\Models\Tag;
use App\Services\ReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

use OpenApi\Attributes as OA;

class PostController extends Controller
{
    /**
     * Menampilkan daftar semua postingan dengan filter (kategori, tag, user, pencarian).
     */
    #[OA\Get(
        path: "/posts",
        summary: "Display a listing of posts",
        responses: [
            new OA\Response(response: 200, description: "List of posts", content: new OA\JsonContent(type: "object"))
        ]
    )]
    public function index(Request $request)
    {
        // Generate a unique cache key based on all request parameters
        $cacheKey = 'posts_index_'.md5(serialize($request->all()));

        $posts = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            // Query dasar dengan relasi user, kategori, dan tag
            $query = Post::with(['user:id,username,avatar_url', 'category:id,name', 'tags:id,name'])
                ->withCount(['likes', 'bookmarks']);

            // Filter berdasarkan kategori
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter berdasarkan tag
            if ($request->has('tag')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('name', $request->tag)->orWhere('slug', $request->tag);
                });
            }

            // Filter berdasarkan user (ID atau username)
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('username')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('username', $request->username);
                });
            }

            // Fitur pencarian berdasarkan judul
            if ($request->has('search')) {
                $query->where('title', 'like', '%'.$request->search.'%');
            }

            // Urutkan dari yang terbaru dan gunakan pagination
            return $query->latest()->paginate($request->get('per_page', 10));
        });

        return response()->json($posts);
    }

    /**
     * Menyimpan postingan baru ke database.
     */
    #[OA\Post(
        path: "/posts",
        responses: [
            new OA\Response(response: 201, description: "Post created successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if user has enough reputation points to create a post
        // Moderators and Admins are exempt from this requirement
        $minPoints = 10;
        if ($user->reputation_points < $minPoints && ! $user->isModerator()) {
            return response()->json([
                'message' => "You need at least {$minPoints} reputation points to create a post."
            ], 403);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:300',
            'body' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Buat record postingan
            $post = Post::create([
                'user_id' => auth()->id(),
                'category_id' => $request->category_id,
                'title' => $request->title,
                'body' => $request->body,
                'status' => 'open',
            ]);

            // Tangani tag (buat tag baru jika belum ada)
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName)]
                    );
                    $tagIds[] = $tag->id;
                }
                $post->tags()->sync($tagIds);
            }

            // Tambahkan poin reputasi karena telah memposting
            ReputationService::addPoints(auth()->user(), 5, 'post_created', $post->id, 'Membuat postingan baru');

            // Cek apakah user berhak mendapatkan badge berdasarkan jumlah postingan
            ReputationService::checkActivityBadges(auth()->user(), 'posts_count');

            Cache::forget('posts_trending');
            if ($request->has('tags')) {
                Cache::forget('tags_list');
            }

            DB::commit();

            return response()->json([
                'message' => 'Post created successfully',
                'data' => $post->load(['user:id,username', 'category:id,name', 'tags:id,name']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to create post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan detail postingan tunggal berdasarkan ID.
     */
    #[OA\Get(
        path: "/posts/{id}",
        responses: [
            new OA\Response(response: 200, description: "Post details", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function show(string $id)
    {
        $post = Post::with(['user:id,username,avatar_url,bio', 'category:id,name', 'tags:id,name', 'comments.user:id,username,avatar_url'])
            ->withCount(['likes', 'bookmarks'])
            ->find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Tambah jumlah view setiap kali postingan dibuka
        $post->increment('view_count');

        return response()->json($post);
    }

    /**
     * Memperbarui data postingan yang sudah ada.
     */
    #[OA\Put(
        path: "/posts/{id}",
        responses: [
            new OA\Response(response: 200, description: "Post updated successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Post not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        Gate::authorize('update', $post);

        $validator = Validator::make($request->all(), [
            'category_id' => 'exists:categories,id',
            'title' => 'string|max:300',
            'body' => 'string',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'status' => 'in:open,closed,deleted',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $oldTitle = $post->title;
            $oldBody = $post->body;
            $post->update($request->only(['category_id', 'title', 'body', 'status']));

            // Simpan riwayat edit jika konten title atau body berubah
            if (($request->has('title') && $request->title !== $oldTitle) || 
                ($request->has('body') && $request->body !== $oldBody)) {
                PostEditHistory::create([
                    'post_id' => $post->id,
                    'edited_by' => auth()->id(),
                    'title_before' => $request->has('title') && $request->title !== $oldTitle ? $oldTitle : null,
                    'title_after' => $request->has('title') && $request->title !== $oldTitle ? $request->title : null,
                    'body_before' => $request->has('body') && $request->body !== $oldBody ? $oldBody : null,
                    'body_after' => $request->has('body') && $request->body !== $oldBody ? $request->body : null,
                    'reason' => $request->get('edit_reason'),
                ]);
            }

            // Perbarui tag
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName)]
                    );
                    $tagIds[] = $tag->id;
                }
                $post->tags()->sync($tagIds);
            }

            Cache::forget('posts_trending');
            if ($request->has('tags')) {
                Cache::forget('tags_list');
            }

            DB::commit();

            return response()->json([
                'message' => 'Post updated successfully',
                'data' => $post->load(['user:id,username', 'category:id,name', 'tags:id,name']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to update post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus postingan.
     */
    #[OA\Delete(
        path: "/posts/{id}",
        responses: [
            new OA\Response(response: 200, description: "Post deleted successfully"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function destroy(string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        Gate::authorize('delete', $post);

        $user = auth()->user();

        // Jika dihapus oleh moderator/admin (bukan pemilik), catat di log moderasi
        if ($post->user_id !== $user->id) {
            \App\Models\ModerationLog::create([
                'moderator_id' => $user->id,
                'target_user_id' => $post->user_id,
                'action_type' => 'delete_post',
                'reason' => request('reason', 'Pelanggaran aturan komunitas'),
                'notes' => 'Post ID: '.$post->id.' | Title: '.$post->title,
            ]);
        }

        $post->delete();

        Cache::forget('posts_trending');

        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Menampilkan riwayat perubahan (edit) dari suatu postingan.
     */
    public function history(string $id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $history = $post->editHistory()
            ->with('editor:id,username,avatar_url')
            ->orderBy('edited_at', 'desc')
            ->get();

        return response()->json($history);
    }

    /**
     * Mengambil daftar postingan yang sedang trending berdasarkan view, vote, dan komentar.
     */
    public function trending()
    {
        $posts = Cache::remember('posts_trending', now()->addMinutes(30), function () {
            return Post::with(['user:id,username,avatar_url', 'category:id,name', 'tags:id,name'])
                ->withCount(['likes', 'bookmarks', 'comments'])
                ->orderByRaw('(view_count + (vote_score * 2) + (comments_count * 5)) DESC')
                ->limit(5)
                ->get();
        });

        return response()->json($posts);
    }
}
