<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoImportCsv extends Model
{

    protected $table = 'tbl_po_import_csv';
    protected $primaryKey   = 'po_import_id';
    protected $fillable = array(
        'uuid', 'po_type', 'po_no', 'crossdock_no', 'po_date', 'eta_date', 'vehicle_no', 'driver_name', 
        'ori_name', 'ori_address1', 'ori_address2', 'ori_province', 'ori_city', 'ori_area', 'ori_sub_area', 'ori_postal_code', 
        'ori_village', 'ori_remarks', 'ori_country', 'sku_code', 'qty_order', 'price', 'sku_remarks'
    );
    public $timestamps = false;
  
}