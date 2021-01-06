<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
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


    protected $table        = 'tbl_order_detail';
    protected $primaryKey   = 'order_detail_id';
    protected $fillable     = array('order_header_id','sku_code','sku_description','qty_order',
            'price','amount_order','qty_ship','amount_ship','remarks','status','promo_code',
            'origin_address_id','dest_address_id','insured','special_packaging','sku_parent',
            'order_price','crossdock_no','qty_available','qty_delivered');
    public $timestamps      = true;
   
    public function header()
    {
        return $this->belongsTo('App\Models\OrderHeader', 'order_header_id','order_header_id');
    }

    public function sku()
    {
        return $this->hasOne('App\Models\Sku', 'sku_code','sku_code');
    }
}
