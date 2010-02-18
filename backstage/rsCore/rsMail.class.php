<?php	/* rsMail 1.0 */

class rsMail {


	protected $message;
	protected $subject;
	protected $to;
	protected $from;
	protected $html_mail;


	public function __construct( $message, $subject, $html_mail=true, $to=null, $from=null ) {
		$this->message = $message;
		$this->subject = $subject;
		$this->to = $to;
		$this->from = $from;
		$this->html_mail = $html_mail;
		if( $to != null && $from != null )
			$this->send();
	}
	
	
	public function send( $to=null, $from=null, $html_mail=null ) {
		if( !$from )
			$from = $this->from;
		if( !$to )
			$to = $this->to;
		if( $html_mail == null )
			$html_mail = $this->html_mail;
		return mail( $to, $this->subject, $this->message, $this->build_header( $from, $html_mail ) );
	}
	
	
	protected function build_header( $from, $html_mail ) {
		$headers = 'From: '. $from ."\n"; 
		if( $html_mail )
			$headers .= 'MIME-Version: 1.0'."\n" . 'Content-Type: text/html; charset=ISO-8859-1'."\n";
		return $headers;
	}


}