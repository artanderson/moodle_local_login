<?php

namespace local_login;

class utils {
    public static function get_settings() {
        $settings = get_config('local_login');

        $enabled = $settings->enablelogin;
        if (!$enabled) {
            throw new \moodle_exception('plugindisabled', 'local_login');
        } else {
            if($settings->oauthprovider == 'none') {
                throw new \moodle_exception('noprovider', 'local_login');
            }
            if(
                $settings->emailconfirmation &&
                $settings->emailconfirmationkey == ''
            ) {
                throw new \moodle_exception('noconfirmationkey', 'local_login');
            }
        }

        return $settings;
    }
}