<?php

$string['pluginname'] = 'Login';
$string['login:validatecredentials'] = 'Validate credentials via the Auth0 migration web service';
$string['manage'] = 'Login Settings';
$string['enablelogin'] = 'Enable local login';
$string['enablelogin_desc'] = 'Check the box to enable the local login page';
$string['oauthprovider'] = 'OAuth2 Provider';
$string['oauthprovider_desc'] = 'Choose the OAuth2 provider to use for login';
$string['emailconfirmation'] = 'Use provider for email confirmation';
$string['emailconfirmation_desc'] = 'Check to bypass Moodle email confirmation for new accounts and use the provider settings';
$string['emailconfirmationkey'] = 'Email confirmation key';
$string['emailconfirmationkey_desc'] = 'User profile key for verifying email confirmation from provider';
$string['excludedtenants'] = 'Excluded tenants';
$string['excludedtenants_desc'] = 'One numeric tenant ID per line (commas and spaces also accepted). These tenants keep the standard Moodle login instead of being redirected to the provider. Only applies on Moodle Workplace; ignored on standard Moodle.';
$string['emailconfirmpending'] = 'This account is pending email confirmation.';
$string['emailconfirmlinksent'] = '<p>This account is pending email confirmation before you can log in.</p>
   <p>An email should have been sent to your address at <b>{$a}</b>.</p>
   <p>It contains easy instructions to confirm your email.</p>
   <p>If you have any difficulty, contact the site administrator.</p>';