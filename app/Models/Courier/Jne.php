<?php
namespace App\Models\Courier;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as Client;
use Psr\Http\Message\RequestInterface;
use App\Http\Controllers\ApiLogController as ApiLog;
use App\Models\CourierChannel;


class Jne
{

    public $endPointGenerateCnote  = 'http://apiv2.jne.co.id:10101/tracing/api/generatecnote';
    public $endPointTracking       = 'http://apiv2.jne.co.id:10101/tracing/api/list/v1/cnote';
    public $endPointCheckTarif     = 'http://apiv2.jne.co.id:10101/tracing/api/pricedev';

    protected $apiLog;


    public function __construct($channel)
    {
        $this->channel          = $channel;
        $this->apiLog           = new ApiLog;
        $this->client           = new Client();
    }

    public function tracking($awb){
        $url    = $this->endPointTracking.'/'.$awb;
        try {
            $response = $this->client->request('POST', $url, [
                'form_params' => [
                    'username'              => $this->channel->user,
                    'api_key'               => $this->channel->key,
                ]
            ]);

            return json_decode($response->getBody(),TRUE);

        }catch (ClientException $e) {
            $responseError = $e->getResponse();
            $this->apiLog->insertLog('Courier\Warehouse\Jne','production',(string)$responseError->getBody(), 'ERROR' , json_encode($url),'/','jne');
            return $responseError;
        }
    }


}