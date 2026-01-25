<?php 
// Definisi rute untuk API (diakses oleh ESP8266). 

$router->post('/api/log', 'Api\DeviceApiController@log');           // Endpoint untuk menerima data sensor (level, rssi).
$router->get('/api/status', 'Api\DeviceApiController@status');         // Endpoint untuk perangkat meminta status & konfigurasi.
$router->post('/api/update', 'Api\DeviceApiController@update');        // Endpoint untuk menerima perintah dari perangkat (mode, status, event).
$router->post('/api/log-offline', 'Api\DeviceApiController@logOffline'); // Endpoint untuk menerima log yang tersimpan saat offline.
$router->get('/api/detected-devices', 'Api\DeviceApiController@getDetectedDevices');
$router->get('/api/dashboard-data', 'Api\DeviceApiController@getDashboardData'); // RUTE BARU: Untuk live update dashboard

// Rute untuk preview template
$router->get('/api/template-preview/{id}', 'Api\DeviceApiController@getTemplatePreview');

// Rute untuk System Automation (Backup Otomatis)
$router->get('/api/system/backup', 'Api\SystemApiController@autoBackup');
