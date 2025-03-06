<?php

require_once 'agora_script_base.class.php';

class script_astra_blog_pro extends agora_script_base {

    public $title = 'Activa el Blog Pro';
    public $info = 'Activa i configura el mòdul Blog Pro del tema Astra.';

    protected function _execute($params = []): bool {

        activate_blog_pro();
        $this->output('S\'ha activat el mòdul Blog Pro de l\'Astra', 'INFO');

        return true;

    }

}
