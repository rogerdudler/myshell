<?php 

require '../../library/bootstrap.php';

$return = array('message' => 'Command not found', 'status' => 'error');
switch ($_REQUEST['action']) {
	
	case 'insert':
		$table = $_REQUEST['table'];
		$row = array();
		foreach ($_REQUEST as $key => $value) {
			if (substr($key, 0, 5) == 'data-') {
				$column = substr($key, 7);
				$value = $value == 'null' ? null : $value;
				$row[$column] = $value;
			}
		}
		$id = $db->insert($_SESSION['db'] . "`.`" . $table, $row);
		$return['message'] = "Inserted row: " . $id . ".";
		$return['table'] = $table;
		$return['status'] = 'ok';
		break;
		
	case 'edit':
	case 'update':
		
		// get table name
		$table = $_REQUEST['table'];
		$return['table'] = $table;
		
		// identify primary key
		$sql = "DESCRIBE `" . $_SESSION['db'] . "`.`" . $table . "`";
		$columns = $db->data($sql);
		$primarykey = array();
		$condition = '';
		while(list($key, $column) = each($columns)) {
			if ($column['Key'] == 'PRI') {
				$primarykey[] = $column;
			}
		}
		// 2. select existing row
		// 3. create a diff of the old and new data
		$update = array();
		$count = 0;
		foreach ($_REQUEST as $key => $value) {
			if (substr($key, 0, 5) == 'data-') {
				$pos_col = substr($key, 5);
				$pos = strpos($pos_col, '-');
				$rownr = substr($pos_col, 0, $pos);
				$column = substr($pos_col, $pos + 1);
				$value = $value == 'null' ? null : $value;
				$current = $_SESSION['data'][$rownr];
				if ($current[$column] != $value || ($current[$column] !== null && $value === null)) {
					$update[$rownr][$column] = $value;
				}
			}
		}
		if (sizeof($update) > 0) {
			$return['message'] = '';
			while(list($rownr, $columns) = each($update)) {
				$sql = "UPDATE `" . $_SESSION['db'] . "`.`" . $table . "` SET ";
				
				// build condition
				$count = 0;
				$condition = '';
				reset($primarykey);
				while(list($key, $column) = each($primarykey)) {
					if ($count > 0) {
						$condition .= ' AND ';
					}
					$condition .= "`" . $column['Field'] . "`='" . $_SESSION['data'][$rownr][$column['Field']] . "'";
					$count++;
				}
				
				// build update statement
				$count = 0;
				while(list($column, $value) = each($columns)) {
					if ($count > 0) {
						$sql .= ', ';
					}
					if ($value === null) {
						$sql .= $column . "=NULL";
					} else {
						$sql .= $column . "='" . $value . "'";
					}
					$count++;
				}
				
				$sql .= " WHERE $condition";
				$db->query($sql);
				$return['message'] .= $sql . ";\n";
			}
			$return['status'] = 'ok';
		} else {
			$return['message'] = 'Nothing has changed. No update has been performed.';
			$return['status'] = 'ok';
		}
		break;
}

echo json_encode($return);
$db->close();

?>