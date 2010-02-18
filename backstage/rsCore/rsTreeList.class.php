<?php	/* rsTreeList 1.1 */

class rsTreeList extends rsCore {

	public function __construct( $startroot, rsContainer $List, rsMysql $DB, $show_ids=true ) {
		$this->db = $DB;
		$this->list_branch( $startroot, $List, $show_ids );
	}
	
	
	protected function list_branch( $root, rsContainer $List, $show_ids ) {
		foreach( $this->get_sublevel_documents( $root, $this->db ) as $doc )
			$this->build_list_element( $doc, $List, $show_ids );
	}
	
	
	protected function build_list_element( $doc, rsContainer $List, $show_ids ) {
		$Li = $List->subordinate( 'li', array('id' => $doc['id']) );
		$LiDiv = $Li->subordinate( 'div', array('id' => 'div'.$doc['id']) );
		$LiDiv->subordinate( 'a', array('onClick' => 'show_doceditor('. $doc['id'] .')'), $doc['name'] );
		if( $show_ids )
			$LiDiv->subordinate( 'span', array('class' => 'id'), '#'.$doc['id'].' ('. $doc['lft'].'|'.$doc['rgt'] .')' );		
		if( $doc['offspring'] > 0 )
			$this->list_branch( $doc['id'], $Li->subordinate( 'ul' ), $show_ids );
	}

}