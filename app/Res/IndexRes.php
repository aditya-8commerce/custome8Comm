<?php
namespace App\Res;

use Response,Auth,Session;

/* 
error status code
	200	success
	404	Not Found (page or other resource doesn’t exist)
	401	Not authorized (not logged in)
	403	Logged in but access to requested area is forbidden
	400	Bad request (something wrong with URL or parameters)
	422	Unprocessable Entity (validation failed)
	500	General server error
*/

class IndexRes
{
	public static function resultData($code='200' , $datas=[], $errors=''){ 
		return response()
		->json(['status'=>$code ,'datas' => $datas, 'errors' => $errors])
		->setStatusCode($code)
		->withHeaders(['Content-Type' => 'application/json',]);
    } 
    
}