<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCors
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $this->allowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'false');
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $this->allowedOrigin($request));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');

        return $response;
    }

    private function allowedOrigin(Request $request): string
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        if ($origin && (in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true))) {
            return $origin;
        }

        return 'http://marcelocaixeta.api.br:5173';
    }
}
