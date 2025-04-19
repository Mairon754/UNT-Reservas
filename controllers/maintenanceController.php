<?php
// maintenanceController.php: Manages maintenance requests

require_once 'models/maintenanceModel.php';

class MaintenanceController {

    // Get all maintenance requests
    public function getAllMaintenanceRequests() {
        return MaintenanceModel::getAllRequests();
    }

    // Create new maintenance request
    public function createMaintenanceRequest($resourceId, $description, $priority) {
        return MaintenanceModel::createRequest($resourceId, $description, $priority);
    }

    // Update maintenance request status
    public function updateMaintenanceRequestStatus($maintenanceId, $status) {
        return MaintenanceModel::updateStatus($maintenanceId, $status);
    }

    // Delete maintenance request
    public function deleteMaintenanceRequest($maintenanceId) {
        return MaintenanceModel::deleteRequest($maintenanceId);
    }
}
?>
