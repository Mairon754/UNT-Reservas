<?php
// resourceController.php: Manages resource operations (CRUD)

require_once 'models/resourceModel.php';

class ResourceController {

    // Get all resources
    public function getAllResources() {
        return ResourceModel::getAllResources();
    }

    // Create a new resource
    public function createResource($name, $type, $status) {
        return ResourceModel::createResource($name, $type, $status);
    }

    // Update resource status
    public function updateResourceStatus($resourceId, $status) {
        return ResourceModel::updateResourceStatus($resourceId, $status);
    }

    // Delete resource
    public function deleteResource($resourceId) {
        return ResourceModel::deleteResource($resourceId);
    }
}
?>
