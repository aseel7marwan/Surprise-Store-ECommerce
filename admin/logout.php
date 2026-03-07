<?php
require_once '../includes/config.php';
require_once '../includes/security.php';

/**
 * صفحة تسجيل الخروج
 */

// Mark session as logged out in the database (before destroying)
markSessionAsLoggedOut();

// Destroy the admin session (also clears remember-me cookie)
destroyAdminSession();

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: login?logged_out=1');
exit;
