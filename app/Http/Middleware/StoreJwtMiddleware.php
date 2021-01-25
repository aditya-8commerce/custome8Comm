<?php
namespace App\Http\Middleware;
use Closure;
use Exception;
use App\Models\Store;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use App\Res\IndexRes;


class StoreJwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->bearerToken();

        if(!$token) {
            // Unauthorized response if token not there
			return IndexRes::resultData(401,[],['message' => "Token not provided."]);
        }
        try {
            $credentials = JWT::decode($token, env('APP_KEY'), ['HS256']);
        } catch(ExpiredException $e) {
			return IndexRes::resultData(400,[],['message' => "Provided token is expired."]);

        } catch(Exception $e) {
			return IndexRes::resultData(400,[],['message' => "An error while decoding token."]);
        }
        // $user = User::find($credentials->sub->user_id);
        // Now let's put the user in the request class so that you can grab it from there
        $request->auth = $credentials->sub;
        return $next($request);
    }
	
	public function bearerToken(){
		$header	= $this->header('Authorization','');
		if(Str::startsWith($header, 'Bearer ')){
			return Str::substr($header, 7);
		}
	}
}