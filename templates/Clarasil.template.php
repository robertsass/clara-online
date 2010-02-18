<?php	/* Template Clarasil */


class Clarasil extends Root {


	protected function build_head() {
		parent::build_head();
		$this->head->link_stylesheet( 'static/css/clarasil.css' );
	}
	
	
	protected function build_top( rsContainer $Container ) {
		$Top = $Container->subordinate( 'div', array('id' => 'top') );
	#	$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/top_logo_w.png', 'id' => 'logo') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/top_title_clarasil.png', 'id' => 'title') );
		return $Top;
	}
	

}