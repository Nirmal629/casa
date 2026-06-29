<?php
session_start();
$_SESSION['user_id'] = 1; // Assuming
$_SESSION['gender'] = 'Male';
$_SESSION['level'] = 'Amateur';

$_POST['type'] = 'filter';
$_POST['year'] = '2026';
$_POST['month'] = '6';
$_POST['host'] = '21';
$_POST['event_category'] = '';

include('play_filter_schedule.php');
