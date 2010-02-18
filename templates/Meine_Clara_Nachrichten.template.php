<?php	/* Template Meine_Clara_Dateien */

class Meine_Clara_Nachrichten extends Meine_Clara {


	protected $mailsdb;
	
	
	protected function build_head() {
		parent::build_head();
		$this->head->link_javascript( 'static/js/jquery.tablesorter.js' );
		$this->head->link_javascript( 'static/js/jquery.searchable.js' );
	}
	
	
	protected function build_content( rsContainer $Container ) {
		parent::build_content( $Container );
		if( $this->Benutzer->auth() ) {
			$this->mailsdb = new rsMysql( 'mails' );
			$this->list_mymails( $Container );
		}
	}
	
	
	protected function list_mymails( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Meine Nachrichten' )->subordinate( 'span', array('class' => 'tablesearch'), 'Nachrichten durchsuchen:' )->subordinate( 'input', array('type' => 'text', 'value' => '') );
		$Form = $Container->subordinate( 'form', array('method' => 'post', 'action' => '#menu', 'onSubmit' => 'if(!confirm(\'Bist Du sicher, dass Du die markierten Nachrichten l&ouml;schen willst?\'))return false;') );
		$Table = $Form->subordinate( 'table', array('class' => 'list') );
		$Table->subordinate( 'thead' )->subordinate( 'tr' )
			->subordinate( 'th', 'Von' )
			->append( 'th', 'Betreff' )
			->append( 'th', array('class' => 'sorted-asc'), 'Datum' )
			->append( 'th', '&nbsp;' );
		$Table = $Table->subordinate( 'tbody' );
		$my_mails = $this->mailsdb->get( 'SELECT * FROM `%TABLE` WHERE `to` = '. $this->Benutzer->get('id') .' ORDER BY `timestamp` DESC' );
		foreach( $my_mails as $mail ) {
		    $userdata = $this->get_userdata($mail['from']);
		    if( $mail['gelesen'] == 0 )
		    	$attributes = array('class' => 'ungelesen');
			$Table->subordinate( 'tr', $attributes )
				->subordinate( 'td', $userdata['vorname'] . " " . $userdata['nachname'] )
				->append( 'td', $mail['betreff'] )
				->append( 'td', date('H:i \/ d.m.Y', $mail['timestamp']) )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'checkbox', 'name' => 'f'.$mail['id']) );
		}
		$Form->subordinate( 'p', array('class' => 'tableoptions') )
				->subordinate( 'input', array('type' => 'submit', 'name' => 'button', 'value' => 'L&ouml;schen') );
	}
	

}