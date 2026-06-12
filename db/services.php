<?php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_login_validate_login' => [
        'classname'    => 'local_login\external\validate_login',
        'methodname'   => 'execute',
        'description'  => 'Validate a username/password for Auth0 migration and switch the user to OAuth2.',
        'type'         => 'write',
        'capabilities' => 'local/login:validatecredentials',
        'ajax'         => false,
    ],
];

$services = [
    'Auth0 credential validator' => [
        'shortname'       => 'local_login_auth0',
        'functions'       => ['local_login_validate_login'],
        'restrictedusers' => 1,
        'enabled'         => 1,
        'downloadfiles'   => 0,
        'uploadfiles'     => 0,
    ],
];
