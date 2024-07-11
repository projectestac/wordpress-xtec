<?php

const CLI_SCRIPT = true;

require_once dirname(__FILE__, 4) . '/wp-load.php';
require_once __DIR__ . '/scripts.lib.php';

$script = get_cli_arg('s');

echo 'Start server Time: ' . date('r') . "\n";

set_time_limit(0);

try {
    $success = scripts_execute_script($script);
} catch (Exception $e) {
    $success = false;
    echo ($e->getMessage());
}

echo 'End server Time: ' . date('r') . "\n";

if ($success) {
    echo 'Script ' . $script . ' succeed' . "\n";
    echo 'success';
    exit (0);
} else {
    echo 'Script ' . $script . ' failed' . "\n";
    exit ('error');
}
