<?php

require_once 'agora_script_base.class.php';

class script_replace_domain_gencat extends agora_script_base {

    public $title = 'Actualitza URL amb domini Gencat';
    public $info = 'Revisa els registres de wp_posts i wp_options per reemplaçar el domini
                    aplicacions.ensenyament.gencat.cat per aplicacions.gestioeducativa.gencat.cat.';

    public const OLD_URL = '://aplicacions.ensenyament.gencat.cat/';
    public const NEW_URL = '://aplicacions.gestioeducativa.gencat.cat/';

    protected function _execute($params = []): bool {

        global $wpdb;

        // Step 1: Update wp_posts content where the old URL appears
        $posts = $wpdb->get_results("
            SELECT ID, post_content 
            FROM {$wpdb->posts} 
            WHERE post_content LIKE '%" . self::OLD_URL . "%'
        ");

        foreach ($posts as $post) {
            $new_content = str_replace(self::OLD_URL, self::NEW_URL, $post->post_content);

            // Only update if content has changed
            if ($new_content !== $post->post_content) {
                $wpdb->update(
                    $wpdb->posts,
                    ['post_content' => $new_content],
                    ['ID' => $post->ID]
                );
                echo "S'ha actualitzat el post amb ID {$post->ID}\n";
            }
        }

        // Step 2: Update 'widget_text' option (text/HTML widgets)
        $widget_types = [
            'widget_text' => 'text',
            'widget_custom_html' => 'content',
        ];

        foreach ($widget_types as $option_name => $field_key) {
            // Retrieve the widget option from the database
            $widget_option = $wpdb->get_row("
                SELECT option_id, option_value 
                FROM {$wpdb->options} 
                WHERE option_name = '{$option_name}'
            ");

            if ($widget_option) {
                // Unserialize the option value to get the widget array
                $widgets = maybe_unserialize($widget_option->option_value);
                $has_changes = false;

                foreach ($widgets as $key => &$widget) {
                    // Only process widgets that are arrays and contain the target field
                    if (!is_array($widget) || !isset($widget[$field_key])) {
                        continue;
                    }

                    $original_text = $widget[$field_key];
                    $updated_text = str_replace(self::OLD_URL, self::NEW_URL, $original_text);

                    if ($updated_text !== $original_text) {
                        $widget[$field_key] = $updated_text;
                        $has_changes = true;
                        echo "S'ha modificat el widget de tipus '{$option_name}' amb clau {$key}\n";
                    }
                }

                // Save the updated widgets back to the database if changes were made
                if ($has_changes) {
                    $wpdb->update(
                        $wpdb->options,
                        ['option_value' => serialize($widgets)],
                        ['option_id' => $widget_option->option_id]
                    );
                    echo "S'han actualitzat els widgets del tipus '{$option_name}'\n";
                }
            }
        }

        return true;
    }

}
