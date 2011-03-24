<?php 

require '../../library/bootstrap.php';

$command = $_REQUEST['command'];
$return = array();
$parts = explode(' ', $command);
$action = array_shift($parts);
$return['suggestion'] = $command;
switch ($action) {
	case 'db':
		$sql = "SHOW DATABASES";
		$databases = $db->data($sql);
		$dbname = trim($parts[0]);
		$length = strlen($dbname);
		while(list($key, $info) = each($databases)) {
			if (strtolower($dbname) == strtolower(substr($info['Database'], 0, $length))) {
				$return['suggestion'] = $action . ' ' . $info['Database'];
				break;
			}
		}
		break;
	case 'edit':
	case 'data':
	case 'delete':
	case 'insert':
		$sql = "SHOW TABLES FROM `" . $_SESSION['db'] . "`";
		$tables = $db->data($sql);
		$tablename = trim($parts[0]);
		$length = strlen($tablename);
		while(list($key, $info) = each($tables)) {
			$name = current($info);
			if (strtolower($tablename) == strtolower(substr($name, 0, $length))) {
				$return['suggestion'] = $action . ' ' . $name;
				if ($action == 'edit' || $action == 'delete') {
					$return['suggestion'] .= ' ';
				}
				break;
			}
		}
		break;
	case 'filter':
		$columnname = trim($parts[0]);
		$length = strlen($columnname);
		$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $_SESSION['table'] . "`";
		$columns = $db->data($sql);
		while(list($key, $column) = each($columns)) {
			if (strtolower($columnname) == strtolower(substr($column['Field'], 0, $length))) {
				$return['suggestion'] = $action . ' ' . $column['Field'] . ' ';
				break;
			}
		}
		break;
	default:
		$actions = array('db', 'data', 'edit', 'query', 'help', 'insert', 'delete', 'filter');
		$length = strlen($action);
		while(list($key, $proposal) = each($actions)) {
			if (strtolower($action) == strtolower(substr($proposal, 0, $length))) {
				$return['suggestion'] = $proposal;
				if ($proposal != 'help') {
					$return['suggestion'] .= ' ';
				}
				break;
			}
		}
		break;
}
echo json_encode($return);

?>