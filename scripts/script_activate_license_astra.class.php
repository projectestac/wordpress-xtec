<?php

require_once 'agora_script_base.class.php';

class script_activate_license_astra extends agora_script_base {

    public $title = 'Registra la llicència Astra Pro';
    public $info = "Registra la llicència de l'Astra Pro en el servidor del proveïdor per a la instància.";


    public function params(): array {
        return [
            'license_key' => '',
        ];
    }

    protected function _execute($params = []) {

        if (!class_exists('Bsf_Core_Rest')) {
            require_once ABSPATH . 'wp-content/plugins/astra-addon/admin/bsf-core/classes/class-bsf-core-rest.php';
        }

        $bsf_core_rest = Bsf_Core_Rest::get_instance();

        // Create a new instance of WP_REST_Request.
        $request = new WP_REST_Request('POST', get_site_url() . '/wp-json/bsf-core/v1/license/activate');

        $request->set_param('product-id', 'astra-addon');
        $request->set_param('license-key', $params['license_key']);

        $response = $bsf_core_rest->activate_license($request);

        // Check the response.
        if (is_wp_error($response)) {
            $this->output('No s\'ha pogut registrar la llicència.', 'WARNING');
        } else if ($response->get_data()['success'] === false) {
            $this->output('La clau proporcionada no és vàlida.', 'WARNING');
        } else {
            $this->output('La llicència s\'ha registrat correctament.', 'INFO');
        }

        // Save the response in the log.
        echo json_encode($response, true);

        return true;

    }

}