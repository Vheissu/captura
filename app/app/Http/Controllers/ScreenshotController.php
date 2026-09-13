<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ErrorCode;
use App\Enums\ScreenshotStatus;
use App\Http\Requests\AsyncScreenshotRequest;
use App\Http\Requests\BulkScreenshotRequest;
use App\Http\Requests\CaptureScreenshotRequest;
use App\Http\Resources\ScreenshotResource;
use App\Models\Screenshot;
use App\Services\ScreenshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ScreenshotController extends Controller
{
    public function __construct(private ScreenshotService $service) {}

    public function capture(CaptureScreenshotRequest $request): Response|JsonResponse
    {
        $params = $request->toParams();
        $screenshot = $this->service->capture($params, $request->ip());

        if (! $screenshot->from_cache) {
            $screenshot = $this->service->waitForCompletion($screenshot, $params->timeout);
        }

        if ($screenshot->status === ScreenshotStatus::Failed) {
            return response()->json([
                'error' => true,
                'code' => $screenshot->error_code,
                'message' => $screenshot->error_message,
            ], 500);
        }

        $responseMode = $request->input('response', $request->input('output', 'image'));
        if ($responseMode === 'json') {
            return response()->json(new ScreenshotResource($screenshot));
        }

        try {
            $content = $screenshot->file_path
                ? Storage::disk(config('screenshot.storage.disk'))->get($screenshot->file_path)
                : null;
        } catch (\Throwable) {
            $content = null;
        }

        if ($content === null) {
            return response()->json([
                'error' => true,
                'code' => ErrorCode::NotFound->value,
                'message' => 'Screenshot file is no longer available',
            ], ErrorCode::NotFound->httpStatus());
        }

        return response($content)
            ->header('Content-Type', $screenshot->file_type?->mimeType() ?? 'application/octet-stream')
            ->header('Content-Length', strlen($content))
            ->header('X-Screenshot-Id', $screenshot->id);
    }

    public function async(AsyncScreenshotRequest $request): JsonResponse
    {
        $params = $request->toParams();
        $screenshot = $this->service->capture($params, $request->ip());

        return response()->json([
            'id' => $screenshot->id,
            'status' => $screenshot->status->value,
            'poll_url' => route('screenshot.show', $screenshot),
            'estimated_seconds' => 5,
        ], 202);
    }

    public function bulk(BulkScreenshotRequest $request): JsonResponse
    {
        $items = [];

        foreach ($request->toParamsArray() as $params) {
            $screenshot = $this->service->capture($params, $request->ip());

            $items[] = [
                'id' => $screenshot->id,
                'status' => $screenshot->status->value,
                'poll_url' => route('screenshot.show', $screenshot),
                'cached' => $screenshot->from_cache,
                'file_url' => $screenshot->file_url,
            ];
        }

        return response()->json([
            'items' => $items,
        ], 202);
    }

    public function show(string $id): JsonResponse
    {
        $screenshot = Screenshot::findOrFail($id);

        return response()->json(new ScreenshotResource($screenshot));
    }
}
