<?php

require_once('agora_script_base.class.php');

class script_replace_url extends agora_script_base {

    public $title = 'Reemplaça la URL base d\'Àgora-Nodes';
    public $info = "
    <ul>
    <li>add_ccentre és un booleà que defineix si a la origin_url se li afegeix el nom del centre al final. (Útil quan es més d'un centre alhora)</li>
    <li>La URL ha d'acabar amb / al final</li>
    <li>La URL de destí sempre serà WP_SITEURL (del centre en particular)</li>
    <li>Si la URL o la BD són buides no farà el replace de URL o DB respectivament</li>
    <li>La BD de destí sempre serà DB_NAME</li>
    <li>origin_bd només s'ha d'especificar si canvia la base de dades (activedId)</li>
    </ul>
    Exemple:
    <ul><li>origin_url = ://agora/agora/
    <li>origin_bd = DB-int
    <li>add_ccentre = 1</ul>
    ";

    public function params(): array {
        return [
            'origin_url' => false,
            'origin_bd' => false,
            'add_ccentre' => false,
        ];
    }

    protected function _execute($params = []) {
        global $wpdb;

        // If this is specified, only replace URLs
        if ($params['origin_url']) {
            $params['origin_url'] = str_replace('http://', '://', $params['origin_url']);
            $params['origin_url'] = str_replace('https://', '://', $params['origin_url']);
            $replaceURL = trim($params['origin_url']);

            // When called from CLI, $params['add_ccentre'] is not defined.
            if (isset($params['add_ccentre']) && $params['add_ccentre']) {
                $replaceURL .= CENTRE . '/';
            }

            $this->output("URL origen: " . $replaceURL);
        } else {
            $replaceURL = false;
        }

        $siteURL = WP_SITEURL;
        $siteURL = str_replace(['http://', 'https://'], '://', $siteURL);

        // Si són iguals no cal reemplaçar res
        if ($replaceURL === $siteURL) {
            $replaceURL = false;
        }

        $this->output('URL destí: ' . $siteURL);

        if ($params['origin_bd']) {
            $replaceDB = trim($params['origin_bd']);
            // Si són iguals no cal reemplaçar res
            if ($replaceDB === DB_NAME) {
                $replaceDB = false;
            } else {
                $this->output('DB origen: ' . $replaceDB);
                $this->output('DB destí: ' . DB_NAME);
            }
        } else {
            $replaceDB = false;
        }

        update_option('siteurl', WP_SITEURL);
        update_option('home', WP_SITEURL);
        update_option('wsl_settings_redirect_url', WP_SITEURL);

        $replace = [
            'bp_activity' => [
                'action' => false,
                'content' => false,
                'primary_link' => false,
            ],
            'posts' => [
                'post_content' => false,
                'post_excerpt' => false,
                'guid' => false,
            ],
            'term_taxonomy' => [
                'description' => false,
            ],
            'postmeta' => [
                'meta_value' => "meta_key = '_menu_item_url'",
            ],
        ];

        // Email Subscribers
        $table_name = $wpdb->prefix . 'es_pluginconfig';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) { // Check for table existence
            $replace['es_pluginconfig'] = ['es_c_optinlink' => false, 'es_c_unsublink' => false];
        }

        foreach ($replace as $tablename => $fields) {
            foreach ($fields as $fieldname => $and) {
                if ($replaceURL && !$this->replace_sql($tablename, $fieldname, $replaceURL, $siteURL, $and)) {
                    return false;
                }
                if ($replaceDB && !$this->replace_sql($tablename, $fieldname, '/' . $replaceDB . '/', '/' . DB_NAME . '/')) {
                    return false;
                }
            }
        }

        if ($replaceDB) {
            $replace = [
                'bp_activity' => ['content'],
                'posts' => ['post_content', 'guid'],
            ];

            foreach ($replace as $tablename => $fields) {
                foreach ($fields as $fieldname) {
                    if (!$this->replace_sql($tablename, $fieldname, '/' . $replaceDB . '/', '/' . DB_NAME . '/')) {
                        return false;
                    }
                }
            }
        }

        echo "Update serialized wp_options fields\n";
        $options = ['my_option_name', 'widget_text', 'reactor_options', 'widget_socialmedia_widget', 'widget_xtec_widget', 'widget_grup_classe_widget'];
        foreach ($options as $option) {
            $value = get_option($option);

            // Update URL recursively
            if ($replaceURL) {
                $value = $this->replaceTree($replaceURL, $siteURL, $value);
            }

            // Update user database recursively
            if ($replaceDB) {
                $value = $this->replaceTree('/' . $replaceDB . '/', '/' . DB_NAME . '/', $value);
            }

            update_option($option, $value);
        }

        if ($replaceURL) {
            echo "Update slides URLs\n";

            $rows = $wpdb->get_results("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'slides'");
            if ($rows) {
                foreach ($rows as $row) {
                    $value = $this->replaceTree($replaceURL, $siteURL, $row->meta_value);
                    $this->execute_sql("UPDATE $wpdb->postmeta set meta_value = '$value' WHERE meta_id = $row->meta_id;");
                }
            }
        }

        return true;
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

    private function replace_sql($table, $field, $search, $replace, $and = false) {
        global $wpdb;

        if (empty($search) || empty($replace)) {
            return true;
        }

        $tablename = $wpdb->prefix . $table;
        $sql = "UPDATE $tablename SET `$field` = REPLACE (`$field` , '$search', '$replace')
                WHERE `$field` like '%$search%'";
        if ($and) {
            $sql .= "AND $and";
        }
        if (!$this->execute_sql($sql)) {
            return false;
        }
        return true;
    }

    private function replaceTree($search = '', $replace = '', $array = false) {
        if (empty($search) || empty($replace)) {
            return $array;
        }

        if (!is_array($array)) {
            if ($this->is_serialized($array)) {
                $array = unserialize($array);
                $value = $this->replaceTree($search, $replace, $array);
                // Escape apostrophes for MySQL
                return str_replace("'", "''", serialize($value));
            }
            // Regular replace
            return str_replace($search, $replace, $array);
        }

        $newArray = [];
        foreach ($array as $k => $v) {
            // Recursive call
            $newArray[$k] = $this->replaceTree($search, $replace, $v);
        }

        return $newArray;
    }

    /**
     * Checks if a value is serialized
     *
     * @return boolean true or false
     * @author Toni Ginard
     */
    private function is_serialized($data) {
        $data_unserialized = @unserialize($data);
        return ($data === 'b:0;' || $data_unserialized !== false);
    }
}
