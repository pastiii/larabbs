<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */

    const TokenPrefix = 'token.';
    const Prefix = 'Bearer';

    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        if ($token == null || substr($token, 0,strlen(self::Prefix)) !== self::Prefix) {
            //false  code
            $response = ['status_code' => 5010];
            return $response;
        }
        $token = substr($token,6);
        if (!$this->checkToken($token)) {
            //false code
            $response = ['status_code' => 5010];
            return $response;
        }
        return $next($request);
    }

    public function checkToken($token)
    {
	    $user_id = Redis::get($this->getTokenKey($token));
	    if (empty($user_id)) {
		 return false;
	    }

	   Redis::setex($this->getTokenKey($token), $this->getTime(), $user_id);
	   return true;

    }
    public  function getTokenKey($token)
    {
        return self::TokenPrefix . trim($token);
    }

    private function getTime() 
    {
        $time = intval(env('TOKEN_EXPIRY_TIME'));
	if($time <= 0) {
		$time = 10;
	}
	return $time * 60;
    }

}
