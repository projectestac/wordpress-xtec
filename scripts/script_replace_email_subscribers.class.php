<?php

require_once('agora_script_base.class.php');

class script_replace_email_subscribers extends agora_script_base
{
    
    public $title = 'Corregeix el nom del centre a l\'Email Subscribers';
    public $info = 'Revisa els textos de les taules de l\'Email Subscribers i canvia el nom i l\'adreça de correu del centre si no és correcta';
    
    public function params()
    {
        $params = array();
        return $params;
    }
    
    private function replace_wp_options_email_subscribers()
    {
        global $wpdb;
        
        $table_fields = array(
            'ig_es_optin_link',
            'ig_es_unsublink',
            'ig_es_unsubscribe_link',
            'ig_es_cronurl'
        );
        
        // Email Subscribers
        $table_name = $wpdb->prefix . 'options';
        
        foreach ($table_fields as $field) {
            $row = $wpdb->get_var("SELECT `option_value` FROM ".$table_name." WHERE `option_name` ='" . $field . "'");
            
            if (isset($row) && (!empty($row))) {
                $parts = explode("?", $row);
                $new_url = WP_SITEURL . '?' . $parts[1];
    
                if (substr($new_url, 0, 7) === "http://") {
                    $new_url = preg_replace("/^http:/i", "https:", $new_url);
                }
    
                if (!$this->replace_sql('options', 'option_value', $row, $new_url)) {
                    $this->output('Error replacing values in table ' . $table_fields);
                    return false;
                }
            }else {
                $this->output('Field ' . $field. ' not exists');
            }
        }
    }
    
    private function checkCronURL()
    {
        global $wpdb;

        $cronurl = $wpdb->get_var("SELECT `option_value` FROM wp_options WHERE `option_name` ='ig_es_cronurl'");
        $siteurl = $wpdb->get_var("SELECT `option_value` FROM wp_options WHERE `option_name` ='siteurl'");

        $originalUrl = basename($siteurl);
        $oldUrl = array_slice(explode('/', rtrim($cronurl, '/')), -2)[0];

        if ($originalUrl != $oldUrl ) {
            $newCronUrl = str_replace($oldUrl, $originalUrl, $cronurl);

            if (!$this->replace_sql('options', 'option_value', $cronurl, $newCronUrl)) {
                $this->output('Error replacing cron value');
                return false;
            }
        }
    }

    protected function _execute($params = array())
    {
        global $wpdb;
        
        $this->replace_wp_options_email_subscribers();
        $this->checkCronURL();
        
        $adminMail = (isset($params['adminMail'])) ? $params['adminMail'] : '';
        $table_name = $wpdb->prefix . 'es_pluginconfig';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) { // Check for table existence
            if (!empty($adminMail)) {
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
            }
            
            $this->output('Set blog name in Email Subscribers');
            
            $blogname = get_option('blogname');
            $es_c_adminmailsubject = $wpdb->get_var("SELECT es_c_adminmailsubject FROM $table_name LIMIT 1");
            $es_c_usermailsubject = $wpdb->get_var("SELECT es_c_usermailsubject FROM $table_name LIMIT 1");
            $common_beginning = $this->get_common_substring($es_c_adminmailsubject, $es_c_usermailsubject);
            
            $fields_to_replace = array(
                'es_c_adminmailsubject',
                'es_c_adminmailcontant',
                'es_c_usermailsubject',
                'es_c_usermailcontant',
                'es_c_optinsubject',
                'es_c_optincontent',
                'es_c_unsubtext'
            );
            foreach ($fields_to_replace as $field) {
                if (!$this->replace_sql('es_pluginconfig', $field, $common_beginning, $blogname)) {
                    return false;
                }
            }
            
            $this->output('Replaced ' . $common_beginning . ' by ' . $blogname . ' in Email Subscribers');
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
    
    /**
     * Compare two strings and return the common beginning. If it is nothing in common, returns false
     *
     * @param $str_to_cmp1
     * @param $str_to_cmp2
     * @return bool|string
     */
    private function get_common_substring ($str_to_cmp1, $str_to_cmp2) {
        
        // Loop to the length of the first string
        $i = 0;
        while ($i < strlen($str_to_cmp1)) {
            
            // Compare the substrings
            if (substr($str_to_cmp1, 0, $i) != substr($str_to_cmp2, 0, $i)) {
                // Didn't match, return the part that did match
                if (0 == $i) {
                    return false;
                } else {
                    // Exclude ending blank space (just one)
                    if (($i > 1) && (substr($str_to_cmp1, $i-2, 1) == ' ')) {
                        return substr($str_to_cmp1, 0, $i-2);
                    } else {
                        return substr($str_to_cmp1, 0, $i-1);
                    }
                }
            }
            
            // Next character
            $i++;
        }
        
        // If it gets here, it means that the whole of the first string is in common
        return $str_to_cmp1;
    }
}