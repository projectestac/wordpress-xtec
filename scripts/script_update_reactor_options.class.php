<?php

require_once('agora_script_base.class.php');

class script_update_reactor_options extends agora_script_base {

    public $title = 'Canvia la variable "emailCentre" per "contacteCentre" i en comprova el contingut';
    public $info = 'Canvia el nom de la variable "emailCentre" del tema Reactor per "contacteCentre" i en comprova el contingut. Si es tracta d\'una adreça de correu electrònic la posa a la variable "correuCentre" i deixa el "contacteCentre" buit.';

    public function params() {
        $params = array();
        return $params;
    }

    protected function _execute($params = array()) {

        $options_reactor = get_option('reactor_options');

        if ( ! empty( $options_reactor['emailCentre'] ) ) {

            if ( preg_match( '/^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,4})$/', $options_reactor['emailCentre'] )) {
                $options_reactor['correuCentre'] = $options_reactor['emailCentre'];
                $options_reactor['contacteCentre'] = '';
                $this->output('Content of "emailCentre" copied to "correuCentre"');
            } else {
                $options_reactor['contacteCentre'] = $options_reactor['emailCentre'];
                $options_reactor['correuCentre'] = '';
                $this->output( 'Content of "emailCentre" copied to "contacteCentre"' );
            }
            unset( $options_reactor['emailCentre'] );

        } else {
            $options_reactor['contacteCentre'] = '';
            $options_reactor['correuCentre'] = '';
            unset( $options_reactor['emailCentre'] );
        }

        update_option( 'reactor_options',$options_reactor );
        $this->output( 'Moved data from "emailCentre" to "contacteCentre"' );

        return true;
    }
}
