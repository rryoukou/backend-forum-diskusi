<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModerationLog;
use App\Models\Report;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class ModerationController extends Controller
{
    #[OA\Get(
        path: "/moderation/reports",
        summary: "List all reports (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filter by status", schema: new OA\Schema(type: "string", enum: ["pending", "resolved", "dismissed"], default: "pending")),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of reports", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function reports(Request $request)
    {
        $status = $request->get('status', 'pending');
        $reports = Report::with([
            'reporter:id,username',
            'resolver:id,username',
            'target' => function ($morphTo) {
                $morphTo->morphWith([
                    Post::class => ['user:id,username,is_banned'],
                    Comment::class => ['user:id,username,is_banned'],
                ]);
            }
        ])
            ->where('status', $status)
            ->latest('id')
            ->paginate(20);

        return response()->json($reports);
    }

    #[OA\Post(
        path: "/moderation/reports/{id}/resolve",
        summary: "Resolve a report (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Report ID", schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["resolved", "dismissed"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Report resolved successfully"),
            new OA\Response(response: 404, description: "Report not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function resolveReport(Request $request, string $id)
    {
        $report = Report::find($id);

        if (! $report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:resolved,dismissed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $report->update([
            'status' => $request->status,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Report '.$request->status]);
    }

    #[OA\Post(
        path: "/moderation/ban",
        summary: "Ban a user (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id", "reason"],
                properties: [
                    new OA\Property(property: "user_id", type: "string", example: "1"),
                    new OA\Property(property: "reason", type: "string", example: "Violating community guidelines"),
                    new OA\Property(property: "notes", type: "string", example: "Banned for 30 days"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User banned successfully"),
            new OA\Response(response: 403, description: "Cannot ban an admin"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function banUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);

        if ($user->isAdmin()) {
            return response()->json(['message' => 'Cannot ban an admin'], 403);
        }

        try {
            DB::beginTransaction();

            $user->update(['is_banned' => true]);

            ModerationLog::create([
                'moderator_id' => auth()->id(),
                'target_user_id' => $user->id,
                'action_type' => 'ban',
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json(['message' => 'User banned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to ban user', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/moderation/warn",
        summary: "Warn a user (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id", "reason"],
                properties: [
                    new OA\Property(property: "user_id", type: "string", example: "1"),
                    new OA\Property(property: "reason", type: "string", example: "Inappropriate language"),
                    new OA\Property(property: "notes", type: "string"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User warned successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function warnUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);

        try {
            DB::beginTransaction();

            ModerationLog::create([
                'moderator_id' => auth()->id(),
                'target_user_id' => $user->id,
                'action_type' => 'warning',
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);

            // Notify user
            \App\Services\NotificationService::send(
                $user->id,
                'moderator_warning',
                auth()->id(),
                'user',
                auth()->id(),
                'You have received a warning: '.$request->reason
            );

            DB::commit();

            return response()->json(['message' => 'User warned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to warn user', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: "/moderation/unban",
        summary: "Unban a user (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id", "reason"],
                properties: [
                    new OA\Property(property: "user_id", type: "string", example: "1"),
                    new OA\Property(property: "reason", type: "string", example: "Ban period ended or appeal accepted"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "User unbanned successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function unbanUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);

        try {
            DB::beginTransaction();

            $user->update(['is_banned' => false]);

            ModerationLog::create([
                'moderator_id' => auth()->id(),
                'target_user_id' => $user->id,
                'action_type' => 'unban',
                'reason' => $request->reason,
            ]);

            DB::commit();

            return response()->json(['message' => 'User unbanned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to unban user', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/moderation/logs",
        summary: "List all moderation logs (Moderator Only)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of logs", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function logs()
    {
        $logs = ModerationLog::with(['moderator:id,username', 'targetUser:id,username'])
            ->latest('id')
            ->paginate(20);

        return response()->json($logs);
    }
}

