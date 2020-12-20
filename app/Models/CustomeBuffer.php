<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomeBuffer extends Model
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


    protected $table        = 'tbl_custome_buffer';
    protected $primaryKey   = 'id';
    protected $fillable     = array('order_no','company_id','type','seq','sku_code','reason','additional_reason','stock_hold');
    public $timestamps      = true;
  
}
