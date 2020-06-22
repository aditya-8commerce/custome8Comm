<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBuffer extends Model
{

    protected $table = 'tbl_order_buffer';
    protected $primaryKey   = 'id';
    protected $fillable = array('order_no','company_id','shop_id','seq','channel','type','datas','datas_sci','create_time');
    public $timestamps = false;
  
}
