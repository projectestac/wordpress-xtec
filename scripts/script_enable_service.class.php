<?php

require_once('agora_script_base.class.php');

class script_enable_service extends agora_script_base {

    public $title = 'Activa el servei Àgora-Nodes';
    public $info = "Fa els passos necessàris per activar el Nodes i deixar-lo a punt per començar";

    public function params(): array {
        return [
            'password' => '',  // admin password in md5
            'xtecadminPassword' => '', // xtecadmin password in md5
            'clientName' => '',
            'clientAddress' => '',
            'clientCity' => '',
            'clientPC' => '', // Postal Code
            'clientDNS' => '', // Not used
            'clientCode' => '',
            'origin_url' => '',
            'origin_bd' => '',
        ];
    }

    protected function _execute($params = []) {
        global $agora, $wpdb;

        // Get the params
        $clientName = $params['clientName'];
        $clientAddress = $params['clientAddress'];
        $clientPCCity = $params['clientPC'] . ' ' . $params['clientCity']; // Post Code and City
        $adminMail = $params['clientCode'] . '@xtec.cat';

        $this->output('Set Blog name to ' . $clientName);
        update_option('blogname', $clientName);
        update_option('nodesbox_name', $clientName);

        $this->output("Set Admin mail to $adminMail");
        update_option('admin_email', $adminMail);

        $this->output('Set Site URL to ' . WP_SITEURL);
        update_option('siteurl', WP_SITEURL);
        update_option('home', WP_SITEURL);
        update_option('wsl_settings_redirect_url', WP_SITEURL);

        $this->output('Updating school name and address');

        // Reactor
        $value = get_option('reactor_options');
        $value['nomCanonicCentre'] = $clientName;
        $value['direccioCentre'] = $clientAddress;
        $value['cpCentre'] = $clientPCCity;
        update_option('reactor_options', $value);

        // Astra
        $value = get_option('theme_mods_astra');
        $value['astra_nodes_options']['postal_address'] = $clientAddress;
        $value['astra_nodes_options']['postal_code_city'] = $clientPCCity;
        update_option('theme_mods_astra', $value);

        $this->output('Configuring admin user');
        $user = get_user_by('login', 'admin');
        $wpdb->update(
            $wpdb->users,
            [
                'user_pass' => $params['password'],
                'user_email' => $adminMail, 
                'user_registered' => date('Y-m-d H:i:s')],
            ['ID' => $user->ID]
        );

        $this->output('Configuring xtecadmin user');
        $user = get_user_by('login', 'xtecadmin');
        $wpdb->update(
            $wpdb->users,
            [
                'user_pass' => $params['xtecadminPassword'],
                'user_email' => $agora['xtecadmin']['mail'],
                'user_registered' => date('Y-m-d H:i:s'),
            ],
            ['ID' => $user->ID]
        );

        // Email Subscribers
        $this->execute_suboperation('replace_email_subscribers', [
            'adminMail' => $adminMail,
        ]);

        $this->output('Reset stats table');
        if (!$this->execute_sql('TRUNCATE ' . $wpdb->prefix . 'stats')) {
            $this->output('Error buidant la taula stats', 'ERROR');
            return false;
        }

        $this->output('Replacing site URL and database');
        $success = $this->execute_suboperation('replace_url', [
            'origin_url' => $params['origin_url'],
            'origin_bd' => $params['origin_bd'],
        ]);

        if (!$success) {
            $this->output('Ha fallat replace_url', 'ERROR');
            return false;
        }

        // Upgrade WordPress
        $this->output('Actualitza el WordPress');
        return $this->execute_suboperation('upgrade');
    }

    private function execute_sql($sql): bool {
        global $wpdb;

        $wpdb->hide_errors();
        if (is_wp_error($wpdb->query($sql))) {
            $wpdb->print_error();
            return false;
        }
        $wpdb->show_errors();

        return true;
    }

}
