<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoBuffer extends Model
{

    protected $table = 'tbl_po_buffer';
    protected $primaryKey   = 'id';
    protected $fillable = array(
        'company_id',
        'po_no',
        'type',
        'seq',
        'channel',
        'datas',
        'datas_sci',
        'create_time'
    );
    public $timestamps = false;
  
}