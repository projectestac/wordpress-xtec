<?php

require_once('agora_script_base.class.php');

class script_helloworld extends agora_script_base {

    public $title = 'Hello world';
    public $info = "Saluda a qui diguis";


    public function params(): array {
        return [
            'name' => '',
        ];
    }

    protected function _execute($params = []) {
        echo 'Hello ' . $params['name'];
        return true;
    }
}