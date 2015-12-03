<?php

require_once('agora_script_base.class.php');

class script_replace_url extends agora_script_base {

    public $title = 'Reemplaça la URL base d\'Àgora-Nodes';
    public $info = "
    <ul>
    <li>add_ccentre és un booleà que defineix si a la origin_url se li afegeix el nom del centre al final. (útil quan es més d'un centre a l'hora)
    <li>La URL ha d'acabar amb / al final
    <li>La URL de destí sempre serà WP_SITEURL (del centre en particular)
    <li>Si la URL o la BD són buides no farà el replace de URL o DB respectivament
    <li>La BD de destí sempre serà DB_NAME
    </ul>
    Exemple:
    <ul><li>origin_url = ://agora/agora/
    <li>origin_bd = DB-int
    <li>add_ccentre = 1</ul>
    ";

    public function params() {
        $params = array();
        $params['origin_url'] = false;
        $params['origin_bd'] = false;
        $params['add_ccentre'] = false;
        return $params;
    }

    protected function _execute($params = array()) {
        global $wpdb;

        // If this is specified, only replace URLs
        if ($params['origin_url']) {
            $params['origin_url'] = str_replace('http://', '://', $params['origin_url']);
            $params['origin_url'] = str_replace('https://', '://', $params['origin_url']);
            $replaceURL = trim($params['origin_url']);
            if ($params['add_ccentre']) {
                $replaceURL .= CENTRE.'/';
            }
            $this->output("URL origen: ".$replaceURL);
        } else {
            $replaceURL = false;
        }

        $siteURL = WP_SITEURL;
        $siteURL = str_replace('http://', '://', $siteURL);
        $siteURL = str_replace('https://', '://', $siteURL);

        // Si són iguals no cal reemplaçar res
        if ($replaceURL == $siteURL) {
            $replaceURL = false;
        }

        $this->output("URL destí: ".$siteURL);

        if ($params['origin_bd']) {
            $replaceDB = trim($params['origin_bd']);
            // Si són iguals no cal reemplaçar res
            if ($replaceDB == DB_NAME) {
                $replaceDB = false;
            } else {
                $this->output("DB origen: " . $replaceDB);
                $this->output("DB destí: " . DB_NAME);
            }
        } else {
            $replaceDB = false;
        }



        update_option('siteurl', WP_SITEURL);
        update_option('home', WP_SITEURL);
        update_option('wsl_settings_redirect_url', WP_SITEURL);

        $replace = array('bp_activity' => array('action' => false,
                                                'content' => false,
                                                'primary_link' => false),
                        'posts' => array('post_content' => false,
                                        'post_excerpt' => false,
                                        'guid' => false),
                        'term_taxonomy' => array('description' => false),
                        'postmeta' => array('meta_value' => "meta_key = '_menu_item_url'")
                    );
        foreach ($replace as $tablename => $fields) {
            foreach ($fields as $fieldname => $and) {
                if ($replaceURL) {
                    if (!$this->replace_sql($tablename, $fieldname, $replaceURL, $siteURL, $and)) {
                        return false;
                    }
                }

                if ($replaceDB) {
                    if (!$this->replace_sql($tablename, $fieldname, '/' . $replaceDB . '/', '/' . DB_NAME . '/')) {
                        return false;
                    }
                }
            }
        }

        if ($replaceDB) {
            $replace = array('bp_activity' => array('content'),
                            'posts' => array('post_content', 'guid')
                        );
            foreach ($replace as $tablename => $fields) {
                foreach ($fields as $fieldname) {
                    if (!$this->replace_sql($tablename, $fieldname, '/'.$replaceDB.'/', '/'.DB_NAME.'/')) {
                        return false;
                    }
                }
            }
        }

        echo "Update serialized wp_options fields\n";
        $options = array ('my_option_name', 'widget_text', 'reactor_options', 'widget_socialmedia_widget', 'widget_xtec_widget', 'widget_grup_classe_widget');
        foreach ($options as $option) {
            $value = get_option($option);

            // Update URL recursively
            if ($replaceURL) {
                $value = $this->replaceTree($replaceURL, $siteURL, $value);
            }

            // Update user database recursively
            if ($replaceDB) {
                $value = $this->replaceTree('/'.$replaceDB.'/', '/'.DB_NAME.'/', $value);
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

        $tablename = $wpdb->prefix.$table;
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

        $newArray = array();
        foreach ($array as $k => $v) {
            // Recursive call
            $newArray[$k] = $this->replaceTree($search, $replace, $v);
        }

        return $newArray;
    }

    /**
     * Checks if a value is serialized
     * @author Toni Ginard
     * @return boolean true or false
     */
    private function is_serialized($data) {
        $data_unserialized = @unserialize($data);
        return ($data === 'b:0;' || $data_unserialized !== false);
    }
}
