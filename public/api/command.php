<?php 

require '../../library/bootstrap.php';

$return = array('message' => 'Command not found', 'status' => 'error');
$command = $_REQUEST['command'];
$_SESSION['command'] = $command;
$parts = explode(' ', $command);
$action = trim(array_shift($parts));
$return['command'] = $action;

switch ($action) {
	
	case '':
		$return['message'] = 'Please enter a command. Enter "help" for a list of available command.';
		break;
	
	case 'help':
		$rows = array(
			array('command' => 'db', 'description' => 'List databases'),
			array('command' => 'query', 'description' => 'Execute custom query'),
			array('command' => 'data', 'description' => 'Show table data'),
			array('command' => 'edit', 'description' => 'Edit a dataset'),
			array('command' => 'help', 'description' => 'Shows a list of commands'),
			array('command' => 'insert', 'description' => 'Inserts a new dataset'),
			array('command' => 'delete', 'description' => 'Deletes an existing dataset'),
			array('command' => 'filter', 'description' => 'Filter a result set'),
		);
		$columns = array(
			array('Field' => 'command', 'Type' => 'char(64)'),
			array('Field' => 'description', 'Type' => 'char(64)'),
		);
		$return['columns'] = $columns;
		$return['message'] = "Showing list of commands";
		$return['data'] = $rows;
		$return['status'] = 'ok';
		break;
	
	case 'query':
		$sql = implode(' ', $parts);
		try {
			$rows = $db->data($sql);
			$return['message'] = $sql;
			$return['data'] = $rows;
			$return['status'] = 'ok';
		} catch (Exception $e) {
			$return['message'] = $sql;
			$return['status'] = 'error';
		}
		break;
	
	case 'db':
		if (sizeof($parts) < 1) {
			$sql = "SHOW DATABASES";
			$rows = $db->data($sql);
			$return['message'] = $sql;
			$return['columns'] = array(array('Field' => 'name', 'Type' => 'char(64)'));
			$return['data'] = $rows;
			$return['status'] = 'ok';
		} else {
			$sql = "SHOW DATABASES";
			$databases = $db->data($sql);
			$dbname = trim($parts[0]);
			$length = strlen($dbname);
			$exists = false;
			while(list($key, $info) = each($databases)) {
				if ($dbname == $info['Database']) {
					$exists = true;
				}
			}
			if (!$exists) {
				$return['message'] = 'Database "' . $dbname . '" does not exist.';
				$return['status'] = 'error';
			} else {
				$sql = "USE `" . $dbname . "`";
				$db->query($sql);
				$sql = "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, ENGINE FROM `information_schema`.`TABLES` WHERE TABLE_SCHEMA = '" . $dbname . "'";
				$rows = $db->data($sql);
				$return['message'] = $sql;
				$return['columns'] = array(
					array('Field' => 'TABLE_NAME', 'Type' => 'char(64)'),
					array('Field' => 'TABLE_ROWS', 'Type' => 'int(11)'),
					array('Field' => 'DATA_LENGTH', 'Type' => 'int(11)'),
					array('Field' => 'INDEX_LENGTH', 'Type' => 'int(11)'),
					array('Field' => 'ENGINE', 'Type' => 'char(64)'),
				);
				$return['data'] = $rows;
				$return['status'] = 'ok';
				$_SESSION['db'] = $parts[0];
			}
		}
		break;
		
	case 'data':
		if (sizeof($parts) < 1) {
			$return['message'] = 'Invalid command';
			$return['status'] = 'error';
			break;
		}
		$table = trim(array_shift($parts));
		$sql = "SELECT * FROM `" . $_SESSION['db'] . "`.`" . $table . "` LIMIT 0, 30";
		try {
			$rows = $db->data($sql);
			$return['data'] = $rows;
			$return['message'] = $sql;
			$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $table . "`";
			$columns = $db->data($sql);			
			$return['columns'] = $columns;
			$return['table'] = $table;
			$return['status'] = 'ok';
			$_SESSION['table'] = $table;
			$_SESSION['data'] = $rows;
		} catch (Exception $e) {
			$return['message'] = $sql;
			$return['status'] = 'error';
		}
		break;
		
	case 'filter':
		if (sizeof($parts) < 2) {
			$return['message'] = 'Invalid command';
			$return['status'] = 'error';
			break;
		}
		$column = array_shift($parts);
		$value = array_shift($parts);
		$condition = "`" . $column . "`='" . $value . "'";
		$sql = "SELECT * FROM `" . $_SESSION['db'] . "`.`" . $_SESSION['table'] . "` WHERE $condition LIMIT 0, 30";
		try {
			$rows = $db->data($sql);
			$return['data'] = $rows;
			$return['message'] = $sql;
			$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $_SESSION['table'] . "`";
			$columns = $db->data($sql);			
			$return['columns'] = $columns;
			$return['table'] = trim($parts[0]);
			$return['status'] = 'ok';
			$_SESSION['data'] = $rows;
		} catch (Exception $e) {
			$return['message'] = $sql;
			$return['status'] = 'error';
		}
		break;
		
	case 'edit':
		if (sizeof($parts) < 1) {
			$return['message'] = 'Invalid command';
			$return['status'] = 'error';
			break;
		}
		if (sizeof($parts) == 1) {
			$parts[1] = $parts[0];
			$parts[0] = $_SESSION['table'];
		}
		
		// get table name
		$tableparts = explode(":", array_shift($parts));
		$table = array_shift($tableparts);
		$columns = array('*');
		$columns_query = "*";
		if (sizeof($tableparts) > 0) {
			$columns = explode(",", array_shift($tableparts));
			$columns_query = "`" . implode("`,`", $columns) . "`";
		}
		
		// get primary key definition
		$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $table . "`";
		$columns = $db->data($sql);
		$primarykey = array();
		while(list($key, $column) = each($columns)) {
			if ($column['Key'] == 'PRI') {
				$primarykey[] = $column;
			}
		}

		// edit all rows
		$condition = '';
		if ($parts[0] == '*') {
			$keys = array();
			while(list($key, $row) = each($_SESSION['data'])) {
				$keys[] = $row[$primarykey[0]['Field']];
			}
			$condition = "`" . $primarykey[0]['Field'] . "` IN ('" . implode("','",$keys) . "')";
		} else {
			// build condition query
			reset($columns);
			$count = 0;
			while(list($key, $column) = each($columns)) {
				if ($column['Key'] == 'PRI') {
					$primarykey[] = $column;
					if ($count > 0) {
						$condition .= ' AND ';
					}
					$condition .= "`" . $column['Field'] . "`='" . array_shift($parts) . "'";
					$count++;
				}
			}
		}
		
		$sql = "SELECT $columns_query FROM `" . $_SESSION['db'] . "`.`" . $table . "` WHERE $condition";
		try {
			$rows = $db->data($sql);
			if ($rows === null) {
				$return['message'] = 'Row not found';
				$return['status'] = 'error';
				break;
			} else {
				$return['data'] = $rows;
				$return['message'] = $sql;
				$return['table'] = trim($table);
				$return['status'] = 'ok';
				$_SESSION['data'] = $rows;
			}
		} catch (Exception $e) {
			$return['message'] = $sql;
			$return['status'] = 'error';
		}
		break;
		
	case 'insert':
		if (sizeof($parts) < 1) {
			$return['message'] = 'Invalid command';
			$return['status'] = 'error';
			break;
		}
		$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $parts[0] . "`";
		$columns = $db->data($sql);
		$row = array();
		while(list($key, $column) = each($columns)) {
			$row[$column['Field']] = '';
		}
		$return['data'] = array($row);
		$return['columns'] = $columns;
		$return['message'] = 'Insert prepared.';
		$return['table'] = trim($parts[0]);
		$return['status'] = 'ok';
		break;
		
	case 'delete':
		if (sizeof($parts) < 2) {
			$return['message'] = 'Invalid command';
			$return['status'] = 'error';
			break;
		}
		
		// get table name
		$table = array_shift($parts);
		
		// get primary key definition
		$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $table . "`";
		$columns = $db->data($sql);
		$primarykey = array();
		while(list($key, $column) = each($columns)) {
			if ($column['Key'] == 'PRI') {
				$primarykey[] = $column;
			}
		}
		
		// build condition
		$count = 0;
		$condition = '';
		reset($primarykey);
		while(list($key, $column) = each($primarykey)) {
			if ($count > 0) {
				$condition .= ' AND ';
			}
			$condition .= "`" . $column['Field'] . "`='" . array_shift($parts) . "'";
			$count++;
		}
		
		$sql = "DELETE FROM `" . $_SESSION['db'] . "`.`" . $table . "` WHERE $condition LIMIT 1";
		$db->query($sql);
		$return['message'] = $sql;
		$return['table'] = $table;
		$return['status'] = 'ok';
		break;
		
}
echo json_encode($return);
$db->close();

?>