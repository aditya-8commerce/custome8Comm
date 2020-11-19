<?php

$router->group(['prefix' => 'v1'], function () use ($router) {
	$router->get('/',['as' => 'index','uses' => 'IndexController@index']); 
	$router->get('/page-not-found',['as' => 'pagenotfound','uses' => 'IndexController@pagenotfound']); 
	$router->get('/server-error',['as' => 'servererror','uses' => 'IndexController@servererror']);
	$router->get('/version', function () use ($router) {
		return $router->app->version();
	});

	$router->get('/coba',['as' => 'coba','uses' => 'IndexController@coba']); 
	 


	$router->group(['prefix' => 'luxasia', 'namespace' => 'Luxasia'], function () use ($router) {
		
		$router->get('/',['as' => 'luxasiaIndex','uses' => 'IndexController@index']);
		$router->get('sku',['as' => 'luxasiaImportSKU','uses' => 'IndexController@sku']);
		$router->get('po',['as' => 'luxasiaImportPo','uses' => 'IndexController@po']);
		$router->get('receipts-po',['as' => 'luxasiaReceiptsPo','uses' => 'IndexController@receiptsPo']);
		$router->get('stock',['as' => 'luxasiaStock','uses' => 'IndexController@stock']);
		$router->get('stock-transfer',['as' => 'luxasiaStockTransfer','uses' => 'IndexController@stockTransfer']);
		$router->get('so',['as' => 'luxasiaSo','uses' => 'IndexController@so']);

	});
	

	$router->group(['prefix' => 'xpress', 'namespace' => 'Xpress'], function () use ($router) {
		
		$router->get('/',['as' => 'xpressIndex','uses' => 'IndexController@index']);
		$router->post('/login',['as' => 'xpressLogin','uses' => 'IndexController@login']);
		$router->get('/search-order/{tripId}',['as' => 'xpressSearchOrder','uses' => 'IndexController@SearchOrder']);
		$router->post('/update-status/{tripDetailId}',['as' => 'xpressUpdateStatus','uses' => 'IndexController@updateStatus']);

	});


});
 