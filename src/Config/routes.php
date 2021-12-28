<?php

use Src\Config\AppConfig;

$_routes = [
    /*
     * Schedule viewer pages
     */
    '/'                     => 'pages/schedule-viewer/select-schedule-file.php',
    '/view-schedule-file'   => 'pages/schedule-viewer/view-schedule-file.php',

    /*
     * Service pages
     */
    '/terms-and-conditions' => 'pages/service/terms-and-conditions.php',
];

if (AppConfig::getInstance()->enableSystemPages) {
    $_routes += [
        /*
         * System pages
         */
        '/system/opcache'       => 'pages/system/opcache-gui-3.3.0/index.php',

        '/system/visits'        => 'pages/system/visits/index.php',
        '/system/visits/show'   => 'pages/system/visits/show.php',
        '/system/visits/delete' => 'pages/system/visits/delete.php',
    ];
}

return $_routes;