<?php

require_once('agora_script_base.class.php');

use App\Models\Instance;

class script_activate_instance extends agora_script_base {

    public $title = 'Activació d\'instància';
    public $info = "Activa una instància i gestiona els errors si n'hi ha";

    public function params(): array {
        return [
            'newDbId' => null,        // New database ID
        ];
    }

    protected function _execute($params = []) {
        $newDbId = $params['newDbId'];

        $errors = [];

        $instance = new Instance();
        $instance->client_id        = $params['instance_client_id']     ?? null;
        $instance->service_id       = $params['instance_service_id']    ?? null;
        $instance->status           = $params['instance_status']        ?? null;
        $instance->db_id            = $params['instance_db_id']         ?? null;
        $instance->db_host          = $params['instance_db_host']       ?? null;
        $instance->quota            = $params['instance_quota']         ?? null;
        $instance->used_quota       = $params['instance_used_quota']    ?? null;
        $instance->model_type_id    = $params['instance_model_type_id'] ?? null;
        $instance->contact_name     = $params['instance_contact_name']  ?? null;
        $instance->observations     = $params['instance_observations']  ?? null;
        $instance->requested_at     = $params['instance_requested_at']  ?? null;
        $instance->updated_at       = $params['instance_updated_at']    ?? null;
        $instance->created_at       = $params['instance_created_at']    ?? null;
        $instance->id               = $params['instance_id']            ?? null;

        // Attempt to activate the instance
        $log = $instanceController->activateInstance($instance, $newDbId, Instance::STATUS_ACTIVE);

        // If errors are found, log them and delete the instance
        if (isset($log['errors'])) {
            $errors[] = $log['errors'];
            $instance->delete();
            echo "S'ha produït un error. S'ha eliminat la instància.\n";
        } else {
            echo "Instància activada correctament.\n";
        }

        // Return true if no errors were encountered
        return empty($errors);
    }
}
