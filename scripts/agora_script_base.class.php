<?php

class agora_script_base{

	public $title = 'No title';
	public $info = "";

	public function params() {
		$params = array();
		return $params;
	}

	function execute($params = false) {
		if ($this->can_be_executed()) {
			$starttime = microtime();

			echo $this->title."\n";

            if (!$params) {
                $params = $this->get_request_params();
            }

            try {
				$return = $this->_execute($params);
			} catch (Exception $e) {
				echo $e->getMessage();
			}

			$difftime = self::microtime_diff($starttime, microtime());

			echo "\n"."Execution took ".$difftime." seconds\n";

			return $return;
		}
		return false;
	}

	private function get_request_params(){
		$params = $this->params();
		foreach ($params as $paramname => $unused) {
			if ($value = get_cli_arg($paramname)) {
				$params[$paramname] = $value;
			}
		}
		return $params;
	}

	private static function microtime_diff($a, $b) {
	    list($adec, $asec) = explode(' ', $a);
	    list($bdec, $bsec) = explode(' ', $b);
	    return $bsec - $asec + $bdec - $adec;
	}


	protected function _execute($params = array()) {
		return false;
	}

	protected function can_be_executed($params = array()) {
		return true;
	}

	protected function output($message, $type = "") {
        if (is_object($message) || is_array($message)) {
            print_r($message);
            return;
        }

        if (!empty($type)) {
            $message = $type.': '.$message;
        }
        echo $message."\n";
        return;
    }

    protected function execute_suboperation($function, $params = array()) {
        $function = 'script_'.$function;
        $filename = $function.'.class.php';
        $basedir = dirname(__FILE__).'/';
        if (!file_exists($basedir.$filename)) {
            $this->output("File $basedir $filename does not exists", 'ERROR');
            return false;
        }
        require_once($filename);
        $script = new $function();
        return $script->execute($params);
    }

}