<?php
/**
 * CRM bootstrap — DB, auth and helpers WITHOUT any layout output.
 * Include at the top of pages that must query the database or redirect
 * before the shared header (layout) is rendered.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();
