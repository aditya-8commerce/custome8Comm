<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'update_time';

    protected $table = 'tbl_log';
    protected $primaryKey   = 'id';
    protected $fillable = array('module','status','response','message','fixed','param1','company_id','ip');
    public $timestamps = true;
  
}