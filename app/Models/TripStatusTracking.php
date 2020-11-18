<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripStatusTracking extends Model
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

    protected $table        = 'tbl_trip_status_tracking';
    protected $primaryKey   = 'trip_status_tracking_id';
    protected $fillable     = array(
        'status', 'system', 'remarks', 'create_by','trip_id'
    );
    public $timestamps = true;
  
    public function tripHeader(){
        return $this->belongsTo('App\Models\TripHeader','trip_id');
    }
}
