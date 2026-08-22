<?php

require_once __DIR__ . '/../BaseHandler.php';

/**
 * LocationHandler
 * ユーザー位置情報の更新・取得を担当
 */
class LocationHandler extends BaseHandler
{
    public function updateLocation(): void
    {
        $lat  = $this->getPost('lat', 0);
        $lon  = $this->getPost('lon', 0);
        $stmt = $this->mysqli->prepare(
            "INSERT INTO user_locations (user_id, lat, lon) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE lat=VALUES(lat), lon=VALUES(lon)"
        );
        $stmt->bind_param("idd", $this->userId, $lat, $lon);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    public function getUserLocations(): void
    {
        $stmt = $this->mysqli->prepare("SELECT * FROM user_locations");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}
