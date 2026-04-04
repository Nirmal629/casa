<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class BookingModel extends Model{
    protected $table = 'hy_booking';
    protected $primaryKey = 'id';
    protected $allowedFields = ['membership_id','court_id','name','phone','email','date','day','start_time','end_time','duration','cost','deposit','card_number','payment_type','payment_status','no_change','booking_type','booking_key', 'booking_status'];

    public function getBookings($orderBy = 'id', $direction = 'ASC')
    {
        // Use the query builder to fetch data with order
        return $this->orderBy($orderBy, $direction)->findAll();
    }

    public function getBookingHistory($court_id, $time, $date)
    {
        return $this->where('court_id', $court_id)
        ->where('date', $date)
        // ->where('start_time <=', $time)
        // ->where('end_time >=', $time)
        ->findAll();
    }

}