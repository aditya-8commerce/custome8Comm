<?php

$router->get('/',['as' => 'index','uses' => 'IndexController@index']); 
$router->get('/page-not-found',['as' => 'pagenotfound','uses' => 'IndexController@pagenotfound']); 
$router->get('/server-error',['as' => 'servererror','uses' => 'IndexController@servererror']);
$router->get('/version', function () use ($router) {
    return $router->app->version();
});

$router->get('/coba',['as' => 'coba','uses' => 'IndexController@coba']); 
 


$router->group(['prefix' => 'luxasia', 'namespace' => 'Luxasia'], function () use ($router) {
	
    $router->get('/',['as' => 'luxasiaIndex','uses' => 'IndexController@index']);
    $router->get('import-sku',['as' => 'luxasiaImportSKU','uses' => 'IndexController@importSKU']);
    $router->get('import-po',['as' => 'luxasiaImportPo','uses' => 'IndexController@importPo']);
    $router->get('import-so',['as' => 'luxasiaImportSo','uses' => 'IndexController@importSo']);

});

 