<?php

require_once('agora_script_base.class.php');

class script_upgrade_simple_calendar extends agora_script_base
{

    public $title = 'Actualitza l\'extensió Simple Calendar';
    public $info = 'Executa la funció d\'actualització del Simple Calendar (google-calendar-events/includes/update.php -> Update::run_updates())';

    public function params() {
        return array();
    }

    protected function _execute($params = array()) {

        $update = new \SimpleCalendar\Update();
        $update->run_updates();

        $this->output('Upgrade completed');

        return true;
    }

}
