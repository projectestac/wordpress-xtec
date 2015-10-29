<?php

require_once('agora_script_base.class.php');

class script_enable_service extends agora_script_base {

    public $title = 'Activa el servei Àgora-Nodes';
    public $info = "Fa els passos necessàris per activar Wordpress i deixar-lo a punt per començar";

    public function params() {
        $params = array();
        $params['password'] = ""; //Admin password en md5
        $params['clientName'] = "";
        $params['clientAddress'] = "";
        $params['clientCity'] = "";
        $params['clientPC'] = ""; // Postal Code
        $params['clientDNS'] = ""; // Not used
        $params['clientCode'] = "";

        $params['URLNodesModelBase'] = "";
        $params['shortcodes'] = "";
        $params['DBNodesModel'] = "";

        return $params;
    }

    protected function _execute($params = array()) {
        global $agora, $wpdb;

        // Get the params
        $clientName = $params['clientName'];
        $clientAddress = $params['clientAddress'];
        $clientPCCity = $params['clientPC'] . ' ' . $params['clientCity']; // Post Code and City
        $adminMail = $params['clientCode'] . '@xtec.cat';

        echo "Set Blog name\n";
        update_option('blogname', $clientName);
        update_option('nodesbox_name', $clientName);
        // Don't change default blog description
        //update_option('blogdescription', 'Espai del centre ' . $clientName);

        echo "Set Admin mail\n";
        update_option('admin_email', $adminMail);

        echo "Set Site URL\n";
        update_option('siteurl', WP_SITEURL);
        update_option('home', WP_SITEURL);
        update_option('wsl_settings_redirect_url', WP_SITEURL);

        echo "Update school name and address\n";
        $value = get_option('reactor_options');
        $value['nomCanonicCentre'] = $clientName;
        $value['direccioCentre'] = $clientAddress;
        $value['cpCentre'] = $clientPCCity;
        $value['nomCanonicCentre'] = $clientName;
        update_option('reactor_options', $value);

        echo "Configure admin and xtecadmin users\n";
        $user = get_user_by('login', 'admin');
        $user_id = wp_update_user(array(
            'ID' => $user->id,
            'user_email' => $adminMail,
            'user_registered' => time()
        ));
        if ( is_wp_error( $user_id ) ) {
            echo 'Error actualitzant usuari admin';
            return false;
        }
        $wpdb->update($wpdb->users, array('user_pass' => $params['password']), array('ID' => $user->id) );

        $user = get_user_by('login', 'xtecadmin');
        $user_id = wp_update_user(array(
            'ID' => $user->id,
            'user_email' => $agora['xtecadmin']['mail']
        ));
        if ( is_wp_error( $user_id ) ) {
            echo 'Error actualitzant usuari xtecadmin';
            return false;
        }
        $wpdb->update($wpdb->users, array('user_pass' => $agora['xtecadmin']['password']), array('ID' => $user->id) );

        echo "Reset stats table\n";
        if (!$this->execute_sql('TRUNCATE '.$wpdb->prefix.'stats')) {
            return false;
        }

        //Time to replace URLs and Database
        $urlModelBase = $params['URLNodesModelBase'];
        $urlModelBase = str_replace('http://', '://', $urlModelBase);
        $urlModelBase = str_replace('https://', '://', $urlModelBase);

        $shortcodes = explode(',', $params['shortcodes']);
        $shortcodes = array_map('trim', $shortcodes);
        $replaceURL = array();
        foreach ($shortcodes as $scode) {
            $replaceURL[] = $urlModelBase . $scode . '/';
        }

        $success = $this->execute_suboperation('replace_url', array(
                'origin_url' => implode(',', $replaceURL),
                'origin_bd' => $params['DBNodesModel']));

        if (!$success) {
            echo "Ha fallat replace_url\n";
            return false;
        }

        // Upgrade WordPress
        return $this->execute_suboperation('upgrade');
    }

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
}
