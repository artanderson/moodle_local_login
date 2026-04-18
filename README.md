# Moodle Local Login

A Moodle local plugin that replaces the standard Moodle login page with a redirect to a configured OAuth2 provider, and routes logout through the provider's session endpoint.

- **Component:** `local_login`
- **Release:** 1.0.0
- **Maturity:** Stable

## What it does

- Intercepts requests to the Moodle login page (`/login/index.php`) and redirects them to the OAuth2 provider selected in plugin settings.
- Completes authentication via the built-in `auth_oauth2` flow and logs the user in.
- Rewrites logout links in the Moodle UI to route through the plugin, so logout also terminates the provider session (via `{issuer baseurl}/v2/logout`).
- Optionally defers email confirmation to the OAuth2 provider. If enabled, the plugin reads a configurable key from the provider's userinfo response and blocks login until that key is truthy.
- Sets a `MoodleAuth` cookie to track whether a session was established via the plugin, so only those sessions are routed through the provider logout.
- Provides an admin escape hatch: appending `?admin=1` to the login URL bypasses the redirect and shows the standard Moodle login form.

## Requirements

- Moodle with the core `auth_oauth2` plugin enabled.
- At least one OAuth2 issuer configured in **Site administration → Server → OAuth 2 services** and available for login.
- The provider's logout endpoint must be compatible with the `/v2/logout?client_id=...&returnTo=...` form (e.g., Auth0). Other providers may require code changes to [logout.php:37](logout.php#L37).

## Installation

1. Copy this directory into your Moodle install at `local/login/`.
2. Visit **Site administration → Notifications** to complete installation.
3. Configure the plugin at **Site administration → Plugins → Local plugins → Login Settings**.

## Settings

Configured under **Site administration → Plugins → Local plugins → Login Settings**:

| Setting | Description |
| --- | --- |
| Enable local login | Master switch. When off, Moodle's default login is used. |
| OAuth2 Provider | Which configured OAuth2 issuer to redirect to. |
| Use provider for email confirmation | If on, the plugin checks a profile key from the provider instead of Moodle's email confirmation. |
| Email confirmation key | The userinfo field (e.g., `email_verified`) used when the option above is enabled. |

## How it works

- [db/hooks.php](db/hooks.php) registers two core output hooks.
- [classes/hook_callbacks.php](classes/hook_callbacks.php):
  - `login_before_http_headers` — on `login-index` pages, sets the `MoodleAuth` cookie and redirects to `/local/login/index.php` (unless disabled or `?admin=1` is present).
  - `login_before_standard_head_html_generation` — injects a small script that rewrites `/login/logout.php` links to `/local/login/logout.php` for plugin-authenticated sessions.
- [index.php](index.php) — drives the OAuth2 login, optionally gates on the email-confirmation key, and calls `auth_oauth2\auth::complete_login`.
- [logout.php](logout.php) — logs the user out of Moodle and redirects to the provider's logout endpoint.
- [classes/utils.php](classes/utils.php) — loads and validates plugin settings, throwing `moodle_exception` on misconfiguration.

## Admin bypass

If the redirect ever locks you out of the admin account, visit:

```
https://<your-site>/login/index.php?admin=1
```

This skips the plugin redirect and shows the standard Moodle login form.

## License

GPL v3 — see [LICENSE](LICENSE).
