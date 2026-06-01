<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success($data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $payload['data'] = $data->resolve(request());
        } elseif (! is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message = 'Error', int $status = 400, $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function paginatedSuccess(ResourceCollection|LengthAwarePaginator $data, string $message = 'OK'): JsonResponse
    {
        if ($data instanceof ResourceCollection) {
            $response = $data->response()->getData(true);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $response['data'] ?? [],
                'meta' => $response['meta'] ?? null,
                'links' => $response['links'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
        ]);
    }
}
