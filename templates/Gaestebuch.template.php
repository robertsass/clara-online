<?php	/* Template GŠstebuch */

class Gaestebuch extends Root {


	protected $entries_per_page = 25;
	protected $Benutzer;
	
	
	protected function build_content( rsContainer $Container ) {
		parent::build_content( $Container );
		$this->Benutzer = new rsUser();
		if( isset($_GET['guestbook']) )
			$this->process_validation( $Container );
		if( isset($_POST['text']) )
			$post_saved = $this->process_postinput( $Container );
		if( isset($_GET['delete']) )
			$this->remove_post( intval($_GET['delete']) );
		$this->clean_db();
		$this->build_postform( $Container->subordinate( 'div', array('class' => 'panel') )->subordinate( 'div', array('class' => 'content') ), ($post_saved ? false : true) );
		$this->build_postlist( $Container );
	}
	
	
	protected function build_postform( rsContainer $Container, $preinsert_sent_input=false ) {
		$Container->subordinate( 'h2', 'Neuen Eintrag verfassen' );
		$Form = $Container->subordinate( 'form', array('method' => 'post') );
		if( $this->Benutzer->auth() )
			$Form->subordinate( 'p', 'Name: ' . $this->Benutzer->get('vorname') .' '. $this->Benutzer->get('nachname') );
		else {
			$Form->subordinate( 'p', 'Name: ' )->subordinate( 'input', array('type' => 'text', 'name' => 'name', 'value' => ($preinsert_sent_input ? $_POST['name'] : '') ) );
			$Form->subordinate( 'p', 'eMail-Adresse: ' )->subordinate( 'input', array('type' => 'text', 'name' => 'email', 'value' => ($preinsert_sent_input ? $_POST['email'] : '') ) );
		}
		$Form->subordinate( 'p' )->subordinate( 'textarea', array('rows' => 8, 'cols' => 55, 'name' => 'text'), ($preinsert_sent_input ? $_POST['text'] : '') );
		$Form->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Abschicken') );
	}
	
	
	protected function clean_db() {
		$postdb = new rsMysql( 'guestbook' );
		$postdb->delete( '`valid` = "0" AND `timestamp` < ' . (time()-86400) );
	}
	
	
	protected function remove_post( $postid ) {
		$postdb = new rsMysql( 'guestbook' );
		$data = $postdb->getRow( '`id` = ' . $postid );
		if( $this->is_author( $data ) )
			$postdb->delete( '`id` = ' . $postid );
		else
			return false;
		return true;
	}
	
	
	protected function is_author( $data ) {
		if( $data['email'] == $this->Benutzer->get('email') && $data['name'] == $this->Benutzer->get('vorname') .' '. $this->Benutzer->get('nachname') )
			return true;
		return false;
	}
	
	
	protected function process_postinput( rsContainer $Container ) {
		if( ( $this->Benutzer->auth() && $_POST['text'] != '' ) || ( $_POST['name'] != '' && $_POST['email'] != '' && substr_count( $_POST['email'], '@' ) == 1 && substr_count( $_POST['email'], '.' ) > 0 && $_POST['text'] != '' ) ) {
			$postdb = new rsMysql( 'guestbook' );
			$data = array(
					'name' => $_POST['name'],
					'email' => $_POST['email'],
					'text' => str_replace( '<br><br>', '<br>', nl2br($_POST['text']) ),
					'timestamp' => time()
				);
			if( $this->Benutzer->auth() ) {
				$data['valid'] = 1;
				$data['name'] = $this->Benutzer->get('vorname') .' '. $this->Benutzer->get('nachname');
				$data['email'] = $this->Benutzer->get('email');
			}
			$postdb->insert( $data );
			if( !$this->Benutzer->auth() ) {
				$postid = $postdb->getColumn( 'id', '`name` = "'. $data['name'] .'" AND `email` = "'. $data['email'] .'" AND `timestamp` = '. $data['timestamp'] );
				$code = md5( $data['name'] . $data['email'] . $data['timestamp'] );
				$Container->subordinate( 'p', array('class' => 'notice'), 'Vielen Dank!<br/>Bitte schalten Sie Ihren Eintrag noch frei, indem Sie innerhalb der n&auml;chsten 24 Stunden auf den Link klicken, der Ihnen per Email zugesandt wurde.' );
				mail( $_POST['email'], 'Freischaltung Ihres Gaestebuch-Eintrags', "Hallo ". $data['name'] .",\nSie haben soeben einen Gaestebuch-Eintrag auf clara-online.de verfasst:\n\n\"". $data['text'] ."\"\n\n\nBitte bestaetigen Sie dies, indem Sie innerhalb von 24 Stunden auf folgenden Link klicken:\n\n" . $_SERVER['SCRIPT_URI'] . "?i=". $this->docid ."&guestbook=". $code ."&postid=". $postid ."\n\nMit freundlichen Gruessen,\ndie Redaktion von clara-online.de", 'FROM: clara-online <clara3@clara-online.de>' );
			}
			return true;
		}
		else
			$Container->subordinate( 'p', array('class' => 'error'), 'Bitte f&uuml;llen Sie alle Felder korrekt aus! Ihre eMail-Adresse ist nur zum Freischalten Ihres Eintrags n&ouml;tig, wird aber nirgends ver&ouml;ffentlicht.' );
		return false;
	}
	
	
	protected function process_validation( rsContainer $Container ) {
		$postdb = new rsMysql( 'guestbook' );
		$data = $postdb->getRow( '`id` = ' . intval($_GET['postid']) );
		if( $_GET['guestbook'] == md5( $data['name'] . $data['email'] . $data['timestamp'] ) )
			$postdb->update( array('valid' => 1), '`id` = ' . intval($_GET['postid']) );
		else {
			$Container->subordinate( 'p', array('class' => 'error'), 'Der Link stimmt nicht oder Sie haben sich mit der Freischaltung zu viel Zeit gelassen.' );
			return false;
		}
		return true;
	}
	
	
	protected function build_postlist( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'G&auml;stebuch-Eintr&auml;ge' );
		$start = ( isset($_GET['start']) ? intval($_GET['start']) : 0 );
		$postdb = new rsMysql( 'guestbook' );
		$entries = $postdb->get( 'SELECT * FROM  `%TABLE` WHERE `valid` = "1" ORDER BY `id` DESC LIMIT '. $start .' , '. ($start+$this->entries_per_page) );
		$Postlist = $Container->subordinate( 'ul', array('class' => 'guestbook list') );
		foreach( $entries as $entry ) {
			$PostLi = $Postlist->subordinate( 'li' );
			$PostLi->subordinate( 'div', array('class' => 'meta'), $entry['name'] )->subordinate( 'br' )->subordinate( 'span', array('class' => 'date'), '(' . date( 'd.m.Y', $entry['timestamp'] ) . ')' )->parent_subordinate( 'br' )->swallow( ($this->is_author($entry) ? ' [<a href="?i='.$this->docid.'&delete='.$entry['id'].'">Eintrag l&ouml;schen</a>]' : '') );
			$PostLi->subordinate( 'p', array('class' => 'post text'), $entry['text'] );
		}
	}

	
}