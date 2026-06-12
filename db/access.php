<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Allows a web service token's bound user to validate credentials and migrate
    // an account to OAuth2 via the Auth0 credential-validation service.
    'local/login:validatecredentials' => [
        'riskbitmask'  => RISK_PERSONAL | RISK_DATALOSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [],
    ],
];
