<?php

namespace local_login;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/authlib.php');

/**
 * Credential-validation logic for the Auth0 migration web service.
 *
 * Kept free of any HTTP/web-service concerns so it can be unit tested directly.
 */
class api {

    /**
     * Validate a username/password and, on success, migrate the user to OAuth2.
     *
     * @param string $username
     * @param string $password
     * @return array ['result' => string, 'profile' => array|null]
     */
    public static function login(string $username, string $password): array {
        $failurereason = null;
        $user = authenticate_user_login($username, $password, false, $failurereason, false);

        if (!$user) {
            switch ($failurereason) {
                case AUTH_LOGIN_SUSPENDED:
                    return ['result' => 'account_suspended'];
                case AUTH_LOGIN_LOCKOUT:
                    return ['result' => 'account_locked'];
                case AUTH_LOGIN_UNAUTHORISED:
                    return ['result' => 'account_unauthorised'];
                default:
                    // AUTH_LOGIN_NOUSER, AUTH_LOGIN_FAILED, AUTH_LOGIN_FAILED_RECAPTCHA.
                    // Collapsed into one message to avoid user enumeration.
                    return ['result' => 'wrong_credentials'];
            }
        }

        // Never hand out / migrate the guest or site admin accounts.
        if (isguestuser($user) || is_siteadmin($user->id)) {
            return ['result' => 'account_unauthorised'];
        }

        $profile = self::build_profile($user);
        self::migrate_to_oauth2($user);

        return ['result' => 'ok', 'profile' => $profile];
    }

    /**
     * Build an Auth0-shaped profile from a Moodle user record.
     *
     * @param \stdClass $user
     * @return array
     */
    public static function build_profile(\stdClass $user): array {
        return [
            'user_id' => (string) $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified' => (bool) $user->confirmed,
            'name' => fullname($user),
            'given_name' => $user->firstname,
            'family_name' => $user->lastname,
            'nickname' => $user->username,
        ];
    }

    /**
     * Best-effort migration of the user to OAuth2: switch the auth method and
     * create a linked-login record for the configured issuer.
     *
     * Never throws: the credentials have already been validated and the profile
     * returned, so a failure here must not break the response. The migration is
     * idempotent and self-heals on the next successful call.
     *
     * @param \stdClass $user
     */
    protected static function migrate_to_oauth2(\stdClass $user): void {
        try {
            if ($user->auth !== 'oauth2') {
                user_update_user((object) ['id' => $user->id, 'auth' => 'oauth2'], false, true);
            }
        } catch (\Throwable $e) {
            debugging('local_login: failed to switch auth for user ' . $user->id . ': ' . $e->getMessage());
        }

        try {
            $issuerid = get_config('local_login', 'oauthprovider');
            if ($issuerid && $issuerid !== 'none') {
                $issuer = \core\oauth2\api::get_issuer($issuerid);
                if ($issuer) {
                    \auth_oauth2\api::link_login(
                        ['username' => $user->username, 'email' => $user->email],
                        $issuer,
                        $user->id,
                        true
                    );
                }
            }
        } catch (\Throwable $e) {
            // 'alreadylinked' on repeat calls is expected and harmless.
            debugging('local_login: link_login skipped for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
