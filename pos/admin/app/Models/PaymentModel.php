<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class PaymentModel extends Model{
    protected $table = 'hy_payment_history';
    protected $primaryKey = 'id';
    protected $allowedFields = ['booking_id','court_id','amount','payment_status','payment_date','payment_type','type', 'booking_key'];

}