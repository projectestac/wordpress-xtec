<?php

class agora_script_base {

    public string $title = 'No title';
    public string $info = '';

    public function params(): array {
        return [];
    }

    public function execute($params = []): bool {
        if ($this->can_be_executed()) {
            $starttime = microtime(true);

            echo $this->title . "\n";

            if (empty($params)) {
                $params = $this->get_request_params();
            }

            try {
                $return = $this->_execute($params);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            $difftime = microtime(true) - $starttime;
            echo "\n" . "Execution took " . $difftime . " seconds\n";

            return $return ?? false;
        }

        return false;
    }

    private function get_request_params(): array {
        $params = $this->params();
        foreach ($params as $paramname => $unused) {
            if ($value = get_cli_arg($paramname)) {
                $params[$paramname] = $value;
            }
        }
        return $params;
    }

    protected function _execute($params = []): bool {
        return false;
    }

    protected function can_be_executed($params = []): bool {
        return true;
    }

    protected function output($message, $type = ''): void {
        if (is_object($message) || is_array($message)) {
            print_r($message);
            return;
        }

        if (!empty($type)) {
            $message = $type . ': ' . $message;
        }

        echo $message . "\n";
    }

    protected function execute_suboperation($function, $params = []): bool {
        $function = 'script_' . $function;
        $filename = $function . '.class.php';
        $basedir = __DIR__ . '/';

        if (!file_exists($basedir . $filename)) {
            $this->output("File $basedir $filename does not exists", 'ERROR');
            return false;
        }

        require_once($filename);

        return (new $function())->execute($params);
    }

}