<?php
define('SMS_TEST_MODE',        getenv('SMS_TEST_MODE') === 'false' ? false : true);
define('FAST2SMS_API_KEY',     getenv('FAST2SMS_API_KEY')     ?: '');
define('WHATSAPP_INSTANCE_ID', getenv('WHATSAPP_INSTANCE_ID') ?: '');
define('WHATSAPP_TOKEN',       getenv('WHATSAPP_TOKEN')       ?: '');
define('SMS_GATEWAY',          getenv('SMS_GATEWAY')          ?: 'whatsapp');
?>
