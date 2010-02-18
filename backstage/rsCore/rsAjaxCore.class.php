<?php	/* rsAjaxCore 1.0 */

class rsAjaxCore extends rsCore {


	protected $request;


	public function __construct( rsMysql $db=null, rsHeader $head=null, rsContainer $body=null ) {
		parent::__construct( $db, $head, $body );
		$this->request = $this->detect_requested_page();
		$this->send_headers();
	}
	
	
	protected function build() {
		echo $this->body->summarize(0);
	}
	
	
	protected function send_headers() {
		header( 'Content-Type: text/html; charset=utf-8' );
	}
	
	
	protected function init_template() {
		$this->start_session();
	}


/* Grundfunktionen des Root-Templates */
	protected function start_session() {
		session_start();
		define( 'SESSION_PARAMETER', session_name() . "=" . session_id() );
	}


	protected function detect_requested_page() {
		return $_GET['x'];
	}
	
	
}