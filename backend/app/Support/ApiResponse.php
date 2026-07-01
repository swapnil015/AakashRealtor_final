<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Throwable;

/**
 * Central factory for the standard API envelope used by EVERY endpoint:
 *
 *   {
 *     "success": true|false,
 *     "data":    mixed|null,
 *     "message": string,
 *     "meta":    { "pagination": {...} }   // present on paginated lists
 *     "errors":  { field: [msg] }          // present on 422 only
 *   }
 *
 * Controllers should call ApiResponse::success() / ::error(); the global
 * exception handler calls ::fromException() so failures share the shape.
 */
class ApiResponse
{
    /**
     * Successful response. Accepts raw data, a Resource, a ResourceCollection
     * or a Paginator — pagination meta is extracted automatically.
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data'    => static::normalizeData($data),
            'message' => $message,
        ];

        $pagination = static::extractPagination($data);
        $meta = array_filter(array_merge($pagination ? ['pagination' => $pagination] : [], $meta));

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Error response in the same envelope. $errors is the validation bag
     * (field => [messages]); omitted when empty.
     */
    public static function error(
        string $message = 'Something went wrong.',
        int $status = 400,
        array $errors = [],
        mixed $data = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'data'    => $data,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Map any Throwable to the correct status + envelope. This is the single
     * place the API decides how exceptions look to clients.
     */
    public static function fromException(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => static::error(
                $e->getMessage() ?: 'The given data was invalid.',
                422,
                $e->errors()
            ),

            $e instanceof AuthenticationException => static::error(
                'Unauthenticated. Please log in to continue.',
                401
            ),

            $e instanceof AuthorizationException => static::error(
                $e->getMessage() ?: 'This action is unauthorized.',
                403
            ),

            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => static::error(
                'The requested resource was not found.',
                404
            ),

            $e instanceof ThrottleRequestsException => static::error(
                'Too many requests. Please slow down and try again shortly.',
                429
            ),

            $e instanceof HttpExceptionInterface => static::error(
                $e->getMessage() ?: 'Request failed.',
                $e->getStatusCode()
            ),

            // Unhandled: hide internals in production, expose in debug.
            default => static::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected server error occurred.',
                500,
                config('app.debug')
                    ? ['exception' => [get_class($e)], 'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8)]
                    : []
            ),
        };
    }

    /** Unwrap Resources/Collections to their array form for the envelope. */
    protected static function normalizeData(mixed $data): mixed
    {
        if ($data instanceof AbstractPaginator) {
            // Paginator -> just the items array; meta is handled separately.
            return $data->items();
        }

        if ($data instanceof ResourceCollection) {
            $resource = $data->resource;
            return $resource instanceof AbstractPaginator
                ? $data->collection->toArray()
                : $data->toArray(request());
        }

        if ($data instanceof JsonResource) {
            return $data->toArray(request());
        }

        return $data;
    }

    /** Build the pagination meta block from a paginator (if any). */
    protected static function extractPagination(mixed $data): ?array
    {
        $paginator = match (true) {
            $data instanceof AbstractPaginator => $data,
            $data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator => $data->resource,
            default => null,
        };

        if (! $paginator) {
            return null;
        }

        return [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => method_exists($paginator, 'total') ? $paginator->total() : null,
            'last_page'    => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
            'has_more'     => $paginator->hasMorePages(),
        ];
    }
}
