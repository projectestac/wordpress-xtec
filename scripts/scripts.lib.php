<?php

const FILE_SUFFIX_LITERAL = '.class.php';

function get_all_scripts(): array
{
    $basedir = __DIR__ . '/';
    $scriptsfiles = glob($basedir . 'script_*.class.php');
    $scripts = [];

    foreach ($scriptsfiles as $scriptpath) {
        require_once $scriptpath;
        $file = str_replace($basedir, '', $scriptpath);
        $class = str_replace(FILE_SUFFIX_LITERAL, '', $file);
        $script = new $class();
        $scripts[$class] = $script;
    }

    return $scripts;
}

/**
 * Get the list of scripts with the data needed to execute them from Àgora Portal.
 */
function scripts_api_list_scripts(): array
{
    $scripts = get_all_scripts();
    $actions = [];

    foreach ($scripts as $scriptname => $script) {
        $action = new StdClass();
        $action->action = $scriptname;
        $action->title = $script->title;
        $action->description = $script->info;
        $action->params = array_keys($script->params());
        $actions[] = $action;
    }

    return $actions;
}

function scripts_execute_script($scriptclass)
{
    $basedir = __DIR__ . '/';

    if (!file_exists($basedir . $scriptclass . FILE_SUFFIX_LITERAL)) {
        echo 'Script ' . $scriptclass . ' not found';
        return false;
    }

    require_once $scriptclass . FILE_SUFFIX_LITERAL;
    return (new $scriptclass())->execute();
}
