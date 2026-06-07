<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Post(
        path: "/reports",
        summary: "Store a newly created report (flag content/user)",
        tags: ["8. MODERASI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["target_id", "target_type", "reason"],
                properties: [
                    new OA\Property(property: "target_id", type: "string", example: "1"),
                    new OA\Property(property: "target_type", type: "string", enum: ["post", "comment", "user"]),
                    new OA\Property(property: "reason", type: "string", example: "Spam or misleading"),
                    new OA\Property(property: "description", type: "string", example: "Detailed explanation of the report"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Report submitted successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_id' => 'required|string',
            'target_type' => 'required|in:post,comment,user',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $report = Report::create([
            'reporter_id' => auth()->id(),
            'target_id' => $request->target_id,
            'target_type' => $request->target_type,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'data' => $report,
        ], 201);
    }
}
