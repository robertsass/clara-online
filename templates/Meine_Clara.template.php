<?php	/* Template Meine_Clara */

class Meine_Clara extends Root {


	protected $Benutzer;
	protected $section;
	protected $docs;
	protected $topid = 13;
	protected $icons;
	protected $userdb;
	protected $groupmemberdb;

	public function __construct( rsMysql $db, rsHeader $head, rsContainer $body ) {
		$this->docs = array(
			'login' => 14,
			'pwforgotten' => 47,
			'register' => 46
		);
		$this->Benutzer = new rsUser();
		if( !$this->is_logged_in() && !in_array($this->detect_requested_page(), $this->docs) )
			$_GET['i'] = $this->docs['login'];
		if( $this->Benutzer->auth() )
			$this->docs = array_merge( $this->docs, array(
				'meinclara' => 13,
				'stundenplan' => 87,
				'terminplan' => 90
			) );
		$this->section = array_search( $this->detect_requested_page(), $this->docs );
		$this->userdb = new rsMysql( 'user' );
		$this->groupmemberdb = new rsMysql( 'groupmember' );
		parent::__construct( $db, $head, $body );
	}
	
	
	protected function is_logged_in() {
		return $this->Benutzer->auth();
	}
	
	
	protected function build_head() {
		parent::build_head();
	#	$this->head->link_javascript( 'static/js/jquery.dimensions.js' );
		$this->head->link_javascript( 'static/js/jquery.maskedinput.js' );
		$this->head->link_javascript( 'static/js/jquery.flexbox.js' );
		$this->head->link_javascript( 'static/js/jquery.ajaxq.js' );
		$this->head->link_stylesheet( 'static/css/flexbox.css' );
	}
	
	
	protected function build_body() {
		parent::build_body();
		if( $this->is_logged_in() ) {
			$Submenu = $this->mainContainer['submenu'];
		#	$Submenu = $this->body->search( array('id' => 'submenu') );
			$this->build_usersign( $this->mainContainer['submenu']/* ->search(array('tag' => 'h1')) */ );
		}
	}
	
	
	protected function build_usersign( rsContainer $Container ) {
		if( $this->is_logged_in() ) {
			$Container->subordinate( 'span' )
				->subordinate( 'form', array('action' => 'index.php?i='. $this->docid .'&logout', 'method' => 'POST') )
				->subordinate( 'input', array('type' => 'submit', 'value' => 'Abmelden') );
		}
	}
	
	
	protected function replace_placeholder( $str ) {
		$str = str_replace( '*VORNAME', $this->Benutzer->get('vorname'), $str );
		$str = str_replace( '*NACHNAME', $this->Benutzer->get('nachname'), $str );
		return $str;
	}
		
	
	protected function build_submenu( rsContainer $Container, $root=null, $active=null ) {
		if( !$this->is_logged_in() )
			return parent::build_submenu( $Container, 48, $active );
		else
			$root = 49;
		$this->icons = array('home', 'calendar', 'globe', 'smilie', 'letters', 'work', 'airdisk', 'tools');
		$Submenu = $Container->subordinate( 'a', array('name' => 'toolbar') )->subordinate( 'div', array('id' => 'symbolbar') )->subordinate( 'ul' );
		$docs = $this->get_submenu_docs( $root );
		$i = 0;
		foreach( $docs as $doc ) {
			$Li = $Submenu->subordinate( 'a', array('href' => '?i='. $doc['id']) )->subordinate( 'li' );
			$Li->subordinate( 'img', array('src' => 'static/images/icons/'. $this->icons[$i] .'.png') )
				->subordinate( 'div', $doc['name'] );
			if( $doc['id'] == ($active ? $active : $this->docid) )
				$Li->add_attribute('class', 'active');
			$i++;
		}
		return $Submenu;
	}
	
	
	protected function get_submenu_docs( $root ) {
		if( $this->Benutzer->get('email') == '' ) {
			$this->icons[0] = 'public';
			$docs = array(array('id' => $this->topid, 'name' => 'Einrichtung'));
		}
		else {
			$docs = array_merge(
				array(array('id' => $this->topid, 'name' => 'Start')),
				$this->get_sublevel_documents( $root )
			);
			if( $this->Benutzer->get_right('docs') > 0 )
				$docs = array_merge( $docs, array(array('id' => MEINCLARA_ADMINISTRATION, 'name' => 'Administration')) );
		}
		return $docs;
	}


	protected function build_wall( rsContainer $Container ) {
		if( $this->is_logged_in() )
			$Container->add_attribute( 'class', 'meinclara' );
		return parent::build_wall( $Container );
	}
	

	protected function build_content( rsContainer $Container ) {
		$this->build_content_header( $Container );
		$Content = $Container->subordinate( 'div', array('class' => 'content') );
		if( $this->is_logged_in() && $this->Benutzer->get('email') == '' && !isset($_POST['email']) ) {
			$Content->subordinate( 'img', array('src' => 'static/images/walle.png', 'align' => 'right') );
			$this->page = new rsPage( MEINCLARA_KONTOEINRICHTUNG );
			$Content = $Content->subordinate( 'blockquote' );
		}
		$Content->subordinate( 'div', array('class' => 'text'), $this->replace_placeholder( $this->page->get_content() ) );
		if( $this->section == 'login' )
			$this->build_loginbox( $Container );
		if( $this->is_logged_in() && $this->Benutzer->get('email') == '' )
			$this->build_setup( $Container );
		return $Container;
	}
	
	
	protected function build_content_header( rsContainer $Container ) {
		$Container->subordinate( 'h1', ( $this->page->get_description() == '' ? $this->page->get_title() : $this->replace_placeholder( $this->page->get_description() ) ) );
	}
	
	
	protected function build_loginbox( rsContainer $Container ) {
		if( isset($_POST['passwort']) )
			$this->build_notification( 'Falsche Benutzerdaten', 'Bitte versuche es erneut!<br/>Oder hast Du vielleicht Deine eMail-Adresse noch nicht best&auml;tigt?' );
		$Loginbox = $Container->subordinate( 'div', array('class' => 'panel', 'id' => 'loginbox') );
		$Loginbox->subordinate( 'div', array('class' => 'icon') )->subordinate( 'img', array('src' => 'static/images/lock.png') );
		$Loginform = $Loginbox->subordinate( 'div', array('class' => 'content') )->subordinate( 'form', array('method' => 'post', 'action' => '?i='.$this->topid) );
		$Loginform->subordinate( 'div', array('class' => 'inputmask'), '<div>eMail-Adresse</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'benutzername', 'id' => 'benutzername') );
		$Loginform->subordinate( 'div', array('class' => 'inputmask'), '<div>Passwort</div>' )->subordinate( 'input', array('type' => 'password', 'name' => 'passwort', 'id' => 'passwort') );
		$Loginform->subordinate( 'div', array('class' => 'inputmask'), '<div>&nbsp;</div>' )->subordinate( 'input', array('class' => 'inputbutton', 'type' => 'submit', 'value' => 'Login') );
	}
	
	
	protected function build_notification( $title=null, $text=null ) {
		$Container = $this->body->subordinate( 'div', array('class' => 'notification') );
		if( $title )
			$Container->subordinate( 'h1', $title );
		if( $text )
			$Container->subordinate( 'p', $text );
		return $Container;
	}
	
	
	protected function build_setup( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Konto einrichten' );
		if( $this->setup_is_valid() && $this->setup_processed() )
			$Container->subordinate( 'p', 'Alles klar! Best&auml;tige nun nur noch Deine eMail-Adresse!' );
		else {
			if( isset($_POST['email']) && !$this->setup_is_valid() ) {
				$Error = $Container->subordinate( 'ul', array('class' => 'error') );
				if( !$this->email_is_valid( $_POST['email'] ) )
					$Error->subordinate( 'li', 'Deine eingegebene eMail-Adresse ist nicht richtig.' );
				if( !$this->password_is_valid( $_POST['pw'], $_POST['pw2'] ) )
					$Error->subordinate( 'li', 'Dein neues Passwort ist nicht richtig. Du musst 2x das Gleiche eingeben und Dein neues Passwort muss mindestens 6 Zeichen lang sein.' );
				if( !$this->date_is_valid( $_POST['geb'] ) )
					$Error->subordinate( 'li', 'Dein Geburtsdatum ist nicht richtig. Gib zuerst den Tag, dann den Monat und dann das Jahr an (z.B. 22.06.1996).' );
			}
			$Form = $Container->subordinate( 'form', array('method' => 'post') );
			$Table = $Form->subordinate( 'table' );
			$Table->subordinate( 'tr' )
				->subordinate( 'td', 'eMail-Adresse:' )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'text', 'name' => 'email', 'value' => htmlentities( $_POST['email'] ) ) );
			$Table->subordinate( 'tr' )->subordinate( 'td', '&nbsp' )->append( 'td', '&nbsp;' );
			$Table->subordinate( 'tr' )
				->subordinate( 'td', 'Neues Passwort (min. 6 Stellen):' )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'password', 'name' => 'pw', 'value' => htmlentities( $_POST['pw']) ) );
			$Table->subordinate( 'tr' )
				->subordinate( 'td', 'Neues Passwort wiederholen:' )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'password', 'name' => 'pw2', 'value' => htmlentities( $_POST['pw2'] ) ) );
			$Table->subordinate( 'tr' )->subordinate( 'td', '&nbsp' )->append( 'td', '&nbsp;' );
			$Table->subordinate( 'tr' )
				->subordinate( 'td', 'Geburtstag:' )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'text', 'name' => 'geb', 'value' => htmlentities( $_POST['geb'] ), 'class' => 'datum') );
			$Form->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Fertig') );
		}
	}
	
	
	protected function setup_processed() {
		$email = $this->Benutzer->set( 'email', $_POST['email'] );
		if( is_int( $this->Benutzer->get('nickname') ) )
			$this->Benutzer->set( 'nickname', '' );
		$passwort = $this->Benutzer->set( 'passwort', crypt( $_POST['pw'], CRYPT_PHRASE ) );
		$date = explode( '.', $_POST['geb'] );
		$geburtsdatum = $this->Benutzer->set( 'geburtsdatum', mktime(0,0,0, $date[1], $date[0], $date[2]) );
	/*	$sendtose = $this->send_to_schuelerevents( array(	// Kopiere die Registrationsdaten auch zu schuelerevents
			'email' => $_POST['email'],
			'vorname' => $this->Benutzer->get('vorname'),
			'nachname' => $this->Benutzer->get('nachname'),
			'pw' => $_POST['pw'],
			'pw2' => $_POST['pw'],
			'geburtsdatum' => $_POST['geb'],
			'schule' => 'CSG'
		) ); */
		return ( $email && $passwort && $geburtsdatum );
	}
	
	
	protected function setup_is_valid() {
		return (
			isset($_POST['email'])
			&& $this->email_is_valid( $_POST['email'] )
			&& $this->password_is_valid( $_POST['pw'], $_POST['pw2'] )
			&& $this->date_is_valid( $_POST['geb'] )
		);
	}
	
	
	protected function password_is_valid( $password, $passwordrepeat=null ) {
		$minimum_symbols = 6;
		if(!$passwordrepeat)
			$passwordrepeat = $password;
		return (
			strlen( $password ) >= $minimum_symbols
			&& $password == $passwordrepeat
		);
	}
	
	
	protected function email_is_valid( $email ) {
		$split = explode( '@', $email );
		return (
			substr_count( $email, '@' ) == 1
			&& substr_count( $split[1], '.' ) > 0
			&& strlen( $split[0] ) > 0
			&& strlen( $split[1] ) > 4	// xx.td -> 5 symbols or more
		);
	}
	
	
	protected function date_is_valid( $date ) {	// tt.mm.yyyy
		$split = explode( '.', $date );
		return (
			$split[0] > 0
			&& $split[0] < 32
			&& $split[1] > 0
			&& $split[1] < 13
			&& $split[2] > 1900
			&& $split[2] < date('Y')-8
		);
	}
	
	
	protected function send_to_schuelerevents( $array ) {
		$schuelerevents_response = http_post_fields( 'http://www.schuelerevents.de/index.php?i=111', $array );
		if( substr_count( $schuelerevents_response, 'Dein Benutzerkonto wurde erstellt.' ) > 0 )
			return true;
		return false;
	}
	
	
	protected function get_userdata( $userid=null ) {
		if( !$userid )
			$userid = $this->Benutzer->get('id');
		return $this->userdb->getRow( '`id`='. intval( $userid ) );
	}
	
	
	protected function get_mygroups( $userid=null ) {
		if( !$userid )
			$userid = $this->Benutzer->get('id');
		$groups = $this->groupmemberdb->get( 'SELECT `groupid` FROM `%TABLE` WHERE `userid` = '. $userid );
		$return = array();
		foreach( $groups as $group )
			$return[] = $group['groupid'];
		return $return;
	}

	
}