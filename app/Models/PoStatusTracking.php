<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoStatusTracking extends Model
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


    protected $table        = 'tbl_po_status_tracking';
    protected $primaryKey   = 'po_status_tracking_id';
    protected $fillable     = array("po_no", "status", "system", "remarks", "create_by", "create_time", "update_time", "po_header_id");
    public $timestamps      = true;
   
    public function header()
    {
        return $this->belongsTo('App\Models\PoHeader', 'po_header_id','po_header_id');
    }

}