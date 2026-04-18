<?php

require_once('../../config.php');

$cookiename = 'MoodleAuth';
$auth = $_COOKIE[$cookiename] ?? '';

if($auth !== 'plugin') {
    redirect(new \moodle_url('/login/logout.php'));
}

$sesskey = optional_param('sesskey', '__notpresent__', PARAM_RAW);

$settings = $settings = \local_login\utils::get_settings();
$issuerid = $settings->oauthprovider;

if (!\auth_oauth2\api::is_enabled()) {
    throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = new \core\oauth2\issuer($issuerid);
if (!$issuer->is_available_for_login()) {
    throw new \moodle_exception('issuernologin', 'auth_oauth2');
}

$returnurl = new \moodle_url('/local/login/index.php');
$client = \core\oauth2\api::get_user_oauth_client($issuer, $returnurl);

$PAGE->set_url('/local/login/logout.php');
$PAGE->set_context(context_system::instance());

$params = [
    'client_id'=>$client->get_clientid(),
    'returnTo'=>new \moodle_url($returnurl)
];

$redirect = new \moodle_url($issuer->get('baseurl').'/v2/logout', $params);

if (!isloggedin()) {
    require_logout();
    redirect($redirect);
} else if (!confirm_sesskey($sesskey)) {
    $PAGE->set_title(get_string('logout'));
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('logoutconfirm'), new moodle_url($PAGE->url, ['sesskey'=>sesskey()]), "{$CFG->wwwroot}/");
    echo $OUTPUT->footer();
    die;
}

require_logout();
redirect($redirect);