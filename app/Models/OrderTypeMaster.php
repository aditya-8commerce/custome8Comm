<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTypeMaster extends Model
{
    protected $table        = 'tbl_order_type_master';
    protected $primaryKey   = 'order_type_id';
    protected $fillable     = array('order_type_code','order_type_name');
    public $timestamps      = false;

}