<?php

namespace local_login;

use \core\hook\output\before_http_headers;
use \core\hook\output\before_standard_head_html_generation;

class hook_callbacks {
    public static function enabled() {
        $settings = get_config('local_login');
        return $settings->enablelogin;
    }

    /**
     * Whether the given tenant id appears in the excluded-tenants config list.
     *
     * Pure matching logic (no Workplace dependency) so it can be unit tested.
     * The list is forgiving: tenant ids may be separated by newlines, commas or spaces.
     *
     * @param int|null $tenantid the current tenant id, or null when it cannot be determined
     * @param string $config the raw excludedtenants setting value
     * @return bool
     */
    public static function is_tenant_excluded(?int $tenantid, string $config): bool {
        if ($tenantid === null) {
            return false;
        }
        $ids = preg_split('/[\s,]+/', $config, -1, PREG_SPLIT_NO_EMPTY);
        $ids = array_map('intval', $ids);
        return in_array($tenantid, $ids, true);
    }

    /**
     * Resolve the current request's tenant id on Moodle Workplace.
     *
     * Returns null on standard Moodle (where tool_tenant is absent) or if the
     * tenant cannot be determined, so the plugin's default behaviour is preserved.
     *
     * @return int|null
     */
    public static function current_tenant_id(): ?int {
        if (!class_exists('\tool_tenant\tenancy') || !method_exists('\tool_tenant\tenancy', 'get_tenant_id')) {
            return null;
        }
        try {
            return (int) \tool_tenant\tenancy::get_tenant_id();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Whether the current tenant is excluded from the plugin's login redirect.
     *
     * @return bool
     */
    public static function tenant_excluded(): bool {
        $config = (string) (get_config('local_login', 'excludedtenants') ?: '');
        return self::is_tenant_excluded(self::current_tenant_id(), $config);
    }

    public static function login_before_http_headers(
        before_http_headers $hook
    ){
        global $CFG, $PAGE;
        
        if ($PAGE->pagetype != 'login-index') return;
        
        $cookiename = 'MoodleAuth';
        $cookiesecure = is_moodle_cookie_secure();
        $admin = optional_param('admin', false, PARAM_BOOL);

        if (!hook_callbacks::enabled() || $admin || self::tenant_excluded()) {
            $auth = $_COOKIE[$cookiename] ?? '';
            if ($auth !== '') {
                setcookie($cookiename, '', time() - HOURSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $cookiesecure, $CFG->cookiehttponly);
            }
            return;
        }
        
        setcookie($cookiename, 'plugin', time() + (DAYSECS * 60), $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $cookiesecure, $CFG->cookiehttponly);
        redirect(new \moodle_url('/local/login/index.php'));
    }

    public static function login_before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ) {
        if (!hook_callbacks::enabled()) return;
        
        $cookiename = 'MoodleAuth';
        $auth = $_COOKIE[$cookiename] ?? '';

        if ($auth == 'plugin') {
            $hook->add_html(
                '<script>'.
                'document.addEventListener("DOMContentLoaded", () => {'.
                '  document.querySelectorAll(\'a[href*="/login/logout.php"]\').forEach(elem => {'.
                '    const href = elem.getAttribute("href");'.
                '    if(href) {'.
                '      const updated = href.replace("/login/logout.php", "/local/login/logout.php");'.
                '      elem.setAttribute("href", updated);'.
                '    }'.
                '  });'.
                '});'.
                '</script>'
            );
        }
    }
}