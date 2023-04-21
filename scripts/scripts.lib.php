<?php

function get_all_scripts(): array {
    $basedir = __DIR__ . '/';
    $scriptsfiles = glob($basedir . 'script_*.class.php');
    $scripts = [];

    foreach ($scriptsfiles as $scriptpath) {
        require_once($scriptpath);
        $file = str_replace($basedir, '', $scriptpath);
        $class = str_replace('.class.php', '', $file);
        $script = new $class();
        $scripts[$class] = $script;
    }

    return $scripts;
}

function scripts_execute_script(string $scriptclass) {
    $basedir = __DIR__ . '/';

    if (!file_exists($basedir . $scriptclass . '.class.php')) {
        echo 'Script ' . $scriptclass . ' not found';
        return false;
    }

    require_once($scriptclass . '.class.php');
    return (new $scriptclass())->execute();
}
