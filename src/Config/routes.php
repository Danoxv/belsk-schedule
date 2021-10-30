<?php

use Src\Config\App;

$_routes = [
    /*
     * Schedule viewer pages
     */
    '/'                     => 'pages/schedule-viewer/select-schedule-file.php',
    '/view-schedule-file'   => 'pages/schedule-viewer/view-schedule-file.php',
];

if (App::getInstance()->enableSystemPages) {
    $_routes = $_routes + [
        /*
         * System pages
         */
        '/system/opcache'       => 'pages/system/opcache-gui-3.3.0/index.php',
        '/system/hits'          => 'pages/system/hits/viewer.php',
        '/system/hits/clean'    => 'pages/system/hits/cleaner.php',
    ];
}

return $_routes;