<?php

require_once dirname(__FILE__, 4) . '/wp-load.php';
require_once __DIR__ . '/scripts.lib.php';

echo json_encode(scripts_api_list_scripts());
