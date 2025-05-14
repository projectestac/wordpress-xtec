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
            'dbFile' => '',
            'dataFile' => '',
        ];
    }

    protected function _execute($params = []) {
        global $agora, $wpdb;

        // Get the params
        $clientName = $params['clientName'];
        $clientAddress = $params['clientAddress'];
        $clientPCCity = $params['clientPC'] . ' ' . $params['clientCity']; // Post Code and City
        $adminMail = $params['clientCode'] . '@xtec.cat';
        $dbFile = $params['dbFile'];
        $dataFile = $params['dataFile'];

        // We extract slow functions from InstanceController->activateInstance to put them in an asynchronous job
        if ($this->createDatabaseFromBaseFile($dbFile, $params['origin_bd'])){
            $this->output('Created database from file: ' . $dbFile);
        }

        else {
            $this->output('Error creating database from file: ' . $dbFile, 'ERROR');
            return false;
        }

        if ($this->unzipBaseFiles($dataFile)){
            $this->output('Unzipped base files: ' . $dataFile);
        }

        else {
            $this->output('Error unzipping base files: ' . $dataFile, 'ERROR');
            return false;
        }

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
                'user_registered' => date('Y-m-d H:i:s')
            ],
            [
                'ID' => $user->ID
            ]
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

    private function createDatabaseFromBaseFile(string $dbFile, string $dbName): bool {

        global $wpdb;

        // Temporary variable, used to store the current query.
        $currentSQL = '';

        // Check if the file exists.
        if (!file_exists($dbFile)) {
            echo 'Error: Database file does not exist: ' . $dbFile;
            return false;
        }

        // Create the database if it doesn't exist.
        $this->wpdb->statement("CREATE DATABASE IF NOT EXISTS $dbName");
        if ($this->wpdb->error) {
            echo 'Error creating database: ' . $this->wpdb->error;
            return false;
        }

        // Select the database.
        $wpdb->select($dbName);

        // Read the entire file.
        $lines = file($dbFile);

        // Loop through each line.
        foreach ($lines as $line) {

            // Skip it if it's a comment or an empty line.
            if ($line === '' || $line === "\n" || str_starts_with($line, '--') || str_starts_with($line, '/*!') || str_starts_with($line, '#')) {
                continue;
            }

            // Add this line to the current segment.
            $currentSQL .= $line;

            // Detection of sentences. If it has a semicolon at the end, it's the end of the query.
            $executeQuery = str_ends_with(trim($line), ';');

            // Note: this script is not able to create the database. It must previously exist.
            if ($executeQuery) {
                try {
                    $result = $wpdb->query($currentSQL);
                    if ($result === false) {
                        echo 'Error importing database file: ' . $wpdb->last_error;
                        return false;
                    }
                } catch (Throwable $e) {
                    echo 'Error importing database file: ' . $e->getMessage();
                    return false;
                }
                // Reset temp variable to empty.
                $currentSQL = '';
            }

        }

        return true;

    }

    private function unzipBaseFiles(string $dataFile): bool {

        $serviceKey = 'nodes';
        $dataDir = Util::getAgoraVar('nodesdata');

        $messages = [];

        // Directory for the new site files
        $dbName = config("app.agora.$serviceKey.userprefix") . $instanceId;
        $targetDir = $dataDir . $dbName . '/';

        // If the directory doesn't exist, create it.
        if (!is_dir($targetDir)) {
            if (mkdir($targetDir, 0777, true) || is_dir($targetDir)) {
                $messages[] = __('instances.dir_created', ['dir' => $targetDir]);
            } else {
                return ['error' => __('instances.dir_not_created', ['dir' => $targetDir])];
            }
        }

        // Extract the files.
        $zip = new ZipArchive();

        $resource = $zip->open($dataFile);
        if (!$resource) {
            return ['error' => __('instances.file_not_opened', ['file' => $dataFile])];
        }

        // Try to extract the file.
        if (!$zip->extractTo($targetDir)) {
            $zip->close();
            return ['error' => __('instances.unzip_error', ['file' => $dataFile, 'dir' => $targetDir])];
        }

        $zip->close();

        $messages[] = __('instances.unzip_success', ['file' => $dataFile, 'dir' => $targetDir]);

        return ['success' => $messages];

    }

}
