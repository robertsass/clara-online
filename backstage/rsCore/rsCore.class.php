<?php	/* rsCore 1.7 */

/*
	Class: rsCore
	Vorbereitung (config.php laden, passendes Template ermitteln, Session starten) und Laden des Templates.
*/

class rsCore {


	protected $db;
	protected $head;
	protected $body;
	protected $docid;
	protected $Template;

	/*	Constructor: __construct
		Initialisiert rsCore und uebernimmt eventuell bereits instanziierte Objekte bei Erweiterung durch ein Template.
	
		Parameters:
			$db - rsMysql-Objekt zur Tabelle "_tree"
			$head - rsHeader-Objekt
			$body - rsContainer-Objekt fuer den HTML-Body-Tag.
	*/
	public function __construct( rsMysql $db=null, rsHeader $head=null, rsContainer $body=null ) {
		if( !defined('DBNAME') )
			require_once( 'config.php' );
		$this->start_session();
		if( isset($_GET['f']) )
			return $this->get_file( intval( $_GET['f'] ) );
		$this->core_init( $db, $head, $body );
		if( defined('BACKUP_HOST') && isset($_GET['backup']) && $_GET['k'] == BACKUP_KEY )
			return $this->backup();
		if( !isset($this->docid) )
			$this->docid = $this->detect_requested_page();
		if( !defined('TEMPLATE') )
			$this->init_template();
	}

	/*	Function: core_init
		Stellt eine Verbindung zur Tabelle "_tree" her und instanziiert ein rsHeader- und ein rsContainer-Objekt fuer den HTML-Body-Tag, sofern diese nicht dem Konstruktor uebergeben wurden.
	*/
	protected function core_init( rsMysql $db=null, rsHeader $head=null, rsContainer $body=null ) {
		if($db)		$this->db = $db;
		else		$this->db = new rsMysql( 'tree' );
		if($head)	$this->head = $head;
		else		$this->head = new rsHeader();
		if($body)	$this->body = $body;
		else		$this->body = new rsContainer( 'body' );
	}
	
	
	private function backup() {
		set_time_limit(0); ignore_user_abort(true);	// Skript-Abbruch unterbinden
		if( ini_get( 'allow_url_fopen' ) == 'off' )
			ini_set( 'allow_url_fopen', '1' );
		if( ini_get( 'allow_url_fopen' ) == 'on' )
			$ticket = file_get_contents( BACKUP_HOST );
		$tables = $this->db->get( 'SHOW TABLES FROM '. DBNAME .' LIKE "'. DBPREFIX .'%"' );
		ob_start();
		$dbarray = array();
		foreach( $tables as $table )
			$dbarray[] = $this->backup_table( $table[0], $ticket );
		echo serialize( $dbarray );
		$output = ob_get_contents();
		#mail( 'webmaster@clara-online.de', 'Database-Backup '. date('d.m.Y H:i:s'), base64_encode( $output ), 'FROM: server@clara-online.de' );
		ob_end_clean();
		if( $this->save_backup( $output ) ) {
			echo "Done.";
			return true;
		}
		return false;
	}
	

	private function backup_table( $table, $ticket=null ) {
		$table_contents = $this->db->get( 'SELECT * FROM `'. $table .'`' );
		foreach( $table_contents as $ckey => $content )
			foreach( $content as $key => $cell )
				if( is_int( $key ) )
					unset( $table_contents[ $ckey ][ $key ] );
		$structure = array();
		$cols = $this->db->get( 'DESCRIBE `'. $table .'`' );
		foreach( $cols as $col )
			$structure[] = array(
					'name' => $col['Field'],
					'type' => $col['Type'],
					'default' => ( empty( $col['Default'] ) ? '' : 'DEFAULT "' . $col['Default'] . '"' ),
					'null' => ( empty(  $col['Null'] ) ? 'NOT NULL' : 'NULL' ),
					'extra' => ( empty( $col['Extra'] ) ? '': $col['Extra'] )
				);
		return array(
				'table' => $table,
				'structure' => $structure,
				'content' => $table_contents
			);
	}
	
	
	private function save_backup( $content ) {
		$remotefile = 'Backup_'. date('Ymd\-His');
		$localfile = fopen( 'media/tmpbackupfile', 'w' );
		fwrite( $localfile, $content );
		fclose( $localfile );
		$host = ftp_connect( BACKUP_HOST );
		ftp_login( $host, BACKUP_FTPUSER, BACKUP_FTPPASS );
		ftp_put( $host, $remotefile, 'media/tmpbackupfile', FTP_BINARY );
		ftp_chmod( $host, 000, $remotefile );
		ftp_close( $host );
		unlink( 'media/tmpbackupfile' );
		return true;
	}
	
	
	protected function get_file( $fileid, $dir=null ) {
		$filesdb = new rsMysql( 'files' );
		$filedata = $filesdb->getRow( '`id` = ' . intval($fileid) );
		if( is_array($filedata) && $filedata['id'] == intval($fileid) && $this->check_file_rights( $filedata ) ) {
			$filename = explode( '.', $filedata['filename'] );
			$filepath = ($dir ? $dir : '') . 'media/' . $filedata['filename'];
			$imagetypes = array( 'jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff' );
			if( in_array( strtolower($filename[1]), $imagetypes ) ) {
				$imagesize = getimagesize( $filepath );
				$content_type = image_type_to_mime_type( $imagesize[2] );
			}
			else
				$content_type = 'application/' . strtolower($filename[1]);
		}
		elseif( is_array($filedata) && $filedata['id'] = intval($fileid) ) {
			$filepath = ($dir ? $dir : '') . 'static/images/notallowed.png';
			$filename = array(1=>'notallowed.png');
			$content_type = 'image/png';
		}
		if( !isset($filepath) || !file_exists($filepath)) {
			$filepath = ($dir ? $dir : '') . 'static/images/notfound.png';
			$filename = array(1=>'notfound.png');
			$content_type = 'image/png';
		}
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: filename=' . $filedata['title'] .'.'. strtolower($filename[1]) );
		header( 'Content-Length: ' . filesize( $filepath ) );
		readfile( $filepath );
		return true;
	}
	
	
	protected function check_file_rights( $filedata ) {
		if( $filedata['rights'] == 'w' || $filedata['rights'] == '' )
			return true;
		if( $filedata['rights'] == 'p' ) {
			$User = new rsUser();
			if( $filedata['owner'] == $User->get('id') )
				return true;
		}
		return false;
	}
	
	
	protected function build() {
		new rsPrinter( $this->head, $this->body );
	}
	
	
	protected function init_template() {
		$this->Template = $this->load_template();
	}


/* Grundfunktionen des Root-Templates */
	protected function start_session() {
		session_start();
	}


	protected function detect_requested_page() {
		return ( isset($_GET['i']) ? intval($_GET['i']) : intval(HOMEPAGE) );
	}


	protected function load_template() {
		$template = $this->get_my_template();
		if( !$template ) {
			$template = $this->get_my_template( PAGE_NOT_FOUND );
			$_GET['i'] = PAGE_NOT_FOUND;
			$this->docid = PAGE_NOT_FOUND;
		}
		define( 'TEMPLATE', $template );
		if( !$this->is_spider )
			$this->update_counter();
		return new $template( $this->db, $this->head, $this->body );
	}
	
	
	protected function is_spider() {
		$agentArray = array(
			"ArchitextSpider", "Googlebot", "TeomaAgent",
			"Zyborg", "Gulliver", "Architext spider", "FAST-WebCrawler",
			"Slurp", "Ask Jeeves", "ia_archiver", "Scooter", "Mercator",
			"crawler@fast", "Crawler", "InfoSeek Sidewinder",
			"almaden.ibm.com", "appie 1.1", "augurfind", "baiduspider",
			"bannana_bot", "bdcindexer", "docomo", "frooglebot", "geobot",
			"henrythemiragorobot", "sidewinder", "lachesis", "moget/1.0",
			"nationaldirectory-webspider", "naverrobot", "ncsa beta",
			"netresearchserver", "ng/1.0", "osis-project", "polybot",
			"pompos", "seventwentyfour", "steeler/1.3", "szukacz",
			"teoma", "turnitinbot", "vagabondo", "zao/0", "zyborg/1.0",
			"Lycos_Spider_(T-Rex)", "Lycos_Spider_Beta2(T-Rex)",
			"Fluffy the Spider", "Ultraseek", "MantraAgent","Moget",
			"T-H-U-N-D-E-R-S-T-O-N-E", "MuscatFerret", "VoilaBot",
			"Sleek Spider", "KIT_Fireball", "WISEnut", "WebCrawler",
			"asterias2.0", "suchtop-bot", "YahooSeeker", "ai_archiver",
			"Jetbot"
		);
		for( $i = 0; $i < count( $agentArray ); $i++ )
			if( strpos( ' '. strtolower( $_SERVER['HTTP_USER_AGENT'] ), strtolower( $agentArray[ $i ] ) ) != false )
				return true;
		return false;
	}
	
	
	protected function update_counter() {
		if( isset( $_COOKIE[ $this->docid .'-visited' ] ) )
			return false;
		$count = $this->db->getColumn( 'count', '`id` = ' . $this->docid );
		$this->db->update( array('count' => $count+1), '`id` = ' . $this->docid );
		setcookie( $this->docid.'-visited', '1', time()+60*30 );
		return true;
	}


	protected function get_my_template( $docid=null ) {
		if( !$docid )
			$docid = $this->docid;
		return $this->db->getColumn( 'template', '`id` = ' . $docid );
	}
	
	
	public function get_left_value( $docid=null, rsMysql $DB=null ) {
		if( !$docid )
			$docid = $this->detect_requested_page();
		if( !$DB )
			$DB = $this->db;
		return $DB->getColumn( 'lft', '`id` = "'. $docid .'"' );
	}
	
	
	public function get_right_value( $docid=null, rsMysql $DB=null ) {
		if( !$docid )
			$docid = $this->detect_requested_page();
		if( !$DB )
			$DB = $this->db;
		return $DB->getColumn( 'rgt', '`id` = "'. $docid .'"' );
	}


	public function get_sublevel_documents( $rootid=null, rsMysql $DB=null ) {
		if( !$rootid )
			$rootid = $this->detect_requested_page();
		if( !$DB )
			$DB = $this->db;
		$docs = array();
		foreach( $DB->get( 'SELECT *, COUNT(*)-1 AS level, ROUND((`rgt` - `lft` - 1) / 2) AS offspring FROM `'. $DB->get_dbtable() .'` WHERE `lft` > ' . $this->get_left_value($rootid, $DB) . ' AND `rgt` < ' . $this->get_right_value($rootid, $DB) . ' GROUP BY `lft` ORDER BY `lft`;' ) as $leaf ) {
			if($leaf['rgt'] > $lastRgt) {
				$docs[$leaf['id']] = $leaf;
				$lastRgt = $leaf['rgt'];
			}
		}
		return $docs;
	}
	
	
	protected function get_used_classes() {
		$classes = get_declared_classes();
		$used_classes = array();
		$reached_user_defined_classes = false;
		foreach( $classes as $index => $class ) {
			if( $class == 'rsCore' )
				$reached_user_defined_classes = true;
			if( $reached_user_defined_classes )
				$used_classes[] = $class;
		}
		return $used_classes;
	}
	
	
}