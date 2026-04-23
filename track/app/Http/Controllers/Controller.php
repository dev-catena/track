<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use stdClass;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    /**
     * Respond with a JSON response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */

    protected function sendJsonResponse(int $status = 1, string $message = '', $data = null): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data ?? new stdClass(),
        ];

        return response()->json($response, 200);
    }

    protected function sendError(int $status = 0, string $message = '', $data = null): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data ?? new stdClass(),
        ];

        return response()->json($response, 500);
    }
}
