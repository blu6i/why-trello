<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Security\Auth;
use Closure;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if ($request->isJson()) {
                return Response::json(['error' => 'Already authenticated'], 400);
            }

            return Response::redirect('/');
        }

        return $next($request);
    }
}
