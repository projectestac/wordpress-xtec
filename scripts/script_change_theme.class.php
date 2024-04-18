<?php

require_once 'agora_script_base.class.php';

class script_change_theme extends agora_script_base {

    public $title = 'Canvi de tema';
    public $info = 'Canvia el tema usat al Nodes. Els temes diponibles són: reactor-primaria-1, reactor-serveis-educatius,
                    reactor-projectes i astra.<br >
                    En activar el tema Astra, es pot especificar que s\'esborri la configuració prèvia mitjançant el paràmetre
                    <em>reset_astra</em>. Aquest paràmetre s\'ignora en activar els altres temes. Els valors possibles són:<br >
                    <ul>
                        <li>0: Manté els registres anteriors (si n\'hi ha).</li>
                        <li>1: Esborra tots els registres existents d\'Astra abans d\'inicialitzar el tema.</li>
                    </ul>';

    public function params(): array {
        return [
            'new_theme' => '',
            'reset_astra' => '',
        ];
    }

    protected function _execute($params = []): bool {

        $new_theme = $params['new_theme'];
        $reset_astra = $params['reset_astra'];

        if (!in_array($new_theme, ['reactor-primaria-1', 'reactor-serveis-educatius', 'reactor-projectes', 'astra'])) {
            $this->output('The value for the new theme was incorrect', 'ERROR');
            $this->output('Operation aborted');

            return false;
        }

        $current_theme = get_stylesheet();

        if ($current_theme === $new_theme) {
            $this->output('The new theme (' . $new_theme . ') is the same as the current theme (' . $current_theme . ')', 'INFO');
            $this->output('The theme has not been changed');

            return false;
        }

        if (($new_theme === 'astra') && $reset_astra === '1') {
            $this->remove_astra_from_db();
            $this->output('The Astra theme records in wp_options have been removed', 'INFO');

            switch_theme($new_theme);
            switch_theme($current_theme);
        }

        switch_theme($new_theme);

        $this->output('The theme has been changed from ' . $current_theme . ' to ' . $new_theme, 'INFO');

        return true;

    }

    private function remove_astra_from_db(): void {

        global $wpdb;

        $table = $wpdb->prefix . 'options';
        $col = 'option_name';
        $sql = $wpdb->prepare("DELETE FROM $table WHERE $col LIKE %s", '%astra%');
        $wpdb->query($sql);

    }

}
