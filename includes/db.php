<?php
/**
 * DB helper — just require config.php which sets $pdo globally.
 * Include this in any file that needs DB access.
 */
if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config.php';
}
