<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class CourtModel extends Model{
    protected $table = 'hy_court';
    protected $primaryKey = 'id';
    protected $allowedFields = ['club_id', 'name','cost','addeddate'];

    public function getCourtsByClub($club_id)
    {
        return $this->where('club_id', $club_id)->findAll();
    }
}