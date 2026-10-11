<?php

return [
    'session_idle_minutes' => (int) env('POS_SESSION_IDLE_MINUTES', 120),
    'login_attempts_per_minute' => (int) env('POS_LOGIN_ATTEMPTS', 5),
    'write_requests_per_minute' => (int) env('POS_WRITE_REQUESTS', 120),
    'exports_per_minute' => (int) env('POS_EXPORTS_PER_MINUTE', 10),
    'require_verified_email' => (bool) env('POS_REQUIRE_VERIFIED_EMAIL', true),
];
