<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Security\Auth;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->isJson()) {
                return Response::json(['error' => 'Unauthorized'], 401);
            }

            $_SESSION['flash_error'] = 'Для доступа к этому разделу необходимо войти в систему.';

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
