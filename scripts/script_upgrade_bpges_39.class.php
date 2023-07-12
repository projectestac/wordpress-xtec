<?php

require_once('agora_script_base.class.php');

class script_upgrade_bpges_39 extends agora_script_base {

    public $title = 'Actualitza el BPGES a la versió 3.9';
    public $info = 'Actualitza l\'extensió Buddypress Group Email Subscription a la versió 3.9';

    protected function _execute($params = []) {

        require_once ABSPATH . 'wp-content/plugins/buddypress-group-email-subscription/admin.php';

        $status = bpges_39_migration_status();

        if (!$status['subscription_table_created']) {
            bpges_install_subscription_table();
            $this->output('Created subscription table', 'INFO');
        } else {
            $this->output('Subscription table already exists', 'INFO');
        }

        if (!$status['queued_items_table_created']) {
            bpges_install_queued_items_table();
            $this->output('Created queued items table', 'INFO');
        } else {
            $this->output('Queued items table already exists', 'INFO');
        }

        if (!$status['subscriptions_migrated']) {
            bpges_39_launch_legacy_subscription_migration();
            $this->output('Migrated subscriptions', 'INFO');
        } else {
            $this->output('Subscriptions already migrated', 'INFO');
        }

        if (!$status['queued_items_migrated']) {
            bpges_39_launch_legacy_digest_queue_migration();
            $this->output('Migrated queued items', 'INFO');
        } else {
            $this->output('Queued items already migrated', 'INFO');
        }

        return true;
    }
}