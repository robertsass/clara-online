<?php /* rsRSSFeed 1 */

class rsRSSFeed {
	
	private $encoding = 'utf-8';
	
	public function __construct( rsContainer $Channel ) {
		$this->prepare_output();
		$this->open_document();
		$this->build_head( $Channel );
		$this->build_body( $Channel );
		$this->close_document();
	}
	
	public function prepare_output() {
		$this->send_headers();
	}
	
	private function send_headers() {
		header( 'Content-Type: application/rss+xml; charset=' . $this->encoding );
	}
	
	public function open_document() {
		echo '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . "\n";
		echo '<rss version="2.0">' . "\n";
	}
	
	public function build_head( $Channel ) {
		$Channel->pre_subordinate( 'generator', 'rsCore' );
		$Channel->pre_subordinate( 'link', $_SERVER['SERVER_NAME'] );
		$Channel->pre_subordinate( 'description', SITE_TITLE );
		$Channel->pre_subordinate( 'title', SITE_NAME );
	}
	
	public function build_body( $Channel ) {
		echo $Channel->summarize(1);
	}
	
	public function close_document() {
		echo '</rss>';
	}

}