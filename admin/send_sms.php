<?php
require_once('../includes/sms_sender.php');

// Handle both direct hits and AJAX from dashboard
$mobile = $_POST['donor_phone'] ?? $_POST['mobile'] ?? $_GET['phone'] ?? "918105382948";
$message = $_POST['individual_msg'] ?? $_POST['message'] ?? $_GET['msg'] ?? "Test SMS from Blood Bank";

$result = sendSMS($mobile, $message);

// Return JSON for the dashboard's fetch()
header('Content-Type: application/json');
echo json_encode($result);
?>