<?php

const CLI_SCRIPT = true;

require_once dirname(__FILE__, 4) . '/wp-load.php';
require_once __DIR__ . '/scripts.lib.php';

// Print the list of scripts in JSON format. Used by Àgora Portal.
if (get_cli_arg('list')) {
    echo json_encode(scripts_api_list_scripts());
    exit(0);
}

$script = get_cli_arg('s');

echo 'Start server Time: ' . date('r') . "\n";

set_time_limit(0);

try {
    $success = scripts_execute_script($script);
} catch (Exception $e) {
    $success = false;
    echo $e->getMessage();
}

echo 'End server Time: ' . date('r') . "\n";

if ($success) {
    echo 'Script ' . $script . ' succeed' . "\n";
    echo 'success';
    exit (0);
}

echo 'Script ' . $script . ' failed' . "\n";
exit ('error');
