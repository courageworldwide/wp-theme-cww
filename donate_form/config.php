<?php
// Authorize.net
define("AUTHORIZENET_API_LOGIN_ID", "yourauthnetloginid");
define("AUTHORIZENET_TRANSACTION_KEY", "yourauthnettransactionkey");
define("AUTHORIZENET_SANDBOX", TRUE); // Set to False for LIVE servers
// Highrise
define('HIGHRISE_ACCOUNT', 'yourhighriseaccount');
define('HIGHRISE_TOKEN', 'yourhighriseapitoken');

define("HR_GROUP_ID_ADMIN", ""); // Highrise admin group - can view deals and tasks
define("HR_USER_ID_ADMIN", ""); // Highrise admin user
define("HR_USER_ID_ADMIN_DEALS", ""); // Highrise user in charge of managing Deals
 
define("HR_DEFAULT_TASK_DELAY", "6 months"); // Time after transaction to set reminder
 
// The following are specific to the CourageWorldwide Highrise system
define("HR_DEAL_CAT_ID_GENERAL_MONTHLY", "");
define("HR_DEAL_CAT_ID_GENERAL_ONETIME", "");
define("HR_DEAL_CAT_ID_GENERAL_YEARLY", "");
define("HR_DEAL_CAT_ID_RESCUE_ONETIME", "");
define("HR_DEAL_CAT_ID_RESTORE_MONTHLY", "");
define("HR_DEAL_CAT_ID_RESTORE_ONETIME", "");
define("HR_DEAL_CAT_ID_RESTORE_YEARLY", "");
define("HR_DEAL_CAT_ID_BUSINESS_MONTHLY", "");
define("HR_DEAL_CAT_ID_SALE", "");
 
// MAILCHIMP
define('MAILCHIMP_TOKEN', 'yourmailchimpapitoken');