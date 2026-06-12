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
- On **Moodle Workplace** installs, lets you exclude specific tenants from the redirect so they keep the standard Moodle login.
- Exposes a credential-validation **web service** (`local_login_validate_login`) that an external identity provider (e.g. Auth0) can call to validate a Moodle username/password and lazily migrate the account to OAuth2.

## Requirements

- Moodle with the core `auth_oauth2` plugin enabled.
- At least one OAuth2 issuer configured in **Site administration → Server → OAuth 2 services** and available for login.
- The provider's logout endpoint must be compatible with the `/v2/logout?client_id=...&returnTo=...` form (e.g., Auth0). Other providers may require code changes to [logout.php:37](logout.php#L37).
- For the **Auth0 migration web service**: Moodle web services and the REST protocol enabled (see below).
- The **Excluded tenants** setting only has an effect on Moodle Workplace (where `tool_tenant` is present); it is ignored on standard Moodle.

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
| Excluded tenants | **Moodle Workplace only.** A list of numeric tenant IDs (one per line; commas/spaces also accepted) that should keep the standard Moodle login instead of being redirected. Ignored on standard Moodle. |

## How it works

- [db/hooks.php](db/hooks.php) registers two core output hooks.
- [classes/hook_callbacks.php](classes/hook_callbacks.php):
  - `login_before_http_headers` — on `login-index` pages, sets the `MoodleAuth` cookie and redirects to `/local/login/index.php` (unless disabled, `?admin=1` is present, or the current Workplace tenant is excluded).
  - `login_before_standard_head_html_generation` — injects a small script that rewrites `/login/logout.php` links to `/local/login/logout.php` for plugin-authenticated sessions.
  - `tenant_excluded` / `current_tenant_id` / `is_tenant_excluded` — resolve the current Workplace tenant (guarded with `class_exists('\tool_tenant\tenancy')`, so it is inert on standard Moodle) and check it against the **Excluded tenants** setting.
- [index.php](index.php) — drives the OAuth2 login, optionally gates on the email-confirmation key, and calls `auth_oauth2\auth::complete_login`.
- [logout.php](logout.php) — logs the user out of Moodle and redirects to the provider's logout endpoint.
- [classes/utils.php](classes/utils.php) — loads and validates plugin settings, throwing `moodle_exception` on misconfiguration.
- [classes/api.php](classes/api.php) — credential-validation logic for the migration web service: validates a username/password, builds the response profile, and migrates the account to OAuth2.
- [classes/external/validate_login.php](classes/external/validate_login.php) — the external (web service) function wrapper; [db/services.php](db/services.php) registers it and a dedicated service, [db/access.php](db/access.php) defines its capability.

## Auth0 migration web service

To support migrating existing Moodle users into an external identity provider (Auth0's *custom database connection* with *"Import users to Auth0 on login"*), the plugin exposes a web service function that validates a username/password and lazily migrates the account to OAuth2.

- **Function:** `local_login_validate_login`
- **Service:** `Auth0 credential validator` (shortname `local_login_auth0`, restricted users)
- **Capability:** `local/login:validatecredentials` (required by the token's bound user)

On a successful validation it returns an Auth0-shaped profile, switches the user's `auth` method to `oauth2`, and creates an `auth_oauth2_linked_login` record for the configured **OAuth2 Provider**. Because the OAuth2 auth method refuses local password logins, the migration is **one-way**: once a user is switched, the function no longer validates their password.

The response always uses HTTP 200; the outcome is carried in the `result` field:

| `result` | Meaning |
| --- | --- |
| `ok` | Credentials valid; `profile` object included; account migrated to OAuth2. |
| `wrong_credentials` | Unknown user or bad password (collapsed to avoid user enumeration). |
| `account_suspended` | The account is suspended; not migrated. |
| `account_locked` | The account is locked out (core login lockout). |
| `account_unauthorised` | Not allowed to log in this way — includes the guest and site-admin accounts, which are never migrated. |

### Setup

1. Enable **Web services** (**Site administration → Advanced features**) and the **REST** protocol (**Site administration → Server → Web services → Manage protocols**).
2. Create a dedicated service-account user and grant its role the `local/login:validatecredentials` capability.
3. Authorise that user on the `Auth0 credential validator` service and create a **token** (**Manage tokens**) for it — optionally restrict it to Auth0's IP ranges and set an expiry.
4. Point Auth0's Login script at the REST endpoint (POST, so the password stays out of the query log):

```
POST /webservice/rest/server.php
  wstoken=<token>&wsfunction=local_login_validate_login&moodlewsrestformat=json
  username=<u>&password=<p>
```

## Admin bypass

If the redirect ever locks you out of the admin account, visit:

```
https://<your-site>/login/index.php?admin=1
```

This skips the plugin redirect and shows the standard Moodle login form.

## License

GPL v3 — see [LICENSE](LICENSE).
