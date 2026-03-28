<?php
define('EMAIL_ENABLED',      getenv('EMAIL_ENABLED') !== false ? (bool)getenv('EMAIL_ENABLED') : true);
define('BREVO_API_KEY',      getenv('BREVO_API_KEY')      ?: '');
define('EMAIL_FROM_ADDRESS', getenv('EMAIL_FROM_ADDRESS') ?: 'noreply@bloodbank.com');
define('EMAIL_FROM_NAME',    getenv('EMAIL_FROM_NAME')    ?: 'Blood Bank');
?>
