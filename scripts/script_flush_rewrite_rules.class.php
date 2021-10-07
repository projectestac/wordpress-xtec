<?php

require_once('agora_script_base.class.php');

class script_flush_rewrite_rules extends agora_script_base {

    public $title = 'Actualitza el valor del registre rewrite_rules';
    public $info = 'Actualitza el valor del registre rewrite_rules de la taula wp_options. Cal fer-ho cada vegada que s\'afegeixen tipus d\'enviaments (<em>Post Types</em>)';

    public function params(): array {
        return [];
    }

    protected function _execute($params = array()): bool {

        flush_rewrite_rules(false);

        $this->output('rewrite rules has been updated', 'INFO');

        return true;

    }

}
