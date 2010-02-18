<?php /* rsPrinter 2 */

class rsPrinter {
	
	private $encoding = 'utf-8';
	
	public function __construct( rsHeader $Head, rsContainer $Body ) {
		$this->prepare_output();
		$this->open_document();
		$this->build_head( $Head );
		$this->build_body( $Body );
		$this->close_document();
	}
	
	public function prepare_output() {
		$this->send_headers();
	}
	
	private function send_headers() {
		header( 'Content-Type: text/html; charset=' . $this->encoding );
	}
	
	public function open_document() {
		echo '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">' . "\n";
	}
	
	public function build_head( $Head ) {
		echo $Head->build();
	}
	
	public function build_body( $Body ) {
		echo $Body->summarize(1);
	}
	
	public function close_document() {
		echo '</html>';
	}

}