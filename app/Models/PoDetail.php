<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoDetail extends Model
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


    protected $table        = 'tbl_po_detail';
    protected $primaryKey   = 'po_detail_id';
    protected $fillable     = array("po_header_id", "sku_code", "sku_description", "qty_order", "price", "amount_order", "qty_received", "amount_received", "remarks", "status", "received_time", "create_time", "update_time");
    public $timestamps      = true;
   
   
    public function header()
    {
        return $this->belongsTo('App\Models\PoHeader', 'po_header_id','po_header_id');
    }

    public function sku()
    {
        return $this->hasOne('App\Models\Sku', 'sku_code','sku_code');
    }
}