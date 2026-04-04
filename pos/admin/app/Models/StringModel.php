<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class StringModel extends Model{
    protected $table = 'hy_stringing';
    protected $primaryKey = 'id';
    protected $allowedFields = ['club_id','service_no','which_string','color','tension','service_date','delivery_date', 'string_cost', 'string_discount', 'labour_charge', 'total_cost', 'membership_id', 'name', 'phone', 'email', 'card_number', 'exp_date', 'cvv', 'payment_type','payment_status', 'status', 'addeddate'];

    public function getStringingByClub($club_id)
    {
        return $this->where('club_id', $club_id)->findAll();
    }
}