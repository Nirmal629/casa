<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class ClubModel extends Model{
    protected $table = 'hy_club';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name','email','password','phone'];

    public function getOpenCloseTime($club_id)
    {
        return $this->where('id', $club_id)->first();
    }
}