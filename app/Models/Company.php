<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
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

    protected $table        = 'tbl_company';
    protected $primaryKey   = 'company_id';
    protected $keyType      = 'string';
    public $incrementing    = false;
    protected $fillable     = array(
        'name', 'address1', 'address2', 'phone', 'mobile', 'contact_person', 
        'fax', 'country', 'zone_activate'
    );
    public $timestamps = true;
  
    public function inventory()
    {
        return $this->belongsTo('App\Models\Inventory', 'company_id','company_id');
    }
}
