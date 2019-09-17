<?php

require_once('agora_script_base.class.php');

class script_delete_duplicated_email_subscribers extends agora_script_base {

    public $title = "Elimina duplicats al mòdul email subscribers";
    public $info = "Afegeix un cron al WordPress que s'executa diàriament i esborra els registres duplicats del mòdul email subscribers";

    protected function _execute($params = array()) {

        $this->checktable_mailing_queue('ig_mailing_queue');
        $this->checktable_sending_queue('ig_sending_queue');
        $this->checktable_lists_contacts('ig_lists_contacts');

        return true;
    }

    private function checktable_mailing_queue($table) {
        global $wpdb;

        $table = $wpdb->prefix . $table;

        // Get duplicated records
        $rows = $wpdb->get_results("SELECT DISTINCT `hash`, `subject`, `start_at` FROM $table limit 100");

        if ($rows) {
            foreach ($rows as $row) {
                // Checks that the connection to the database is still up. If not, try to reconnect.
                $wpdb->check_connection();

                // Get last Id
                $max_id = $wpdb->get_var("SELECT MAX(`id`) FROM $table WHERE `hash`='" . $row->hash . "'");

                // Drop duplicated rows
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $table
                        WHERE `id` != %d
                        AND `hash` = %s
                        AND `subject` = %s
                        AND `start_at`  = %s", $max_id, $row->hash, $row->subject, $row->start_at)
                );
            }
        }
    }

    private function checktable_sending_queue($table) {
        global $wpdb;

        $table = $wpdb->prefix . $table;

        // Get duplicated records
        $hashes = $wpdb->get_results("SELECT DISTINCT `mailing_queue_id`, `email` FROM $table limit 100");

        if ($hashes) {
            foreach ($hashes as $hash) {
                $wpdb->check_connection();

                // Get last id
                $max_id = $wpdb->get_var("SELECT MAX(`id`) FROM wp_ig_sending_queue WHERE `mailing_queue_id` ='" . $hash->mailing_queue_id . "' AND `email`='" . $hash->email . "'");

                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table
                                WHERE `id` < %d
                                AND `mailing_queue_id` = %s
                                AND `email` = %s", $max_id, $hash->mailing_queue_id, $hash->email)
                );
            }
        }
    }

    private function checktable_lists_contacts($table) {
        global $wpdb;

        $table = $wpdb->prefix . $table;

        // Get duplicated records
        $rows = $wpdb->get_results("SELECT DISTINCT `list_id`, `contact_id` FROM $table limit 100");

        if ($rows) {
            foreach ($rows as $row) {
                // Checks that the connection to the database is still up. If not, try to reconnect.
                $wpdb->check_connection();

                // Get last Id
                $max_id = $wpdb->get_var("SELECT MAX(`id`) FROM $table WHERE `contact_id`='" . $row->contact_id . "'");

                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $table
                                WHERE `id` < %d
                                AND `list_id` = %d
                                AND `contact_id` = %d", $max_id, $row->list_id, $row->contact_id)
                );
            }
        }
    }
}