<?php

namespace App\Http\Middleware;

use App\Services\CrownThemeResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyCrownTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        CrownThemeResolver::apply();

        return $next($request);
    }
}
