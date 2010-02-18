<?php	/* rsTreeList 1.0 */

class rsMediaTreeList extends rsTreeList {


	protected $options;
	protected $show_ids;
	

	public function __construct( $startroot, rsContainer $List, rsMysql $DB, $show_ids=true, $options=array() ) {
		$this->show_ids = $show_ids;
		$this->options = $options;
		parent::__construct( $startroot, $List, $DB, $show_ids );
	}
	
	
	protected function build_list_element( $dir, rsContainer $Li, $show_ids ) {
		$Li = $this->subordinate_dir( $dir, $Li );
		$filesdb = new rsMysql( 'files' );
		$files = $filesdb->get( 'SELECT * FROM `' . $filesdb->get_dbtable() . '` WHERE `parentdir` = ' . $dir['id'] . ' ORDER BY `title` ASC' );
		if( count($files) > 0 )
			$List = $Li->subordinate( 'ul' );
		foreach( $files as $file )
			$this->subordinate_file( $file, $List );
		if( $dir['offspring'] > 0 )
			$this->list_branch( $dir['id'], $Li->subordinate( 'ul' ), $show_ids );
	}


	protected function subordinate_file( $item, rsContainer $List ) {
		$Li = $List->subordinate( 'li', array('class' => 'file', 'id' => $item['id']) );
		$LiDiv = $Li->subordinate( 'div', array('id' => '?f='.$item['id']) );
		$suffix = explode( '.', $item['filename'] );
		$suffix = strtolower( $suffix[1] );
		if( isset($this->options['file_link']) ) {
			foreach( $this->options['file_link'] as $key => $value )
				$this->options['file_link'][ $key ] = str_replace( '%FILEID', $item['id'], $value );
			$LiDiv->subordinate( 'a', $this->options['file_link'], $item['title'] )->subordinate( 'span', array('class' => 'suffix'), '.' . $suffix );
		}
		else
			$LiDiv->swallow( $item['title'] )->subordinate( 'span', array('class' => 'suffix'), '.' . $suffix );
		$LiDiv->subordinate( 'span', array('class' => 'description'), $item['description'] );
	}


	protected function subordinate_dir( $item, rsContainer $List ) {
		$Li = $List->subordinate( 'li', array('class' => 'dir', 'id' => $item['id']) );
		$LiDiv = $Li->subordinate( 'div', array('id' => 'div'.$item['id']) );
		if( isset($this->options['dir_link']) )
			$LiDiv->subordinate( 'a', $this->options['dir_link'], $item['name'] );
		else
			$LiDiv->swallow( $item['name'] );
		if( $this->show_ids )
			$LiDiv->subordinate( 'span', array('class' => 'id'), '('.$item['id'].')' );
		return $Li;
	}


}