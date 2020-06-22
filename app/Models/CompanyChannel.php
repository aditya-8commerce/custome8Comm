<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyChannel extends Model
{

    protected $table = 'tbl_company_channel';
    protected $primaryKey = 'channel_id';
    protected $fillable = array('company_id','fc','channel','user','password','key','create_time','update_time','partnercode','email','email_password','shop_id','refresh_token');
    public $timestamps = false;
  
}
