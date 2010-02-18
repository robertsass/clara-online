<?php	/* Template Blog */

class Blog extends Root {


	protected $blogdir = 10;
	
	
	protected function build_content( rsContainer $Container ) {
		$this->blogdir = $this->docid;
		parent::build_content( $Container );
	}
	
	
	protected function build_content_part( rsPage $Page, rsContainer $Container ) {
		$text = explode( '<!-- pagebreak -->', str_replace('<p><!-- pagebreak --></p>', '<!-- pagebreak -->', $Page->get_content() ) );
		$Container->subordinate( 'h1', ( $Page->get_description() == '' ? $Page->get_title() : $Page->get_description() ) );
		$Content = $Container->subordinate( 'div', array('class' => 'content') );
		$Content->subordinate( 'div', array('class' => 'text'), $text[0] );
		$this->build_bloglist( $Content );
		$Content->subordinate( 'div', array('class' => 'text'), $text[1] );
		return $Content;
	}
	
	
	protected function build_bloglist( rsContainer $Container ) {
		$Bloglist = $Container->subordinate( 'ul', array('class' => 'blog list') );
		$first = true;
		foreach( $this->get_sublevel_documents($this->blogdir) as $blogpost ) {
			$teaser = explode( '<!-- pagebreak -->', $blogpost['content'] );
			$teaser = str_replace( '<p>', '', str_replace( '</p>', '', $teaser[0] ) );
			$BlogLi = $Bloglist->subordinate( 'li', array('class' => 'blogpost') );
			if( $first ) {
				$BlogLi->add_attribute('class', 'first');
				$first = false;
			}
			$BlogLi->subordinate( 'a', array('href' => '?i='. $blogpost['id']) )->subordinate( 'h2', $blogpost['name'] );
			$BlogLi->subordinate( 'div', array('class' => 'teaser'), $teaser )->subordinate( 'a', array('href' => '?i='. $blogpost['id']), 'Weiterlesen...' );
		}
	}

	
}