<?php

declare(strict_types=1);

if (isset($_SERVER['REQUEST_URI'])) {
    $adjustedUri = preg_replace('#^/public(?=/|$)#', '', $_SERVER['REQUEST_URI']);
    $_SERVER['REQUEST_URI'] = $adjustedUri !== '' ? $adjustedUri : '/';
}

require_once dirname(__DIR__) . '/index.php';
