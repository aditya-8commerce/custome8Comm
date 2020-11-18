<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripDetails extends Model
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

    protected $table        = 'tbl_trip_detail';
    protected $primaryKey   = 'trip_detail_id';
    protected $fillable     = array(
        'trip_id', 'order_header_id', 'start_time', 'finish_time', 'remarks',
        'pod','longitude','latitude'
    );
    public $timestamps = true;
  
    public function tripHeader()
    {
        return $this->belongsTo('App\Models\TripHeader', 'trip_id');
    }
    public function order()
    {
        return $this->belongsTo('App\Models\OrderHeader', 'order_header_id');
    }
}
