<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoTypeMaster extends Model
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';
    protected $table        = 'tbl_po_type_status';
    protected $primaryKey   = 'po_type_status_id';
    protected $fillable     = array('po_type_status_code','po_type_code');
    public $timestamps      = true;

}