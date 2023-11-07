<?php

namespace GPapakitsos\LaravelDatatables\Tests\Http\Middleware;

use Closure;

class Unauthorized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user() === null) {
            return abort(401);
        }

        return $next($request);
    }
}
