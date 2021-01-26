<?php

namespace App\Http\Middleware;

use Closure;

class AccessMarketplaceMiddleware
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
		if($request->ip() != '::1' and $request->ip() != '127.0.0.1'){
             return $this->resultData(403,[],'for local tourist only!'); 
        }
             
		if(@$request->token != '_8commerce_'){
            return $this->resultData(403,[],'Please don\'t do that to me!'); 
       }

       if(@$request->fc == ''){
            return $this->resultData(403,[],'Oppps Something Wrong!'); 
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
