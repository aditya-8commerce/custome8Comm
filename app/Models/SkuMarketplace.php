<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuMarketplace extends Model
{
    /**
 * primaryKey 
 * 
 * @var integer
 * @access protected
 */
protected $primaryKey = null;

/**
 * Indicates if the IDs are auto-incrementing.
 *
 * @var bool
 */
public $incrementing = false;

    protected $table        = 'tbl_sku_marketplace';
    protected $fillable     = array('sku_code','marketplace_id','status','company_id','datas');
    public $timestamps      = false;
   


}
