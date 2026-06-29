<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');
$routes->get('/login', 'Auth::index');
$routes->get('/logout', 'Auth::logout');
$routes->get('/club-owner/dashboard', 'Clubadmin::index',['filter' => 'auth']);
$routes->get('/club-owner/dashboard/(:any)/(:any)/(:any)', 'Clubadmin::index/$1/$2/$3',['filter' => 'auth']);
$routes->get('/club-owner/add-new-court', 'Clubadmin::addNewCourt',['filter' => 'auth']);
$routes->get('/club-owner/list-court', 'Clubadmin::listCourt',['filter' => 'auth']);


$routes->get('/club-owner/list-category', 'Clubadmin::listCategory',['filter' => 'auth']);
$routes->post('/club-owner/add-category', 'Clubadmin::addCategory',['filter' => 'auth']);
$routes->post('/club-owner/update-category', 'Clubadmin::updateCategory',['filter' => 'auth']);
$routes->get('/club-owner/get-category/(:num)', 'Clubadmin::getCategory/$1',['filter' => 'auth']);

$routes->get('/club-owner/list-brand', 'Clubadmin::listBrand',['filter' => 'auth']);
$routes->post('/club-owner/add-brand', 'Clubadmin::addBrand',['filter' => 'auth']);
$routes->post('/club-owner/update-brand', 'Clubadmin::updateBrand',['filter' => 'auth']);
$routes->get('/club-owner/get-brand/(:num)', 'Clubadmin::getBrand/$1',['filter' => 'auth']);

$routes->get('/club-owner/list-size', 'Clubadmin::listSize',['filter' => 'auth']);
$routes->post('/club-owner/add-size', 'Clubadmin::addSize',['filter' => 'auth']);
$routes->post('/club-owner/update-size', 'Clubadmin::updateSize',['filter' => 'auth']);
$routes->get('/club-owner/get-size/(:num)', 'Clubadmin::getSize/$1',['filter' => 'auth']);


$routes->get('/club-owner/add-new-booking', 'Clubadmin::addNewBooking',['filter' => 'auth']);
$routes->get('/club-owner/list-booking', 'Clubadmin::listBooking',['filter' => 'auth']);
$routes->get('/club-owner/list-pre-booking', 'Clubadmin::listPreBooking',['filter' => 'auth']);
$routes->get('/club-owner/pre-booking', 'Clubadmin::preBooking',['filter' => 'auth']);
$routes->get('/club-owner/edit-booking/(:any)', 'Clubadmin::editBooking/$1',['filter' => 'auth']);
$routes->get('/club-owner/edit-pre-booking/(:any)', 'Clubadmin::editPreBooking/$1',['filter' => 'auth']);
$routes->get('/club-owner/add-new-membership', 'Clubadmin::addNewMembership',['filter' => 'auth']);
$routes->get('/club-owner/list-membership', 'Clubadmin::listMembership',['filter' => 'auth']);
$routes->get('/club-owner/profile', 'Clubadmin::profile',['filter' => 'auth']);
$routes->post('/authentication', 'Auth::login');
$routes->get('/club-owner/addmembership', 'Clubadmin::saveMembership',['filter' => 'auth']);
$routes->post('/club-owner/addmembership', 'Clubadmin::saveMembership',['filter' => 'auth']);
$routes->post('/club-owner/membershipdetails', 'Clubadmin::getMembershipDetails',['filter' => 'auth']);
$routes->post('/club-owner/getAvailableCourt', 'Clubadmin::getAvailableCourt',['filter' => 'auth']);
$routes->post('/club-owner/checkCourtAvailibity', 'Clubadmin::checkCourtAvailibity',['filter' => 'auth']);
$routes->post('/club-owner/getCourtCost', 'Clubadmin::getCourtCost',['filter' => 'auth']);
$routes->post('/club-owner/getBookingHistory', 'Clubadmin::getBookingHistory',['filter' => 'auth']);
$routes->post('/club-owner/deleteitem', 'Clubadmin::deleteItem',['filter' => 'auth']);
$routes->get('/club-owner/newbooking', 'Clubadmin::makeBooking',['filter' => 'auth']);
$routes->post('/club-owner/newbooking', 'Clubadmin::makeBooking',['filter' => 'auth']);
$routes->get('/club-owner/addcourt', 'Clubadmin::saveCourt',['filter' => 'auth']);
$routes->post('/club-owner/addcourt', 'Clubadmin::saveCourt',['filter' => 'auth']);
$routes->get('/club-owner/get_user_details', 'Clubadmin::get_user_details',['filter' => 'auth']);
$routes->get('/club-owner/makeprebooking', 'Clubadmin::makePreBooking',['filter' => 'auth']);
$routes->post('/club-owner/makeprebooking', 'Clubadmin::makePreBooking',['filter' => 'auth']);
$routes->get('/club-owner/modifyprebooking', 'Clubadmin::modifyPreBooking',['filter' => 'auth']);
$routes->post('/club-owner/modifyprebooking', 'Clubadmin::modifyPreBooking',['filter' => 'auth']);
$routes->get('/club-owner/updatebooking', 'Clubadmin::updateBooking',['filter' => 'auth']);
$routes->post('/club-owner/updatebooking', 'Clubadmin::updateBooking',['filter' => 'auth']);
$routes->get('/club-owner/payment-history', 'Clubadmin::paymentHistory',['filter' => 'auth']);
$routes->get('/club-owner/add-new-inventory', 'Clubadmin::addNewInventory',['filter' => 'auth']);
$routes->get('/club-owner/list-inventory', 'Clubadmin::listInventory',['filter' => 'auth']);
$routes->get('/club-owner/addInventory', 'Clubadmin::saveInventory',['filter' => 'auth']);
$routes->post('/club-owner/addInventory', 'Clubadmin::saveInventory',['filter' => 'auth']);
$routes->get('/club-owner/addentry', 'Clubadmin::addEntry',['filter' => 'auth']);
$routes->post('/club-owner/addentry', 'Clubadmin::addEntry',['filter' => 'auth']);
$routes->post('/club-owner/changeBookingStatus', 'Clubadmin::changeBookingStatus',['filter' => 'auth']);
$routes->get('/club-owner/edit-inventory/(:any)', 'Clubadmin::editInventory/$1',['filter' => 'auth']);
$routes->get('/club-owner/editinventory', 'Clubadmin::updateInventory',['filter' => 'auth']);
$routes->post('/club-owner/editinventory', 'Clubadmin::updateInventory',['filter' => 'auth']);
$routes->get('/club-owner/edit-membership/(:any)', 'Clubadmin::editMembership/$1',['filter' => 'auth']);
$routes->get('/club-owner/editmembership', 'Clubadmin::updateMembership',['filter' => 'auth']);
$routes->post('/club-owner/editmembership', 'Clubadmin::updateMembership',['filter' => 'auth']);
$routes->get('/club-owner/add-new-stringing', 'Clubadmin::addNewStringing',['filter' => 'auth']);
$routes->get('/club-owner/edit-stringing/(:any)', 'Clubadmin::editStringing/$1',['filter' => 'auth']);
$routes->get('/club-owner/list-stringing', 'Clubadmin::listStringing',['filter' => 'auth']);
$routes->get('/club-owner/addstringing', 'Clubadmin::saveStringing',['filter' => 'auth']);
$routes->post('/club-owner/addstringing', 'Clubadmin::saveStringing',['filter' => 'auth']);
$routes->get('/club-owner/editstringing', 'Clubadmin::updateStringing',['filter' => 'auth']);
$routes->post('/club-owner/editstringing', 'Clubadmin::updateStringing',['filter' => 'auth']);


$routes->get('/club-owner/getInventoryDetails/(:num)', 'Clubadmin::getInventoryDetails/$1',['filter' => 'auth']);

