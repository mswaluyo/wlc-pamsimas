<?php 
// Definisi rute untuk antarmuka web (diakses oleh pengguna). 

$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');


// Rute Web
$router->get('/', 'DashboardController@index'); // Dashboard
$router->get('/controllers', 'ControllerController@index'); // Daftar perangkat
$router->get('/controllers/register', 'ControllerController@showRegistrationForm'); // Form pendaftaran
$router->post('/controllers/register', 'ControllerController@storeRegistration'); // Simpan pendaftaran
$router->get('/controllers/{id}', 'ControllerController@show'); // Detail controller
$router->post('/controllers/delete/{id}', 'ControllerController@delete'); // Hapus perangkat
$router->post('/controllers/apply-settings/{id}', 'ControllerController@applySettings'); // Terapkan pengaturan
$router->post('/controllers/sync/{id}', 'ControllerController@syncWithMasterData'); // Sinkronisasi data master
$router->get('/logs/sensors', 'LogController@sensorLogs'); // Log sensor
$router->get('/logs/pumps', 'LogController@pumpHistory'); // Riwayat pompa
$router->get('/logs/events', 'LogController@eventLogs'); // Log kejadian/power
$router->get('/users', 'UserController@index'); // Manajemen pengguna
$router->get('/users/create', 'UserController@create'); // Form tambah pengguna
$router->post('/users/create', 'UserController@store'); // Simpan pengguna
$router->get('/users/edit/{id}', 'UserController@edit'); // Edit pengguna
$router->post('/users/update/{id}', 'UserController@update'); // Update pengguna
$router->post('/users/delete/{id}', 'UserController@delete'); // Hapus pengguna
$router->get('/settings/tanks', 'SettingController@tanks'); // Pengaturan tangki
$router->get('/settings/pumps', 'SettingController@pumps'); // Pengaturan pompa
$router->get('/settings/pumps/create', 'SettingController@createPump'); // Form tambah pompa
$router->post('/settings/pumps/create', 'SettingController@storePump'); // Simpan pompa
$router->get('/settings/pumps/edit/{id}', 'SettingController@editPump'); // Edit pompa
$router->post('/settings/pumps/edit/{id}', 'SettingController@updatePump'); // Update pompa
$router->get('/settings/tanks/create', 'SettingController@createTank'); // Form tambah tangki
$router->post('/settings/tanks/create', 'SettingController@storeTank'); // Simpan tangki
$router->get('/settings/tanks/edit/{id}', 'SettingController@editTank'); // Edit tangki
$router->post('/settings/tanks/edit/{id}', 'SettingController@updateTank'); // Update tangki
$router->get('/settings/sensors', 'SettingController@sensors'); // Pengaturan sensor
$router->get('/settings/sensors/create', 'SettingController@createSensor'); // Form tambah sensor
$router->post('/settings/sensors/create', 'SettingController@storeSensor'); // Simpan sensor
$router->get('/settings/sensors/edit/{id}', 'SettingController@editSensor'); // Edit sensor
$router->post('/settings/sensors/edit/{id}', 'SettingController@updateSensor'); // Update sensor
$router->get('/settings/display', 'SettingController@displaySettings'); // Pengaturan tampilan
$router->post('/settings/display', 'SettingController@displaySettings'); // Simpan pengaturan tampilan
$router->get('/settings/display/reset', 'SettingController@resetDisplaySettings'); // Reset pengaturan tampilan
$router->get('/settings/indicators', 'SettingController@indicators'); // Rute lama, akan redirect
$router->get('/settings/database/backup', 'SettingController@backupDatabase'); // Aksi backup
$router->post('/settings/database/restore', 'SettingController@restoreDatabase'); // Aksi restore
$router->get('/settings/maintenance', 'SettingController@maintenance'); // Halaman maintenance
$router->post('/settings/maintenance/toggle', 'SettingController@toggleMaintenance'); // Aksi toggle maintenance

// Rute untuk Manajemen Template Gauge
$router->get('/templates', 'TemplateController@index'); // Daftar template
$router->get('/templates/create', 'TemplateController@create'); // Form tambah template
$router->post('/templates/create', 'TemplateController@create'); // Simpan template
$router->get('/templates/edit/{id}', 'TemplateController@edit'); // Edit template
$router->post('/templates/update/{id}', 'TemplateController@update'); // Update template
$router->post('/templates/delete/{id}', 'TemplateController@delete'); // Hapus template

// Rute untuk halaman deteksi perangkat real-time
$router->get('/detect', 'DetectionController@index'); // Deteksi perangkat

// Rute API untuk preview template gauge
$router->get('/api/template/preview/{id}', 'Api\DeviceApiController@getTemplatePreview');
