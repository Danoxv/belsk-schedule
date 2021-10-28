<?php

use Src\Config\AppConfig;

$_routes = [
    /*
     * Schedule viewer pages
     */
    '/'                     => 'pages/schedule-viewer/select-schedule-file.php',
    '/view-schedule-file'   => 'pages/schedule-viewer/view-schedule-file.php',
];

if (AppConfig::getInstance()->enableStatusPages) {
    $_routes = $_routes + [
        /*
         * Status pages
         */
        '/status/opcache'       => 'pages/status/opcache-gui-3.3.0/index.php',
        '/status/hits'          => 'pages/status/hits/viewer.php',
        '/status/hits/clean'    => 'pages/status/hits/cleaner.php',
    ];
}

return $_routes;