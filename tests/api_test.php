<?php

namespace local_login;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for \local_login\api.
 *
 * @covers \local_login\api
 */
final class api_test extends \advanced_testcase {

    /**
     * Valid credentials return a profile and migrate the account to OAuth2.
     */
    public function test_login_valid_credentials_migrates_to_oauth2(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'jane',
            'password' => 'Correct-horse-1',
            'email' => 'jane@example.com',
            'firstname' => 'Jane',
            'lastname' => 'Smith',
        ]);

        $result = api::login('jane', 'Correct-horse-1');

        $this->assertSame('ok', $result['result']);
        $this->assertArrayHasKey('profile', $result);
        $this->assertSame((string) $user->id, $result['profile']['user_id']);
        $this->assertSame('jane@example.com', $result['profile']['email']);
        $this->assertSame('jane', $result['profile']['username']);
        $this->assertArrayNotHasKey('password', $result['profile']);

        // The account auth method should now be oauth2.
        $reloaded = \core_user::get_user($user->id);
        $this->assertSame('oauth2', $reloaded->auth);
    }

    /**
     * A successful login with a configured issuer creates a linked-login record.
     */
    public function test_login_creates_linked_login(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $issuer = \core\oauth2\api::create_standard_issuer('google');
        set_config('oauthprovider', $issuer->get('id'), 'local_login');

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'jane',
            'password' => 'Correct-horse-1',
            'email' => 'jane@example.com',
        ]);

        $result = api::login('jane', 'Correct-horse-1');
        $this->assertSame('ok', $result['result']);

        $linkedlogins = \auth_oauth2\api::get_linked_logins($user->id, $issuer);
        $this->assertCount(1, $linkedlogins);
    }

    /**
     * A wrong password is reported as generic wrong credentials.
     */
    public function test_login_wrong_password(): void {
        $this->resetAfterTest();

        $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'jane',
            'password' => 'Correct-horse-1',
        ]);

        $result = api::login('jane', 'wrong-password');

        $this->assertSame('wrong_credentials', $result['result']);
        $this->assertArrayNotHasKey('profile', $result);
    }

    /**
     * A non-existent user is indistinguishable from a wrong password (no enumeration).
     */
    public function test_login_unknown_user(): void {
        $this->resetAfterTest();

        $result = api::login('nobody', 'whatever');

        $this->assertSame('wrong_credentials', $result['result']);
    }

    /**
     * A suspended user is rejected and not migrated.
     */
    public function test_login_suspended_user_not_migrated(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'jane',
            'password' => 'Correct-horse-1',
            'suspended' => 1,
        ]);

        $result = api::login('jane', 'Correct-horse-1');

        $this->assertSame('account_suspended', $result['result']);
        $reloaded = \core_user::get_user($user->id);
        $this->assertSame('manual', $reloaded->auth);
    }

    /**
     * The site admin account is never returned or migrated.
     */
    public function test_login_admin_account_rejected(): void {
        $this->resetAfterTest();

        $admin = \core_user::get_user_by_username('admin');
        // Give the admin a known password.
        update_internal_user_password($admin, 'Admin-pass-1');

        $result = api::login('admin', 'Admin-pass-1');

        $this->assertSame('account_unauthorised', $result['result']);
        $reloaded = \core_user::get_user($admin->id);
        $this->assertSame('manual', $reloaded->auth);
    }

    /**
     * Migration is one-way: once a user is on oauth2 the API no longer validates the password.
     */
    public function test_login_is_one_way(): void {
        $this->resetAfterTest();

        $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'jane',
            'password' => 'Correct-horse-1',
        ]);

        $first = api::login('jane', 'Correct-horse-1');
        $this->assertSame('ok', $first['result']);

        // The user is now oauth2; the same password must no longer authenticate.
        $second = api::login('jane', 'Correct-horse-1');
        $this->assertNotSame('ok', $second['result']);
    }
}
