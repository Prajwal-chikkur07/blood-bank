<?php
/**
 * Script to trigger the WhatsApp donor notification loop
 */
$run_donor_notifications = true; // This triggers the loop in sms_config.php

echo "--- Blood Bank WhatsApp Notification Runner ---\n";

// Include configuration (this will trigger the loop thanks to the flag above)
// Using absolute path for safety
require_once(__DIR__ . '/config/sms_config.php');

echo "\n--- Process Finished ---\n";
?>