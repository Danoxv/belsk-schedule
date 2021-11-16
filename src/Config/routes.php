<?php

use Src\Config\AppConfig;

$_routes = [
    /*
     * Service pages
     */
    '/terms-and-conditions' => 'pages/service/terms-and-conditions.php',

    /*
     * Schedule viewer pages
     */
    '/'                     => 'pages/schedule-viewer/select-schedule-file.php',
    '/view-schedule-file'   => 'pages/schedule-viewer/view-schedule-file.php',
];

if (AppConfig::getInstance()->enableSystemPages) {
    $_routes = $_routes + [
        /*
         * System pages
         */
        '/system/opcache'       => 'pages/system/opcache-gui-3.3.0/index.php',
        '/system/visits'        => 'pages/system/visits/viewer.php',
        '/system/visits/clean'  => 'pages/system/visits/cleaner.php',
    ];
}

return $_routes;