<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Controllers\AbstractController;
use App\Http\Middleware\MiddlewareInterface;
use Closure;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

final class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, mixed> */
    private array $groupStack = [];

    /** @var ?Closure(Request): Response */
    private ?Closure $notFoundHandler = null;

    public function get(string $path, Closure|array $action): Route
    {
        return $this->addRoute('GET', $path, $action);
    }

    public function post(string $path, Closure|array $action): Route
    {
        return $this->addRoute('POST', $path, $action);
    }

    public function put(string $path, Closure|array $action): Route
    {
        return $this->addRoute('PUT', $path, $action);
    }

    public function delete(string $path, Closure|array $action): Route
    {
        return $this->addRoute('DELETE', $path, $action);
    }

    public function patch(string $path, Closure|array $action): Route
    {
        return $this->addRoute('PATCH', $path, $action);
    }

    public function any(string $path, Closure|array $action): Route
    {
        return $this->addRoute('ANY', $path, $action);
    }

    /**
     * Группировка маршрутов с общим префиксом и/или middleware.
     *
     * @param  array{prefix?: string, middleware?: class-string<MiddlewareInterface>|MiddlewareInterface|array<int, class-string<MiddlewareInterface>|MiddlewareInterface>}  $attributes
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Регистрация кастомного обработчика 404 Not Found.
     *
     * @param  Closure(Request): Response  $handler
     */
    public function setNotFoundHandler(Closure $handler): self
    {
        $this->notFoundHandler = $handler;

        return $this;
    }

    /**
     * Обработка запроса и возврат Response.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $matchedRoute = null;
        $routeParams = [];
        $methodNotAllowed = false;

        foreach ($this->routes as $route) {
            $params = [];
            if ($route->matches($method, $path, $params)) {
                $matchedRoute = $route;
                $routeParams = $params;
                break;
            }

            // Проверяем, есть ли такой путь с другим методом для 405
            if ($route->matchesPath($path, $params)) {
                $methodNotAllowed = true;
            }
        }

        if ($matchedRoute === null) {
            if ($methodNotAllowed) {
                return $request->isJson()
                    ? Response::json(['error' => 'Method Not Allowed'], 405)
                    : Response::html('<h1>405 Method Not Allowed</h1>', 405);
            }

            if ($this->notFoundHandler !== null) {
                return ($this->notFoundHandler)($request);
            }

            return $request->isJson()
                ? Response::json(['error' => 'Not Found'], 404)
                : Response::html('<h1>404 Not Found</h1>', 404);
        }

        $request->setRouteParams($routeParams);

        // Формируем цепочку Middleware (луковичная модель)
        $middlewares = $matchedRoute->getMiddleware();

        $coreAction = function (Request $req) use ($matchedRoute, $routeParams): Response {
            return $this->invokeAction($matchedRoute->getAction(), $req, $routeParams);
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (Closure $next, string|object $middleware): Closure {
                return function (Request $req) use ($next, $middleware): Response {
                    $instance = is_string($middleware) ? new $middleware : $middleware;
                    if (! $instance instanceof MiddlewareInterface) {
                        throw new RuntimeException('Middleware must implement MiddlewareInterface.');
                    }

                    return $instance->handle($req, $next);
                };
            },
            $coreAction
        );

        return $pipeline($request);
    }

    /**
     * @param  Closure|array{0: class-string, 1: string}  $action
     * @param  array<string, mixed>  $routeParams
     */
    private function invokeAction(Closure|array $action, Request $request, array $routeParams): Response
    {
        if ($action instanceof Closure) {
            $reflection = new ReflectionFunction($action);
            $args = $this->resolveArguments($reflection->getParameters(), $request, $routeParams);
            $result = $action(...$args);
        } else {
            [$controllerClass, $method] = $action;
            if (! class_exists($controllerClass)) {
                throw new RuntimeException("Controller [{$controllerClass}] does not exist.");
            }

            $controller = new $controllerClass;

            if ($controller instanceof AbstractController) {
                $controller->setRequest($request);
            }

            if (! method_exists($controller, $method)) {
                throw new RuntimeException("Method [{$method}] does not exist on controller [{$controllerClass}].");
            }

            $reflection = new ReflectionMethod($controller, $method);
            $args = $this->resolveArguments($reflection->getParameters(), $request, $routeParams);
            $result = $controller->{$method}(...$args);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        return Response::empty();
    }

    /**
     * Внедрение зависимостей в параметры метода (Request, именованные параметры маршрута).
     *
     * @param  \ReflectionParameter[]  $parameters
     * @param  array<string, mixed>  $routeParams
     * @return array<int, mixed>
     */
    private function resolveArguments(array $parameters, Request $request, array $routeParams): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
            } elseif (array_key_exists($name, $routeParams)) {
                $args[] = $routeParams[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    /**
     * @param  Closure|array{0: class-string, 1: string}  $action
     */
    private function addRoute(string $method, string $path, Closure|array $action): Route
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix = rtrim($prefix, '/').'/'.trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $mw = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddleware = array_merge($groupMiddleware, $mw);
            }
        }

        $fullPath = rtrim($prefix, '/').'/'.ltrim($path, '/');
        if ($fullPath !== '/') {
            $fullPath = rtrim($fullPath, '/');
        }

        $route = new Route($method, $fullPath, $action);
        if (count($groupMiddleware) > 0) {
            $route->middleware($groupMiddleware);
        }

        $this->routes[] = $route;

        return $route;
    }
}
