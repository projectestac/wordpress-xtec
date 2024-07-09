<?php

require_once dirname(__FILE__, 4) . '/wp-load.php';
require_once __DIR__ . '/scripts.lib.php';

$scripts = get_all_scripts();
$actions = [];

foreach ($scripts as $scriptname => $script) {

    $action = new StdClass();

    $action->action = $scriptname;
    $action->title = $script->title;
    $action->description = $script->info;
    $scriptclass = new $scriptname();
    $action->params = array_keys($scriptclass->params());

    $actions[] = $action;

}

echo json_encode($actions);
