<?php	/* Template Registration */

class Registration extends Meine_Clara {


	protected $userdb;
	
	
	protected function build_content( rsContainer $Container ) {
		$this->userdb = new rsMysql( 'user' );
		parent::build_content( $Container );
		if( !$this->Benutzer->auth() ) {
			if( isset($_GET['c']) )
				$this->process_validation( $Container );
			elseif( isset($_POST['email']) )
				$this->process_registration( $Container );
			else
				$this->build_form( $Container );
		}
	}
	
	
	protected function build_form( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Pers&ouml;nliche Daten' );
		$Form = $Container->subordinate( 'form', array('method' => 'post') );
		
		if( !isset($_POST['name']) || !$this->check_captcha() )
			$this->build_nachnamenfeld( $Form );
			
		elseif( !isset($_POST['email']) ) {
		
			$personen = $this->userdb->get( 'SELECT `klasse`, `vorname`, `id` FROM `%TABLE` WHERE `nachname` LIKE "'. mysql_real_escape_string( stripslashes( $_POST['name'] ) ) .'" AND `aktiv` = 0 ORDER BY `klasse` ASC;' );
			
			if( count($personen) == 0 )
				$this->build_nachnamenfeld( $Form );
			else
				$this->build_personendaten( $personen, $Form );
			
		}
	}
	
	
	protected function build_nachnamenfeld( rsContainer $Container ) {
		$Container->subordinate( 'p', 'Nachname: ' )
			->subordinate( 'input', array('type' => 'text', 'name' => 'name') );
		$this->build_captcha( $Container );
		$Container->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Weiter') );
	}
	
	
	protected function build_personendaten( $personen, rsContainer $Container ) {
		$Table = $Container->subordinate( 'table' );
		$Table->subordinate( 'tr' )
			->subordinate( 'td', array('class' => 'label'), 'Nachname:' )
			->append( 'td', $_POST['name'] )->subordinate( 'input', array('type' => 'hidden', 'name' => 'name', 'value' => $_POST['name']) );
		
		if( count($personen) == 1 ) {
			$Container->add_attribute('action', '?i='. $this->docid .'&u='. $personen[0][2]);
			$Table->subordinate( 'tr' )
				->subordinate( 'td', array('class' => 'label'), 'Vorname:' )
				->append( 'td', $personen[0][1] );
		}
		
		$this->build_klassenauswahl( $personen, $Table );
		
		$Geb = $Table->subordinate( 'tr' )
			->subordinate( 'td', array('class' => 'label'), 'Geburtsdatum:' )
			->parent_subordinate( 'td' );
		
		$this->build_geburtsdatumwahl( $Geb );
		
		$Table->subordinate( 'tr' )
			->subordinate( 'td', array('class' => 'label'), 'eMail-Adresse:' )
			->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'text', 'name' => 'email') );
		$Table->subordinate( 'tr' )
			->subordinate( 'td', array('class' => 'label'), 'Passwort:' )
			->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'password', 'name' => 'pw') );
		$Table->subordinate( 'tr' )
			->subordinate( 'td', array('class' => 'label'), 'Passwort wiederholen:' )
			->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'password', 'name' => 'pw2') );
			
		$Container->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Fertig') );
	}
	
	
	protected function build_klassenauswahl( $personen, rsContainer $Container ) {
		$Container = $Container->subordinate( 'tr' )->subordinate( 'td', array('class' => 'label'), 'Klasse:' )->parent_subordinate( 'td' );
		if( count($personen) > 1 ) {
			$Container = $Container->subordinate( 'select', array('name' => 'klasse') );
			foreach( $personen as $klasse ) {
				if( $klasse[0] != $lastone )
					$Container->subordinate( 'option', $klasse[0] );
				$lastone = $klasse[0];
			}
		}
		else {
			$Container->swallow( $personen[0][0] );
		}
	}
	
	
	protected function build_geburtsdatumwahl( rsContainer $Container ) {
		$Tag = $Container->subordinate( 'select', array('name' => 'tag') );
		for( $i = 1; $i < 32; $i++ )
			$Tag->subordinate( 'option', $i );
			
		$Monat = $Container->subordinate( 'select', array('name' => 'monat') );
		for( $i = 1; $i < 13; $i++ )
			$Monat->subordinate( 'option', $i );
			
		$Jahr = $Container->subordinate( 'select', array('name' => 'jahr') );
		for( $i = date('Y'); $i >= 1900; $i-- )
			$Jahr->subordinate( 'option', $i );
	}
	
	
	protected function process_registration( rsContainer $Container ) {
		if( $_POST['pw'] == $_POST['pw2'] ) {
			if( isset($_GET['u']) )
				$user = $this->userdb->getRow( '`id` = '. intval($_GET['u']) );
			else 
				$user = $this->userdb->getRow( '`nachname` LIKE "'. mysql_real_escape_string( $_POST['name'] ) .'" AND `klasse` LIKE "'. mysql_real_escape_string( $_POST['klasse'] ) .'"' );
			if( $user['id'] > 0 ) {
				$this->send_to_schuelerevents( array( 'email' => $_POST['email'], 'vorname' => $user['vorname'], 'nachname' => $_POST['nachname'], 'pw' => $_POST['pw'], 'pw2' => $_POST['pw'], 'schule' => 'CSG', 'geburtsdatum' => intval($_POST['tag']).'.'.intval($_POST['monat']).'.'.intval($_POST['jahr']) ) );
				$code = sha1($user['id'].$_POST['email'].intval($_POST['tag']).intval($_POST['monat']).intval($_POST['jahr']).$user['vorname'].crypt( $_POST['pw'], CRYPT_PHRASE ));
				$validation_link = 'http://www.clara-online.de/?i='. $this->docid .'&u='. $user['id'] .'&email='. $_POST['email'] .'&geb='. intval($_POST['tag']) . intval($_POST['monat']) . intval($_POST['jahr']) .'&pw='. urlencode( crypt( $_POST['pw'], CRYPT_PHRASE ) ) .'&t='. time() .'&c='. $code;
				$mailvorlage = $this->build_doc( 124 )->get_content();
				$mailvorlage = str_replace( 'VORNAME', $user['vorname'], $mailvorlage );
				$mailvorlage = str_replace( 'LINK', $validation_link, $mailvorlage );
				new rsMail( $mailvorlage, 'Registration bei clara-online', true, $_POST['email'], 'webmaster@clara-online.de' );
				$Container->subordinate( 'h2', 'Erfolgreich abgeschlossen.' )
					->append( 'p', 'Es wurde dir eine eMail geschickt um deine angegebene eMail-Adresse zu &uuml;berpr&uuml;fen. Klick bitte auf den in der eMail enthaltenen Link!' );
			}
		}
		else
			$Container->subordinate( 'h2', 'Fehler' )->append( 'p', 'Du musst 2x das selbe Passwort eingeben.' );
	}
	
	
	protected function send_to_schuelerevents( $array ) {
		$schuelerevents_response = http_post_fields( 'http://www.schuelerevents.de/index.php?i=111', $array );
		if( substr_count( $schuelerevents_response, 'Dein Benutzerkonto wurde erstellt.' ) > 0 )
			return true;
		return false;
	}
	
	
	protected function process_validation( rsContainer $Container ) {
		$user = $this->userdb->getRow( '`aktiv` = 0 AND `id` = ' . intval($_GET['u']) );
		$code = sha1($user['id'].$_GET['email'].$_GET['geb'].$user['vorname'].$_GET['pw']);
		if( $_GET['c'] == $code )
			$this->userdb->update( array(
					'email' => $_GET['email'],
					'passwort' => $_GET['pw'],
					'geburtsdatum' => $_GET['geb'],
					'registered' => time()
				), '`id` = '. $user['id'] );
		else {
			$Container->subordinate( 'p', array('class' => 'error'), 'Der Link stimmt nicht oder du hast dir mit der Freischaltung zu viel Zeit gelassen.' );
			return false;
		}
		$Container->subordinate( 'h2', 'Deine Kontodaten wurden vervollst&auml;ndigt.' )->append( 'p', 'Jetzt musst du nur noch pers&ouml;nlich bei der SV best&auml;tigen, dass du das Konto eingerichtet hast.' );
		return true;
	}
	
	
	protected function check_captcha() {
		require_once('recaptchalib.php');
		if( $_POST['recaptcha_response_field'] )
			return recaptcha_check_answer( CAPTCHAKEY_PRIVATE, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );
		return false;
	}
	
	
	protected function build_captcha( rsContainer $Container, $theme="white" ) {
		require_once('recaptchalib.php');
		$Container->swallow( '<script type="text/javascript"> var RecaptchaOptions = {theme:"'. $theme .'"}; </script>' )
			->swallow( recaptcha_get_html( CAPTCHAKEY_PUBLIC) );
	}

	
}