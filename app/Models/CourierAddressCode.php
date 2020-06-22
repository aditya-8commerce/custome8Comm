<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierAddressCode extends Model
{

    protected $table        = 'tbl_address_courier_code';
    protected $primaryKey   = 'address_courier_code_id';
    protected $fillable     = array('courier_id','address_code','country','province','city','city_code',
    'area','sub_area','postal_code');
    public $timestamps      = true;
  
}
