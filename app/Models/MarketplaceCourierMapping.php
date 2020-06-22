<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceCourierMapping extends Model
{

    protected $table = 'tbl_marketplace_courier_mapping';
    protected $primaryKey   = 'id';
    protected $fillable = array('company_id','order_source','courier_id','courier_marketplace','type','id_marketplace_courier');
    public $timestamps = false;
}