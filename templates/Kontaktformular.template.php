<?php	/* Template Kontaktformular */


class Kontaktformular extends Root {

	protected $empfaenger = "webmaster@clara-online.de";

	protected function build_head() {
		parent::build_head();
	}
	
	
	protected function build_content( rsContainer $Container ) {
		$Content = parent::build_content( $Container );
		if( !isset($_POST['nachricht']) )
			$this->build_formular( $Container );
		else
			$this->send_message( $Container );
	}
	
	
	protected function build_formular( rsContainer $Container ) {
		$Form = $Container->subordinate( 'form', array('action' => 'index.php?i='.$this->docid, 'method' => 'post') );
		$Form->subordinate( 'p', 'Name:' )->subordinate( 'br' )->subordinate( 'input', array('type' => 'text', 'name' => 'absenderName') );
		$Form->subordinate( 'p', 'eMail-Adresse:' )->subordinate( 'br' )->subordinate( 'input', array('type' => 'text', 'name' => 'absenderEmail') );
		$Form->subordinate( 'p', 'Nachricht:' )->subordinate( 'br' )->subordinate( 'textarea', array('rows' => 15, 'cols' => 40, 'name' => 'nachricht') );
		$Form->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'abschicken') );
	}
	
	
	protected function send_message( rsContainer $Container ) {
		$Mail = new rsMail( nl2br( htmlentities( $_POST['nachricht'] ) ), 'Nachricht von clara-online.de' );
		$Mail->send( $this->empfaenger, stripslashes( $_POST['absenderName'] .'<'. $_POST['absenderEmail'] .'>' ) );
		$this->build_success( $Container );
	}
	
	
	protected function build_success( rsContainer $Container ) {
		$Container->subordinate( 'p' )->subordinate( 'b', 'Vielen Dank! Wir werden schnellstm&ouml;glich antworten.' );
	}
	

}