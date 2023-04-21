<?php

require_once('agora_script_base.class.php');

class script_helloworld extends agora_script_base {

    public string $title = 'Hello world';
    public string $info = "Saluda a qui diguis";


    public function params(): array {
        $params = [];
        $params['name'] = '';
        return $params;
    }

    protected function _execute($params = array()): bool {
        echo 'Hello ' . $params['name'];
        return true;
    }
}