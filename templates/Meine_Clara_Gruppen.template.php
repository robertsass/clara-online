<?php	/* Template Meine_Clara_Gruppen */

class Meine_Clara_Gruppen extends Meine_Clara {


	protected $groupsdb;
	protected $groupmembersdb;
	protected $userdb;
	protected $filesdb;
	protected $group;
	
	
	protected function build_content( rsContainer $Container ) {
		if( !$this->Benutzer->auth() )
			parent::build_content( $Container );
		else {
			$this->groupsdb = new rsMysql( 'groups' );
			$this->groupmembersdb = new rsMysql( 'groupmember' );
			$this->userdb = new rsMysql( 'user' );
			$this->filesdb = new rsMysql( 'files' );
			$this->build_groupselect( $Container );
			$this->build_content_header( $Container );
			if( isset( $_GET['g'] ) )
				$this->build_groupdetails( intval($_GET['g']), $Container );
		}
	}
	
	
	protected function build_groupselect( rsContainer $Container ) {
		$Select = $Container->subordinate( 'select', array('id' => 'groupmenu', 'onChange' => 'location.href=\'index.php?i='. $this->docid .'&g=\'+this.options[this.selectedIndex].value+\'#menu\'') );
		if( !isset( $_GET['g'] ) )
			$Select->subordinate( 'option', array('value' => '#', 'selected' => 'selected'), '- Meine Gruppen -' );
		foreach( $this->get_my_groups() as $group ) {
			if( $Select->get_tag() != 'optgroup' && $group['leiter'] == $this->Benutzer->get('id') )
				$Select = $Select->subordinate( 'optgroup', array('label' => 'In meiner Leitung') );
			$attributes = array('value' => $group['id']);
			if( isset( $_GET['g'] ) && $_GET['g'] == $group['id'] )
				$attributes['selected'] = 'selected';
			$Select->subordinate( 'option', $attributes, $group['name'] );
		}
	}
	
	
	protected function build_groupdetails( $groupid, rsContainer $Container ) {
		// Gruppen-Datensatz holen
		$this->group = $this->get_groupdata( $groupid );
		
		if( $this->is_leader() )	// wenn Leiter dieser Gruppe...
			$this->add_leaderfeatures( $Container );	// Leiterfunktionen hinzufŸgen
		
		// Seitentitel mit dem Gruppennamen Ÿberschreiben
		$H1 = $Container->search( array('tag' => 'h1') );
		$H1[0]->clear()->swallow( $this->group['name'] );
		
		$Container = $Container->subordinate( 'div', array('id' => 'group') );
		if( $this->is_leader() )
			$Container->add_attribute( 'class', 'leader' );
		
		$this->build_group_newestfiles( $Container );
		$this->build_group_homepage( $Container );
	}
	
	
	protected function is_leader() {
		return ( $this->group['leiter'] == $this->Benutzer->get('id') );
	}
	
	
	protected function add_leaderfeatures( rsContainer $Container ) {
		$this->handle_requests();
		$this->head->link_javascript('static/js/ckeditor/ckeditor.js');
		$this->head->link_javascript('static/js/jquery.CKEditor.js');
	}
	
	
	protected function handle_requests() {
		if( isset($_POST['homepage']) )
			$this->db->update( array('content' => $_POST['homepage']), '`id`='. $this->group['docid'] );
	}
	
	
	protected function build_group_homepage( rsContainer $Container ) {
		$Grouppage = $this->build_doc( $this->group['docid'] );
		$Container = $Container->subordinate( 'div', array('id' => 'grouphomepage') )->subordinate( 'form', array('method' => 'post') );
		$Container->subordinate( 'span', $Grouppage->get_content() );
		$Container->subordinate( 'textarea', array('class' => 'editor', 'name' => 'homepage'), $Grouppage->get_content() );
		if( $this->is_leader() )
			$Container->subordinate( 'img', array('src' => 'static/images/pencil.png', 'class' => 'edit') );
	}
	
	
	protected function build_group_newestfiles( rsContainer $Container ) {
		$Container = $Container->subordinate( 'div', array('class' => 'sidebox') );
		$Container->subordinate( 'h2', 'Die neusten Dateien' );
		$List = $Container->subordinate( 'ul' );
		foreach( $this->filesdb->getAll('`attachedto`='. $this->group['docid'] .' ORDER BY `timestamp` DESC LIMIT 0,5') as $file )
			$List->subordinate( 'li', array('href' => 'index.php?f='. $file['id'] ), $file['title'] );
		$Container->subordinate( 'a', array('href' => ''), 'Mehr' );
		$Container->swallow( '|' )->subordinate( 'a', array('href' => ''), 'Datei hochladen' );
	}
	
	
	protected function get_groupdata( $id ) {
		return $this->groupsdb->getRow( '`id`='. $id );
	}
	
	
	protected function get_my_groups( $by=null ) {
		if( !$by )
			$by = $this->Benutzer->get('id');
		$leaded_groups = $this->get_groups_leaded( $by );
		$joined_groups = $this->get_groups_joined( $by );
		$all_my_groups = array();
		foreach( $joined_groups as $group )
			$all_my_groups[ $group['id'] ] = $group;
		foreach( $leaded_groups as $group )
			if( !isset( $all_my_groups[ $group['id'] ] ) )
				$all_my_groups[ $group['id'] ] = $group;
		return $all_my_groups;
	}
	
	
	protected function get_groups_leaded( $by=null ) {
		if( !$by )
			$by = $this->Benutzer->get('id');
		$userid = intval( $by );
		return $this->groupsdb->getAll( '`leiter`='. $userid .' ORDER BY `name` ASC' );
	}
	
	
	protected function get_groups_joined( $by=null ) {
		if( !$by )
			$by = $this->Benutzer->get('id');
		$userid = intval( $by );
		$memberin = $this->groupmembersdb->getAll( '`userid`='. $userid );
		$groupids = array();
		foreach( $memberin as $group )
			$groupids[] = $group['groupid'];
		$whereStatement = '`id`='. $groupids[0];
		unset( $groupids[0] );
		if( count($groupids) > 0 )
			$whereStatement .= implode( ' OR `id`=', $groupids );
		return $this->groupsdb->getAll( $whereStatement .' ORDER BY `name` ASC' );
	}
	
	
	protected function build_groupmembers( rsContainer $Tabs, rsContainer $Container ) {
		$Li = $Tabs->subordinate( 'li', 'Mitglieder' );
		if( isset($_POST['user']) ) {
			$this->invite_user( intval($_POST['user']) );
			$Li->add_attribute( 'class', 'selected' );
		}
		$Memberlist = $Container->subordinate( 'ul', array('class' => 'memberlist') );
		foreach( $this->groupmembersdb->get('
			SELECT `%TABLE`.*, `'.DBPREFIX.'user`.*
			FROM `%TABLE`
			LEFT JOIN `'.DBPREFIX.'user`
			ON `%TABLE`.`userid` = `'.DBPREFIX.'user`.`id`
			WHERE `groupid` = '. $this->groupdata['id'] .'
			ORDER BY `'.DBPREFIX.'user`.`vorname`, `'.DBPREFIX.'user`.`nachname`'
		) as $member ) {
			$member = $this->userdb->getRow( '`id` = ' . $member['userid'] );
			$Memberlist->subordinate( 'li', $member['vorname'] . ' ' . $member['nachname'] );
		}
		$InvitationForm = $Container->subordinate( 'form', array('action' => '?i='.$this->docid.'&g='.$this->groupdata['id'], 'method' => 'post') );
		$InvitationForm->subordinate( 'p', '<div>Benutzer: <span id="foundusername"></span></div>' )->subordinate( 'input', array('type' => 'hidden', 'name' => 'user', 'id' => 'inputfounduserid') )->subordinate( 'div', array('id' => 'getUser') );
		$InvitationForm->subordinate( 'input', array('type' => 'submit', 'id' => 'invite', 'value' => 'Benutzer einladen') );
	}
	
	
	protected function invite_user( $userid ) {
		$this->groupmembersdb->update_insert( array('groupid' => $this->groupdata['id'], 'userid' => $userid), '`userid` = '.$userid.' AND `groupid` ='.$this->groupdata['id'] );
	}
	

}