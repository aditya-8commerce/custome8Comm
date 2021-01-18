<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderHeader extends Model
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


    protected $table        = 'tbl_order_header';
    protected $primaryKey   = 'order_header_id';
    protected $fillable     = array('order_type','order_no','order_date','company_id','due_date','courier_id','awb_no','status',
            'insured','insured_by_id','cod','payment_status','create_by','dest_name','dest_address1','dest_address2','dest_province',
            'dest_city','dest_area','dest_sub_area','dest_postal_code','dest_village','ori_name','ori_address1','ori_address2','ori_province',
            'ori_city','ori_area','ori_sub_area','ori_postal_code','ori_village','ori_remarks','fulfillment_center_id','promo_code','order_source',
            'omni_channel','dest_phone','dest_mobile','dest_phone2','dest_email','special_packaging','interface_job_id',
            'interface_time','ori_country','dest_country','message_shipped_id','message_delivered_id','message_delivered_sms_id','trip_id','order_amount',
            'shipping_amount','insurance_amount','ori_phone');
    public $timestamps      = true;
  
    public function details(){
        return $this->hasMany('App\Models\OrderDetail','order_header_id','order_header_id');
    }

    public function statusTracking(){
        return $this->hasMany('App\Models\OrderStatusTracking','order_header_id','order_header_id');
    }

    public function courier(){
        return $this->belongsTo('App\Models\Courier','courier_id','courier_id');
    }

    public function company(){
        return $this->belongsTo('App\Models\Company','company_id','company_id');
    }

    public function fulfillmentCenter(){
        return $this->belongsTo('App\Models\FulfillmentCenter','fulfillment_center_id','fulfillment_center_id');
    }

    public function orderType(){
        return $this->belongsTo('App\Models\OrderTypeMaster','order_type','order_type');
    }
}
