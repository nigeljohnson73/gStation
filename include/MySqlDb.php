<?php

class MySqlDb {

	public function __construct($db_server, $db_user, $db_pass, $db_name) {
		$this->conn = new mysqli ( $db_server, $db_user, $db_pass, $db_name );

		// Check connection
		if ($this->conn->connect_error) {
			logger ( LL_ERROR, "Database connection ('$db_server', '$db_user', '$db_pass', '$db_name') failed: " . $conn->connect_error );
			if ($this->conn) {
				@$this->conn->close ();
				$this->conn = null;
			}
		} else {
			logger ( LL_DEBUG, "Database connection ('$db_server', '$db_user', '$db_pass', '$db_name') established" );
		}
	}

	public function close() {
		if ($this->conn) {
			$this->conn->close ();
		}
	}

	public function errorMessage() {
		return $this->conn->error;
	}

	public function query($query, $type = null, $params = null) {
		$this->query = $query;
		$stmt = $this->conn->prepare ( $this->query );
		if ($stmt == false) {
			logger ( LL_ERROR, "mySqlDb::query(): prepare statement failed: " . $query );
			logger ( LL_ERROR, "mySqlDb::query(): prepare statement error: " . $this->conn->error );
			// echo "mySqlDb::query(): prepare statement failed: ".$this->conn->error."\n";
			return null;
		}

		if ($type) {
			$stmt->bind_param ( $type, ...$params );
		}
		logger ( LL_DEBUG, "mySqlDb::query(): " . $query );
		logger ( LL_DEBUG, "mySqlDb::query(): \$stmt->execute(): " . ob_print_r ( tfn ( $stmt->execute () ) ) );
		logger ( LL_DEBUG, "mySqlDb::query(): \$stmt->store_result(): " . ob_print_r ( tfn ( $stmt->store_result () ) ) );

		$meta = $stmt->result_metadata ();
		// logger ( LL_DEBUG, "mySqlDb::query(): \$stmt->result_metadata(): " . ob_print_r ( $meta ) );

		if ($meta) {
			while ( $column = $meta->fetch_field () ) {
				$bindVarsArray [$column->name] = &$results [$column->name];
			}
			call_user_func_array ( array (
					$stmt,
					'bind_result'
			), $bindVarsArray );

			$results = array ();
			logger ( LL_DEBUG, "mySqlDb::query(): Statement Object: \n" . ob_print_r ( $stmt ) );
			while ( $stmt->fetch () ) {
				// echo "Got fetch: ".ob_print_r($bindVarsArray)."\n";
				$results [] = array_map ( "__my_sql_db_copy_value", $bindVarsArray );
				// var_dump ( $bindVarsArray );
			}

			$stmt->free_result ();

			return $results;
		} else {
			// logger ( LL_ERROR, "mySqlDb::query(): \$stmt->result_metadata() failed: " . ob_print_r ( tfn ( $this->conn->error ) ) );
			// echo "mySqlDb::query(): \$stmt->result_metadata() failed: " . ob_print_r ( tfn ( $this->conn->error ) );
		}

		logger ( LL_DEBUG, "mySqlDb::query(): no meta data returned" );
	}
}

// Function used for the query function above
function __my_sql_db_copy_value($v) {
	return $v;
}

$mysql = new MySqlDb ( $db_server, $db_user, $db_pass, $db_name );
// $mysql->query("INSERT INTO MyGuests (firstname, lastname, email) VALUES (?, ?, ?)", "sss", array("Nigel", "Johnson", "nigel@nigeljohnson.net"));
// $mysql->query("SELECT * FROM MyGuests");
// $mysql->query("SELECT * FROM MyGuests WHERE id < ?", "i", array(9));
// $tmp = $mysql->query("SELECT * FROM MyGuests WHERE id < 9");
// print_r($tmp);
?>
