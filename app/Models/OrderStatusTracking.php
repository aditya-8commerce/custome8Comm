<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusTracking extends Model
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


    protected $table        = 'tbl_order_status_tracking';
    protected $primaryKey   = 'order_status_tracking_id';
    protected $fillable     = array('order_no','status','system','remarks',
            'create_by','order_header_id');
    public $timestamps      = true;
   
    public function header()
    {
        return $this->belongsTo('App\Models\OrderHeader', 'order_header_id','order_header_id');
    }

}