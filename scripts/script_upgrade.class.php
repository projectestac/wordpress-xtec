<?php

require_once 'agora_script_base.class.php';

class script_upgrade extends agora_script_base {

    public $title = 'Actualitza els espais d\'Àgora-Nodes';
    public $info = 'Crida l\'script wp-admin/upgrade.php de cada espai per dur a terme l\'actualització estàndard del WordPress';

    protected function _execute($params = []) {

        global $wp_db_version;

        $this->output('Versió de la base dades: ' . get_option('db_version'));
        $this->output('Versió dels fitxers: ' . $wp_db_version );

        if ((get_option('db_version') !== $wp_db_version) || !is_blog_installed()) {
            $_GET['step'] = 1;

            // Update WordPress and send all output to a buffer
            ob_start();
            require_once ABSPATH . 'wp-admin/upgrade.php';
            $output = ob_get_clean();

            // Process the HTML code to extract the content of header h1
            $DOM = new DOMDocument;
            $DOM->loadHTML($output, LIBXML_NOERROR);
            $items = $DOM->getElementsByTagName('h1');
            for ($i = 0; $i < $items->length; $i++) {
                $this->output($items->item($i)->nodeValue);
            }
        } else {
            $this->output(''); // Empty line
            $this->output('El WordPress ja estava actualitzat!');
        }

        return true;
    }
}
