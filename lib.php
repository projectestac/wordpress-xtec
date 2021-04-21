<?php

/*
 * This file contains extra functions for Agora and XTECBlocs services
 */

CONST XTECADMIN_USERNAME = 'xtecadmin';
CONST ADMIN_USERNAME = 'admin';


/**
 * Decide if current installation is XTECBlocs or not
 *
 * @return boolean
 */
function is_xtecblocs() {
    global $isBlocs;
    return $isBlocs;
}

/**
 * Decide if current installation is Àgora or not
 *
 * @return boolean
 */
function is_agora() {
    global $isAgora;
    return $isAgora;
}

/**
 * Decide if user has full access. In Àgora must be xtecadmin and
 * in XTECBlocs must be network admin.
 *
 * @return boolean
 */
function is_xtec_super_admin() {
    return is_xtecadmin() || is_blocsadmin();
}

/*
 * Check if current logged user is Agora xtecadmin
 */
function is_xtecadmin() {
    global $current_user, $isAgora;
    return $isAgora && isset($current_user->user_login) && ($current_user->user_login == 'xtecadmin');
}

/*
 * Check if current logged user is blocs admin
 */
function is_blocsadmin() {
    global $isBlocs;
    return $isBlocs && is_super_admin();
}

/*
 * Get the ID of xtecadmin user.
 * Deprecated. Use constant instead of this.
 *
 * return int ID of xtecadmin
 */
function get_xtecadmin_id() {

    return get_user_by('login', 'xtecadmin')->ID;
}

/*
 * Get the username of xtecadmin
 *
 * return string username of xtecadmin
 */
function get_xtecadmin_username() {

    return 'xtecadmin';
}

/**
 * Collect basic statistical and security information.
 *
 * @throws Exception
 */
function save_stats() {

    global $current_user, $table_prefix, $wpdb;

    $table = $table_prefix . 'stats';

    // Get the local timestamp
    $dt = new DateTime('now', new DateTimeZone(get_option('timezone_string')));
    $dt->setTimestamp(time());
    $datetime = $dt->format('Y-m-d H:i:s');

    $ip = $ipForward = $ipClient = $userAgent = $uri = '';

    // Usage of filter_input() guarantees that info is clean
    if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING);
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipForward = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING);
    }

    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipClient = filter_input(INPUT_SERVER, 'HTTP_CLIENT_IP', FILTER_SANITIZE_STRING);
    }

    if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING);
    }

    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING);
        if (strrpos($uri, 'admin-ajax.php') && $_REQUEST['action']!=null) {
            // Added action information to ajax callbacks
            $uri .='?action='.$_REQUEST['action'];
        }
    }

    $uid = $current_user->ID;
    $username = $current_user->user_login;
    $email = $current_user->user_email;

    $isadmin = current_user_can('manage_options');

    $content = NULL;
    $action = array_key_exists('action', $_REQUEST)?$_REQUEST['action']:'';
    // Save additional information in some cases
    switch ($action) {
        case 'delete_activity':
        case 'delete_activity_comment':
            // Deleting bp-activity
            $result = array( 'id' => $_REQUEST['id']);
            $query = "
                    SELECT content FROM $table_prefix" . "bp_activity
                    WHERE id='".$result['id']."'
                    ";
            $result['content'] = $wpdb->get_var($query);
            $content = var_export($result, true);
            break;

        case 'bbp-edit-topic':
            // Editing bp-forum topic
            if (!$_REQUEST['bbp_log_topic_edit']) {
                // Only save information on stats table if log button is disabled
                $result = array( 'id' => $_REQUEST['bbp_topic_id']);
                $query = "
                        SELECT post_content, post_title FROM $table_prefix" . "posts
                        WHERE id=".$result['id']."
                        ";
                $tmp_result = $wpdb->get_results($query);
                $result['old_content'] = $tmp_result[0]->post_content;
                $result['old_title'] = $tmp_result[0]->post_title;
                $content = var_export($result, true);
            }
            break;
        case 'bbp-edit-reply':
            // Editing bp-forum reply
            if (!$_REQUEST['bbp_log_reply_edit']) {
                // Only save information on stats table if log button is disabled
                $result = array( 'id' => $_REQUEST['bbp_reply_id']);
                $query = "
                        SELECT post_content FROM $table_prefix" . "posts
                        WHERE id=".$result['id']."
                        ";
                $result['old_content'] = $wpdb->get_var($query);
                $content = var_export($result, true);
            }
            break;
    }

    $data = array(
        'datetime' => $datetime,
        'ip' => $ip,
        'ipForward' => $ipForward,
        'ipClient' => $ipClient,
        'userAgent' => $userAgent,
        'uri' => $uri,
        'uid' => $uid,
        'isadmin' => $isadmin,
        'username' => $username,
        'email' => $email
    );
    $fields = array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s');

    if (!is_null($content)) {
        $data['content'] = $content;
        $fields[] = '%s';
    }
    $wpdb->insert($table, $data, $fields);

    // Update number of visits in wp_options. Exclude cron and Heartbeat actions
    if ((strpos($uri, 'wp-cron') === false) && (strpos($uri, 'wp-admin') === false)) {

        $visits = get_option('xtec-stats-visits');
        $include_admin = get_option('xtec-stats-include-admin');

        // If option is not set yet, creates it and set initial value
        if ($visits === false) {
            // Get initial value
            global $wpdb;
            $query = "
                SELECT count(*) AS Total FROM $table_prefix" . "stats
				WHERE uri NOT LIKE('%wp-cron%')
				AND uri NOT LIKE('%wp-admin%')
                ";
            $result = $wpdb->get_results($query);
            $total = $result[0]->Total;

            // Add options to table
            add_option('xtec-stats-visits', $total);
            if ($include_admin === false) {
                add_option('xtec-stats-include-admin', 'on');
            }
        } else {
            if (!$isadmin || ($isadmin && $include_admin == 'on')) {
                // Increase the number of visits by 1
                update_option('xtec-stats-visits', (int) $visits + 1);
            }
        }
    }
}

/**
 * This action is called from the WordPress cron (it's supposed to be programmed). Remove all wp_stats content
 *  older that one year and the visits of the search robots older than two months.
 *
 * @global object $wpdb
 * @author Toni Ginard
 */
function remove_old_stats() {
    global $wpdb;

    $time = strtotime("-1 year", time());
    $datetime = date('Y-m-d H:i:s', $time);

    $table = $wpdb->prefix . 'stats';
    $wpdb->query( "DELETE FROM `$table` WHERE datetime < '$datetime' ");

    $time = strtotime("-2 month", time());
    $datetime = date('Y-m-d H:i:s', $time);
    $search_bots = array (
        'Baidu',
        'Googlebot',
        'Yahoo',
        'bingbot',
        'YandexBot',
        'GrapeshotCrawler',
        'DotBot',
        'Gecko/20100101 Firefox/6.0.2'
    );
    $where = "WHERE datetime < '$datetime' AND (`userAgent` like '%" . implode( '%\' or `userAgent` like \'%', $search_bots ) . "%')";

    $wpdb->query( "DELETE FROM `$table` $where");
}

function parse_cli_args() {
    global $cliargs;
    $cliargs = array();
    $rawoptions = $_SERVER['argv'];

    if (($key = array_search('--', $rawoptions)) !== false) {
        $rawoptions = array_slice($rawoptions, 0, $key);
    }

    unset($rawoptions[0]);
    foreach ($rawoptions as $raw) {
        if (substr($raw, 0, 2) === '--') {
            $value = substr($raw, 2);
            $parts = explode('=', $value);
            if (count($parts) == 1) {
                $key   = reset($parts);
                $value = true;
            } else {
                $key = array_shift($parts);
                $value = implode('=', $parts);
                $value = str_replace("\\'", "'", $value);
            }
            $cliargs[$key] = $value;

        } else if (substr($raw, 0, 1) === '-') {
            $value = substr($raw, 1);
            $parts = explode('=', $value);
            if (count($parts) == 1) {
                $key   = reset($parts);
                $value = true;
            } else {
                $key = array_shift($parts);
                $value = implode('=', $parts);
                $value = str_replace("\\'", "'", $value);
            }
            $cliargs[$key] = $value;
        }
    }
}

function get_cli_arg($arg){
    global $cliargs;
    if (empty($cliargs)) {
        parse_cli_args();
    }
    if (isset($cliargs[$arg])) {
        return $cliargs[$arg];
    }
    return false;
}