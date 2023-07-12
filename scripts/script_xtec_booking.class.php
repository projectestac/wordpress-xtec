<?php

require_once('agora_script_base.class.php');

class script_xtec_booking extends agora_script_base {

    public $title = "Programa l'esborrament de les reserves antigues";
    public $info = "Afegeix un cron al WordPress que s'executa diàriament i esborra les reserves que porten més d'un any caducades o modifica la data d'inici d'aquelles que van començar fa més d'un any";

    protected function _execute($params = []) {

        if (!wp_next_scheduled('cron_xtec_booking')) {
            wp_schedule_event(time(), 'daily', 'cron_xtec_booking');
        }

        return true;
    }

}
