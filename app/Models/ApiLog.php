<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{

    protected $table = 'tbl_api_logs';
    protected $primaryKey   = 'id';
    protected $fillable = array('instance','channel','level','url','ip','message','user_agent','context','company_id');
    public $timestamps = true;
  
}