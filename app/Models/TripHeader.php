<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripHeader extends Model
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

    protected $table        = 'tbl_trip_header';
    protected $primaryKey   = 'trip_id';
    protected $fillable     = array(
        'status', 'trip_date', 'start_time', 'finish_time', 'courier_id', 'username', 
        'company_id', 'fulfillment_center_id', 'remarks','driver_name','vehicle_no','km_start','km_finish'
    );
    public $timestamps = true;
  
    public function tripDetail()
    {
        return $this->hasMany('App\Models\TripDetails', 'trip_id');
    }

    public function statusTracking(){
        return $this->hasMany('App\Models\TripStatusTracking','trip_id','trip_id');
    }

    public function courier(){
        return $this->belongsTo('App\Models\Courier','courier_id','courier_id');
    }

    public function fulfillmentCenter(){
        return $this->belongsTo('App\Models\FulfillmentCenter','fulfillment_center_id','fulfillment_center_id');
    }

    public function company(){
        return $this->belongsTo('App\Models\Company','company_id');
    }
}
