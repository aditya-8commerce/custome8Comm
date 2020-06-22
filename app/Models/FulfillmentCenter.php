<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentCenter extends Model
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

    protected $table        = 'tbl_fulfillment_center';
    protected $primaryKey   = 'fulfillment_center_id';
    protected $keyType      = 'string';
    protected $fillable     = array('name','address','address2','province','city','area','sub_area','postal_code','village','phone','type','company_id');
    public $timestamps      = true;
  
    public function orderHeader()
    {
        return $this->belongsTo('App\Models\OrderHeader', 'fulfillment_center_id','fulfillment_center_id');
    }
    
    public function poHeader()
    {
        return $this->belongsTo('App\Models\PoHeader', 'fulfillment_center_id','fulfillment_center_id');
    }
}
