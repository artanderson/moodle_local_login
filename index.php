<?php

require_once('../../config.php');

$settings = \local_login\utils::get_settings();

$wantsurl = $CFG->wwwroot;
$issuerid = $settings->oauthprovider;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new \moodle_url('/local/login/index.php'));

if (!\auth_oauth2\api::is_enabled()) {
    throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = new \core\oauth2\issuer($issuerid);
if (!$issuer->is_available_for_login()) {
    throw new \moodle_exception('issuernologin', 'auth_oauth2');
}

$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $issuerid];
$returnurl = new \moodle_url('/local/login/index.php', $returnparams);

$client = \core\oauth2\api::get_user_oauth_client($issuer, $returnurl);

function check_email_verification($key, $userdata) {
    return $userdata[$key];
}

if ($client) {
    if (!$client->is_logged_in()) {
        redirect($client->get_login_url());
    }

    $auth = new \auth_oauth2\auth();

    if ($settings->emailconfirmation) {
        $isverified = false;
        $userdata = $client->get_raw_userinfo();
        
        $confirmationkey = $settings->emailconfirmationkey;
        if (property_exists($userdata, $confirmationkey)) {
            $isverified = $userdata->$confirmationkey;
        }
              
        if (!$isverified) {
            $emailconfirm = get_string('emailconfirmpending', 'local_login');
            $message = get_string('emailconfirmlinksent', 'local_login', $userdata->email);
            $auth->print_confirm_required($emailconfirm, $message);
            exit();
        }
    }
    
    $auth->complete_login($client, $wantsurl);
} else {
    throw new moodle_exception('Could not get an OAuth client.');
}
