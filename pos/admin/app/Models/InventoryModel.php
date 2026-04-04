<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class InventoryModel extends Model{
    protected $table = 'hy_inventory';
    protected $primaryKey = 'id';
    protected $allowedFields = ['club_id', 'name', 'sku', 'category', 'brand', 'size', 'color', 'quantity', 'price','addeddate', 'status'];

    public function getInventoryByClub($club_id)
    {
        return $this->where('club_id', $club_id)->findAll();
    }

    public function getInventoryDetailsById($id)
    {
        return $this->where('id', $id)->first();
    }
}