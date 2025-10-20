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
