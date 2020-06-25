<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFullfilmentCenter extends Model
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


    protected $table        = 'tbl_company_fulfillment';
    protected $primaryKey   = 'id';
    protected $fillable     = array('company_id','fulfillment_center_id','create_time');
    public $timestamps      = false;
   
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id','company_id');
    }
}
