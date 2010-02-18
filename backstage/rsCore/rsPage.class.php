<?php	/* rsPage 2.2 */

class rsPage {


	private $title;
	private $description;
	private $content;
	private $template;
	private $page;
	private $docid;
	
	
	public function __construct( $docid, rsMysql $db=null ) {
		if( !$db )
			$db = new rsMysql( 'tree' );
		$this->page = $db->getRow( '`id` = ' . intval( $docid ) );
		$this->set_title( $this->page['name'] );
		$this->set_description( $this->page['beschreibung'] );
		$this->set_content( $this->page['content'] );
		$this->set_template( $this->page['template'] );
		$this->docid = intval( $docid );
	}
	
	
	public function clear() {
		$this->title = false;
		$this->description = '';
		$this->content = '';
		$this->template = '';
		return $this;
	}
	
	
	public function set( $array ) {
		$this->set_title( $array['title'] );
		$this->set_description( $array['description'] );
		$this->set_content( $array['content'] );
		return $this;
	}
	
	
	public function set_title( $value ) {
		$this->title = $value;
		return $this;
	}
	
	
	public function set_description( $value ) {
		$this->description = $value;
		return $this;
	}
	
	
	public function set_content( $value ) {
		$this->content = $value;
		return $this;
	}
	
	
	public function set_template( $value ) {
		$this->template = $value;
		return $this;
	}
	
	
	public function get_title() {
		return $this->title;
	}
	
	
	public function get_description() {
		return $this->description;
	}
	
	
	public function get_content() {
		return $this->content;
	}
	
	
	public function get_template() {
		return $this->template;
	}
	
	
	public function get_docid() {
		return $this->docid;
	}


}