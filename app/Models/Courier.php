<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
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

    protected $table        = 'tbl_courier';
    protected $primaryKey   = 'courier_id';
    protected $keyType      = 'string';

    protected $fillable = array('name','pic','phone','mobile','fax','email','is_trip','company_id','access_token','refresh_token','expires_in','type');
    
    public $timestamps = true;
    public $incrementing = false;
  
    public function header()
    {
        return $this->belongsTo('App\Models\OrderHeader', 'courier_id','courier_id');
    }
}
