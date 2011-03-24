<?php 

/**
 * Get a configuration variable by path.
 * 
 * Usage:
 * $title = config('application.title'); 
 *
 * @param var Name of the variable
 * @param value Default value to return if the variable is not set
 */
function config($var, $value = null) {
    global $config;
    if ($config == null) {
        require CONFIG . 'config.php';
    }
    return isset($config[$var]) ? $config[$var] : $value;
}

function table($table) {
	return $table;
}

?>