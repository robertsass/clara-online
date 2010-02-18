<?php	/* rsUser 1.3 */

class rsUser {

	
	protected $authentifiziert = false;
	protected $benutzerdaten;
	protected $userdb;
	protected $rightsdb;
	
	
	public function __construct( $vbenutzername="benutzername", $vpasswort="passwort" ) {
		$this->userdb = new rsMysql( 'user' );
		$this->rightsdb = new rsMysql( 'rights' );
		if( isset($_GET['logout']) )
			$this->logout();
		else
			$this->authentificate( $vbenutzername, $vpasswort );
	}
	
	
	protected function authentificate( $vbenutzername, $vpasswort ) {
		if( isset($_POST[ $vbenutzername ], $_POST[ $vpasswort ]) ) {
			$benutzername = strtolower( $_POST[ $vbenutzername ] );
			$passwort = $_POST[ $vpasswort ];
		}
		elseif( isset($_SESSION[ $vbenutzername ], $_SESSION[ $vpasswort ]) ) {
			$benutzername = $_SESSION[ $vbenutzername ];
			$passwort = $_SESSION[ $vpasswort ];
		}
		if( isset($benutzername) ) {
			$cpasswort = ( defined('CRYPT_PHRASE') ? crypt( $passwort, CRYPT_PHRASE ) : $passwort );
			$benutzerdaten = $this->userdb->getRow( '`email` = "' . mysql_real_escape_string($benutzername) . '" OR `nickname` = "' . mysql_real_escape_string($benutzername) . '"' );
			if( $benutzerdaten['passwort'] == $cpasswort && $benutzerdaten['aktiv'] == 1 ) {
				$_SESSION[ $vbenutzername ] = $benutzername;
				$_SESSION[ $vpasswort ] = $passwort;
				$this->init( $benutzerdaten );
			}
		}
	}
	
	
	protected function logout() {
		session_destroy();
		$this->authentifiziert = false;
		$this->benutzerdaten = null;
	}
	
	
	protected function init( $benutzerdaten ) {
		$this->benutzerdaten = $benutzerdaten;
		$this->authentifiziert = true;
		$this->init_rights();
		return true;
	}
	
	
	protected function init_rights() {
		$rights = array();
		$specific_rights = $this->rightsdb->getColumn( 'specific', '`userid` = '.$this->get('id') );
		foreach( explode( '/', $specific_rights ) as $right ) {
			$right = explode( ':', $right );
			$rights[ $right[0] ] = $right[1];
		}
		$rights[ 'docs' ] = $this->rightsdb->getColumn( 'docid', '`userid` = '.$this->get('id') );
		$rights[ 'mediadirs' ] = $this->rightsdb->getColumn( 'mediaid', '`userid` = '.$this->get('id') );
		$this->rights = $rights;
	}
	
	
	public function save_rights() {
		$rights = $this->rights;
		unset( $rights['docs'] );
		unset( $rights['mediadirs'] );
		$specific_rights = array();
		foreach( $rights as $key => $value ) {
			$specific_rights[] = implode( ':', array($key, $value) );
		}
		$specific_rights = implode( '/', $specific_rights );
		$this->rightsdb->update( array('specific' => $specific_rights, 'docid' => $this->rights['docs'], 'mediaid' => $this->rights['mediadirs']), '`userid` = '.$this->get('id') );
		$this->init_rights();
	}
	
	
	public function get_right( $key ) {
		if( isset( $this->rights[ $key ] ) )
			return $this->rights[ $key ];
		return null;
	}
	
	
	public function set_right( $key, $value, $save_immediately=false ) {
		$this->rights[ $key ] = $value;
		if( $save_immediately )
			$this->save_rights();
	}
	
	
	public function get( $benutzerdatum ) {
		if( isset($this->benutzerdaten[ $benutzerdatum ]) )
			return $this->benutzerdaten[ $benutzerdatum ];
		return null;
	}
	
	
	public function set( $benutzerdatum, $value) {
		$this->benutzerdaten[ $benutzerdatum ] = $value;
		$benutzerdaten = $this->benutzerdaten;
		foreach( $benutzerdaten as $key => $value )
			if( is_int($key) )
				unset( $benutzerdaten[$key] );
		unset( $benutzerdaten['id'] );
		if( $this->userdb->update( $benutzerdaten, '`id` = '.$this->get('id') ) )
			return $this->init( $this->userdb->getRow('`id`='.$this->benutzerdaten['id']) );
	}
	
	
	public function auth() {
		return $this->authentifiziert;
	}


}