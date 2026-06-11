<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use OpenApi\Attributes as OA;

class TagController extends Controller
{
    #[OA\Get(
        path: "/tags",
        summary: "Display a listing of tags",
        tags: ["2. POSTINGAN"],
        responses: [
            new OA\Response(response: 200, description: "List of tags", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object")))
        ]
    )]
    public function __invoke(Request $request)
    {
        $tags = Cache::remember('tags_list', now()->addDay(), function () {
            return Tag::all();
        });

        return response()->json($tags);
    }
}
