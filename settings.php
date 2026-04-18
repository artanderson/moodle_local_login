<?php

defined('MOODLE_INTERNAL') || die;

if($hassiteconfig) {
    $choices = ["none"=>""];
    $providers = \core\oauth2\api::get_all_issuers(true);
    foreach($providers as $provider) {
        $choices[$provider->get('id')] = $provider->get('name');
    }

    $ADMIN->add('localplugins', new admin_category('local_login_settings', new lang_string('pluginname', 'local_login')));
    $settingspage = new admin_settingpage('managelocallogin', new lang_string('manage', 'local_login'));

	if ($ADMIN->fulltree) {
        $settingspage->add(new admin_setting_configcheckbox(
            'local_login/enablelogin',
            new lang_string('enablelogin', 'local_login'),
            new lang_string('enablelogin_desc', 'local_login'),
            $defaultsetting=false,
            $yes=true,
            $no=false
        ));
        $settingspage->add(new admin_setting_configselect(
            'local_login/oauthprovider',
            new lang_string('oauthprovider', 'local_login'),
            new lang_string('oauthprovider_desc', 'local_login'),
            'none',
            $choices
        ));
        $settingspage->add(new admin_setting_configcheckbox(
            'local_login/emailconfirmation',
            new lang_string('emailconfirmation', 'local_login'),
            new lang_string('emailconfirmation_desc', 'local_login'),
            $defaultsetting=false,
            $yes=true,
            $no=false
        ));
    	$settingspage->add(new admin_setting_configtext(
			'local_login/emailconfirmationkey',
			new lang_string('emailconfirmationkey', 'local_login'),
            new lang_string('emailconfirmationkey_desc', 'local_login'),
            '',
			PARAM_TEXT,
		));
    }

    $ADMIN->add('localplugins', $settingspage);
}