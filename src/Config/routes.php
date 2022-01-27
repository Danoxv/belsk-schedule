<?php
declare(strict_types=1);

use Src\Config\AppConfig;

$_routes = [
    '/'                     => 'pages/index.php',
    '/schedule-file'        => 'pages/schedule-file.php',
    '/terms-and-conditions' => 'pages/terms-and-conditions.php',

    /*
     * Utils
     */
    '/utils' => 'pages/utils/index.php',
    // LoveRead downloader
    '/utils/loveread-downloader'            => 'pages/utils/loveread-downloader/index.php',
    '/utils/loveread-downloader/download'   => 'pages/utils/loveread-downloader/download.php',
];

if (AppConfig::getInstance()->enableSystemPages) {
    $_routes += [
        /*
         * System pages
         */
        // Opcache GUI
        '/system/opcache'       => 'pages/system/opcache-gui-3.3.0/index.php',
        // Visits
        '/system/visits'        => 'pages/system/visits/index.php',
        '/system/visits/show'   => 'pages/system/visits/show.php',
        '/system/visits/delete' => 'pages/system/visits/delete.php',
    ];
}

return $_routes;