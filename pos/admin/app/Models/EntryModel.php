<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class EntryModel extends Model{
    protected $table = 'hy_entries';
    protected $primaryKey = 'id';
    protected $allowedFields = ['booking_key', 'entry_id','entry_name','entry_qnty','entry_cost','addeddate'];

}