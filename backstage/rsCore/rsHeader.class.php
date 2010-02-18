<?php /* rsHeader 2.3 */

class rsHeader {

	public $Header;
	public $pagetitle;
	protected $javascripts;
	protected $stylesheets;
	protected $metas;


	public function __construct() {
		$this->Header = new rsContainer('head');
		$this->javascripts = array();
		$this->stylesheets = array();
		$this->metas = array();
	}


	public function set_pagetitle($title) {
		$this->pagetitle = $title;
	}
	
	
	public function complete_pagetitle($title) {
		$this->pagetitle .= $title;
	}


	public function link_javascript($src) {
		$this->javascripts[$src] = new rsContainer( 'script', array( 'type' => 'text/javascript', 'src' => $src ), '' );
	}


	public function insert_javascript($src) {
		$this->javascripts[] = $src;
	}


	public function link_stylesheet($src, $media="all") {
		$this->stylesheets[$src] = new rsContainer( 'link', array( 'rel' => 'stylesheet', 'type' => 'text/css', 'href' => $src, 'media' => $media ) );
	}


	public function link_favicon($src) {
		$this->stylesheets[$src] = new rsContainer( 'link', array( 'rel' => 'shortcut icon', 'href' => $src ) );
	}


	public function add_meta($name, $content) {
		$this->metas[$name] = new rsContainer( 'meta', array( 'name' => $name, 'content' => $content ) );
	}


	public function build() {
		$this->Header->swallow( '<title>' . $this->pagetitle . '</title>' );
		foreach( $this->metas as $meta )
			$this->Header->swallow( $meta );
		foreach( $this->javascripts as $javascriptlink )
			$this->Header->swallow( $javascriptlink );
		foreach( $this->stylesheets as $stylesheetlink )
			$this->Header->swallow( $stylesheetlink );
		return $this->Header->summarize(1);
	}


}