<?php

require_once 'agora_script_base.class.php';

class script_update_buddypress_subscriptions extends agora_script_base {

    public $title = "Actualitza les taules del mòdul buddypress-group-email-subscription ";
    public $info = "Comprova l'existència de les taules bpges_subscriptions i bpges_queued_items. En cas que no existeixin les crea.";


    private function execute_sql($sql) {
        global $wpdb;
        $wpdb->hide_errors();
        if (is_wp_error($wpdb->query($sql))) {
            $wpdb->print_error();
            return false;
        }
        $wpdb->show_errors();
        return true;
    }

    protected function checktable_subscriptions($table){
        global $wpdb;

        $table = $wpdb->prefix . $table;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $this->output('Table ' .$table. ' already exists');
        }else {
            $sql = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `user_id` bigint(20) NOT NULL,
                  `group_id` bigint(20) NOT NULL,
                  `type` varchar(75) COLLATE utf8mb4_unicode_ci NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `user_id` (`user_id`),
                  KEY `group_id` (`group_id`),
                  KEY `user_type` (`user_id`,`type`)
                ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;            
            ";
            if ($this->execute_sql($sql)) {
                $this->output('Table ' .$table. ' created');
            } else {
                $this->output('Error creating table '. $table);
            }
        }

        return true;
    }

    protected function checktable_queued_items($table){
        global $wpdb;

        $table = $wpdb->prefix . $table;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $this->output('Table ' .$table. ' already exists');
        }else {
            $sql = "CREATE TABLE IF NOT EXISTS `wp_bpges_queued_items` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `user_id` bigint(20) NOT NULL,
                  `group_id` bigint(20) NOT NULL,
                  `activity_id` bigint(20) NOT NULL,
                  `type` varchar(75) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `date_recorded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `user_group_activity_type` (`user_id`,`group_id`,`activity_id`,`type`),
                  KEY `user_id` (`user_id`),
                  KEY `group_id` (`group_id`),
                  KEY `activity_id` (`activity_id`),
                  KEY `user_group_type_date` (`user_id`,`type`,`date_recorded`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;           
            ";
            if ($this->execute_sql($sql)) {
                $this->output('Table ' .$table. ' created');
            } else {
                $this->output('Error creating table '. $table);
            }
        }
    }

    protected function _execute($params = array()) {
        $this->checktable_subscriptions('bpges_subscriptions');
        $this->checktable_queued_items('bpges_queued_items');

        return true;
    }
}
