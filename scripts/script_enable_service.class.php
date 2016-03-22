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

        $params['origin_url'] = "";
        $params['origin_bd'] = "";

        return $params;
    }

    protected function _execute($params = array()) {
        global $agora, $wpdb;

        // Get the params
        $clientName = $params['clientName'];
        $clientAddress = $params['clientAddress'];
        $clientPCCity = $params['clientPC'] . ' ' . $params['clientCity']; // Post Code and City
        $adminMail = $params['clientCode'] . '@xtec.cat';

        $this->output("Set Blog name $clientName");
        update_option('blogname', $clientName);
        update_option('nodesbox_name', $clientName);

        $this->output('Set Admin mail');
        update_option('admin_email', $adminMail);

        $this->output("Set Site URL");
        update_option('siteurl', WP_SITEURL);
        update_option('home', WP_SITEURL);
        update_option('wsl_settings_redirect_url', WP_SITEURL);

        $this->output('Update school name and address');
        $value = get_option('reactor_options');
        $value['nomCanonicCentre'] = $clientName;
        $value['direccioCentre'] = $clientAddress;
        $value['cpCentre'] = $clientPCCity;
        $value['nomCanonicCentre'] = $clientName;
        update_option('reactor_options', $value);

        $this->output('Configuring admin and xtecadmin users');
        $user = get_user_by('login', 'admin');
        $user_id = wp_update_user(array(
            'ID' => $user->id,
            'user_email' => $adminMail,
            'user_registered' => time()
        ));
        if (is_wp_error($user_id)) {
            $this->output('Error actualitzant usuari admin', 'ERROR');
            return false;
        }
        $wpdb->update($wpdb->users, array('user_pass' => $params['password']), array('ID' => $user->id));

        $user = get_user_by('login', 'xtecadmin');
        $user_id = wp_update_user(array(
            'ID' => $user->id,
            'user_email' => $agora['xtecadmin']['mail']
        ));
        if (is_wp_error($user_id)) {
            $this->output('Error actualitzant usuari xtecadmin', 'ERROR');
            return false;
        }
        $wpdb->update($wpdb->users, array('user_pass' => $agora['xtecadmin']['password']), array('ID' => $user->id));

        // Email Subscribers
        $table_name = $wpdb->prefix . 'es_pluginconfig';
        $var = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) { // Check for table existence
            $this->output('Set admin e-mail in Email Subscribers');
            $wpdb->update(
                    $wpdb->prefix . 'es_pluginconfig', 
                    array('es_c_fromemail' => $adminMail, 'es_c_adminemail' => $adminMail), 
                    array('es_c_id' => 1)
            );
            $wpdb->update(
                    $wpdb->prefix . 'es_emaillist', 
                    array('es_email_mail' => $adminMail), 
                    array('es_email_name' => 'Admin')
            );

            $this->output('Set blog name in Email Subscribers');
            $blogname = get_option('blogname');
            $fields_to_replace = array('es_c_adminmailsubject', 'es_c_adminmailcontant', 'es_c_usermailsubject', 'es_c_usermailcontant', 'es_c_optinsubject', 'es_c_optincontent');
            foreach ($fields_to_replace as $field) {
                if (!$this->replace_sql('es_pluginconfig', $field, 'Màster Serveis Educatius', $blogname)) {
                    return false;
                }
            }
        }

        $this->output('Reset stats table');
        if (!$this->execute_sql('TRUNCATE ' . $wpdb->prefix . 'stats')) {
            $this->output('Error buidant la taula stats', 'ERROR');
            return false;
        }

        $success = $this->execute_suboperation('replace_url', array(
                'origin_url' => $params['origin_url'],
                'origin_bd' => $params['origin_bd']));

        if (!$success) {
            $this->output('Ha fallat replace_url', 'ERROR');
            return false;
        }

        // Upgrade WordPress
        $this->output('Actualitza el WordPress');
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

    private function replace_sql($table, $field, $search, $replace) {
        global $wpdb;

        if (empty($search) || empty($replace)) {
            return true;
        }

        $tablename = $wpdb->prefix . $table;
        $sql = "UPDATE $tablename SET `$field` = REPLACE (`$field` , '$search', '$replace')
                WHERE `$field` like '%$search%'";

        if (!$this->execute_sql($sql)) {
            return false;
        }

        return true;
    }

}
