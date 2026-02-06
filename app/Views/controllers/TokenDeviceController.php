<?php

namespace app\Controllers\Api;

use app\Models\Controller;
use app\Models\SensorLog;
use app\Models\PumpLog;
use app\Models\EventLog;
use app\Models\DetectedDevice;

class TokenDeviceController {

    /**
     * Mencari Controller berdasarkan Token.
     */
    private function findByToken($token) {
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM controllers WHERE device_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    /**
     * Endpoint HTTP: Menerima data sensor via URL Token.
     * POST /api/device/{token}/log
     */
    public function log($token) {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        $this->processLog($token, $data);
    }

    /**
     * Logika Inti: Memproses data log (Bisa dipanggil dari HTTP atau MQTT Worker).
     */
    public function processLog($token, $data) {
        $controller = $this->findByToken($token);

        if (!$controller) {
            if (http_response_code()) http_response_code(404);
            echo json_encode(['error' => 'Invalid Token']);
            return;
        }

        // Update Last Seen
        Controller::update($controller['id'], [
            'rssi' => $data['rssi'] ?? null,
            'last_update' => date('Y-m-d H:i:s')
        ]);

        // Simpan Log Sensor
        SensorLog::create([
            'controller_id' => $controller['id'],
            'water_percentage' => $data['water_percentage'] ?? null,
            'water_level' => $data['water_level_cm'] ?? 0.0,
            'rssi' => $data['rssi'] ?? null,
            'record_time' => date('Y-m-d H:i:s')
        ]);

        // Cek Status Pompa (jika dikirim)
        if (isset($data['pump_status'])) {
            $status = $data['pump_status'] ? 'ON' : 'OFF';
            if ($controller['status'] !== $status) {
                Controller::update($controller['id'], ['status' => $status]);
                PumpLog::create($controller['id'], ($status === 'ON'));
            }
        }

        if (http_response_code()) {
            echo json_encode(['status' => 'success']);
        }
    }

    /**
     * Endpoint HTTP: Menerima update status/event via URL Token.
     * POST /api/device/{token}/update
     */
    public function update($token) {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        $controller = $this->findByToken($token);
        if (!$controller) {
            http_response_code(404);
            return;
        }

        $action = $data['action'] ?? '';
        $value = $data['value'] ?? '';
        $updateData = ['last_update' => date('Y-m-d H:i:s')];

        switch ($action) {
            case 'set_mode':
                $updateData['control_mode'] = $value;
                $updateData['mode_update_command'] = 1; // Flag command
                EventLog::create($controller['id'], 'Mode Change', 'Mode diubah ke ' . $value);
                break;
            case 'set_status':
                $updateData['status'] = $value;
                PumpLog::create($controller['id'], ($value === 'ON'));
                break;
            case 'report_event':
                EventLog::create($controller['id'], 'Device Event', $value);
                break;
        }

        Controller::update($controller['id'], $updateData);
        echo json_encode(['status' => 'success']);
    }
}