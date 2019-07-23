<?php

require_once('agora_script_base.class.php');

class script_upgrade_reactor_options_https extends agora_script_base {

    public $title = 'Corregeix enllaços http a https a reactor_options';
    public $info = 'Edita el registre reactor_options i canvia els enllaços que comencen per http a https';

    public function params() {
        return [];
    }

    protected function _execute($params = []) {

        $options = (get_option('reactor_options')) ? get_option('reactor_options') : null;
        foreach ($options as $key => $value) {
            if ( ($key == 'logo_image') || ($key == 'contacteCentre') || ($key == 'favicon_image') ) {
                if (substr($value, 0, 7) === "http://") {
                    $value         = preg_replace("/^http:/i", "https:", $value);
                    $options[$key] = $value;
                }
            }
        }

        update_option('reactor_options', $options);

        $this->output('Reactor options https upgrade completed');

        return true;
    }

}
