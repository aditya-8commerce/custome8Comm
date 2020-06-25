<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
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


    protected $table        = 'tbl_inventory';
    protected $primaryKey   = 'inventory_id';
    protected $fillable     = array(
        'company_id', 'sku_code', 'stock_date', 'stock_available', 'stock_hold', 'stock_on_hand', 
        'stock_booked', 'stock_booked_pending', 'fulfillment_center_id', 'job_id'
    );
    public $timestamps      = true;
   
    public function fulfillmentCenter()
    {
        return $this->hasOne('App\Models\FulfillmentCenter', 'fulfillment_center_id','fulfillment_center_id');
    }
    
    public function company()
    {
        return $this->hasOne('App\Models\Company','company_id','company_id');
    }

}
