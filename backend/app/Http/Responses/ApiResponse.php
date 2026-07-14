<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => array_merge(['request_id' => self::requestId()], $meta),
        ], $status);
    }

    /**
     * Cursor-paginated collection envelope per API.md §3.2. Pass the
     * paginator's items already mapped through their Resource class.
     */
    public static function paginated(iterable $data, CursorPaginator $paginator, int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'request_id' => self::requestId(),
                'pagination' => [
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'per_page' => $paginator->perPage(),
                ],
            ],
        ], $status);
    }

    /**
     * @param  list<array{field: string, message: string}>  $details
     * @param  array<string, mixed>  $context  Extra fields merged into the top-level "error" object.
     */
    public static function error(
        string $code,
        string $message,
        array $details = [],
        int $status = 400,
        array $context = []
    ): JsonResponse {
        $error = array_merge(['code' => $code, 'message' => $message], $context);

        if ($details !== []) {
            $error['details'] = $details;
        }

        return new JsonResponse([
            'error' => $error,
            'meta' => ['request_id' => self::requestId()],
        ], $status);
    }

    private static function requestId(): ?string
    {
        return request()->attributes->get('request_id');
    }
}
