<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerCustomeTransaction extends Model
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


    protected $table        = 'tbl_server_custome_transaction';
    protected $primaryKey   = 'id';
    protected $fillable     = array('company_id','datas','file_name');
    public $timestamps      = true; 
}
