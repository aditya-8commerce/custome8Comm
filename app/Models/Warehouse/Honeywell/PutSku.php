<?php
namespace App\Models\Warehouse\Honeywell;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as Client;
use Psr\Http\Message\RequestInterface;
use App\Http\Controllers\ApiLogController as ApiLog;


class PutSku
{
	/*
	** demo
	*/
    public $endpointWMSTest   = 'http://147.139.135.96:19192/datahub/FluxWmsJsonApi/';
	
	/*
	** production
	*/
    public $endpointWMS   = 'http://147.139.134.156:19192/datahub/FluxWmsJsonApi/';
    protected $apiLog;

    public function __construct()
    {
        $this->apiLog           = new ApiLog;
        $this->client           = new Client();
    }
	
	/*
	**  SKU Information
	*/
    public function index($datas) {
        set_time_limit(0);

        $InterfaceMethod    = 'POST'; 
        $method             = 'putSKUData'; 
        $messageid          = "SKU"; 
        $client_customerid  = 'FLUXWMSJSON';  // Fixed value
        $client_db          = "FLUXWMSJSON"; // Fixed value
        $apptoken           = "80AC1A3F-F949-492C-A024-7044B28C8025"; 
        $appkey             = "test";
        $timestamp          = date('Y-m-d H:i:s'); // Fixed value
        $header             = [];
		$asnReference2      = '';
        $no                 = 1;
        
        $header[] = array(
            "customerId"      => strtoupper($datas->company_id), 
            "sku"             => strtoupper($datas->sku_code),
            "activeFlag"      => 'Y',
            "skuDescr1"       => strtoupper($datas->sku_description),
            "skuDescr2"       => strtoupper($datas->sku_short_description),
            "grossWeight"     => strtoupper($datas->weight),
            "netWeight"       => strtoupper($datas->net_weight),
            "cube"            => strtoupper($datas->cube),
            "freightClass"    => "",
            "price"           => strtoupper($datas->price),
            "skuWidth"        => strtoupper($datas->width),
            "skuHigh"	      => strtoupper($datas->height),
            "skuLength"       => strtoupper($datas->length),
            "shelfLifeFlag"   => strtoupper($datas->is_shelf_life),
            "shelfLifeType"   => strtoupper($datas->shelf_life_type),
            "inboundLifeDays" => strtoupper($datas->inbound_life_days),
            "outboundLifeDays"=> strtoupper($datas->outbond_life_days),
            "shelfLife"       => strtoupper($datas->shelf_life),
            "reservedField01" => strtoupper($datas->qty_per_carton),
            "reservedField02" => strtoupper($datas->carton_per_pallet),
            "reservedField03" => strtoupper($datas->uom), //KG or EA
            "alternateSku1"   =>strtoupper($datas->sku_code)
        );

        
        $datasRecord = array(
            "xmldata" =>  array(
                "header" => $header
            )
        );

        try {
            $response = $this->client->request('POST', $this->endpointWMS, [
                'form_params' => [
                    'method'                => $method,
                    'client_customerid'     => $client_customerid,
                    'client_db'             => $client_db,
                    'messageid'             => $messageid,
                    'apptoken'              => $apptoken,
                    'appkey'                => $appkey,
                    'sign'                  => 1,
                    'timestamp'             => $timestamp,
                    'data'                  => json_encode($datasRecord)
                ]
            ]);

            return json_decode($response->getBody(),TRUE);

        }catch (ClientException $e) {
            $responseError = $e->getResponse();
            $this->apiLog->insertLog('Warehouse\Honeywell\PutSku','production',(string)$responseError->getBody(), 'ERROR' , json_encode($datas),'/','honeywell');
            return $responseError;
        }

    }

    public function updateSku($datas) {
        set_time_limit(0);
        
        $InterfaceMethod    = 'POST'; 
        $method             = 'putSKUData'; 
        $messageid          = "SKU"; 
        $client_customerid  = 'FLUXWMSJSON';  // Fixed value
        $client_db          = "FLUXWMSJSON"; // Fixed value
        $apptoken           = "80AC1A3F-F949-492C-A024-7044B28C8025"; 
        $appkey             = "test";
        $timestamp          = date('Y-m-d H:i:s'); // Fixed value
        $header             = [];
		$asnReference2      = '';
        $no                 = 1;
        
        $header[] = array(
            "customerId"      => strtoupper($datas->company_id), 
            "sku"             => strtoupper($datas->sku_code),
            "activeFlag"      => 'Y',
            "skuDescr1"       => strtoupper($datas->sku_description),
            "skuDescr2"       => strtoupper($datas->sku_short_description),
            "price"           => strtoupper($datas->price)
        );

        
        $datasRecord = array(
            "xmldata" =>  array(
                "header" => $header
            )
        );

        try {
            $response = $this->client->request('POST', $this->endpointWMS, [
                'form_params' => [
                    'method'                => $method,
                    'client_customerid'     => $client_customerid,
                    'client_db'             => $client_db,
                    'messageid'             => $messageid,
                    'apptoken'              => $apptoken,
                    'appkey'                => $appkey,
                    'sign'                  => 1,
                    'timestamp'             => $timestamp,
                    'data'                  => json_encode($datasRecord)
                ]
            ]);

            return json_decode($response->getBody(),TRUE);

        }catch (ClientException $e) {
            $responseError = $e->getResponse();
            $this->apiLog->insertLog('Warehouse\Honeywell\PutSku','production',(string)$responseError->getBody(), 'ERROR' , json_encode($datas),'/','honeywell');
            return $responseError;
        }
    }

 
}