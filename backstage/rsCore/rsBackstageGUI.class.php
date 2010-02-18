<?php	/* rsBackstageGUI 1.0 */

class rsBackstageGUI extends rsBackstage {

	protected $rightsdb;
	protected $container_array;
	protected $menu;
	protected $sections;
	
	public function __construct() {
		$this->sections = array( 'Dokumente', 'Mediathek' );	// MenŸ-EintrŠge
		parent::__construct();
		$this->rightsdb = new rsMysql( 'rights' );
		if( isset( $_GET['x'] ) && $this->handle_ajax_request( $_GET['x'] ) )
			return $this;
		$this->build_head();
		$this->body = new rsContainer( 'body' );
		$this->container_array = array();
		$this->build_body();
		$this->build();
	}


	protected function build_head() {
		$this->head->set_pagetitle( 'rsBackstage / ' . SITENAME );
		$this->head->link_javascript( 'static/js/jquery.js' );
		$this->head->link_javascript( 'static/js/jquery.dimensions.js' );
		$this->head->link_javascript( 'static/js/jquery.jcorners.js' );
		$this->head->link_javascript( 'static/js/jquery.ajaxq.js' );
		$this->head->link_javascript( 'static/js/jquery.ajax_upload.js' );
		$this->head->link_javascript( 'static/js/jquery.expose.js' );
		$this->head->link_javascript( 'static/js/jquery.jgrowl.js' );
		$this->head->link_javascript( 'static/js/jquery-ui.js' );
		$this->head->link_javascript( 'static/js/tiny_mce/tiny_mce.js' );
		$this->head->link_javascript( 'static/js/main.js' );
		$this->head->link_stylesheet( 'static/css/common.css' );
		$this->head->link_stylesheet( 'http://ui.jquery.com/testing/themes/base/ui.all.css', 'screen' );
		$this->head->add_meta( 'author', 'Robert Sass' );
	}
	
	
	protected function build_body() {
		$this->container_array['body'] = $this->body->subordinate( 'div', array('id' => 'body') );
		$Body = $this->container_array['body'];
		$this->container_array['top'] = $this->build_top( $Body );
		if( $this->Benutzer->auth() && $this->Benutzer->get('admin') > 1 /* nur für schuelerevents */ )
			$this->container_array['menu'] = $this->build_menu( $Body );
		$this->container_array['content'] = $this->build_content( $Body );
		$this->container_array['footer'] = $this->build_footer( $Body );
		
		/* MYSQL - DEBUG-AUSGABE */
		if( $this->db->ErrorLog->report() != '' )
			$Body->subordinate( 'div', array('class' => 'error-report'), $this->db->ErrorLog->report() );
	}
	
	
	private function build_top( rsContainer $Container ) {
		$Top = $Container->subordinate( 'div', array('id' => 'top') );
		$Top->subordinate( 'a', array('href' => './') )->subordinate( 'img', array('src' => 'static/images/logo.png', 'id' => 'logo') );
		
		$Syslinks = $Top->subordinate( 'div', array('id' => 'syslinks') );
		$Syslinks->subordinate( 'a', array('href' => '../', 'target' => 'blank'), 'Zur Homepage' );
		$Syslinks->subordinate( 'a', array('href' => '../', 'target' => 'blank'), 'Abmelden' );
		
		return $Top;
	}
	
	
	private function build_footer( rsContainer $Container ) {
		$Foot = $Container->subordinate( 'div', array('id' => 'foot') );
		$Footer = $Foot->subordinate( 'p', array('class' => 'footer'), '&copy; ' . date('Y') . ' <a href="http://www.rsapps.de" target="_blank">Robert Sass</a>' );
		return $Foot;
	}
	
	
	private function build_menu( rsContainer $Container ) {
		$this->menu = $Container->subordinate( 'div', array('id' => 'menu') )->subordinate( 'ul' );
		foreach( $this->sections as $menuitem )
			$this->add_menuitem( $menuitem, strtolower($menuitem) );
		return $Menu;
	}
	
	
	private function add_menuitem( $title, $link ) {
		$attributes = array();
		if( $this->section == $link )
			$attributes['class'] = 'selected';
		$this->menu->subordinate( 'li', $attributes )->subordinate( 'a', array('href' => '?i='.$link) )->swallow( $title );
	}
	
	
	protected function build_content( rsContainer $Container ) {
		$Container = $Container->subordinate( 'div', array('id' => 'content') );
		if( $this->Benutzer->auth() && $this->Benutzer->get('admin') > 1 /* nur für schuelerevents */ ) {
			$Container->subordinate( 'div', array('id' => 'editor') );
			$Content = $Container->subordinate( 'div', array('class' => 'content') );
			$Content->subordinate( 'img', array('src' => 'static/images/spinner.gif', 'id' => 'spinner') );
			if( $this->section == 'dokumente' )
				$this->build_dokumentbaum( $Content->subordinate( 'div', array('class' => 'document tree') ), $this->db );
			if( $this->section == 'mediathek' )
				$this->build_mediathek( $Content );
			}
		else
			$this->build_loginform( $Container );
	}
	
	
	protected function build_loginform( rsContainer $Container ) {
		$Form = $Container->subordinate( 'form', array('method' => 'post') );
		$Form->subordinate( 'p', 'Benutzer: ' )->subordinate( 'br' )->subordinate( 'input', array('type' => 'text', 'name' => 'benutzername') );
		$Form->subordinate( 'p', 'Passwort: ' )->subordinate( 'br' )->subordinate( 'input', array('type' => 'password', 'name' => 'passwort') );
		$Form->subordinate( 'p' )->subordinate( 'input', array('type' => 'submit', 'value' => 'Login') );
	}
	
	
	protected function build_doceditor( rsContainer $Container, $docid ) {
		$Doc = new rsPage( $docid, $this->db );
		$Container->subordinate( 'h2', 'Dokument &quot;'. $Doc->get_title() .'&quot; bearbeiten' );
		$TemplateSelect = $Container->subordinate( 'div', array('class' => 'fieldset', 'style' => 'float: right;'), 'Template: ' )->subordinate( 'select', array('id' => 'doctemplate') );
		$Container->subordinate( 'div', array('class' => 'fieldset', 'style' => 'float: right; clear: right;'), 'Link: ' )->subordinate( 'a', array('href' => basename($_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']) .'/../../?i='. $docid, 'target' => '_blank'), 'index.php?i='. $docid );
		$Container->subordinate( 'div', array('class' => 'fieldset'), 'Titel: ' )->subordinate( 'input', array('type' => 'text', 'size' => 25, 'id' => 'doctitle', 'value' => $Doc->get_title() ) );
		$Container->subordinate( 'div', array('class' => 'fieldset'), 'Untertitel: ' )->subordinate( 'input', array('type' => 'text', 'size' => 30, 'id' => 'docdescription', 'value' => $Doc->get_description() ) );
		foreach( scandir('../templates/') as $file ) {
			$file = explode( '.', $file );
			if($file[1] == 'template' && $file[2] == 'php') {
				$attributes = array('value' => $file[0]);
				if( $Doc->get_template() == $file[0] ) $attributes['selected'] = 'true';
				$TemplateSelect->subordinate( 'option', $attributes, str_replace( '_', ' ', $file[0] ) );
			}
		}
		$Container->subordinate( 'p' )->subordinate( 'textarea', array('id' => 'doccontent', 'name' => 'doccontent', 'style' => 'width: 898px; height: 402px;'), $Doc->get_content() );
		$Container->subordinate( 'p' )->subordinate( 'input', array('type' => 'button', 'onClick' => 'save_doc('. $docid .')', 'value' => 'Speichern') )->subordinate( 'input', array('type' => 'button', 'onClick' => 'hide_doceditor()', 'value' => 'Abbrechen') )->subordinate( 'span', array('id' => 'editorOptions'), 'Graphischer Editor <input type="checkbox" onChange="toggle_editor()" checked />' );
	}
	
	
	protected function build_tree( $rootdoc, rsContainer $Container, rsMysql $DB ) {
			$TreeList = new rsTreeList( $rootdoc, $Container, $DB );
	}
	
	
	protected function build_mediatree( $rootdoc, rsContainer $Container, rsMysql $DB ) {
			$TreeList = new rsMediaTreeList( $rootdoc, $Container, $DB );
	}
	
	
	protected function build_dokumentbaum( rsContainer $Container, rsMysql $DB=null ) {
		if( !$DB )
			$DB = $this->db;
		$Container->subordinate( 'h1', 'Dokumente' );
		$user_roots = $this->rightsdb->getColumn( 'docid', '`userid` = '. $this->Benutzer->get('id') );
		$user_roots = explode(',', $user_roots);
		$this->build_doctoolbar( $Container, count($user_roots) );
		$Treelist = $Container->subordinate( 'ul', array('class' => 'first') );
		foreach( $user_roots as $user_root ) {
			if( intval($user_root) > 0 ) {
				$Page = new rsPage( $user_root, $DB );
				$Li = $Treelist->subordinate( 'li', array('id' => $user_root) );
				$LiDiv = $Li->subordinate( 'div', array('id' => 'div'.$user_root) );
				$LiDiv->subordinate( 'a', array('onClick' => 'show_doceditor('. $user_root .')'), $Page->get_title() );
				$LiDiv->subordinate( 'span', array('class' => 'id'), '('.$user_root.')' );		
				$this->build_tree( $user_root, $Li->subordinate( 'ul' ), $DB );
			}
		}
		$this->build_doctoolbar( $Container, count($user_roots) );
		return $Container;
	}
	
	
	protected function build_doctoolbar( rsContainer $Container, $trees=1 ) {
			$Toolbar = $Container->subordinate( 'div', array('class' => 'toolbar') );
			$Toolbar->subordinate( 'span', array('class' => 'button new-subdoc'.($trees > 1 ? ' itemorientated disabled' : '')), '<img src="static/images/create.png"> <span>Neues Dokument</span>' );
			$Toolbar->subordinate( 'span', array('class' => 'button move-doc-up itemorientated disabled'), '<img src="static/images/move_left.png">' );
			$Toolbar->subordinate( 'span', array('class' => 'button move-doc-down itemorientated disabled'), '<img src="static/images/move_right.png">' );
			$Toolbar->subordinate( 'span', array('class' => 'button delete-doc itemorientated disabled'), '<img src="static/images/delete.png"> Dokument l&ouml;schen' );
	}
	
	
	protected function build_dateibaum( rsContainer $Container, rsMysql $DB=null ) {
		if( !$DB )
			$DB = new rsMysql( 'media' );
		$user_roots = $this->rightsdb->getColumn( 'mediaid', '`userid` = '. $this->Benutzer->get('id') );
		$user_roots = explode(',', $user_roots);
		$this->build_mediatoolbar( $Container );
		$Treelist = $Container->subordinate( 'ul', array('class' => 'first') );
		foreach( $user_roots as $user_root ) {
			if( intval($user_root) > 0 ) {
				$Page = new rsPage( $user_root, $DB );
				$Li = $Treelist->subordinate( 'li', array('class' => 'dir', 'id' => $user_root) );
				$LiDiv = $Li->subordinate( 'div', array('id' => 'div'.$user_root) );
				$LiDiv->subordinate( 'a', array('onClick' => 'show_mediaeditor('. $user_root .')'), $Page->get_title() );
				$this->build_mediatree( $user_root, $Li->subordinate( 'ul' ), $DB );
			}
		}
		$this->build_mediatoolbar( $Container );
	}
	
	
	protected function build_upload_dialog( rsContainer $Container ) {
		$UploadDialog = $Container->subordinate( 'div', array('id' => 'upload', 'class' => 'dialog') );
		$UploadDialog->subordinate( 'h2', 'Datei-Upload' );
		$UploadDialog->subordinate( 'p', 'Bitte w&auml;hlen Sie eine Datei (max. 2MB) aus! Diese wird dann sofort hochgeladen.' );
		$UploadDialog->subordinate( 'p' )->subordinate( 'div', array('class' => 'button', 'id' => 'uploadbutton' ), 'Datei ausw&auml;hlen...' );
		$UploadDialog->subordinate( 'p', '<b>Wichtig:</b> Verlassen Sie so lange nicht die Seite, bis eine R&uuml;ckmeldung erscheint! Sie k&ouml;nnen in der Mediathek ungehindert weiterarbeiten und auch weitere Dateien hochladen. Dieser Dialog schlie&szlig;t sich nach dem Start des Uploads automatisch; der Upload l&auml;uft im Hintergrund.' );
	}
	
	
	protected function build_mediatoolbar( rsContainer $Container ) {
			$Toolbar = $Container->subordinate( 'div', array('class' => 'toolbar') );
			$Toolbar->subordinate( 'span', array('class' => 'button new-dir'), '<img src="static/images/create.png"> <span>Neues Verzeichnis</span>' );
			$Toolbar->subordinate( 'span', array('class' => 'button upload itemorientated disabled'), '<img src="static/images/add.png"> Datei hochladen' );
			$Toolbar->subordinate( 'span', array('class' => 'button move-dir-up itemorientated disabled'), '<img src="static/images/move_left.png">' );
			$Toolbar->subordinate( 'span', array('class' => 'button move-dir-down itemorientated disabled'), '<img src="static/images/move_right.png">' );
			$Toolbar->subordinate( 'span', array('class' => 'button delete-dir itemorientated disabled'), '<img src="static/images/delete.png"> <span>Verzeichnis l&ouml;schen</span>' );
			$Toolbar->subordinate( 'input', array('class' => 'inclusion-code') );
	}
	
	
	protected function build_mediathek( rsContainer $Container ) {
		$mediadb = new rsMysql( 'media' );
		$Container->subordinate( 'h1', 'Mediathek' );
		$this->build_dateibaum( $Container->subordinate( 'div', array('class' => 'media tree') ), $mediadb );
		$this->build_upload_dialog( $Container );
		return $Container;
	}

}
