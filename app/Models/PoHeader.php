<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoHeader extends Model
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'create_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'update_time';


    protected $table        = 'tbl_po_header';
    protected $primaryKey   = 'po_header_id';
    protected $fillable     = array("po_type", "po_no", "crossdock_no", "po_date", "company_id", "eta_date", "vehicle_no", "driver_name", "status", "create_by", "dest_name", "dest_address1", "dest_address2", "dest_province", "dest_city", "dest_area", "dest_sub_area", "dest_postal_code", "dest_village", "dest_remarks", "ori_name", "ori_address1", "ori_address2", "ori_province", "ori_city", "ori_area", "ori_sub_area", "ori_postal_code", "ori_village", "ori_remarks", "fulfillment_center_id", "create_time", "update_time", "interface_job_id", "inteface_time", "dest_country", "ori_country");
    public $timestamps      = true;
   
    public function details()
    {
        return $this->hasMany('App\Models\PoDetail', 'po_header_id','po_header_id');
    }

    public function statusTracking(){
        return $this->hasMany('App\Models\PoStatusTracking','po_header_id','po_header_id');
    }

    public function fulfillmentCenter(){
        return $this->hasOne('App\Models\FulfillmentCenter','fulfillment_center_id','fulfillment_center_id');
    }
}