<?php	/* Template Sphider */

class Sphider extends Root {

	
	protected function build_head() {
		parent::build_head();
		$this->head->link_stylesheet( 'static/css/suche.css' );
	}

	protected function build_submenu( rsContainer $Container ) {}


	protected function build_content( rsContainer $Container ) {
		$this->build_sidebar( $Container );
		$Container->add_attribute( 'class', 'suche' );
		$Container = $Container->subordinate( 'div', array('class' => 'content') );
		$Container->subordinate( 'script', array('type' => 'text/javascript', 'language' => 'JavaScript'), '$(\'.content\').load(\'sphider/search.php?search=1&query='. $_GET['suche'] .'\');' );
	}
	
	
	protected function build_sidebar( rsContainer $Container ) {
		$Sidebar = $Container->subordinate( 'div', array('id' => 'sidebar') );
		$this->build_mostviewed( $Sidebar );
		$Sidebar->subordinate( 'input', array('type' => 'button', 'value' => 'Zufallsseite aufrufen', 'onClick' => 'location.href=\'?i='. $this->get_random_pageid() .'\'') );
		$Headers = $Sidebar->search( array('tag' => 'h2') );
		$Headers[0]->add_attribute( 'class', 'first');
	}
	
	
	protected function get_random_pageid() {
		$pages = $this->db->get('SELECT `id` FROM `%TABLE` WHERE `lft` > '. $this->get_left_value(NAVIGATION) .' AND `rgt` < '. $this->get_right_value(NAVIGATION) .' AND (`lft` < '. $this->get_left_value(13) .' OR `rgt` > '. $this->get_right_value(13) .')');
		$random = mt_rand( 0, count($pages)-1 );
		return $pages[ $random ][0];
	}
	
	
	protected function build_mostviewed( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Meist angesehen' );
		$List = $Container->subordinate( 'ul', array('class' => 'mostviewed') );
		foreach( $this->db->get('SELECT * FROM `%TABLE` WHERE `lft` > '. $this->get_left_value(NAVIGATION) .' AND `rgt` < '. $this->get_right_value(NAVIGATION) .' ORDER BY `count` DESC LIMIT 0,10') as $item )
			$List->subordinate( 'li' )->subordinate( 'a', array('href' => '?i='.$item['id']), $item['name'] )->append( 'span', '('. $item['count'] .')' );
		return $List;
	}


}