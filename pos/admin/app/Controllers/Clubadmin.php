<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\MembershipModel;
use App\Models\BookingModel;
use App\Models\CourtModel;
use App\Models\ClubModel;
use App\Models\CourtPriceModel;
use App\Models\PaymentModel;
use App\Models\InventoryModel;
use App\Models\PosCategoryModel;
use App\Models\PosBrandModel;
use App\Models\PosSizeModel;
use App\Models\StringModel;
use App\Models\EntryModel;
class Clubadmin extends BaseController
{
    protected $helpers = ['form'];
    public function index($date = "", $from_time = "", $to_time = "")
    {
        $clubModel = new ClubModel();
        $courtModel = new CourtModel();
        $bookingModel = new BookingModel();
        $inventoryModel = new InventoryModel();

        $club_id = session()->get('user_id');
        $getInventoryData = $inventoryModel->getInventoryByClub($club_id);
        
        $getOpenCloseTime = $clubModel->getOpenCloseTime($club_id);
        $club_open_time = $getOpenCloseTime['open_time'];
        $club_close_time = $getOpenCloseTime['close_time'];
        $time_slots = $this->get_time_intervals($club_open_time, $club_close_time);
        $data['from_time'] = $club_open_time;
        $data['to_time'] = $club_close_time;
        if($from_time != '' && $to_time != ''){
            $from_time = str_replace("-", " ", $from_time);
            $to_time = str_replace("-", " ", $to_time);
            $time_slots = $this->get_time_intervals($from_time, $to_time);
            $data['from_time'] = $from_time;
            $data['to_time'] = $to_time;
        }
        $currendDate = date('Y-m-d');
        if($date !=''){
            $currendDate = date('Y-m-d', strtotime($date));
        }
        $data['date'] = date('d-m-Y', strtotime($currendDate));

        $getCourtData = $courtModel->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;
        $data['inventory_list'] = $getInventoryData;
        $bookingSlots = [];
        if (!empty($time_slots)) {
        // echo strtotime($currendDate);exit;
        foreach ($time_slots as $key => $time_slot) {
                // Initialize an array to store booking details for each time slot
                $bookingSlots[$key]['time'] = $time_slot;
                $bookingSlots[$key]['booking_data'] = [];
                
                if (!empty($getCourtData)) {
                    foreach ($getCourtData as $court) {
                        // Fetch booking details for each court and time slot
                        $bookingDetails = $bookingModel->getBookingHistory($court['id'], $time_slot, $currendDate);
                        $formattedTimeSlot = strtotime($time_slot);
                        // Check if there are any booking details and store them
                        // $bookingSlots[$key]['booking_data'][$court['id']] = $bookingDetails;
                        $bookingSlots[$key]['booking_data'][$court['id']] = array();
                        if(!empty($bookingDetails)){
                            foreach ($bookingDetails as $booking) {
                                $startTime = strtotime($booking['start_time']);
                                $endTime = strtotime($booking['end_time']);

                                // Check if the time slot falls between start and end time
                                if ($formattedTimeSlot >= $startTime && $formattedTimeSlot <= $endTime) {
                                    // Add booking details if the time slot is within the booking period
                                    $bookingSlots[$key]['booking_data'][$court['id']] = $booking;
                                }
                            }
                        }
                        
                    }
                }
            }
        }
        /*echo "<pre>";
        print_r($bookingSlots);
        echo "<pre>";exit;*/
        $data['time_slots'] = $time_slots;
        $data['booking_slots'] = $bookingSlots;
        $data['page'] = 'dashboard';
        $data['title'] = 'Dashboard';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/dashboard');
        echo view('admin/common/footer');
    }

    public function addNewBooking()
    {
        $model = new CourtModel();
        $club_id = session()->get('user_id');
        $getCourtData = $model->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;

        $data['page'] = 'addnewbooking';
        $data['title'] = 'Add New Booking';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/add-new-booking');
        echo view('admin/common/footer');
    }

    public function preBooking()
    {
        $model = new CourtModel();
        $club_id = session()->get('user_id');
        $getCourtData = $model->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;

        $data['page'] = 'prebooking';
        $data['title'] = 'Pre Booking';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/pre-booking');
        echo view('admin/common/footer');
    }

    public function editPreBooking($booking_key = "")
    {
        $model = new CourtModel();
        $bookingModel = new BookingModel();
        $membershipModel = new MembershipModel();
        $club_id = session()->get('user_id');
        $getCourtData = $model->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;

        $builder = $bookingModel->builder();
        $builder->select('*');
        $builder->where('booking_key', $booking_key);

        // Fetch the results
        $bookings = $builder->get()->getResultArray();
        if(!empty($bookings)){
            foreach ($bookings as $key => $value) {
                $bookings[$key]['membership_id'] = $membershipModel->where('id', $value['membership_id'])->first()['unique_id'];
            }
        }

        $data['bookings'] = $bookings;
        $data['booking_key'] = $booking_key;
        $data['page'] = 'editprebooking';
        $data['title'] = 'Modify Pre Booking';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/edit-pre-booking');
        echo view('admin/common/footer');
    }
    public function editBooking($booking_id = "")
    {
        $model = new CourtModel();
        $bookingModel = new BookingModel();
        $membershipModel = new MembershipModel();
        $club_id = session()->get('user_id');
        $getCourtData = $model->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;

        $builder = $bookingModel->builder();
        $builder->select('*');
        $builder->where('id', $booking_id);

        // Fetch the results
        $booking_details = $builder->get()->getrowArray();
        if(!empty($booking_details)){
            $booking_details['membership_id'] = $membershipModel->where('id', $booking_details['membership_id'])->first()['unique_id'];
            $booking_details['court_no'] = $model->where('id', $booking_details['court_id'])->first()['name'];
            $booking_details['date'] = date('d/m/Y', strtotime($booking_details['date']));
        }

        $data['booking_details'] = $booking_details;
        $data['booking_id'] = $booking_id;
        $data['page'] = 'editbooking';
        $data['title'] = 'Edit Booking';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/edit-booking');
        echo view('admin/common/footer');
    }

    public function listBooking()
    {
        $model = new BookingModel();
        $membershipModel = new MembershipModel();
        $courtModel = new CourtModel();
        $bookings = $model->getBookings('id', 'DESC');
        if(!empty($bookings)){
            foreach ($bookings as $key => $value) {
                $bookings[$key]['membership_id'] = $membershipModel->where('id', $value['membership_id'])->first()['unique_id'];
                $bookings[$key]['court_id'] = $courtModel->where('id', $value['court_id'])->first()['name'];
                $bookings[$key]['booking_type_text'] = 'New Booking';
                if($value['booking_type'] != 'new_booking'){
                    $bookings[$key]['booking_type_text'] = 'Pre Booking';
                }
            }
        }
        $data['bookings'] = $bookings;
        $data['page'] = 'listbooking';
        $data['title'] = 'List Booking';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-booking');
        echo view('admin/common/footer');
    }

    public function listPreBooking()
    {
        $model = new BookingModel();
        $membershipModel = new MembershipModel();
        $courtModel = new CourtModel();
        $builder = $model->builder();
        $builder->select('*');
        $builder->where('booking_type', 'pre_booking');
        $builder->groupBy('booking_key');

        // Fetch the results
        $bookings = $builder->get()->getResultArray();
        if(!empty($bookings)){
            foreach ($bookings as $key => $value) {
                $bookings[$key]['membership_id'] = $membershipModel->where('id', $value['membership_id'])->first()['unique_id'];
            }
        }
        // print_r($bookings);exit;
        $data['bookings'] = $bookings;
        $data['page'] = 'listprebooking';
        $data['title'] = 'Pre Bookings';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-pre-booking');
        echo view('admin/common/footer');
    }

    public function addNewMembership()
    {
        $data['page'] = 'addnewmembership';
        $data['title'] = 'Add New Membership';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/add-new-membership');
        echo view('admin/common/footer');
    }

    public function listMembership()
    {
        $model = new MembershipModel();
        $getMembershipData = $model->findAll();
        $data['page'] = 'listmembership';
        $data['title'] = 'List Membership';
        $data['membership_list'] = $getMembershipData;
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-membership');
        echo view('admin/common/footer');
    }
    public function paymentHistory()
    {
        $model = new PaymentModel();
        $getPaymentData = $model->orderBy('id', 'DESC')->findAll();

        $data['page'] = 'paymenthistory';
        $data['title'] = 'Payment History';
        $data['payment_history'] = $getPaymentData;
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/payment-history');
        echo view('admin/common/footer');
    }

    public function profile()
    {
        $clubModel = new ClubModel();
        $club_id = session()->get('user_id');
        $builder = $clubModel->builder();
        $builder->select('*');
        $builder->where('id', $club_id);

        // Fetch the results
        $club_details = $builder->get()->getrowArray();
        
        $data['club_details'] = $club_details;

        $data['page'] = 'profile';
        $data['title'] = 'Profile';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/profile');
        echo view('admin/common/footer');
    }

    public function addNewCourt()
    {
        $data['page'] = 'addnewcourt';
        $data['title'] = 'Add New Court';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/add-new-court');
        echo view('admin/common/footer');
    }

    public function listCourt()
    {
        $model = new CourtModel();
        $club_id = session()->get('user_id');
        $getCourtData = $model->getCourtsByClub($club_id);
        $data['court_list'] = $getCourtData;
        $data['page'] = 'listcourt';
        $data['title'] = 'List Court';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-court');
        echo view('admin/common/footer');
    }


    public function listCategory()
    {
        $model = new PosCategoryModel();
        $getCategoryList = $model->findAll();
        $data['category_list'] = $getCategoryList;
        $data['page'] = 'listcategory';
        $data['title'] = 'List Category';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-category');
        echo view('admin/common/footer');
    }
    public function addCategory()
    {
        $categoryModel = new PosCategoryModel();
        $name = $this->request->getPost("name");        
        $isCategoryExist = $categoryModel->where('name', $name)->first();
        if(empty($isCategoryExist)){
            $data = [
                'name' => $name
            ];
            $isdataInsert =  $categoryModel->insert($data, false);
            if($isdataInsert){
                session()->setFlashdata('success', 'Category Added successfully');                
            }else{
                session()->setFlashdata('error', 'Category Not added. Please try again');
            }
        }else{
            session()->setFlashdata('error', 'Category name already exists');
        }
        return redirect()->to('/club-owner/list-category');
    }
    public function updateCategory()
    {
        $categoryModel = new PosCategoryModel();
        $name = $this->request->getPost("name");        
        $id = $this->request->getPost("editid");
        $isCategoryExist = $categoryModel->where('name', $name)->where('id !=', $id)->first();
        if(empty($isCategoryExist)){
            $data = [
                'name' =>  $name            
            ];
            $categoryModel->update($id, $data);
            session()->setFlashdata('success', 'Category Updated successfully');
        }else{
            session()->setFlashdata('error', 'Category name already exists');
        }
        return redirect()->to('/club-owner/list-category');
    }
    public function getCategory($id='0')
    {
        $categoryModel = new PosCategoryModel();
        $categoryData = $categoryModel->where('id', $id)->first();
        echo json_encode($categoryData);
    }

    public function listBrand()
    {

        $model = new PosBrandModel();
        $getBrandList = $model->findAll();
        $data['brand_list'] = $getBrandList;
        $data['page'] = 'listbrand';
        $data['title'] = 'List Brand';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-brand');
        echo view('admin/common/footer');
    }
    public function addBrand()
    {
        $brandModel = new PosBrandModel();
        $name = $this->request->getPost("name");        
        $isCategoryExist = $brandModel->where('name', $name)->first();
        if(empty($isCategoryExist)){
            $data = [
                'name' => $name
            ];
            $isdataInsert =  $brandModel->insert($data, false);
            if($isdataInsert){
                session()->setFlashdata('success', 'Brand Added successfully');                
            }else{
                session()->setFlashdata('error', 'Brand Not added. Please try again');
            }
        }else{
            session()->setFlashdata('error', 'Brand name already exists');
        }
        return redirect()->to('/club-owner/list-brand');
    }
    public function updateBrand()
    {
        $brandModel = new PosBrandModel();
        $name = $this->request->getPost("name");        
        $id = $this->request->getPost("editid");
        $isCategoryExist = $brandModel->where('name', $name)->where('id !=', $id)->first();
        if(empty($isCategoryExist)){
            $data = [
                'name' =>  $name            
            ];
            $brandModel->update($id, $data);
            session()->setFlashdata('success', 'Brand Updated successfully');
        }else{
            session()->setFlashdata('error', 'Brand name already exists');
        }
        return redirect()->to('/club-owner/list-brand');
    }
    public function getBrand($id='0')
    {
        $brandModel = new PosBrandModel();
        $brandData = $brandModel->where('id', $id)->first();
        echo json_encode($brandData);
    }


    public function listSize()
    {

        $model = new PosSizeModel();
        $getList = $model->findAll();
        $data['size_list'] = $getList;
        $data['page'] = 'listsize';
        $data['title'] = 'List Size';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-size');
        echo view('admin/common/footer');
    }
    public function addSize()
    {
        $sizeModel = new PosSizeModel();
        $name = $this->request->getPost("name");        
        $isSizeExist = $sizeModel->where('name', $name)->first();
        if(empty($isSizeExist)){
            $data = [
                'name' => $name
            ];
            $isdataInsert =  $sizeModel->insert($data, false);
            if($isdataInsert){
                session()->setFlashdata('success', 'Size Added successfully');                
            }else{
                session()->setFlashdata('error', 'Size Not added. Please try again');
            }
        }else{
            session()->setFlashdata('error', 'Size name already exists');
        }
        return redirect()->to('/club-owner/list-size');
    }
    public function updateSize()
    {
        $sizeModel = new PosSizeModel();
        $name = $this->request->getPost("name");        
        $id = $this->request->getPost("editid");
        $isSizeExist = $sizeModel->where('name', $name)->where('id !=', $id)->first();
        if(empty($isSizeExist)){
            $data = [
                'name' =>  $name            
            ];
            $sizeModel->update($id, $data);
            session()->setFlashdata('success', 'Size Updated successfully');
        }else{
            session()->setFlashdata('error', 'Size name already exists');
        }
        return redirect()->to('/club-owner/list-size');
    }
    public function getSize($id='0')
    {
        $Model = new PosSizeModel();
        $Data = $Model->where('id', $id)->first();
        echo json_encode($Data);
    }


    
    public function addNewInventory()
    {
        $data['page'] = 'addnewinventory';
        $data['title'] = 'Add New Inventory';
        $posSizemodel = new PosSizeModel();
        $posCategorymodel = new PosCategoryModel();
        $posBrandmodel = new PosBrandModel();
        $dataP['categorylist'] = $posCategorymodel->findAll();
        $dataP['brandlist'] = $posBrandmodel->findAll();
        $dataP['sizelist'] = $posSizemodel->findAll();
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/add-new-inventory', $dataP);
        echo view('admin/common/footer');
    }

    public function getInventoryDetails($id='')
    {
        $model = new InventoryModel();
        $data = $model->getInventoryDetailsById($id);
        echo json_encode($data);
    }

    public function listInventory()
    {
        $model = new InventoryModel();
        
        $club_id = session()->get('user_id');
        // $getInventoryData = $model->getInventoryByClub($club_id);


        $db = db_connect();

        $builder = $db->table('hy_inventory');
        $builder->select('
            hy_inventory.*,
            pos_category.name AS category_name,
            pos_brand.name AS brand_name,
            pos_size.name AS size_name
        ');
        $builder->join('pos_category', 'pos_category.id = hy_inventory.category', 'left');
        $builder->join('pos_brand', 'pos_brand.id = hy_inventory.brand', 'left');
        $builder->join('pos_size', 'pos_size.id = hy_inventory.size', 'left');

        $builder->where("hy_inventory.club_id", $club_id);

        $query = $builder->get();
        $getInventoryData = $query->getResultArray();


        $data['inventory_list'] = $getInventoryData;

        $data['page'] = 'listinventory';
        $data['title'] = 'List Inventory';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-inventory');
        echo view('admin/common/footer');
    }


    public function saveMembership(){
        $rules = [
            'player_name'          => 'required',
            'phone'          => 'required',
            'email'         => 'required|valid_email|is_unique[hy_membership.email]',
            'start_date'      => 'required',
            'end_date'      => 'required',
            'amount'      => 'required',
            'discount'      => 'required'
        ];
         
        if($this->validate($rules)){
            $model = new MembershipModel();
            $lastId = $model->select('unique_id')->orderBy('id', 'desc')->first();
            if ($lastId) {
                $lastNumber = (int) substr($lastId['unique_id'], 2); // Assuming 'HA' prefix
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $newUniqueId = 'HA' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            $start_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('start_date'))));
            $end_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('end_date'))));
            $data = [
                'unique_id'     => $newUniqueId,
                'name'    => $this->request->getVar('player_name'),
                'phone' => $this->request->getVar('phone'),
                'email' => $this->request->getVar('email'),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'amount' => $this->request->getVar('amount'),
                'discount' => $this->request->getVar('discount'),
                'card_number' => $this->request->getVar('card_number'),
                'status' => $this->request->getVar('status'),
                'addeddate' => date('Y-m-d h:i:s')
            ];
            $model->save($data);
            return redirect()->to('/club-owner/list-membership');
        }else{
            $data['validation'] = $this->validator;
            $data['title'] = "Add New Membership";
            $data['page'] = "addnewmembership";
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-membership');
            echo view('admin/common/footer');
        }
    }

    function getMembershipDetails(){
        $model = new MembershipModel();
        $id = $this->request->getVar('id');
        $data = $model->where('id', $id)->first();
        if(!empty($data)){
            $data['start_date'] = date('jS F, Y', strtotime($data['start_date']));
            $data['end_date'] = date('jS F, Y', strtotime($data['end_date']));
        }
        echo json_encode($data);exit;
    }
    function getAvailableCourt(){
        $bookingModel = new BookingModel();
        $courtModel = new CourtModel();
        $date = $this->request->getVar('date');
        $booking_id = $this->request->getVar('booking_id');
        $court_id = $this->request->getVar('court_id');
        $dateFormat = explode('/', $date);
        $date = $dateFormat[2].'-'.$dateFormat[1].'-'.$dateFormat[0];
        $time = $this->request->getVar('time');
        $postedTime = strtotime($time);

        $allCourts = $courtModel->findAll();
        $bookedCourts = $bookingModel->where('date', $date);
        if ($booking_id > 0) {
            $bookedCourts = $bookedCourts->where('id !=', $booking_id);
        }
        $bookedCourts = $bookedCourts->findAll();

        $bookedCourtIds = array_filter($bookedCourts, function($court) use ($postedTime) {
            // Convert the start_time and end_time to Unix timestamps using strtotime()
            $startTime = strtotime($court['start_time']);
            $endTime = strtotime($court['end_time']);
            
            // Check if the posted time falls within the booked court's start_time and end_time
            return ($postedTime >= $startTime && $postedTime <= $endTime);
        });

        $bookedCourtIds = array_column($bookedCourtIds, 'court_id');

        // print_r($bookedCourtIds);exit;
        $availableCourts = array_filter($allCourts, function($court) use ($bookedCourtIds) {
            return !in_array($court['id'], $bookedCourtIds);
        });
        $availableCourts = array_values($availableCourts);
        $html = "";
        if(!empty($availableCourts)){
            foreach($availableCourts as $key => $value){
                if($court_id > 0 && $value['id'] == $court_id){
                    $html .= '
                        <li class="courtlist active_court court_'.$value['id'].'" onclick="selectCourt('.$value['id'].', \''.$value['name'].'\')">'.$value['name'].'</li>
                    ';
                }
                else{
                    $html .= '
                        <li class="courtlist court_'.$value['id'].'" onclick="selectCourt('.$value['id'].', \''.$value['name'].'\')">'.$value['name'].'</li>
                    ';
                }
                
            }
        }
        else{
            $html .='<label class="text-center text-danger" style="width:100%" id="no_court">No courts are available!</label>';
        }
        echo json_encode($html);exit;
    }
    function getCourtCost(){
        $model = new CourtPriceModel();
        $date = $this->request->getVar('date');
        $time = $this->request->getVar('time');
        $duration = $this->request->getVar('duration');

        $timestamp = strtotime($date);
        $dayOfWeek = date('l', $timestamp);
        $isWeekend = 0;
        if($dayOfWeek == 'Saturday' || $dayOfWeek == 'Sunday'){
            $isWeekend = 1;
        }
        $costData = $model->getCostData($time, $duration, $isWeekend);
        if (!$costData) {
            return json_encode(['cost' => 0]);
        }
        return json_encode(['cost' => number_format($costData*$duration, 2, '.', '')]);
    }
    function deleteItem(){
        
        $id = $this->request->getVar('id');
        $delete_for = $this->request->getVar('delete_for');
        if($delete_for == 'membership'){
            $model = new MembershipModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'court'){
            $model = new CourtModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'inventory'){
            $model = new InventoryModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'stringing'){
            $model = new StringModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'pos_category'){
            $model = new PosCategoryModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'pos_brand'){
            $model = new PosBrandModel();
            $data = $model->where('id', $id)->delete();
        }else if($delete_for == 'pos_size'){
            $model = new PosSizeModel();
            $data = $model->where('id', $id)->delete();
        }
        echo "success";exit;
    }

    public function makeBooking(){
        // Validation rules for the booking form
        $rules = [
            'membership_id' => [
                'label' => 'Membership ID',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Membership ID is required.'
                ]
            ],
            'court_id' => [
                'label' => 'Court',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Please select a court.'
                ]
            ],
            'name' => [
                'label' => 'Name',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Name is required.'
                ]
            ],
            'phone' => [
                'label' => 'Phone',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Phone number is required.'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Email is required.'
                ]
            ],
            'date' => [
                'label' => 'Date',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Booking date is required.'
                ]
            ],
            'time' => [
                'label' => 'Time',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Booking time is required.'
                ]
            ],
            'cost' => [
                'label' => 'Cost',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Court cost is required.'
                ]
            ],
        ];


        // Validate the input fields
        if ($this->validate($rules)) {
            $model = new BookingModel();
            $modelMembership = new MembershipModel();
            $getMembershipDetails = $modelMembership->where('unique_id', $this->request->getVar('membership_id'))->first();

            // Check if the same membership_id already has a booking on the same date
            $membership_id = $getMembershipDetails['id'];
            $court_id = $this->request->getVar('court_id');
            $date = $this->request->getVar('date');
            $formattedDate = date('Y-m-d', strtotime(str_replace('/', '-', $date)));

            // Query to check if the booking already exists
            $existingBooking = $model->where('membership_id', $membership_id)
                                     ->where('date', $formattedDate)
                                     ->where('court_id', $court_id)
                                     ->first();

            if ($existingBooking) {
                // If a booking already exists, show an error message
                $model = new CourtModel();
                $getCourtData = $model->findAll();
                $data['court_list'] = $getCourtData;

                $data['page'] = 'addnewbooking';
                $data['title'] = 'Add New Booking';
                $data['error_message'] = 'This membership ID already has a booking on the selected date.';

                echo view('admin/common/header', $data);
                echo view('admin/common/sidebar');
                echo view('admin/club-owner/add-new-booking');
                echo view('admin/common/footer');
            } else {
                // No existing booking found, proceed with saving the new booking
                $duration = $this->request->getVar('duration');
                $start_time = $this->request->getVar('time');
                $startTime = strtotime($start_time);
                $endTime = $startTime + ($duration * 60 * 60);
                $end_time = date('h:i A', $endTime);
                $booking_key = "HA".rand(100,1000000);

                $data = [
                    'membership_id' => $membership_id,
                    'court_id' => $court_id,
                    'name' => $this->request->getVar('name'),
                    'phone' => $this->request->getVar('phone'),
                    'email' => $this->request->getVar('email'),
                    'date' => $formattedDate,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'duration' => $duration,
                    'cost' => $this->request->getVar('cost'),
                    'card_number' => $this->request->getVar('card_number'),
                    'payment_type' => $this->request->getVar('payment_type'),
                    'payment_status' => $this->request->getVar('payment_status'),
                    'no_change' => $this->request->getVar('no_change'),
                    'booking_type' => $this->request->getVar('booking_type'),
                    'booking_key' => $booking_key,
                    'addeddate' => date('Y-m-d h:i:s'),
                ];
                
                // Save the new booking
                $model->save($data);
                $lastInsertedId = $model->getInsertID();

                if($lastInsertedId){
                    $paymentModel = new PaymentModel();
                    $paymentData = [
                        'booking_id' => $lastInsertedId,
                        'court_id' => $court_id,
                        'amount' => $this->request->getVar('cost'),
                        'payment_status' => $this->request->getVar('payment_status'),
                        'payment_date' => date('Y-m-d h:i:s'),
                        'payment_type' => $this->request->getVar('payment_type'),
                        'type' => 'Court',
                        'booking_key' => $booking_key,
                    ];
                    $paymentModel->save($paymentData);
                }

                // Redirect to the booking list page
                return redirect()->to('/club-owner/list-booking');
            }
        } else {
            // Validation failed, render the form again
            $model = new CourtModel();
            $getCourtData = $model->findAll();
            $data['court_list'] = $getCourtData;

            $data['validation'] = $this->validator;
            $data['page'] = 'addnewbooking';
            $data['title'] = 'Add New Booking';

            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-booking');
            echo view('admin/common/footer');
        }
    }

    public function updateBooking(){
        // Validation rules for the booking form
        $rules = [
            'membership_id'  => 'required',
            'court_id'       => 'required',
            'name'           => 'required',
            'phone'          => 'required',
            'email'          => 'required',
            'date'           => 'required',
            'time'           => 'required',
            'cost'           => 'required',
            'payment_type'   => 'required',
            'payment_status' => 'required',
            'no_change'      => 'required'
        ];

        // Validate the input fields
        if ($this->validate($rules)) {
            $model = new BookingModel();
            $modelMembership = new MembershipModel();
            $getMembershipDetails = $modelMembership->where('unique_id', $this->request->getVar('membership_id'))->first();

            // Check if the same membership_id already has a booking on the same date
            $membership_id = $getMembershipDetails['id'];
            $court_id = $this->request->getVar('court_id');
            $date = $this->request->getVar('date');
            $formattedDate = date('Y-m-d', strtotime(str_replace('/', '-', $date)));

            
            // No existing booking found, proceed with saving the new booking
            $booking_id = $this->request->getVar('booking_id');
            $duration = $this->request->getVar('duration');
            $start_time = $this->request->getVar('time');
            $startTime = strtotime($start_time);
            $endTime = $startTime + ($duration * 60 * 60);
            $end_time = date('h:i A', $endTime);

            $data = [
                'membership_id' => $membership_id,
                'court_id' => $court_id,
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'email' => $this->request->getVar('email'),
                'date' => $formattedDate,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration' => $duration,
                'cost' => $this->request->getVar('cost'),
                'card_number' => $this->request->getVar('card_number'),
                'payment_type' => $this->request->getVar('payment_type'),
                'payment_status' => $this->request->getVar('payment_status'),
                'no_change' => $this->request->getVar('no_change')
            ];
            
            // Save the new booking
            $model->update($booking_id, $data);

            $paymentModel = new PaymentModel();
            $paymentData = [
                'court_id' => $court_id,
                'amount' => $this->request->getVar('cost'),
                'payment_status' => $this->request->getVar('payment_status'),
                'payment_date' => date('Y-m-d h:i:s'),
                'payment_type' => $this->request->getVar('payment_type'),
                'type' => 'Court',
            ];
            $paymentModel->update($booking_id, $paymentData);

            // Redirect to the booking list page
            return redirect()->to('/club-owner/list-booking');
            
        } else {
            // Validation failed, render the form again
            $model = new CourtModel();
            $getCourtData = $model->findAll();
            $data['court_list'] = $getCourtData;

            $data['validation'] = $this->validator;
            $data['page'] = 'addnewbooking';
            $data['title'] = 'Add New Booking';

            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-booking');
            echo view('admin/common/footer');
        }
    }

    public function makePreBooking(){
        // Validation rules for the booking form
        $rules = [
            'membership_id'  => 'required',
            'court_id'       => 'required',
            'name'           => 'required',
            'phone'          => 'required',
            'email'          => 'required',
            'date'           => 'required',
            // 'time'           => 'required',
            // 'cost'           => 'required',
            'deposit'           => 'required',
            'payment_type'   => 'required',
            'payment_status' => 'required',
            'no_change'      => 'required'
        ];

        // Validate the input fields
        if ($this->validate($rules)) {
            /*echo "<pre>";
            print_r($_POST);
            echo "</pre>";exit;*/
            $model = new BookingModel();
            $modelMembership = new MembershipModel();
            $getMembershipDetails = $modelMembership->where('unique_id', $this->request->getVar('membership_id'))->first();

            // Get the membership ID
            $membership_id = $getMembershipDetails['id'];

            $booking_key = "HA" . rand(100, 1000000);
            $booking_days = $this->request->getVar('booking_day');
            $months = $this->request->getVar('pre_booking_month');
            $startTime = $this->request->getVar('start_time'); 
            $endTime = $this->request->getVar('end_time'); 
            $courtId = $this->request->getVar('court_id');
            $deposit = $this->request->getVar('deposit'); 
            $payment_status = $this->request->getVar('payment_status');
            $payment_type = $this->request->getVar('payment_type');
            $name = $this->request->getVar('name');
            $phone = $this->request->getVar('phone');
            $email = $this->request->getVar('email');
            $card_number = $this->request->getVar('card_number');
            $currentDate = date('Y-m-d');

            // Prepare an array to collect all the booking data
            $bookingData = [];
            $booking_key_pre = '';
            foreach ($booking_days as $index => $day) {
                $booking_key_pre = "HA" . rand(100, 1000000);
                $dates = $this->getAllOccurrencesOfDay($this->request->getVar('pre_booking_month')[$index], $day, $currentDate);

                foreach ($dates as $date) {
                    $formattedDate = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                    $noChangeValue = $this->request->getVar('no_change')[$index] ?? 'No';
                    $paymentStatusValue = $this->request->getVar('payment_status')[$index] ?? 'Unpaid';
                    if (!empty($date) && !empty($startTime[$index]) && !empty($endTime[$index]) && !empty($deposit[$index])) {
                        $bookingData[] = [
                            'membership_id' => $membership_id,
                            'court_id' => $courtId[$index],
                            'name' => $name,
                            'phone' => $phone,
                            'email' => $email,
                            'day' => $day,
                            'date' => $formattedDate,
                            'start_time' => $startTime[$index],
                            'end_time' => $endTime[$index],
                            'cost' => '0.00',
                            'deposit' => $deposit[$index],
                            'card_number' => $card_number,
                            'payment_type' => $payment_type,
                            'payment_status' => $paymentStatusValue,
                            'no_change' => $noChangeValue,
                            'booking_type' => 'pre_booking',
                            'booking_key' => $booking_key_pre,
                            'addeddate' => date('Y-m-d h:i:s'),
                        ];
                    }

                    $paymentModel = new PaymentModel();
                    $paymentData = [
                        // 'booking_id' => $lastInsertedId,
                        'court_id' => $courtId[$index],
                        'amount' => $deposit[$index],
                        'payment_status' =>$paymentStatusValue,
                        'payment_date' => date('Y-m-d h:i:s'),
                        'payment_type' => $payment_type,
                        'type' => 'Court',
                        'booking_key' => $booking_key_pre,
                    ];
                    $paymentModel->save($paymentData);
                }
                
            }
            /*echo "<pre>";
            print_r($bookingData);
            echo "</pre>";exit;*/
            // Save the new bookings
            $model->insertBatch($bookingData);
            

            // Redirect to the booking list page
            return redirect()->to('/club-owner/list-booking');
        } else {
            // Validation failed, render the form again
            $model = new CourtModel();
            $getCourtData = $model->findAll();
            $data['court_list'] = $getCourtData;

            $data['validation'] = $this->validator;
            $data['page'] = 'prebooking';
            $data['title'] = 'Pre Booking';

            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/pre-booking');
            echo view('admin/common/footer');
        }
    }

    public function modifyPreBooking(){
        /*echo "<pre>";
        print_r($_POST);
        echo "</pre>";exit;*/
        $model = new BookingModel();
        $modelMembership = new MembershipModel();
        $getMembershipDetails = $modelMembership->where('unique_id', $this->request->getVar('membership_id'))->first();

        // Get the membership ID
        $membership_id = $getMembershipDetails['id'];

        $booking_key = $this->request->getVar('booking_key');

        $booking_days = $this->request->getVar('booking_day_new');
        $months = $this->request->getVar('pre_booking_month_new');
        $startTime = $this->request->getVar('start_time_new'); 
        $endTime = $this->request->getVar('end_time_new'); 
        $courtId = $this->request->getVar('court_id_new');
        $deposit = $this->request->getVar('deposit_new'); 
        $payment_status = $this->request->getVar('payment_status_new');
        $payment_type = $this->request->getVar('payment_type');
        $name = $this->request->getVar('name');
        $phone = $this->request->getVar('phone');
        $email = $this->request->getVar('email');
        $card_number = $this->request->getVar('card_number');
        $currentDate = date('Y-m-d');

        // Prepare an array to collect all the booking data
        $bookingData = [];
        if(!empty($booking_days)){
            foreach ($booking_days as $index => $day) {
                $dates = $this->getAllOccurrencesOfDay($this->request->getVar('pre_booking_month_new')[$index], $day, $currentDate);

                foreach ($dates as $date) {
                    $formattedDate = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                    $noChangeValue = $this->request->getVar('no_change_new')[$index] ?? 'No';
                    $paymentStatusValue = $this->request->getVar('payment_status_new')[$index] ?? 'Unpaid';
                    if (!empty($date) && !empty($startTime[$index]) && !empty($endTime[$index]) && !empty($deposit[$index])) {
                        $bookingData[] = [
                            'membership_id' => $membership_id,
                            'court_id' => $courtId[$index],
                            'name' => $name,
                            'phone' => $phone,
                            'email' => $email,
                            'day' => $day,
                            'date' => $formattedDate,
                            'start_time' => $startTime[$index],
                            'end_time' => $endTime[$index],
                            'cost' => '0.00',
                            'deposit' => $deposit[$index],
                            'card_number' => $card_number,
                            'payment_type' => $payment_type,
                            'payment_status' => $paymentStatusValue,
                            'no_change' => $noChangeValue,
                            'booking_type' => 'pre_booking',
                            'booking_key' => $booking_key,
                            'addeddate' => date('Y-m-d h:i:s'),
                        ];
                    }

                    $paymentModel = new PaymentModel();
                    $paymentData = [
                        // 'booking_id' => $lastInsertedId,
                        'court_id' => $courtId[$index],
                        'amount' => $deposit[$index],
                        'payment_status' =>$paymentStatusValue,
                        'payment_date' => date('Y-m-d h:i:s'),
                        'payment_type' => $payment_type,
                        'type' => 'Court',
                        'booking_key' => $booking_key,
                    ];
                    $paymentModel->save($paymentData);
                }
            }
            /*echo "<pre>";
            print_r($bookingData);
            echo "</pre>";exit;*/
            // Save the new bookings
            $model->insertBatch($bookingData);
        }
        

        if(!empty($this->request->getVar('canceled_bookings'))){
            $cancelledBookings = $this->request->getVar('canceled_bookings');
            foreach($cancelledBookings as $bookingId) {
                $model->update($bookingId, [
                    'booking_status' => 'Cancelled'
                ]);
            }
        }

        // Redirect to the booking list page
        return redirect()->to('/club-owner/list-pre-booking');
    }

    function getAllOccurrencesOfDay($month, $day, $startDate) {
        $days = [];
        $year = date('Y'); // Current year

        // Get the first day of the month
        $firstDayOfMonth = strtotime("first day of $month $year");

        // Get the first occurrence of the selected day in the month
        $firstOccurrence = strtotime("first $day of $month $year");

        // If the first occurrence is before the current date, skip to the next occurrence of the day
        if ($firstOccurrence < strtotime($startDate)) {
            $firstOccurrence = strtotime("next $day", strtotime($startDate));
        }

        // Generate all occurrences of the selected day in the selected month
        $currentDay = $firstOccurrence;
        while (date('m', $currentDay) == date('m', $firstDayOfMonth)) {
            $days[] = date('d/m/Y', $currentDay);
            $currentDay = strtotime('+1 week', $currentDay); // Move to the next occurrence of the selected day
        }

        return $days;
    }

    public function saveCourt(){
        $club_id = session()->get('user_id');
        $rules = [
            'name'          => 'required|is_unique[hy_court.name]',
            // 'cost'          => 'required'
        ];
         
        if($this->validate($rules)){
            $model = new CourtModel();
            $data = [
                'club_id'    => $club_id,
                'name'    => $this->request->getVar('name'),
                // 'cost' => $this->request->getVar('cost'),
                'addeddate' => date('Y-m-d h:i:s')
            ];
            $model->save($data);
            return redirect()->to('/club-owner/list-court');
        }else{
            $data['validation'] = $this->validator;
            $data['page'] = 'addnewcourt';
            $data['title'] = 'Add New Court';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-court');
            echo view('admin/common/footer');
        }
    }

    public function saveInventory(){
        $club_id = session()->get('user_id');
        $rules = [
            'name'          => 'required|is_unique[hy_inventory.name]',
            'price'          => 'required'
        ];
         
        if($this->validate($rules)){
            $model = new InventoryModel();
            $data = [
                'club_id'    => $club_id,
                'name'    => $this->request->getVar('name'),
                'sku'    => $this->request->getVar('sku'),
                'category'    => $this->request->getVar('category'),
                'brand'    => $this->request->getVar('brand'),
                'size'    => $this->request->getVar('size'),
                'color'    => $this->request->getVar('color'),
                'quantity'    => $this->request->getVar('quantity'),
                'price' => $this->request->getVar('price'),
                'addeddate' => date('Y-m-d h:i:s'),
                'status' => '1'
            ];
            $model->save($data);
            return redirect()->to('/club-owner/list-inventory');
        }else{
            $data['validation'] = $this->validator;
            $data['page'] = 'addnewinventory';
            $data['title'] = 'Add New Inventory';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-inventory');
            echo view('admin/common/footer');
        }
    }

    public function get_user_details()
    {

        
        // Get the membership ID from the request
        $membership_id = $this->request->getVar('membership_id');
        $membership_type = $this->request->getVar('type');
        $model = new MembershipModel();
        // Fetch user details from the database
        if($membership_type == "membershipID"){
            $user = $model->getUserDetailsByMembershipId($membership_id);
        }else if($membership_type == "membershipPhone") {
            $user = $model->getUserDetailsByMembershipPhone($membership_id);
        }else{
            $user = false;
        }
        

        if ($user) {
            // Return the user data as a JSON response
            return $this->response->setJSON([
                'name' => $user['name'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'card_number' => $user['card_number'],
                'unique_id' => $user['unique_id']
            ]);
        } else {
            // If no user found, return an empty response
            return $this->response->setJSON(null);
        }
    }

    public function get_time_intervals($open_time = "", $close_time = "") {
        // Example times (could be from the database)
        $open_time = date('H:i', strtotime($open_time)); // Opening time (HH:mm)
        $close_time = date('H:i', strtotime($close_time)); // Closing time (HH:mm)

        // Create DateTime objects
        $open_time_obj = new \DateTime($open_time); // Ensure the global DateTime class is referenced
        $close_time_obj = new \DateTime($close_time); // Ensure the global DateTime class is referenced
        
        // Define the interval (30 minutes in this case)
        $interval = new \DateInterval('PT30M'); // PT30M = 30 minutes

        // Initialize an array to hold time slots
        $time_slots = [];

        // Loop from open time to close time in 30-minute intervals
        while ($open_time_obj <= $close_time_obj) {
            // Add formatted time to array
            $time_slots[] = $open_time_obj->format('h:i A');

            // Add interval
            $open_time_obj->add($interval);
        }

        return $time_slots;
    }

    function getBookingHistory(){
        $membershipModel = new MembershipModel();
        $bookingModel = new BookingModel();
        $courtModel = new CourtModel();
        $entryModel = new EntryModel();
        $booking_key = $this->request->getVar('booking_key');
        $data = $bookingModel->where('booking_key', $booking_key)->first();
        if(!empty($data)){
            $cost = $data['cost'];
            $deposit = $data['deposit'];
            $data['date'] = date('jS F, Y', strtotime($data['date']));
            $data['membership_id'] = $membershipModel->where('id', $data['membership_id'])->first()['unique_id'];
            $data['court_no'] = $courtModel->where('id', $data['court_id'])->first()['name'];
            $data['cost'] = CURRENCY.number_format($cost, 2, '.', '');
            $data['cost_value'] = $cost;
            $data['deposit'] = CURRENCY.number_format($deposit, 2, '.', '');
            $data['deposit_value'] = $deposit;
            $data['entries'] = $entryModel->where('booking_key', $booking_key)->findAll();
        }
        echo json_encode($data);exit;
    }
    function checkCourtAvailibity() {
        $bookingModel = new BookingModel();
        $courtModel = new CourtModel();

        $date = $this->request->getVar('date');
        // Convert date from dd/mm/yyyy to yyyy-mm-dd
        $dateFormat = explode('/', $date);
        $date = $dateFormat[2] . '-' . $dateFormat[1] . '-' . $dateFormat[0];

        $start_time = $this->request->getVar('start_time');
        $end_time = $this->request->getVar('end_time');
        
        // Convert times to Unix timestamps for comparison
        $postedStartTime = strtotime($start_time);
        $postedEndTime = strtotime($end_time);

        // Retrieve all bookings for the selected date
        $bookedCourts = $bookingModel->where('date', $date)->findAll();

        // Filter booked courts based on the posted time range
        $bookedCourtIds = array_filter($bookedCourts, function($court) use ($postedStartTime, $postedEndTime) {
            // Convert the booked court's start and end times to Unix timestamps
            $bookedStartTime = strtotime($court['start_time']);
            $bookedEndTime = strtotime($court['end_time']);
            
            // Check if the posted time range overlaps with any booked time range
            return (
                ($postedStartTime < $bookedEndTime && $postedEndTime > $bookedStartTime)
            );
        });

        // Get the IDs of the booked courts
        $bookedCourtIds = array_column($bookedCourtIds, 'court_id');
        
        // Check if the requested court is already booked
        if (in_array($this->request->getVar('court_id'), $bookedCourtIds)) {
            // Court is already booked for the requested time
            echo json_encode(['status' => 'exists']);
        } else {
            // Court is available for the requested time
            echo json_encode(['status' => 'available']);
        }

        exit;
    }

    public function addEntry(){
        $bookingModel = new BookingModel();
        $paymentModel = new PaymentModel();
        $entryModel = new EntryModel();

        $booking_key = $this->request->getVar('booking_key_val');
        $payment_type = $this->request->getVar('payment_type');
        $entry_type_name = $this->request->getVar('entry_type_name');
        $entry_type_cost = $this->request->getVar('entry_type_cost');
        $entryType = $this->request->getVar('entryType');
        $entry = $this->request->getVar('entry');
        if(isset($entryType) && $entryType == 'save'){
            if(!empty($entry)){
                $entries = $entryModel->where('booking_key', $booking_key)->findAll();
                if(!empty($entries)){
                    $deleteEntry = $entryModel->where('booking_key', $booking_key)->delete();
                }

                foreach($entry as $key => $value){
                    $data = [
                        'booking_key'     => $booking_key,
                        'entry_id'     => '', //$value['id'],
                        'entry_name'    => $value['name'],
                        'entry_qnty' => $value['quantity'],
                        'entry_cost' => $value['cost'],
                        'addeddate' => date('Y-m-d h:i:s')
                    ];
                    $entryModel->save($data);
                }
            }
        }
        else{
            $data = $paymentModel->where('booking_key', $booking_key)->first();
            if(!empty($data) && $data['payment_status'] == 'Unpaid'){
                $paymentData = [
                    'payment_status' => 'Paid',
                    'payment_date' => date('Y-m-d h:i:s'),
                    'payment_type' => $payment_type,
                    'type' => 'Court',
                ];
                $paymentModel
                ->where('booking_key', $booking_key)
                ->set($paymentData)
                ->update();

                $bookingData = [
                    'payment_status' => 'Paid',
                    'payment_type' => $payment_type
                ];
                $bookingModel
                ->where('booking_key', $booking_key)
                ->set($bookingData)
                ->update();
            }

            if(!empty($entry)){
                $entries = $entryModel->where('booking_key', $booking_key)->findAll();
                if(!empty($entries)){
                    $deleteEntry = $entryModel->where('booking_key', $booking_key)->delete();
                }

                foreach($entry as $key => $value){
                    $entryData = [
                        'booking_key'     => $booking_key,
                        'entry_id'     => '', //$value['id'],
                        'entry_name'    => $value['name'],
                        'entry_qnty' => $value['quantity'],
                        'entry_cost' => $value['cost'],
                        'addeddate' => date('Y-m-d h:i:s')
                    ];
                    $entryModel->save($entryData);

                    $paymentData = [
                        'court_id' => $data['court_id'],
                        'amount' => $value['cost'],
                        'payment_status' => 'Paid',
                        'payment_date' => date('Y-m-d h:i:s'),
                        'payment_type' => $payment_type,
                        'type' => $value['name'],
                        'booking_key' => $booking_key,
                    ];
                    
                    // Save the new booking
                    $paymentModel->save($paymentData);
                }
            }
        }
        return redirect()->to('/club-owner/dashboard'); 

    }

    function changeBookingStatus(){
        $bookingModel = new BookingModel();
        $status = $this->request->getVar('status');
        $booking_key = $this->request->getVar('booking_key');
        $bookingData = [
            'booking_status' => $status
        ];
        $updateStatus = $bookingModel
        ->where('booking_key', $booking_key)
        ->set($bookingData)
        ->update();

        if($updateStatus){
            echo json_encode(array('success'=>1));
        }
    }

    public function editInventory($inv_id = "")
    {

        $posSizemodel = new PosSizeModel();
        $posCategorymodel = new PosCategoryModel();
        $posBrandmodel = new PosBrandModel();
        $dataP['categorylist'] = $posCategorymodel->findAll();
        $dataP['brandlist'] = $posBrandmodel->findAll();
        $dataP['sizelist'] = $posSizemodel->findAll();


        $inventoryModel = new InventoryModel();

        $builder = $inventoryModel->builder();
        $builder->select('*');
        $builder->where('id', $inv_id);

        // Fetch the results
        $inventory_details = $builder->get()->getRowArray();



        
        $data['inventory_details'] = $inventory_details;
        $data['inventory_id'] = $inv_id;
        $data['page'] = 'editinventory';
        $data['title'] = 'Edit Inventory';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/edit-inventory', $dataP);
        echo view('admin/common/footer');
    }

    public function updateInventory(){
        $inventory_id = $this->request->getVar('inventory_id');
        $rules = [
            'name'          => 'required',
            'price'          => 'required'
        ];
         
        if($this->validate($rules)){
            $model = new InventoryModel();
            $data = [
                'name'    => $this->request->getVar('name'),
                'sku'    => $this->request->getVar('sku'),
                'category'    => $this->request->getVar('category'),
                'brand'    => $this->request->getVar('brand'),
                'size'    => $this->request->getVar('size'),
                'color'    => $this->request->getVar('color'),
                'quantity'    => $this->request->getVar('quantity'),
                'price' => $this->request->getVar('price')
            ];
            $model->update($inventory_id, $data);
            return redirect()->to('/club-owner/list-inventory');
        }else{
            $inventoryModel = new InventoryModel();

            $builder = $inventoryModel->builder();
            $builder->select('*');
            $builder->where('id', $inv_id);

            // Fetch the results
            $inventory_details = $builder->get()->getrowArray();
            
            $data['inventory_details'] = $inventory_details;
            $data['validation'] = $this->validator;
            $data['page'] = 'editinventory';
            $data['title'] = 'Edit Inventory';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/edit-inventory');
            echo view('admin/common/footer');
        }
    }

    public function editMembership($membership_id = "")
    {
        $membershipModel = new MembershipModel();

        $builder = $membershipModel->builder();
        $builder->select('*');
        $builder->where('id', $membership_id);

        // Fetch the results
        $membership_details = $builder->get()->getrowArray();
        if(!empty($membership_details)){
            $membership_details['start_date'] = date('d/m/Y', strtotime($membership_details['start_date']));
            $membership_details['end_date'] = date('d/m/Y', strtotime($membership_details['end_date']));
        }
        
        $data['membership_details'] = $membership_details;
        $data['membership_id'] = $membership_id;
        $data['page'] = 'editmembership';
        $data['title'] = 'Edit Membership';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/edit-membership');
        echo view('admin/common/footer');
    }

    public function updateMembership(){
        $membershipModel = new MembershipModel();
        $membership_id = $this->request->getVar('membership_id');
        $rules = [
            'player_name'          => 'required',
            'phone'          => 'required',
            'email'         => 'required',
            'start_date'      => 'required',
            'end_date'      => 'required',
            'amount'      => 'required',
            'discount'      => 'required'
        ];
         
        if($this->validate($rules)){
            $start_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('start_date'))));
            $end_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('end_date'))));
            $data = [
                'name'    => $this->request->getVar('player_name'),
                'phone' => $this->request->getVar('phone'),
                'email' => $this->request->getVar('email'),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'amount' => $this->request->getVar('amount'),
                'discount' => $this->request->getVar('discount'),
                'card_number' => $this->request->getVar('card_number'),
                'status' => $this->request->getVar('status')
            ];
            $membershipModel->update($membership_id, $data);
            return redirect()->to('/club-owner/list-membership');
        }else{
            $membershipModel = new MembershipModel();

            $builder = $membershipModel->builder();
            $builder->select('*');
            $builder->where('id', $membership_id);

            // Fetch the results
            $membership_details = $builder->get()->getrowArray();
            
            $data['membership_details'] = $membership_details;
            $data['membership_id'] = $membership_id;
            $data['page'] = 'editmembership';
            $data['title'] = 'Edit Membership';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/edit-membership');
            echo view('admin/common/footer');
        }
    }

    public function addNewStringing()
    {
        $club_id = session()->get('user_id');

        $inventoryModel = new InventoryModel();
        $dataP['inventory_list'] = $inventoryModel->select('name, id')->where('club_id', $club_id)->findAll();

        $data['page'] = 'addnewstringing';
        $data['title'] = 'Add New Stringing';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/add-new-stringing', $dataP);
        echo view('admin/common/footer');
    }

    public function saveStringing(){

        $rules = [
            'which_string'          => 'required',
            'color'          => 'required',
            'tension'         => 'required',
            'service_date'      => 'required',
            'delivery_date'      => 'required',
            'labour_charge'      => 'required'
        ];
         
        if($this->validate($rules)){
            $club_id = session()->get('user_id');
            $model = new StringModel();
            $lastId = $model->select('service_no')->orderBy('id', 'desc')->first();
            if ($lastId) {
                $lastNumber = (int) substr($lastId['service_no'], 3); // Assuming 'HAS' prefix
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $service_no = 'HAS' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            $service_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('service_date'))));
            $delivery_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('delivery_date'))));
            $data = [
                'club_id'     => $club_id,
                'service_no'     => $service_no,
                'which_string'    => $this->request->getVar('which_string'),
                'color' => $this->request->getVar('color'),
                'tension' => $this->request->getVar('tension'),                
                'service_date' => $service_date,
                'delivery_date' => $delivery_date,
                'string_cost' => $this->request->getVar('string_cost'),
                'string_discount' => $this->request->getVar('string_discount'),
                'labour_charge' => $this->request->getVar('labour_charge'),
                'total_cost' => $this->request->getVar('total_cost'),
                'membership_id' => $this->request->getVar('membership_id'),
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'email' => $this->request->getVar('email'),
                'card_number' => $this->request->getVar('card_number'),
                'exp_date' => $this->request->getVar('exp_date'),
                'cvv' => $this->request->getVar('cvv'),
                'payment_type' => $this->request->getVar('payment_type'),
                'payment_status' => $this->request->getVar('payment_status'),
                'status' => $this->request->getVar('status'),
                'addeddate' => date('Y-m-d h:i:s')
            ];
            $model->save($data);
            $lastInsertedId = $model->getInsertID();

            if($lastInsertedId){
                $paymentModel = new PaymentModel();
                $paymentData = [
                    'amount' => $this->request->getVar('total_cost'),
                    'payment_status' => $this->request->getVar('payment_status'),
                    'payment_date' => date('Y-m-d h:i:s'),
                    'payment_type' => $this->request->getVar('payment_type'),
                    'type' => 'String',
                    'booking_key' => $service_no,
                ];
                $paymentModel->save($paymentData);
            }
            return redirect()->to('/club-owner/list-stringing');
        }else{
            $data['validation'] = $this->validator;
            $data['page'] = 'addnewstringing';
            $data['title'] = 'Add New Stringing';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/add-new-stringing');
            echo view('admin/common/footer');
        }
    }

    public function listStringing()
    {
        $model = new StringModel();
        $club_id = session()->get('user_id');
        // $getStringDetails = $model->getStringingByClub($club_id);
        $db = db_connect();
        $builder = $db->table('hy_stringing');
        $builder->select('hy_stringing.*, hy_inventory.name as inventory_name');
        $builder->join('hy_inventory', 'hy_inventory.id = hy_stringing.which_string', 'left');
        $builder->where('hy_stringing.club_id', $club_id);
        $query = $builder->get();
        $getStringDetails = $query->getResultArray();

        // echo "<pre>";
        // print_r($getStringDetails);
        // echo "</pre>";
        // exit();
        $data['string_list'] = $getStringDetails;
        $data['page'] = 'liststringing';
        $data['title'] = 'List Stringing';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/list-stringing');
        echo view('admin/common/footer');
    }

    public function editStringing($string_id = "")
    {
        $stringModel = new StringModel();

        $club_id = session()->get('user_id');

        $inventoryModel = new InventoryModel();
        $data['inventory_list'] = $inventoryModel->select('name, id')->where('club_id', $club_id)->findAll();

        // print_r($dataP);
        // exit();



        $builder = $stringModel->builder();
        $builder->select('*');
        $builder->where('id', $string_id);

        // Fetch the results
        $string_details = $builder->get()->getrowArray();
        if(!empty($string_details)){
            $string_details['service_date'] = date('d/m/Y', strtotime($string_details['service_date']));
            $string_details['delivery_date'] = date('d/m/Y', strtotime($string_details['delivery_date']));
        }
        $data['string_details'] = $string_details;
        $data['string_id'] = $string_id;
        $data['page'] = 'editstringing';
        $data['title'] = 'Edit Stringing';
        echo view('admin/common/header', $data);
        echo view('admin/common/sidebar');
        echo view('admin/club-owner/edit-stringing');
        echo view('admin/common/footer');
    }

    public function updateStringing(){
        $string_id = $this->request->getVar('string_id');
        $service_no = $this->request->getVar('service_no');
        $stringModel = new StringModel();
        $rules = [
            'which_string'          => 'required',
            'color'          => 'required',
            'tension'         => 'required',
            'service_date'      => 'required',
            'delivery_date'      => 'required',
            'labour_charge'      => 'required'
        ];
         
        if($this->validate($rules)){
            
            $service_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('service_date'))));
            $delivery_date = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('delivery_date'))));
            // $data = [
            //     'which_string'    => $this->request->getVar('which_string'),
            //     'color' => $this->request->getVar('color'),
            //     'tension' => $this->request->getVar('tension'),
            //     'labour_charge' => $this->request->getVar('labour_charge'),
            //     'service_date' => $service_date,
            //     'delivery_date' => $delivery_date,
            //     'card_number' => $this->request->getVar('card_number'),
            //     'payment_type' => $this->request->getVar('payment_type'),
            //     'payment_status' => $this->request->getVar('payment_status'),
            //     'addeddate' => date('Y-m-d h:i:s')
            // ];
            $data = [
                'which_string'    => $this->request->getVar('which_string'),
                'color' => $this->request->getVar('color'),
                'tension' => $this->request->getVar('tension'),                
                'service_date' => $service_date,
                'delivery_date' => $delivery_date,
                'string_cost' => $this->request->getVar('string_cost'),
                'string_discount' => $this->request->getVar('string_discount'),
                'labour_charge' => $this->request->getVar('labour_charge'),
                'total_cost' => $this->request->getVar('total_cost'),
                'membership_id' => $this->request->getVar('membership_id'),
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'email' => $this->request->getVar('email'),
                'card_number' => $this->request->getVar('card_number'),
                'exp_date' => $this->request->getVar('exp_date'),
                'cvv' => $this->request->getVar('cvv'),
                'payment_type' => $this->request->getVar('payment_type'),
                'payment_status' => $this->request->getVar('payment_status'),
                'status' => $this->request->getVar('status')
            ];
            $update = $stringModel->update($string_id, $data);

            if($update){
                $paymentModel = new PaymentModel();
                $paymentData = [
                    'amount' => $this->request->getVar('total_cost'),
                    'payment_status' => $this->request->getVar('payment_status'),
                    'payment_date' => date('Y-m-d h:i:s'),
                    'payment_type' => $this->request->getVar('payment_type'),
                    'type' => 'String',
                ];
                $paymentModel
                ->where('booking_key', $service_no)
                ->set($paymentData)
                ->update();
            }
            return redirect()->to('/club-owner/list-stringing');
        }else{
            $club_id = session()->get('user_id');

            $inventoryModel = new InventoryModel();
            $data['inventory_list'] = $inventoryModel->select('name, id')->where('club_id', $club_id)->findAll();
            
            $stringModel = new StringModel();

            $builder = $stringModel->builder();
            $builder->select('*');
            $builder->where('id', $string_id);

            // Fetch the results
            $string_details = $builder->get()->getrowArray();
            
            $data['string_details'] = $string_details;
            $data['string_id'] = $string_id;
            $data['page'] = 'editstringing';
            $data['title'] = 'Edit Stringing';
            echo view('admin/common/header', $data);
            echo view('admin/common/sidebar');
            echo view('admin/club-owner/edit-stringing');
            echo view('admin/common/footer');
        }
    }
}
