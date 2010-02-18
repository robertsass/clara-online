<?php /* rsMenu 2.2 */

class rsMenu extends rsCore {


	public $menu_container;
	protected $active_menu_element = null;
	protected $active_doc;
	protected $alt_select_first;
	
	
	public function __construct( rsContainer $Container, rsMysql $db, $active_doc=null ) {
		parent::__construct( $db );
		$this->menu_container = $Container->subordinate( 'ul' );
		if( $active_doc )
			$this->active_doc = $active_doc;
		else
			$this->active_doc = parent::detect_requested_page();
	}


	public function add_doc( $doc ) {
		if( is_int($doc) )
			$doc = 	$this->db->getRow( '`id` = "'. $doc .'"' );
		$Li = $this->menu_container->subordinate( 'li' );
		if( $doc['id'] == $this->active_doc || ( $this->get_left_value($doc['id']) < $this->get_left_value() && $this->get_right_value($doc['id']) > $this->get_right_value() ) )
			$this->select_item( $Li, $doc['id'] );
		$Li->subordinate( 'a', array('href' => '?i=' . $doc['id']) )->swallow( $this->get_title($doc['id']) );
		return $Li;
	}


	public function select_item( rsContainer $Container, $docid ) {
		$Container->add_attribute( 'class', 'active' );
		$this->active_menu_element = intval($docid);
	}
	
	
	public function clear() {
		$this->menu_container->clear();
		$this->active_menu_element = null;
		$this->active_doc = null;
		return $this;
	}
	
	
	protected function get_title( $docid ) {
		return $this->db->getColumn( 'name', '`id` = ' . $docid );
	}


	public function add_item( $title, $link ) {
		$Li = $this->menu_container->subordinate( 'li' /*, $attr*/ );
		$Li->subordinate( 'a', array('href' => (is_int($link) ? '?i=' . $link : $link) ) )->swallow( $title );
		return $Li;
	}
	
	
	public function get_active() {
		return $this->active_menu_element;
	}
	
	
	public function is_active( $docid ) {
		if( $docid == $this->active_doc || ( $this->get_left_value($docid) < $this->get_left_value($this->active_doc) && $this->get_right_value($docid) > $this->get_right_value($this->active_doc) ) )
			return true;
		return false;
	}


	public function summarize( $ebene ) {
		return $this->menu_container->summarize( $ebene );
	}


}