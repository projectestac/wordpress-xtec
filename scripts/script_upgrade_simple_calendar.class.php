<?php

require_once('agora_script_base.class.php');

class script_upgrade_simple_calendar extends agora_script_base
{

    public $title = 'Actualitza l\'extensió Simple Calendar';
    public $info = 'Executa la funció d\'actualització del Simple Calendar (google-calendar-events/includes/update.php -> Update::run_updates())';

    public function params() {
        return array();
    }

    protected function _execute($params = array()) {

        // Execute standard update
        $update = new \SimpleCalendar\Update();
        $update->run_updates();

        // Extra custom update: 1. Fix post meta data, 2. Update templates to show events
        $posts = get_posts( array(
            'post_type'   => 'calendar',
            'post_status' => array(
                'draft',
                'future',
                'publish',
                'pending',
                'private',
                'trash',
            ),
            'nopaging'    => true,
        ) );

        $meta_to_update = array(
            '_feed_earliest_event_date' => 'months_before',
            '_feed_latest_event_date' => 'years_after',
            '_calendar_version' => '3.1.2',
            '_calendar_begins_nth' => '1',
            '_calendar_begins_custom_date' => '',
            '_calendar_is_static' => 'no',
            '_no_events_message' => '',
            '_event_formatting' => 'preserve_linebreaks',
            '_poweredby' => 'no',
            '_feed_timezone_setting' => 'use_site',
            '_feed_timezone' => 'Europe/Madrid',
            '_calendar_date_format' => 'l, d F Y',
            '_calendar_date_format_php' => 'd/m/Y',
            '_calendar_time_format' => 'G:i a',
            '_calendar_time_format_php' => 'G:i',
            '_calendar_week_starts_on' => '0',
            '_google_events_search_query' => '',
            '_grouped_calendars_source' => 'ids',
            '_grouped_calendars_ids' => '',
            '_grouped_calendars_category' => '',
            '_default_calendar_style_theme' => 'light',
            '_default_calendar_style_today' => '#1e73be',
            '_default_calendar_style_days_events' => '#000000',
            '_default_calendar_list_header' => 'no',
            '_default_calendar_compact_list' => 'no',
            '_default_calendar_limit_visible_events' => 'no',
            '_default_calendar_visible_events' => '3',
            '_default_calendar_trim_titles' => 'no',
            '_default_calendar_trim_titles_chars' => '20'
        );

        foreach ( $posts as $post ) {
            // Updates the templates to show the events
            $post->post_content = '<strong>[title]</strong><br/>[when]<br/>[location]<br/><div>[description]</div><br/>[link newwindow="yes"]Més detalls...[/link]';
            wp_update_post( $post );

            // Adds or updates the meta data
            foreach ( $meta_to_update as $key => $value ) {
                update_post_meta( $post->ID, $key, $value );
            }
        }

        $this->output( 'Simple Calendar upgrade completed' );

        return true;
    }

}
