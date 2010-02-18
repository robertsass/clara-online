<?php	/* Template SV_ClaraRockt */


class SV_ClaraRockt extends Root {


	protected function build_head() {
		parent::build_head();
		$this->head->link_stylesheet( 'static/css/sv_clararockt.css' );
	}
	
	
	protected function build_top( rsContainer $Container ) {
		$Top = $Container->subordinate( 'div', array('id' => 'top') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/top_title_clararockt.png', 'id' => 'title') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/csgstamp_white_s.png', 'id' => 'stamp') );
		return $Top;
	}
	

}