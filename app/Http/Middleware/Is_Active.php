<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class Is_Active
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		$response = $next($request);
        //If the status is not approved redirect to login 
        if(Auth::check() && !Auth::user()->status){
            Auth::logout();
            return redirect('/login')->with('erro_login', 'Your Account Inactivated');
        }
        return $response;
    }
}
