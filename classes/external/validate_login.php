<?php

namespace local_login\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function used by Auth0 to validate a username/password and migrate the user to OAuth2.
 */
class validate_login extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            // PARAM_RAW_TRIMMED rather than PARAM_USERNAME: it must not be lowercased/stripped
            // so that email-as-username (authloginviaemail) still works.
            'username' => new external_value(PARAM_RAW_TRIMMED, 'Username, or email when authloginviaemail is enabled'),
            'password' => new external_value(PARAM_RAW, 'Plain-text password'),
        ]);
    }

    /**
     * Validate the credentials.
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    public static function execute(string $username, string $password): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'password' => $password,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/login:validatecredentials', $context);

        return \local_login\api::login($params['username'], $params['password']);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(
                PARAM_ALPHAEXT,
                'Outcome: ok, wrong_credentials, account_suspended, account_locked or account_unauthorised'
            ),
            'profile' => new external_single_structure(
                [
                    'user_id' => new external_value(PARAM_RAW, 'Moodle user id as a string'),
                    'username' => new external_value(PARAM_RAW, 'Username'),
                    'email' => new external_value(PARAM_RAW, 'Email address'),
                    'email_verified' => new external_value(PARAM_BOOL, 'Whether the account email is confirmed'),
                    'name' => new external_value(PARAM_RAW, 'Full name'),
                    'given_name' => new external_value(PARAM_RAW, 'First name'),
                    'family_name' => new external_value(PARAM_RAW, 'Last name'),
                    'nickname' => new external_value(PARAM_RAW, 'Nickname'),
                ], 
                'User profile, present only when result is ok', VALUE_OPTIONAL
            )
        ]);
    }
}
