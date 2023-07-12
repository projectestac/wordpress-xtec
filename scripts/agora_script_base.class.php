<?php

class agora_script_base {

    public $title = 'No title';
    public $info = '';

    public function params(): array {
        return [];
    }

    public function execute($params = []): bool {
        if ($this->can_be_executed()) {
            $starttime = microtime();

            echo $this->title . "\n";

            if (!$params) {
                $params = $this->get_request_params();
            }

            $return = false;
            try {
                $return = $this->_execute($params);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            $difftime = self::microtime_diff($starttime, microtime());

            echo "\n" . 'Execution took ' . $difftime . " seconds\n";

            return $return;
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

    private static function microtime_diff($a, $b) {
        [$adec, $asec] = explode(' ', $a);
        [$bdec, $bsec] = explode(' ', $b);
 
        return $bsec - $asec + $bdec - $adec;
    }

    protected function _execute($params = []) {
        return false;
    }

    protected function can_be_executed(): bool {
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

    protected function execute_suboperation($function, $params = []) {
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