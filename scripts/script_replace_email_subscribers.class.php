<?php

require_once('agora_script_base.class.php');

class script_replace_email_subscribers extends agora_script_base {
    public $title = 'Corregeix els paràmetres de l\'Email Subscribers';
    public $info = 'A la taula wp_options, revisa els URL de l\'Email Subscribers i els textos, tot modificant el nom i l\'adreça de correu del centre si no és correcta';

    public function params() {
        $params = [];
        return $params;
    }

    protected function _execute($params = []) {
        $this->replace_urls();
        $this->replace_mail_strings();

        return true;
    }

    private function replace_urls() {

        /* Fields that consist, exactly, in a URL */
        $fields_to_replace = [
            'ig_es_optin_link',
            'ig_es_optinlink',
            'ig_es_unsublink',
            'ig_es_unsubscribe_link',
            'ig_es_cronurl',
        ];

        foreach ($fields_to_replace as $field) {

            $row = get_option($field);

            if (isset($row) && (!empty($row))) {
                $parts   = explode("?", $row);
                $new_url = WP_SITEURL . '?' . $parts[1];

                if (substr($new_url, 0, 7) === "http://") {
                    $new_url = preg_replace("/^http:/i", "https:", $new_url);
                }

                if (!$this->replace_sql('options', 'option_value', $row, $new_url)) {
                    $this->output('Error updating field: ' . $field);
                } else {
                    $this->output('Field updated: ' . $field);
                }
            } else {
                $this->output('Field ' . $field . ' does not exist');
            }
        }
    }

    private function replace_mail_strings() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'options';

        $blogname = get_option('blogname');

        $es_c_adminmailsubject = $wpdb->get_var("SELECT `option_value` FROM $table_name WHERE `option_name` = 'ig_es_welcomesubject'");
        $es_c_usermailsubject  = $wpdb->get_var("SELECT `option_value` FROM $table_name WHERE `option_name` = 'ig_es_confirmsubject'");
        $common_beginning      = $this->get_common_substring($es_c_adminmailsubject, $es_c_usermailsubject);

        /* Fields that begin with the blog name. Set the beginning as [blog name] */
        $fields_to_replace = [
            'ig_es_admin_new_sub_subject',
            'ig_es_welcomesubject',
            'ig_es_confirmsubject',
            'ig_es_admin_new_contact_email_subject',
            'ig_es_confirmation_mail_subject',
            'ig_es_welcome_email_subject',
            'acui_mail_subject',
        ];

        foreach ($fields_to_replace as $field) {
            $this->execute_sql("UPDATE `wp_options` SET `option_value` = REPLACE(`option_value`, '$common_beginning', '[$blogname] ') WHERE `option_name`='$field'");
            $this->output('Field updated: ' . $field);
        }

        /* Fields that contain the blog name. Just replace it */
        $fields_to_replace = [
            'ig_es_admin_new_contact_email_content',
            'ig_es_admin_new_sub_content',
            'ig_es_confirmation_mail_content',
            'ig_es_confirmcontent',
            'ig_es_welcome_email_content',
            'ig_es_welcomecontent',
        ];

        foreach ($fields_to_replace as $field) {
            $this->execute_sql("UPDATE `wp_options` SET `option_value` = REPLACE(`option_value`, '$common_beginning', '$blogname') WHERE `option_name`='$field'");
            $this->output('Field updated: ' . $field);
        }

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
        $sql       = "UPDATE $tablename SET `$field` = REPLACE (`$field` , '$search', '$replace') WHERE `$field` like '%$search%'";

        if ($and) {
            $sql .= " AND $and ";
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
     *
     * @return bool|string
     */
    private function get_common_substring($str_to_cmp1, $str_to_cmp2) {

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
                    if (($i > 1) && (substr($str_to_cmp1, $i - 2, 1) == ' ')) {
                        return substr($str_to_cmp1, 0, $i - 2);
                    } else {
                        return substr($str_to_cmp1, 0, $i - 1);
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