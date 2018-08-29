<?php

namespace App\Http\Middleware;

use Closure;
use Response;

class Cors
{
    public function handle( $request, Closure $next)
    {
        /* @var Request $response*/
//        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept,Authorization,Appname');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE,OPTIONS');
        $response->header('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

}
