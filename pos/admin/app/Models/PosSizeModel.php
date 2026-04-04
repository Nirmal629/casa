<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class PosSizeModel extends Model{
    protected $table = 'pos_size';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'name'];
}