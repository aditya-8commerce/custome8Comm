<?php
namespace App\Http\Controllers;  


use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use App\Res\IndexRes;
 
use App\Http\Controllers\ApiLogController as ApiLog;
 
use Carbon\Carbon;
use File;
 

class NewEmployeeController extends Controller
{
	
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

    public function __construct(){
         
    }

	public function index(Request $request){
        $datas          = $request->all();
        $headers        = $request->headers->all();
        $time           = time();
        
        $microtime      = round(microtime(true) * 1000);
        $array          = array("email" => "test-new-employee@lincgrp.com");
        $accept         = $this->validationAccept($request->header('accept',''));
        $contentType    = $this->validationAccept($request->header('content-type',''));
        $apiKey         = $this->validationApiKey($request->header('api-key',''));
        $signature      = $this->validationSignature($request->header('signature',''));
        $signatureTime  = $this->validationSignatureTime($request->header('signature-time','')); 
        $PHP_AUTH_USER  = $request->header('php-auth-user',''); 
        $PHP_AUTH_PW    = $request->header('php-auth-pw',''); 
        $getSignature   = $this->getSignature($microtime,"ojh545we4t5254sdgfsaefstg65478","POST",json_encode($array),"application/json","/v1/test-new-employee");
        if (!isset($_SERVER['PHP_AUTH_USER'])){
            return response()
            ->json(['status'=>422 ,'datas' => [], 'errors' => ["messages" => ["basic auth fails"]]])
            ->setStatusCode(422)
            ->withHeaders(['Content-Type' => 'application/json']);

        }
        
        if(!$accept OR !$contentType OR !$apiKey OR !$signature OR !$signatureTime["status"] OR !$signatureTime["datas"] < 0 OR !$signatureTime["datas"] > 15 ){

            return response()
            ->json(['status'=>422 ,'datas' => [], 'errors' => ["messages" => ["header validation fails"]]])
            ->setStatusCode(422)
            ->withHeaders(['Content-Type' => 'application/json']);
        }

        $json          = json_encode($datas);
        $signatureCheck = $this->getSignature($request->header('signature-time',''),"ojh545we4t5254sdgfsaefstg65478","POST",$json,"application/json","/v1/test-new-employee");
 
        if($signatureCheck != $request->header('signature','')){
            // $datas  = [ "getSignature" => $getSignature , "microtime" => $microtime];
            $datas      = [];

            return response()
            ->json(['status'=>422 ,'datas' => $datas, 'errors' => ["messages" => ["header signature fail"]]])
            ->setStatusCode(422)
            ->withHeaders(['Content-Type' => 'application/json']);
        }

        if($PHP_AUTH_USER != "linc-test" OR $PHP_AUTH_PW != "123456"){
            return response()
            ->json(['status'=>422 ,'datas' => [], 'errors' => ["messages" => ["username / password auth fail"]]])
            ->setStatusCode(422)
            ->withHeaders(['Content-Type' => 'application/json']);

        }

		return response()
		->json(['status'=>200 ,'datas' => ["body" => $datas , "headers" => $headers , "signatureTime" => $signatureTime , "signature" => $signature, "getSignature" => $getSignature , "microtime" => $microtime], 'errors' => []])
		->setStatusCode(200)
		->withHeaders(['Content-Type' => 'application/json']);
	}

    private function validationAccept($string){
        $response   = false;
        if($string == "application/json"){
            $response   = true;
        }

        return $response;
    }
    
    private function validationApiKey($string){
        $response   = false;
        if($string == "ojh545we4t5254sdgfsaefstg65478"){
            $response   = true;
        }

        return $response;
    }
	 
    private function validationSignatureTime($string){
        $response   = ["status" => false , "datas" => null];
        if(is_numeric($string)){ 
            $milliseconds = (int)$string / 1000;
            $fromTime   = date("Y-m-d H:i:s",$milliseconds);
            $toTime     = date("Y-m-d H:i:s");
            $diff       = strtotime($toTime) - strtotime($fromTime);
            $minutes    = $diff / 60;

            $response   = ["status" => true , "datas" => (int)$minutes , "fromTime" => $fromTime];

        }

        return $response;
    }
	
	 
    private function validationSignature($string){
        $response   = false;
        if(!empty($string)){ 
            $response   = true;
        }

        return $response;
    }


    private function getSignature($microtime, $reqSecret, $reqMethod, $reqBody, $reqContentType, $reqUrl)
    {

        date_default_timezone_set("Asia/Jakarta");
        $milliseconds = $microtime / 1000;

        $patternDate = date("Y-m-d H:i:s",$milliseconds);

        $reqBody = str_replace("\r", "\\r", $reqBody);
        $reqBody = str_replace("\n", "\\n", $reqBody);
        $reqBody = $reqBody != "" ? md5($reqBody) : "";

        $apiKey = $reqMethod . "\n" . trim($reqBody) . "\n" . trim($reqContentType) . "\n" . $patternDate . "\n" .$reqUrl;


        $signature = hash_hmac('sha256', $apiKey, $reqSecret, true);
        $encodedSignature = base64_encode($signature);

        return $encodedSignature;
    }


    private function verifySignature($microtime, $reqSecret, $reqMethod, $reqBody, $reqContentType, $reqUrl)
    {

        date_default_timezone_set("Asia/Jakarta");
        $milliseconds = $microtime / 1000;

        $patternDate = date("Y-m-d H:i:s",$milliseconds);

        $reqBody = str_replace("\r", "\\r", $reqBody);
        $reqBody = str_replace("\n", "\\n", $reqBody);
        $reqBody = $reqBody != "" ? md5($reqBody) : "";

        $apiKey = $reqMethod . "\n" . trim($reqBody) . "\n" . trim($reqContentType) . "\n" . $patternDate . "\n" .$reqUrl;

        return $apiKey;
    }
}
