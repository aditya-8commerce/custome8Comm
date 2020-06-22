<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sku extends Model
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


    protected $table        = 'tbl_sku';
    protected $primaryKey   = 'sku_id';
    protected $fillable     = array('sku_code','sku_description','company_id','price',
            'insured','width','height','length','special_packaging','weight','type',
            'conv_pcs','conv_bundle','conv_box','conv_cbm','conv_pallet','category_id',
            'image','sku_short_description','net_weight','cube','is_shelf_life','inbound_life_days',
            'outbond_life_days','shelf_life','shelf_life_type','qty_per_carton','carton_per_pallet','uom',
            'barcode','freight_class');
    public $timestamps      = true;
   
    public function details()
    {
        return $this->belongsTo('App\Models\OrderDetail', 'sku_code','sku_code');
    }
    
    public function kits()
    {
        return $this->hasMany('App\Models\SkuKitComponent','sku_kit_id','sku_id');
    }

}
