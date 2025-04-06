<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleAuth
{
    public function handle($request, Closure $next)
    {
        if (!session()->has('authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
