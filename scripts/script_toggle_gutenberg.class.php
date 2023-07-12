<?php

require_once('agora_script_base.class.php');

class script_toggle_gutenberg extends agora_script_base {

    public $title = 'Activa o desactiva l\'editor Gutenberg';
    public $info = 'Permet indicar si el Nodes ha d\'utilitzar l\'editor clàssic o el Gutenberg<br /><br />
                    gutenberg: on per activar / off per desactivar';

    public function params(): array {
        return [
            'gutenberg' => '',
        ];
    }

    protected function _execute($params = []) {

        define('OPTIONS_KEY', 'tadv_admin_settings');
        define('TINYMCE_KEY', 'replace_block_editor');

        switch ($params['gutenberg']) {

            case 'on':
                $this->output('Opció escollida: Activar Gutenberg', 'INFO');
                $tadv_admin_settings = get_option(OPTIONS_KEY);

                // Check if option is set
                if (strpos($tadv_admin_settings['options'], TINYMCE_KEY) !== false) {
                    // Remove the keyword that disables the editor
                    $tadv_admin_settings['options'] = str_replace(TINYMCE_KEY, '', $tadv_admin_settings['options']);

                    // Remove eventually resulting double commas
                    $tadv_admin_settings['options'] = str_replace(',,', ',', $tadv_admin_settings['options']);

                    // Remove eventually resulting trailing commas
                    $tadv_admin_settings['options'] = trim($tadv_admin_settings['options'], ',');

                    // Save to database
                    update_option(OPTIONS_KEY, $tadv_admin_settings);

                    $this->output('S\'ha activat l\'editor Gutenberg', 'INFO');
                } else {
                    $this->output('L\'editor Gutenberg ja estava activat', 'INFO');
                }

                break;

            case 'off':
                $this->output('Opció escollida: Desactivar Gutenberg', 'INFO');
                $tadv_admin_settings = get_option(OPTIONS_KEY);

                // Check if option is set
                if (strpos($tadv_admin_settings['options'], TINYMCE_KEY) === false) {
                    // Add the keyword only or a comma and the keyword depending on if the string if empty or not
                    if (empty($tadv_admin_settings['options'])) {
                        $tadv_admin_settings['options'] = TINYMCE_KEY;
                    } else {
                        $tadv_admin_settings['options'] .= ',' . TINYMCE_KEY;
                    }

                    // Save to database
                    update_option(OPTIONS_KEY, $tadv_admin_settings);

                    $this->output('S\'ha desactivat l\'editor Gutenberg', 'INFO');
                } else {
                    $this->output('L\'editor Gutenberg ja estava desactivat', 'INFO');
                }

                break;

            default:
                $this->output('El valor del paràmetre no era correcte. Ha de ser "on" o "off". No s\'ha fet cap acció', 'WARNING');
                break;
        }

        return true;
    }
}
