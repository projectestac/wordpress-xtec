<?php

require_once('agora_script_base.class.php');

class script_activate_license_astra extends agora_script_base {

    public $title = 'Activar la llicència Astra Pro';
    public $info = "Activar la llicència Astra Pro de l'usuari mitjançant la seva clau de llicència.";


    public function params(): array {
        return [
            'license_key' => '',
        ];
    }

    protected function _execute($params = []) {
        if ( ! class_exists( 'Bsf_Core_Rest' ) ) {
            require_once( ABSPATH . 'wp-content/plugins/astra-addon/admin/bsf-core/classes/class-bsf-core-rest.php' );
        }

        $bsf_core_rest = Bsf_Core_Rest::get_instance();

        // crear una nueva instancia de WP_REST_Request
        $request = new WP_REST_Request( 'POST', get_site_url() . '/wp-json/bsf-core/v1/license/activate' );

        $request->set_param( 'product-id', 'astra-addon' );
        $request->set_param( 'license-key', $params['license_key'] );

        $response = $bsf_core_rest->activate_license( $request );

        // verificar la respuesta
        if ( is_wp_error( $response ) ) {
            $this->output('Hi ha hagut un problema amb la llicència.', 'WARNING');
        } else {
            if( $response->get_data()['success'] === false ) {
                $this->output('La clau de compra no és vàlida.', 'WARNING');
            }

            else {
                $this->output('Llicència activada correctament.', 'INFO');
            }
        }

        // guardar en logs por si acaso
        echo json_encode( $response , true );

        return true;
    }
}