<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
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


    protected $table        = 'tbl_store';
    protected $primaryKey   = 'store_id';
    protected $fillable     = array('company_id','store_code','store_name','address1',
            'address2','country','province','city','area','sub_area','village',
            'pic_name','pic_phone','pic_email','latitude','longitude');
    public $timestamps      = true;


    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id','company_id');
    }

}