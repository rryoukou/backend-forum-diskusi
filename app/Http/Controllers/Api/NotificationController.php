<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;

use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Get(
        path: "/notifications",
        summary: "Display a listing of notifications for the authenticated user",
        tags: ["6. NOTIFIKASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of notifications", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index()
    {
        $notifications = Notification::with('actor:id,username,avatar_url')
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    #[OA\Post(
        path: "/notifications/{id}/read",
        summary: "Mark a specific notification as read",
        tags: ["6. NOTIFIKASI"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Notification ID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notification marked as read"),
            new OA\Response(response: 404, description: "Notification not found"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function markAsRead(string $id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    #[OA\Post(
        path: "/notifications/read-all",
        summary: "Mark all notifications as read",
        tags: ["6. NOTIFIKASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "All notifications marked as read"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    #[OA\Get(
        path: "/notifications/unread-count",
        summary: "Get unread notification count",
        tags: ["6. NOTIFIKASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Unread count", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function unreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    #[OA\Delete(
        path: "/notifications/{id}",
        summary: "Delete a notification",
        tags: ["6. NOTIFIKASI"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Notification ID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notification deleted"),
            new OA\Response(response: 404, description: "Notification not found"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function destroy(string $id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }
}
