<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Report;
use App\Models\Role;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminDashboardController extends Controller
{
    #[OA\Get(
        path: "/admin/stats",
        summary: "Get global statistics for admin dashboard",
        tags: ["6. ADMIN & MODERATION"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Dashboard statistics", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function stats()
    {
        $totalUsers = User::where('is_banned', false)->count();
        
        $adminRole = Role::where('name', 'admin')->first();
        $adminUsers = $adminRole ? $adminRole->users()->count() : 0;
        
        $moderatorRole = Role::where('name', 'moderator')->first();
        $moderatorUsers = $moderatorRole ? $moderatorRole->users()->count() : 0;
        
        $reportCount = Report::where('status', 'pending')->count();

        return response()->json([
            'totalUsers' => $totalUsers,
            'adminUsers' => $adminUsers,
            'moderatorUsers' => $moderatorUsers,
            'reportCount' => $reportCount,
        ]);
    }
}
