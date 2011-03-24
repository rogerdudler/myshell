<?php 

// start/continue session
session_start();

// set library and class location
define('ROOT', dirname(__FILE__) . '/../');
define('LIBRARY', ROOT . 'library/');
define('CONFIG', ROOT . 'config/');

// load libraries
require LIBRARY . 'core.php';
require LIBRARY . 'db.php';

// create database connection instance
global $db;
$db = new Database(
    config('database.server.host'), 
    config('database.server.username'), 
    config('database.server.password'), 
    null
);

?>