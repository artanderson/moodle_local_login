<?php

namespace local_login;

use \core\hook\output\before_http_headers;
use \core\hook\output\before_standard_head_html_generation;

class hook_callbacks {
    public static function enabled() {
        $settings = get_config('local_login');
        return $settings->enablelogin;
    }

    public static function login_before_http_headers(
        before_http_headers $hook
    ){
        global $CFG, $PAGE;
        
        if ($PAGE->pagetype != 'login-index') return;
        
        $cookiename = 'MoodleAuth';
        $cookiesecure = is_moodle_cookie_secure();
        $admin = optional_param('admin', false, PARAM_BOOL);

        if (!hook_callbacks::enabled() || $admin) {
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