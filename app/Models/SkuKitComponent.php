<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuKitComponent extends Model
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


    protected $table        = 'tbl_sku_kit_component';
    protected $primaryKey   = 'sku_kit_component_id';
    protected $fillable     = array('sku_kit_id','sku_component_id','qty','company_id');
    public $timestamps      = true;
   
    public function sku()
    {
        return $this->belongsTo('App\Models\Sku', 'sku_id','sku_kit_id');
    }
    
}
