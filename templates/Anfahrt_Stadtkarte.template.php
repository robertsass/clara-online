<?php	/* Template Anfahrt_Stadtkarte 1.0 */

class Anfahrt_Stadtkarte extends Root {


	public function __construct( rsMysql $db, rsHeader $head, rsContainer $body ) {
		parent::__construct( $db, $head, $body );
	}
	
	protected function build_content( rsContainer $Container ) {
		$Content = $this->build_content_part( $this->page, $Container );
		$Content->subordinate( 'iframe', array('width' => 600, 'height' => 500, 'frameborder' => 0, 'scrolling' => 'no', 'marginheight' => 0, 'src' => 'http://maps.google.de/maps?f=q&amp;source=s_q&amp;hl=de&amp;geocode=&amp;q=clara-schumann-gymnasium+bonn&amp;sll=50.729474,7.104721&amp;sspn=0.01543,0.036306&amp;ie=UTF8&amp;cid=50727104,7101256,11769686578733600730&amp;s=AARTsJqcISHOeSLV2pnQcj5eFgNqrltUwQ&amp;ll=50.729882,7.100816&amp;spn=0.013582,0.025749&amp;z=15&amp;iwloc=A&amp;output=embed') );
		return $Content;
	}

}
