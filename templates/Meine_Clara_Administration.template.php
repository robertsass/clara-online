<?php	/* Template Meine_Clara_Administration */

class Meine_Clara_Administration extends Meine_Clara {
	

	protected function build_content( rsContainer $Container ) {
		parent::build_content( $Container );
		$this->build_meinclara( $Container );
		return $Container;
	}


	protected function build_meinclara( rsContainer $Container ) {
		$rightsdb = new rsMysql( 'rights' );
		if( $this->has_some_admin_rights() || $this->Benutzer->get_right('docs') > 0 )
			$this->build_sekretariat( $Container );
	}
	
	
	protected function has_some_admin_rights() {
		if( $this->Benutzer->get_right( 'creating_users' ) > 0 )
			return true;
		if( $this->Benutzer->get_right( 'creating_groups' ) > 0 )
			return true;
		return false;
	}
	
	
	protected function build_sekretariat( rsContainer $Container ) {
		if( $this->Benutzer->get_right( 'creating_users' ) > 0 )
			$this->build_usercreation( $Container );
		if( $this->Benutzer->get_right( 'managing_users' ) > 0 )
			$this->build_usermanagement( $Container );
		if( $this->Benutzer->get_right( 'creating_groups' ) > 0 )
			$this->build_groupcreation( $Container );
		if( $this->Benutzer->get_right('docs') > 0 && !isset($_GET['k']) )
			$this->build_adminpanel( 'Content-Management', 'machine.png', array(
				array( 'label' => '&quot;Backstage&quot; &ouml;ffnen', 'action' => 'window.open(\'backstage\')' )
			), $Container );
	}
	
	
	protected function build_adminpanel( $title, $icon, $buttons, rsContainer $Container, $action=null ) {
		$Container = $Container->subordinate( 'div', array('class' => 'adminpanel') );
		$Container->subordinate( 'img', array('src' => 'static/images/'. $icon) )->subordinate( 'h2', $title );
		if( $action )
			$Container = $Container->subordinate( 'form', array('action' => $action, 'method' => 'GET') );
		foreach( $buttons as $button )
			$Container->subordinate( 'input', array('type' => (isset($button['type']) && $button['type']=='submit' ? 'submit' : 'button'), 'value' => $button['label'], 'onClick' => $button['action']) );
	}
	
	
	protected function build_usercreation( rsContainer $Container ) {
		if( isset($_GET['k']) && $_GET['k'] == 'usercreation' ) {
			if( isset($_POST['email']) ) {
				$gebdatum = intval( $_POST['geburtsdatum'] );
				$userdb = new rsMysql( 'user' );
				$userdb->insert( array(
					'vorname' => $_POST['vorname'],
					'nachname' => $_POST['nachname'],
					'nickname' => strtolower( $_POST['nickname'] ),
					'typ' => $_POST['typ'],
					'amt' => $_POST['amt'],
					'email' => strtolower( $_POST['email'] ),
					'passwort' => crypt( $_POST['passwort'], CRYPT_PHRASE ),
					'geburtsdatum' => intval($gebdatum),
					'aktiv' => 1
				) );
				$userid = $userdb->getColumn( 'id', '`email` = "'. mysql_real_escape_string($_POST['email']) .'"' );
				if( $_POST['docs'] != '' || $_POST['mediadirs'] != '' ) {
					$rightsdb = new rsMysql( 'rights' );
					$rightsdb->insert( array(
						'docid' => $_POST['docs'],
						'mediaid' => $_POST['mediadirs'],
						'userid' => $userid
					) );
				}
				$Container->subordinate( 'p', array('class' => 'success'), 'Der Benutzer &quot;' . $_POST['vorname'] . ' ' . $_POST['nachname'] . '&quot; ('. strtolower( $_POST['nickname'] ) .' / '. strtolower( $_POST['email'] ) .') wurde eingerichtet.' );
			}
			$Container = $Container->subordinate( 'form', array('method' => 'post', 'class' => 'spalten') );
			$Container->subordinate( 'p', '<div>Vorname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'vorname') );
			$Container->subordinate( 'p', '<div>Nachname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'nachname') );
			$Container->subordinate( 'p', '<div>Nickname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'nickname') );
			$Container->subordinate( 'p', '<div>Passwort:</div>' )->subordinate( 'input', array('type' => 'password', 'name' => 'passwort') );
			$Container->subordinate( 'p', '<div>eMail-Adresse:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'email') );
			$Container->subordinate( 'p', '<div>Amt:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'amt') );
			$Container->subordinate( 'p', '<div>Geburtsdatum:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'geburtsdatum') );
			$Typ = $Container->subordinate( 'p', '<div>Benutzer-Typ:</div>' )->subordinate( 'select', array('name' => 'typ') );
			$Typ->subordinate( 'option', array('value' => 'schueler'), 'Sch&uuml;ler' );
			$Typ->subordinate( 'option', array('value' => 'lehrer'), 'Lehrer' );
			$Typ->subordinate( 'option', array('value' => 'aussenstehender'), 'Au&szlig;enstehender' );
			$Container->subordinate( 'p', '<div>Dokument(e):</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'docs') );
			$Container->subordinate( 'p', '<div>Medienverzeichniss(e):</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'mediadirs') );
			$Container->subordinate( 'p', '<div></div>' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Einrichten') )->subordinate( 'input', array('type' => 'button', 'onClick' => 'document.location.href=\'?i='.$this->docid.'&j=administration\'', 'value' => 'Abbrechen') );
		}
		if( isset($_GET['k']) && $_GET['k'] == 'userpwautogeneration' ) {
			$this->head->link_stylesheet( 'static/css/autouserpwupdate.css', 'print' );
			$userdb = new rsMysql( 'user' );
			$List = $Container->subordinate( 'table', array('id' => 'updated_users') );
			foreach( $userdb->get('SELECT * FROM `%TABLE` WHERE `passwort` = "" AND `typ` = "schueler"') as $benutzer ) {
				$generated_pw = substr( md5( $benutzer['vorname'] . $benutzer['nachname'] . $benutzer['klasse'] . time() ), 0, 8 );
				if( !isset($_GET['debug']) )	// Zu Testzwecken!
					$userdb->update( array('passwort' => crypt($generated_pw, CRYPT_PHRASE), 'nickname' => $benutzer['id'], 'aktiv' => 1), '`id`='.$benutzer['id'] );
				$List->subordinate( 'tr' )
					->subordinate( 'td', '<b>'. $benutzer['klasse'] .')</b>' )
					->append( 'td', '<b>'. $benutzer['nachname'] .'</b>,' )
					->append( 'td', '<b>'. $benutzer['vorname'] .'</b>' )
					->append( 'td', 'Benutzer: <b>'. $benutzer['id'] .'</b>' )
					->append( 'td', 'Passwort: <b>'. $generated_pw .'</b>' );
			}
		}
		elseif( !isset($_GET['k']) ) {
			$this->build_adminpanel( 'Benutzer', 'user.png', array(
				array(
					'label' => 'Neuen Benutzer einrichten',
					'action' => 'location.href=\'?i='.$this->docid.'&j=administration&k=usercreation\''
					),
				array(
					'label' => 'Benutzer verwalten',
					'action' => 'location.href=\'?i='.$this->docid.'&j=administration&k=usermanagement\''
					),
				array(
					'label' => 'Tempor&auml;re Passw&ouml;rter zuweisen',
					'action' => 'location.href=\'?i='.$this->docid.'&j=administration&k=userpwautogeneration\''
					)
			), $Container );
		}
	}
	
	
	protected function build_usermanagement( rsContainer $Container ) {
		if( isset($_GET['k']) && $_GET['k'] == 'usermanagement' ) {
			if( intval($_POST['userid'] > 0) ) {
				$userdb = new rsMysql( 'user' );
				$rightsdb = new rsMysql( 'rights' );
				if( isset($_POST['email']) ) {
					$gebdatum = explode( '.', $_POST['geburtsdatum'] );
					$userdb->update( array(
						'vorname' => $_POST['vorname'],
						'nachname' => $_POST['nachname'],
						'aktiv' => ($_POST['aktiv'] == 'on' ? '1' : '0'),
						'nickname' => strtolower( $_POST['nickname'] ),
						'klasse' => strtolower( $_POST['klasse'] ),
						'typ' => $_POST['typ'],
						'amt' => $_POST['amt'],
						'email' => strtolower( $_POST['email'] ),
						'geburtsdatum' => mktime( 0, 0, 0, intval($gebdatum[1]), intval($gebdatum[0]), intval($gebdatum[2]) )
					), '`id`='. intval($_POST['userid']) );
					if( $_POST['docs'] != '' || $_POST['mediadirs'] != '' ) {
						$rightsdb->update_insert( array(
							'docid' => $_POST['docs'],
							'mediaid' => $_POST['mediadirs'],
							'userid' => intval($_POST['userid'])
						), '`userid`='. intval($_POST['userid']) );
					}
					$Container->subordinate( 'p', array('class' => 'success'), 'Der Benutzer &quot;' . $_POST['vorname'] . ' ' . $_POST['nachname'] . '&quot; ('. strtolower( $_POST['email'] ) .') wurde &uuml;berarbeitet.' );
				}
				$user = $userdb->getRow( '`id` = ' . intval($_POST['userid']) );
				$Container = $Container->subordinate( 'form', array('method' => 'post', 'class' => 'spalten') );
				$Container->subordinate( 'input', array('type' => 'hidden', 'name' => 'userid', 'value' => intval($_POST['userid'])) );
				$Container->subordinate( 'p', '<div>Vorname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'vorname', 'value' => $user['vorname']) );
				$Container->subordinate( 'p', '<div>Nachname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'nachname', 'value' => $user['nachname']) );
				$Container->subordinate( 'p', '<div>Freigeschaltet:</div>' )->subordinate( 'input', array('type' => 'checkbox', 'name' => 'aktiv', 'checked' => ($user['aktiv'] == 0 ? 'false' : 'true')) );
				$Container->subordinate( 'p', '<div>Klasse:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'klasse', 'value' => $user['klasse']) );
				$Container->subordinate( 'p', '<div>Nickname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'nickname', 'value' => $user['nickname']) );
				$Container->subordinate( 'p', '<div>eMail-Adresse:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'email', 'value' => $user['email']) );
				$Container->subordinate( 'p', '<div>Amt:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'amt', 'value' => $user['amt']) );
				$Container->subordinate( 'p', '<div>Geburtsdatum:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'geburtsdatum', 'value' => date('d.m.Y', $user['geburtsdatum']) ) );
				$Typ = $Container->subordinate( 'p', '<div>Benutzer-Typ:</div>' )->subordinate( 'select', array('name' => 'typ') );
				$Typ->subordinate( 'option', array('value' => 'schueler'), 'Sch&uuml;ler' );
				$Typ->subordinate( 'option', array('value' => 'lehrer'), 'Lehrer' );
				$Typ->subordinate( 'option', array('value' => 'aussenstehender'), 'Au&szlig;enstehender' );
				$Container->subordinate( 'p', '<div>Dokument(e):</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'docs', 'value' => $rightsdb->getColumn('docid', '`userid`='.$user['id']) ) );
				$Container->subordinate( 'p', '<div>Medienverzeichniss(e):</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'mediadirs', 'value' => $rightsdb->getColumn('mediaid', '`userid`='.$user['id'])) );
				$Container->subordinate( 'p', '<div></div>' )->subordinate( 'input', array('type' => 'submit', 'value' => '&Auml;nderungen sichern') )->subordinate( 'input', array('type' => 'button', 'onClick' => 'document.location.href=\'?i='.$this->docid.'&j=administration\'', 'value' => 'Abbrechen') );
			}
			else {
				$Container = $Container->subordinate( 'form', array('method' => 'post') );
				$Container->subordinate( 'p', '<div>Benutzer: <span id="foundusername"></span></div>' )->subordinate( 'input', array('type' => 'hidden', 'name' => 'userid', 'id' => 'inputfounduserid') )->subordinate( 'div', array('id' => 'getUser') );
				$Container->subordinate( 'div', array('id' => 'userprofile') );
				$Container->subordinate( 'p', '<div></div>' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Benutzerkonto bearbeiten') )->subordinate( 'input', array('type' => 'button', 'onClick' => 'document.location.href=\'?i='.$this->docid.'&j=administration\'', 'value' => 'Abbrechen') );
			}
		}
	}
	
	
	protected function build_groupcreation( rsContainer $Container ) {
		if( isset($_GET['k']) && $_GET['k'] == 'groupcreation' ) {
			if( isset($_POST['name']) && $_POST['name'] != '' ) {
				$groupsdb = new rsMysql( 'groups' );
				$rightsdb = new rsMysql( 'rights' );
				$groupmembersdb = new rsMysql( 'groupmember' );
				$doctree = new rsTree( 'tree' );
				$docid = $doctree->createChild( 98 );
				$doctree->update( array('name' => $_POST['name']), '`id` = '.$docid );
				$mediatree = new rsTree( 'media' );
				$mediaid = $mediatree->createChild( 1 );
				$mediatree->update( array('name' => $_POST['name']), '`id` = '.$mediaid );
				$groupsdb->insert( array(
					'name' => $_POST['name'],
					'leiter' => intval($_POST['leiter']),
					'typ' => $_POST['typ'],
					'docid' => $docid,
					'mediaid' => $mediaid
				) );
				$docids = $rightsdb->getColumn( 'docid', '`id` = '.intval($_POST['leiter']) ) . ',' . $docid;
				$mediaids = $rightsdb->getColumn( 'mediaid', '`id` = '.intval($_POST['leiter']) ) . ',' . $mediaid;
				$rightsdb->update_insert( array('docid' => $docids, 'mediaid' => ','.$mediaids), array('userid' => intval($_POST['leiter'])), '`userid` = '.intval($_POST['leiter']) );
				$groupmembersdb->update_insert( array('groupid' => $this->groupdata['id'], 'userid' => $userid), '`userid` = '.$userid.' AND `groupid` ='.$this->groupdata['id'] );
				$Container->subordinate( 'p', array('class' => 'success'), 'Die Gruppe &quot;' . $_POST['name'] . '&quot; wurde eingerichtet.' );
			}
			$Container = $Container->subordinate( 'form', array('method' => 'post') );
			$Container->subordinate( 'p', '<div>Gruppenname:</div>' )->subordinate( 'input', array('type' => 'text', 'name' => 'name') );
			$Container->subordinate( 'p', '<div>Leiter: <span id="foundusername"></span></div>' )->subordinate( 'input', array('type' => 'hidden', 'name' => 'leiter', 'id' => 'inputfounduserid') )->subordinate( 'div', array('id' => 'getUser') );
			$Typ = $Container->subordinate( 'p', '<div>Gruppen-Typ:</div>' )->subordinate( 'select', array('name' => 'typ') );
			$Typ->subordinate( 'option', array('value' => 'klasse'), 'Klasse' );
			$Typ->subordinate( 'option', array('value' => 'kurs'), 'Kurs' );
			$Typ->subordinate( 'option', array('value' => 'ag'), 'AG / Sonstige' );
			$Container->subordinate( 'p', '<div></div>' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Einrichten') )->subordinate( 'input', array('type' => 'button', 'onClick' => 'document.location.href=\'?i='.$this->docid.'&j=administration\'', 'value' => 'Abbrechen') );
		}
		elseif( !isset($_GET['k']) ) {
			$this->build_adminpanel( 'Gruppen', 'group_icon.png', array(
				array( 'label' => 'Neue Gruppe gr&uuml;nden', 'action' => 'location.href=\'?i='.$this->docid.'&j=administration&k=groupcreation\'' )
			), $Container );
		}
	}

	
}