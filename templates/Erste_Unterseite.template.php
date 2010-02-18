<?php	/* Template Erste_Unterseite */

class Erste_Unterseite extends Root {

	
	protected function build_submenu( rsContainer $Container, $root=null, $active=null, $overwritingOld=false ) {
		$Page = new rsPage( $this->active_menu_element, $this->db );
		if( $overwritingOld )
			$Submenu = $Container;
		else
			$Submenu = $Container->subordinate( 'div', array('id' => 'submenu') );
		$Menu = new rsMenu( $Submenu, $this->db, ($overwritingOld ? $this->page->get_docid() : false) );
		$first = true;
		foreach( $this->get_sublevel_documents($this->active_menu_element) as $menuItem ) {
			$Li = $Menu->add_doc( $menuItem );
			if($first) {
				$firstLi = $Li;
				$firstId = $menuItem['id'];
				if( $Menu->is_active( $menuItem['id'] )  ) {
					$Menu->select_item( $Li, $menuItem['id'] );
					$this->docid = $menuItem['id'];
				}
				$first = false;
			}
			if( $Menu->is_active( $menuItem['id'] ) && $menuItem['offspring'] > 0  )
				$this->build_thirdlevelsubmenu( $menuItem['id'], $Li );
		}
		if( $Menu->get_active() == null ) {
			$Menu->select_item( $firstLi, $firstId );
			$this->build_thirdlevelsubmenu( $Menu->get_active(), $firstLi, ($menuItem['template'] == 'Erste_Unterseite' ? true : false) );
			$this->docid = $firstId;
		}
		return $Submenu;
	}
	
	
	protected function build_thirdlevelsubmenu( $docid, rsContainer $Li, $alt_select_first=false ) {
		$Menu = new rsMenu( $Li->subordinate( 'div', array('class' => 'menu thirdlevel') ), $this->db );
		$first = true;
		foreach( $this->get_sublevel_documents($docid) as $menuItem ) {
			$Li = $Menu->add_doc( $menuItem );
			if($first) {
				$firstLi = $Li;
				$firstId = $menuItem['id'];
				if( $Menu->is_active( $menuItem['id'] )  )
					$Menu->select_item( $Li, $menuItem['id'] );
				$first2 = false;
			}
		}
		if( $alt_select_first && $Menu->get_active() == null ) {
			$Menu->select_item( $firstLi, $firstId );
			$this->build_thirdlevelsubmenu( $Menu->get_active(), $firstLi );
			$this->docid = $firstId;
		}
	}
	
	
	protected function build_content( rsContainer $Container ) {
		$this->page = new rsPage( $this->docid, $this->db );
		$this->build_submenu( $this->mainContainer['submenu']->clear(), null, null, true );
		parent::build_content( $Container );
	}

	
}