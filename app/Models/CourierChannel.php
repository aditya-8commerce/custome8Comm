<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierChannel extends Model
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

    protected $table = 'tbl_courier_channel';
    protected $primaryKey = 'courier_channel_id';
    protected $fillable = array('company_id','courier_id','user','password','key','partnercode','cod','access_token','refresh_token');
    public $timestamps = true;
  
    public static function getData($courier_id,$company_id,$cod=null){
        $where = [
            ['courier_id',$courier_id],["company_id",$company_id]
        ];

        if(isset($cod)){
            $where[] = ['cod',$cod];
        }

        $model  = CourierChannel::where($where)->first();
        return $model;
    }

}
