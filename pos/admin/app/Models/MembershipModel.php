<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class MembershipModel extends Model{
    protected $table = 'hy_membership';
    protected $allowedFields = ['unique_id','name','phone','email','start_date','end_date','amount','discount','card_number','status','addeddate'];

    public function getUserDetailsByMembershipId($membershipId)
    {
        // Query the database to get user details based on the membership ID
        $builder = $this->db->table('hy_membership');
        $builder->where('unique_id', $membershipId);
        $query = $builder->get();
        return $query->getRowArray();
    }
    public function getUserDetailsByMembershipPhone($membershipPhone)
    {
        // Query the database to get user details based on the membership ID
        $builder = $this->db->table('hy_membership');
        $builder->where('phone', $membershipPhone);
        $query = $builder->get();
        return $query->getRowArray();
    }
}