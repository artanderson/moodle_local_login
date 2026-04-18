<?php

$callbacks = [ 
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [local_login\hook_callbacks::class, 'login_before_standard_head_html_generation'],
        'priority' => 100,
    ],
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [local_login\hook_callbacks::class, 'login_before_http_headers'],
        'priority' => 100,
    ]
];