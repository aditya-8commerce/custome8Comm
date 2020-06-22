<?php

namespace App\Http\Middleware;

use Closure;

class AccessCourierMiddleware
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
		if(@$request->token != '_8commerce_'){
            return $this->resultData(403,[],'Please don\'t do that to me!'); 
       }

        return $next($request);
    }
	
	private function resultData($code='' , $data=[], $message=''){ 
		return response()
		->json(['status'=>$code ,'data' => $data, 'message' => $message])
		->setStatusCode($code)
		->withHeaders(['Content-Type' => 'application/json',]);
		
	}
}
