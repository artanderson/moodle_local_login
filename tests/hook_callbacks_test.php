<?php

namespace local_login;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the tenant-exclusion logic in \local_login\hook_callbacks.
 *
 * @covers \local_login\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {

    /**
     * An unknown tenant (null) is never excluded.
     */
    public function test_unknown_tenant_is_not_excluded(): void {
        $this->assertFalse(hook_callbacks::is_tenant_excluded(null, '5'));
    }

    /**
     * Matching against a newline-separated list.
     */
    public function test_matches_newline_list(): void {
        $this->assertTrue(hook_callbacks::is_tenant_excluded(5, "1\n5\n9"));
        $this->assertFalse(hook_callbacks::is_tenant_excluded(7, "1\n5\n9"));
    }

    /**
     * Matching against a comma-separated list.
     */
    public function test_matches_comma_list(): void {
        $this->assertTrue(hook_callbacks::is_tenant_excluded(5, '1,5,9'));
        $this->assertFalse(hook_callbacks::is_tenant_excluded(7, '1,5,9'));
    }

    /**
     * The parse tolerates stray whitespace and mixed separators.
     */
    public function test_forgiving_parse(): void {
        $this->assertTrue(hook_callbacks::is_tenant_excluded(5, " 1 , 5 "));
        $this->assertTrue(hook_callbacks::is_tenant_excluded(9, "1, 5\n 9"));
    }

    /**
     * An empty config excludes nobody.
     */
    public function test_empty_config(): void {
        $this->assertFalse(hook_callbacks::is_tenant_excluded(5, ''));
        $this->assertFalse(hook_callbacks::is_tenant_excluded(5, "  \n  "));
    }

    /**
     * Off Workplace (no tool_tenant) the tenant cannot be resolved, so the
     * feature is inert and the redirect behaviour is unchanged.
     */
    public function test_inert_without_workplace(): void {
        $this->resetAfterTest();

        // tool_tenant is not present in this tree.
        $this->assertFalse(class_exists('\tool_tenant\tenancy'));
        $this->assertNull(hook_callbacks::current_tenant_id());

        set_config('excludedtenants', "1\n2\n3", 'local_login');
        $this->assertFalse(hook_callbacks::tenant_excluded());
    }
}
