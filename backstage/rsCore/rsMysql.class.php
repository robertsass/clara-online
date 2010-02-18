<?php /* rsMysql 2.7 */

class rsMysql {


	protected $connection;
	public $ErrorLog;
	protected $dbtable;


	public function __construct( $dbtable, $utf8=true ) {
		if( !defined('DBHOST') )
			return null;
		$this->ErrorLog = new rsErrorHandler();
		$this->dbtable = DBPREFIX . $dbtable;
		$this->connection = mysql_connect( DBHOST, DBUSER, DBPASS )
			OR $this->ErrorLog->logError( mysql_errno(), '<b>Keine Verbindung zur Datenbank ('.mysql_errno().')</b><br />Fehlermeldung: '.mysql_error() );
		mysql_select_db( DBNAME )
			OR $this->ErrorLog->logError( mysql_errno(), '<b>Konnte Datenbank nicht benutzen ('.mysql_errno().')</b><br />Fehlermeldung: '.mysql_error() );
		if( $utf8 )
			$this->set_utf8();
		if( !$this->table_exists() )
			return null;
	}
	
	
	protected function table_exists() {
		$tables = $this->get( 'SHOW TABLES LIKE "%TABLE"' );
		if( count($tables) == 0 )
			return false;
		return true;
	}
	
	
	public function set_utf8() {
		$this->execute( 'SET NAMES "utf8"'); 
		$this->execute( 'SET CHARACTER SET "utf8"' );
	}
	
	
	public function execute( $sql ) {
		if( $res = mysql_query( $sql ) OR $res = $this->ErrorLog->logError( mysql_errno(), '<b>Fehler beim Ausf&uuml;hren ('.mysql_errno().'): </b>' . $sql . '<br /><br />' . mysql_error()) )
			return $res;
		else
			return false;
	}
	
	
	public function insert( $array ) {
		$spalten = array_keys($array);
		$daten = $array;
		$first = true;
		$sql = 'INSERT INTO `' . $this->dbtable . '`(';
		foreach($spalten as $spalte) {
			if(!$first) $sql .= ',';
			$sql .= '`' . $spalte . '`';
			$first = false;
		}
		$sql .= ') ';
		$sql .= 'VALUES(';
		$first = true;
		foreach($daten as $data) {
		 if(!$first) $sql .= ',';
		 $sql .= '"' . mysql_real_escape_string( stripslashes( $data ) ) . '"';
		 $first = false;
		}
		$sql .= ');';
		if( $this->send($sql) == false ) return false;
		return true;
	}
	
	
	public function update( $array, $where ) {
		$first = true;
		$sql = 'UPDATE `' . $this->dbtable . '` SET ';
		foreach($array as $spalte => $value) {
			if(!$first) $sql .= ', ';
			$sql .= '`' . $spalte . '`' . '="' . mysql_real_escape_string( stripslashes( $value ) ) . '"';
			$first = false;
		}
		$sql .= ' WHERE ' . $where;
		if( $this->send($sql) == false ) return false;
		return true;
	}

	
	public function update_insert( $array, $where ) {
		$row = $this->getOne('SELECT * FROM `' . $this->dbtable . '` WHERE ' . $where);
		if($row[0] !== NULL)
			return $this->update( $array, $where );
		else
			return $this->insert( $array );
	}
	
	
	public function delete( $where ) {
		if($this->send('DELETE FROM `' . $this->dbtable . '` WHERE ' . $where)) return true;
		else return false;
	}

	
	public function get( $sql ) {
		$sql = str_replace( '%TABLE', $this->dbtable, $sql );
		$rows = array();
		if( $result = mysql_query( $sql )
			OR $this->ErrorLog->logError( mysql_errno(), '<b>Fehler beim Ausf&uuml;hren ('.mysql_errno().'): </b>'.$sql.'<br><br>'.mysql_error() ) )
		while( $row = mysql_fetch_array( $result ) ) {
			$rows[] = $row;
		}
		return $rows;
	}
		
	
	private function getOne( $sql ) {
		$result = $this->get( $sql );
		return $result[0];
	}
	
	
	public function getSpalten() {
		$rows = $this->get('SELECT * FROM `' . $this->dbtable . '` LIMIT 0, 1');
		foreach($rows as $row) {
			return array_keys($row);
		}
	}
	
	
	public function getRow( $whereStatement ) {
		$result = $this->getOne('SELECT * FROM `' . $this->dbtable . '` WHERE ' . $whereStatement);
		return $result;
	}

	
	public function getAll( $whereStatement ) {
		return $this->get( 'SELECT * FROM `' . $this->dbtable . '` WHERE ' . $whereStatement );
	}
	
	
	public function getColumn( $spalte, $whereStatement ) {
		$result = $this->getOne('SELECT `' . $spalte . '` FROM `' . $this->dbtable . '` WHERE ' . $whereStatement);
		return $result[0];
	}
	
	
	public function exists( $whereStatement ) {
		$result = $this->getOne('SELECT COUNT(*) FROM `' . $this->dbtable . '` WHERE ' . $whereStatement);
		if($result[0] > 0) return true;
		else return false;
	}
	
	
	public function send( $sql ) {	// Alias zu execute()
		return $this->execute( $sql );
	}
	
	
	public function get_dbtable() {
		return $this->dbtable;
	}


}