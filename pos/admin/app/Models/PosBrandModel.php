<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class PosBrandModel extends Model{
    protected $table = 'pos_brand';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'name'];

    public function getBrandById($id)
    {
        return $this->where('id', $id)->first();
    }
}