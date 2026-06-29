<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class PosCategoryModel extends Model{
    protected $table = 'pos_category';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'name'];

    public function getCategoryById($id)
    {
        return $this->where('id', $id)->first();
    }
}