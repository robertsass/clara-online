<?php	/* Template SV */


class SV extends Root {


	protected function build_head() {
		parent::build_head();
		$this->head->link_stylesheet( 'static/css/sv.css' );
	}
	
	
	protected function dye( $section ) {}
	
	
	protected function build_top( rsContainer $Container ) {
		$Top = $Container->subordinate( 'div', array('id' => 'top') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/top_logo_sv.png', 'id' => 'logo') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/top_title_sv.png', 'id' => 'title') );
		return $Top;
	}
	

}