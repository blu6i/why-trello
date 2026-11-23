<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Security\Csrf;
use Closure;

/**
 * Middleware проверки CSRF-токена для всех модифицирующих HTTP-методов.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            return $next($request);
        }

        $token = $request->input('_csrf_token')
            ?? $request->header('x-csrf-token')
            ?? $request->header('x-xsrf-token');

        if (! Csrf::verify(is_string($token) ? $token : null)) {
            if ($request->isJson()) {
                return Response::json([
                    'is_successful' => false,
                    'time' => date(DATE_ATOM),
                    'error' => 'Invalid CSRF token',
                ], 403);
            }

            return Response::html('<h1>403 Forbidden: Invalid CSRF token</h1>', 403);
        }

        return $next($request);
    }
}
