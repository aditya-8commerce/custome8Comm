<?php
namespace App\Models\Warehouse\Honeywell;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client as Client;
use Psr\Http\Message\RequestInterface;
use App\Http\Controllers\ApiLogController as ApiLog;


class InboundAsn
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
    public function index($datas, $return_order_no = '', $return_awb_no = '') {
        $InterfaceMethod    = 'POST'; 
        $method             = 'putASNData'; 
        $messageid          = "ASN"; 
        $client_customerid  = 'FLUXWMSJSON';  // Fixed value
        $client_db          = "FLUXWMSJSON"; // Fixed value
        $apptoken           = "80AC1A3F-F949-492C-A024-7044B28C8025"; 
        $appkey             = "test";
        $timestamp          = date('Y-m-d H:i:s'); // Fixed value
        $header             = [];
        $detailsItem        = []; 
		$no = 1;


		 
		$asnReference2 = '';
		if($datas->po_type == 'crossdock' || $datas->po_type == 'put_to_store'){
			$asnReference2 = strtoupper($datas->crossdock_no);
		}
		
        foreach($datas->details as $k=>$row) {
            $detailsItem[] = array(
                "asnLineNo"    => $no, 
                "customerId"   => strtoupper($datas->company_id), 
                "sku"          => strtoupper($row->sku_code),
                "expectedQty"  => $row->qty_order,
				"lotAtt07"	   => $asnReference2,
				// "lotAtt09"	   => strtoupper($data->po_no)
            );
			$no++;
        }

        
		
			$orderType = "NOM";
            $userDefine4="";
            
            
			
		if($datas->po_type == 'crossdock'){
			// $orderType = "CRD";
			$orderType = "PTS";
			$userDefine4=$datas->crossdock_no;
		}elseif($datas->po_type == 'put_to_store'){
			$orderType = "PTS";
			$userDefine4='';
		}elseif($datas->po_type == 'po_return'){
			$orderType = "REF";
			$userDefine4=$data->po_no;
		}elseif($datas->po_type == 'so_return'){
			$orderType = "RE";
			$userDefine4=$data->po_no;
		}elseif($datas->po_type == 'regular_return'){
			$orderType = "RR";
		}elseif($datas->po_type == 'transfer_inbound'){
			$orderType = "TRF";
		}else{
			$orderType = "NOM";
		}
        $datasRecord = array(
            "xmldata" =>  array(
                "header" => [
                    array(
                        "asnReference1"        => strtoupper($datas->po_no), // tbl_po_header.po_no
                        "asnReference2"        => $asnReference2, // tbl_po_header.po_no
                        "orderType"            => $orderType, // Normal Inbound: NOM ; Returns Inbound : RE ; Inbound Kitting :INK; Cross Dock: CRD
                        "customerId"           => strtoupper($datas->company_id), // tbl_po_header.company_id
                        "asnCreationTime"      => "",
                        "asnReference3"        => strtoupper($datas->po_no), // tbl_po_header.po_no,
                        "asnReference4"        => strtoupper($datas->po_no),
                        "asnReference5"        => '',
                        "countryOfOrigin"      => "ID",  
                        "countryOfDestination" => "ID",  
                        "warehouseId"          => strtoupper($datas->fulfillment_center_id), // tbl_po_header.fulfillment_center_id
						"supplierId"		   => strtoupper($datas->company_id), // tbl_po_header.company_id
						"supplierName"		   => strtoupper($datas->company_id), // tbl_po_header.company_id
						"hedi07"			   => strtoupper($datas->ori_remarks),
						"userDefine4"		   => $userDefine4,
                        "detailsItem"          => $detailsItem
                    )
                ]
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
            $this->apiLog->insertLog('Warehouse\Honeywell\InboundAsn','production',(string)$responseError->getBody(), 'ERROR' , json_encode($datas),'/','honeywell');
            return $responseError;
        }
    }

 
}