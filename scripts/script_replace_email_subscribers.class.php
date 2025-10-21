<?php

require_once 'agora_script_base.class.php';

const FIELD_UPDATED_LITERAL = 'Field updated: ';

class script_replace_email_subscribers extends agora_script_base
{
    public $title = 'Corregeix els paràmetres de l\'Email Subscribers';
    public $info = 'A la taula wp_options, revisa els URL de l\'Email Subscribers i els textos, tot modificant el nom i l\'adreça de correu del centre si no és correcta';

    protected function _execute($params = []): bool
    {
        $this->replace_urls();
        $this->set_mail_short_values();
        $this->set_mail_contents();
        $this->update_campaigns();

        return true;
    }

    private function replace_urls(): void
    {
        /* Fields that consist, exactly, in a URL */
        $fields_to_replace = [
            'ig_es_optin_link',
            'ig_es_optinlink',
            'ig_es_unsublink',
            'ig_es_unsubscribe_link',
            'ig_es_cronurl',
        ];

        foreach ($fields_to_replace as $field) {
            $value = get_option($field);

            if (!empty($value)) {
                $parts = explode('?', $value);
                $new_url = WP_SITEURL . '?' . $parts[1];

                if (str_starts_with($new_url, 'http://')) {
                    $new_url = preg_replace("/^http:/i", 'https:', $new_url);
                }

                if ($value !== $new_url && update_option($field, $new_url)) {
                    $this->output(FIELD_UPDATED_LITERAL . $field);
                }
            } else {
                $this->output('Field ' . $field . ' does not exist');
            }
        }
    }

    private function set_mail_short_values(): void
    {
        $blogname = get_option('blogname');
        $admin_email = get_option('admin_email');

        $fields_to_set = [
            'ig_es_admin_new_sub_subject' => "[$blogname] Subscripció nova",
            'ig_es_admin_new_contact_email_subject' => "[$blogname] Subscripció nova",
            'ig_es_welcomesubject' => "[$blogname] Benvingut/da al nostre butlletí",
            'ig_es_welcome_email_subject' => "[$blogname] Benvingut/da al nostre butlletí",
            'ig_es_confirmsubject' => "[$blogname] Confirmeu la subscripció",
            'ig_es_confirmation_mail_subject' => "[$blogname] Confirmeu la subscripció",
            'ig_es_adminemail' => $admin_email,
            'ig_es_admin_emails' => $admin_email,
            'ig_es_fromemail' => $admin_email,
            'ig_es_from_email' => $admin_email,
            'ig_es_fromname' => 'Administració',
            'ig_es_from_name' => 'Administració',
            'ig_es_sentreport_subject' => 'S\'ha enviat el correu electrònic del butlletí',
            'ig_es_sent_report_subject' => 'S\'ha enviat el correu electrònic del butlletí',
            'ig_es_unsubcontent' => "Si no voleu rebre més notificacions de $blogname, " .
                "feu clic <a href='{{LINK}}'>aquí</a> per donar-vos de baixa.",
            'ig_es_unsubscribe_link_content' => "Si no voleu rebre més notificacions de $blogname, " .
                "feu clic <a href='{{LINK}}'>aquí</a> per donar-vos de baixa.",
        ];

        foreach ($fields_to_set as $field => $value) {
            update_option($field, $value);
            $this->output(FIELD_UPDATED_LITERAL . $field);
        }
    }

    private function set_mail_contents(): void
    {
        $blogname = get_option('blogname');

        $fields_to_replace['ig_es_admin_new_sub_content'] = <<<EOT
     Hola,

Hem rebut una sol·licitud de subscripció per rebre notificacions de la publicació d'articles al
lloc web <strong>$blogname</strong>, del qual en sou administrador/a. Les dades de la nova subscripció
són les següents:

     Nom: {{NAME}}
     Adreça: {{EMAIL}}
     Llistes: {{LIST}}

Salutacions,
$blogname
EOT;

        $fields_to_replace['ig_es_confirmcontent'] = <<<EOT
     Hola, {{NAME}},

Hem rebut una petició de subscripció d'aquesta adreça de correu electrònic. Feu clic
<a href='{{LINK}}'>aquí</a> per confirmar-la. Si no podeu clicar l'enllaç anterior,
podeu utilizar l'URL següent:

     {{LINK}}

Atentament,
$blogname
EOT;

        $fields_to_replace['ig_es_welcomecontent'] = <<<EOT
     Hola, {{NAME}}, 

La vostra subscripció al butlletí de <strong>$blogname</strong> ha estat confirmada. A
partir d'ara, rebreu notificacions quan es publiquin nous articles al lloc web.

Salutacions,
$blogname
EOT;

        $fields_to_replace['ig_es_cron_adminmail'] = <<<EOT
     Hola, Administrador/a,

L'URL del cron s'ha executat correctament el {{DATE}} per al butlletí
<strong>{{SUBJECT}}</strong>, i s'ha enviat el correu electrònic a
{{COUNT}} destinatari(s).

Salutacions,
$blogname
EOT;

        $fields_to_replace['ig_es_sentreport'] = <<<EOT
     Hola, Administrador/a,

S'ha enviat el butlletí a {{COUNT}} adreces de correu. Trobareu els detalls a
continuació:

     Id únic: {{UNIQUE}} 
     Hora d'inici: {{STARTTIME}} 
     Hora de finalització: {{ENDTIME}} 

Per a més informació, accediu al tauler i visiteu els informes de les subscripcions.

Salutacions,
$blogname
EOT;

        // New names of the fields.
        $fields_to_replace['ig_es_admin_new_contact_email_content'] = $fields_to_replace['ig_es_admin_new_sub_content'];
        $fields_to_replace['ig_es_confirmation_mail_content'] = $fields_to_replace['ig_es_confirmcontent'];
        $fields_to_replace['ig_es_welcome_email_content'] = $fields_to_replace['ig_es_welcomecontent'];
        $fields_to_replace['ig_es_cron_admin_email'] = $fields_to_replace['ig_es_cron_adminmail'];
        $fields_to_replace['ig_es_sent_report_content'] = $fields_to_replace['ig_es_sentreport'];

        foreach ($fields_to_replace as $field => $value) {
            update_option($field, $value);
            $this->output(FIELD_UPDATED_LITERAL . $field);
        }
    }

    private function update_campaigns(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ig_campaigns';
        $blogname = get_option('blogname');
        $admin_email = get_option('admin_email');

        $sql = $wpdb->prepare(
            "UPDATE `{$table_name}` SET `from_name` = %s, `from_email` = %s, `reply_to_name` = %s, `reply_to_email` = %s",
            $blogname,
            $admin_email,
            $blogname,
            $admin_email
        );

        $result = $wpdb->query($sql);

        if (false === $result) {
            $this->output('Error updating ig_campaigns: ' . $wpdb->last_error);
        } else {
            $this->output('Table ' . $table_name . ' updated: ' . $result . ' rows affected');
        }
    }

}
