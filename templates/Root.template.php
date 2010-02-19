<?php	/* Template Root */

class Root extends rsCore {


	protected $active_menu_element;
	protected $mainContainer;
	protected $Benutzer;
	protected $feeddoc;
	
	
	public function __construct( rsMysql $db, rsHeader $head, rsContainer $body ) {
		parent::__construct( $db, $head, $body );
		$this->page = $this->build_doc();
		if( !is_int($this->feeddoc) )
			$this->feeddoc = FEED;
		if( $this->build_feed( new rsContainer('channel') ) )
			return true;
		$this->build_head();
		$this->body = new rsContainer( 'body' );
		$this->mainContainer = array();
		$this->build_body();
		$this->build();
	}
	
	
	protected function build_feed( rsContainer $Container ) {
		if( !isset($_GET['rss']) )
			return false;
		foreach( $this->get_sublevel_documents( $this->feeddoc ) as $item )
			$Container->subordinate( 'item' )
				->subordinate( 'title', $item['name'] )
				->append( 'link', $_SERVER['SERVER_NAME'] .'?i='. $item['id'], false, false )
				->append( 'description', strip_tags( $item['content'] ) );
		$Feed = new rsRSSFeed( $Container );
		return true;
	}
	
	
	protected function build_head() {
		$this->head->set_pagetitle( SITE_NAME );
		$this->head->link_javascript( 'static/js/jquery.js' );
		$this->head->link_javascript( 'static/js/jquery-ui.js' );
		$this->head->link_javascript( 'static/js/jquery.jcorners.js' );
		$this->head->link_javascript( 'static/js/FancyZoom.js' );
		$this->head->link_javascript( 'static/js/FancyZoomHTML.js' );
		$this->head->link_javascript( 'static/js/main.js' );
		$this->head->link_stylesheet( 'static/css/common.css' );
		$this->head->add_meta( 'author', 'Robert Sass' );
		$this->head->add_meta( 'generator', 'rsCore / rsBackstage' );
		$this->head->add_meta( 'geo.region', 'DE-NW' );
		$this->head->add_meta( 'geo.placename', 'Loestrasse 14, 53113 Bonn, Deutschland' );
		$this->head->add_meta( 'geo.position', '50.7266064;7.1012765' );
		$this->head->add_meta( 'ICBM', '50.7266064,7.1012765' );
	}
	
	
	protected function dye( $section ) {
		$colorscheme = array(
			3 => 'blue',
			5 => 'lime',
			19 => 'ocean',
			18 => 'red',
			17 => 'violet',
			13 => 'orange'
		);
		$this->head->link_stylesheet( 'static/css/'. $colorscheme[ $section ] .'.css' );
	}
	
	
	protected function build_body() {
		$Body = $this->body->subordinate( 'div', array('id' => 'body') );
		$this->mainContainer['top'] = $this->build_top( $Body );
		$Top = $this->mainContainer['top'];
		$this->mainContainer['menu'] = $this->build_menu( $Top );
		$this->mainContainer['submenu'] = $this->build_submenu( $Body );
		$this->mainContainer['wall'] = $this->build_wall( $Body );
		$Wall = $this->mainContainer['wall'];
		$this->mainContainer['content'] = $this->build_content( $Wall );
		$this->mainContainer['footer'] = $this->build_footer( $Body );
		
		if( $this->db->ErrorLog->report() != '' )
			$Body->subordinate( 'div', array('class' => 'notification error-report') )
				->subordinate( 'img', array('src' => 'static/images/database.png') )
				->subordinate( 'h1', 'Datenbank-Fehler' )
				->append( $this->db->ErrorLog->report() );
	}
	
	
	protected function build_top( rsContainer $Container ) {
		$Top = $Container->subordinate( 'div', array('id' => 'top') );
		$Head = $Top->subordinate( 'div', array('id' => 'head') );
		$Banner = $Head->subordinate( 'div', array('id' => 'banner') );
		$this->event_banner( $Banner );
		$Title = $Head->subordinate( 'div', array('id' => 'title') );
		if( date('H') < 9 || date('H') > 18 )
			$tageszeit = 'night';
		else
			$tageszeit = 'day';
		$Top->add_attribute( 'class', $tageszeit );
		return $Top;
	}
	
	
	protected function event_banner( rsContainer $Banner ) {
		if( date('md') >= 1224 && date('md') <= 1226 )	// An Weihnachten (von Heilig Abend bis einschlie�lich dem zweiten Feiertag)
			$banner_image = "top_background_weihnachten.jpg";
		
		elseif( date('md') >= 1231 || date('nj') == 11 )	// An Silvester und Neujahr
			$banner_image = "top_background_neujahr.jpg";
		
		if( isset( $banner_image ) )
			$Banner->add_attribute( 'style', 'background: url(static/images/'. $banner_image .') no-repeat;' );	// Anderen Banner anzeigen
	}
	
	
	private function build_menu( rsContainer $Container ) {
		$Menu = new rsMenu( $Container->subordinate( 'a', array('name' => 'menu') )->subordinate( 'div', array('id' => 'menu') ), $this->db );
		foreach( $this->get_sublevel_documents(NAVIGATION) as $menuItem )
			$Menu->add_doc( $menuItem );
		$this->active_menu_element = $Menu->get_active();
		$this->dye( $this->active_menu_element );
		$this->build_searchform( $Menu->menu_container->subordinate( 'li' ) );
		return $Menu;
	}
	
	
	protected function build_searchform( rsContainer $Container ) {
		$Container->add_attribute( 'id', 'searchform' );
		if( isset($_GET['suche']) )
			$Container->add_attribute( 'class', 'active' );
		$Container->subordinate( 'form', array('method' => 'get') )->subordinate( 'input', array('type' => 'hidden', 'name' => 'i', 'value' => 159) )->subordinate( 'input', array('type' => 'search', 'name' => 'suche', 'value' => $_GET['suche'], 'placeholder' => 'Suche', 'results' => 10, 'autosave' => 'clara-online') );
	}
	
	
	protected function build_submenu( rsContainer $Container, $root=null, $active=null ) {
		if( !$root )
			$root = $this->active_menu_element;
		$Page = new rsPage( $this->active_menu_element, $this->db );
		$Submenu = $Container->subordinate( 'div', array('id' => 'submenu') );
		$Menu = new rsMenu( $Submenu, $this->db, ($active ? $active : $this->docid) );
		foreach( $this->get_sublevel_documents( $root ) as $menuItem ) {
			$Li = $Menu->add_doc( $menuItem );
			if( $menuItem['id'] == $Menu->get_active() && $menuItem['offspring'] > 0  ) {
				$Menu2 = new rsMenu( $Li->subordinate( 'div', array('class' => 'menu thirdlevel') ), $this->db );
				foreach( $this->get_sublevel_documents($menuItem['id']) as $menuItem2 )
					$Menu2->add_doc( $menuItem2 );
			}
		}
		return $Submenu;
	}
	
	
	protected function build_wall( rsContainer $Container ) {
		$this->head->complete_pagetitle( ' :: '. $this->page->get_title() . ( $this->page->get_description() != '' ? ' ('. $this->page->get_description() .')' : '' ) );
		return $Container->subordinate( 'div', array('id' => 'wall') );
	}
	
	
	protected function build_content( rsContainer $Container ) {
		return $this->build_content_part( $this->page, $Container );
	}
	
	
	protected function build_content_part( rsPage $Page, rsContainer $Container ) {
		$Container->subordinate( 'h1', ( $Page->get_description() == '' ? $Page->get_title() : $Page->get_description() ) );
		$Content = $Container->subordinate( 'div', array('class' => 'content') );
		$Content->subordinate( 'div', array('class' => 'text'), $Page->get_content() );
		return $Content;
	}
	
	
	private function build_footer( rsContainer $Container ) {
		$this->build_marklets( $Container );
		$Foot = $Container->subordinate( 'div', array('id' => 'foot') );
		$Foot->subordinate( 'a', array('href' => 'http://www.apple.com/de', 'target' => '_blank') )->subordinate( 'img', array('src' => 'static/images/made_on_a_mac.png', 'id' => 'madeonamac') );
		$fussnote = new rsPage( 126 );
		$Foot->swallow( $fussnote->get_content() );
		$Foot->subordinate( 'p', 'Design &amp; Umsetzung von <a href="http://www.brainedia.de" target="_blank">Brainedia</a>, Hosting & langj&auml;hrige Unterst&uuml;tzung und Treue von der <a href="http://www.webfactory.de" target="_blank">webfactory</a>.' );
		return $Foot;
	}
	
	
	protected function build_marklets( rsContainer $Container ) {
		$Marklets = $Container->subordinate( 'div', array('id' => 'marklets') );
		$Marklets->subordinate( 'a', array('href' => 'http://delicious.com/save', 'onClick' => 'window.open(\'http://delicious.com/save?v=5&noui&jump=close&url=\'+encodeURIComponent(location.href)+\'&title=\'+encodeURIComponent(document.title), \'delicious\',\'toolbar=no,width=550,height=550\'); return false;', 'title' => 'Lesezeichen dieser Seite auf delicious.com speichern') )->subordinate( 'img', array('src' => 'static/images/delicious.png') );
		$Marklets->subordinate( 'a', array('href' => '?rss&i='.$this->docid, 'title' => 'RSS 2.0 Feed dieser Seite abrufen') )->subordinate( 'img', array('src' => 'static/images/rss.png') );
		$Marklets->subordinate( 'a', array('href' => 'http://www.facebook.com/pages/Bonn-Germany/Clara-Schumann-Gymnasium-Bonn/300523910014', 'title' => 'Facebook-Seite des Clara-Schumann-Gymnasiums aufrufen') )->subordinate( 'img', array('src' => 'static/images/facebook.png') );
		$Marklets->subordinate( 'a', array('href' => 'http://twitter.com/csgbonn', 'title' => 'Twitter-Seite des Clara-Schumann-Gymnasiums aufrufen') )->subordinate( 'img', array('src' => 'static/images/twitter.png') );
		$Marklets->subordinate( 'a', array('href' => 'http://www.studivz.net/Suggest/Selection/?u='. urlencode( 'http://www.clara-online.de/?i='. $this->docid ) .'&desc='. urlencode( $this->page->get_title() ) .'&prov='. urlencode( SITE_TITLE ), 'title' => 'Diese Seite auf Sch&uuml;lerVZ meinen Freunden empfehlen') )->subordinate( 'img', array('src' => 'static/images/schuelervz.png') );
		$Marklets->subordinate( 'a', array('href' => 'http://www.schulhomepage.de/topliste/index.php?vote=2623', 'title' => 'F&uuml;r unsere Schulhomepage beim Schulhomepage-Award stimmen') )->subordinate( 'img', array('src' => 'static/images/award.png') );
	}


	protected function build_doc( $docid=null ) {
		if(!$docid)
			$docid = $this->docid;
		return new rsPage( $docid, $this->db );
	}

	
}