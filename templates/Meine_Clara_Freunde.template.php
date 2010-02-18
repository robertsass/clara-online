<?php	/* Template Meine_Clara_Freunde */

class Meine_Clara_Freunde extends Meine_Clara {


	protected $groupsdb;
	protected $groupmembersdb;
	protected $groupdata;
	protected $userdb;
	
	
	protected function build_content( rsContainer $Container ) {
		$this->groupsdb = new rsMysql( 'groups' );
		$this->groupmembersdb = new rsMysql( 'groupmember' );
		$this->userdb = new rsMysql( 'user' );
		$this->build_content_header( $Container );
		if( !isset($_GET['u']) )
			$this->list_friends( $Container );
		else {
			$this->build_user_profile( $Container );
		}
	}
	
	
	protected function build_content_header( rsContainer $Container ) {
		if( !isset($_GET['u']) )
			parent::build_content_header( $Container );
		else
			$Container->subordinate( 'h1', $this->groupdata['name'] );
	}
	
	
	protected function build_group_profile( rsContainer $Container ) {
		$leader = $this->userdb->getRow( '`id` = ' . $this->groupdata['leiter'] );
		$Container->subordinate( 'p', 'Leiter: ' )->subordinate( 'a', array('href' => ''), $leader['vorname'] .' '. $leader['nachname'] );
		$this->build_profile_tabs( $Container /*->subordinate( 'div', array('class' => 'fieldset') )*/ );
	}
	
	
	protected function build_profile_tabs( rsContainer $Container ) {
		$Container = $Container->subordinate( 'div', array('class' => 'tabs') );
		$Tabs = $Container->subordinate( 'ul', array('class' => 'tabs') );
		$this->build_groupinfo( $Tabs, $Container->subordinate( 'div', array('class' => 'tabcontainer') ) );
		$this->build_groupmembers( $Tabs, $Container->subordinate( 'div', array('class' => 'tabcontainer') ) );
		$this->build_groupfiles( $Tabs, $Container->subordinate( 'div', array('class' => 'tabcontainer') ) );
		$this->build_groupsettings( $Tabs, $Container->subordinate( 'div', array('class' => 'tabcontainer') ) );
	}
	
	
	protected function build_groupinfo( rsContainer $Tabs, rsContainer $Container ) {
		$Tabs->subordinate( 'li', 'Info' );
		$Page = new rsPage( $this->groupdata['docid'], $this->db );
		$Container->swallow( $Page->get_content() );
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
	
	
	protected function build_groupfiles( rsContainer $Tabs, rsContainer $Container ) {
		$Tabs->subordinate( 'li', 'Dateien' );
//		$Container->add_attribute( 'class', 'tabcontainer selected' );
		$MediaTree = new rsMediaTreeList( $this->groupdata['mediaid'], $Container->subordinate( 'ul', array('class' => 'files') ), new rsMysql('media'), false, array( 'file_link' => array('href' => 'index.php?f=%FILEID', 'target' => '_blank') ) );
	}
	
	
	protected function build_groupsettings( rsContainer $Tabs, rsContainer $Container ) {
		if( $this->groupdata['leiter'] == $this->Benutzer->get('id') ) {
			$Tabs->subordinate( 'li', 'Einstellungen' );
			$Memberlist = $Container->subordinate( 'ul' );
			foreach( $this->groupmembersdb->get('SELECT * FROM `%TABLE` WHERE `groupid` = ' . $this->groupdata['id']) as $member ) {
				$member = $this->userdb->getRow( '`id` = ' . $member['userid'] );
				$Memberlist->subordinate( 'li' )->subordinate( 'p', $member['vorname'] . ' ' . $member['nachname'] );
			}
		}
	}


	protected function list_groups( rsContainer $Container ) {
		$this->build_leading_groups( $Container );
		$this->build_participating_groups( $Container );
	}
	
	
	protected function invite_user( $userid ) {
		$this->groupmembersdb->update_insert( array('groupid' => $this->groupdata['id'], 'userid' => $userid), '`userid` = '.$userid.' AND `groupid` ='.$this->groupdata['id'] );
	}

	
	protected function build_leading_groups( rsContainer $Container ) {
		$leading_groups = $this->groupsdb->get( 'SELECT * FROM `%TABLE` WHERE `leiter` = '.$this->Benutzer->get('id') );
		if( count($leading_groups) > 0 ) {
			$Container->subordinate( 'p', 'Leitung folgender Gruppen...' );
			$Groups = $Container->subordinate( 'ul', array('class' => 'leading-groups') );
			foreach( $leading_groups as $row ) {
				$Li = $Groups->subordinate( 'li' );
				$Li->subordinate( 'a', array('href' => '?i='.$this->docid.'&g='.$row['id'] ), $row['name'] );
				$Grouppage = new rsPage( $row['docid'], $this->groupsdb );
				$Li->subordinate( 'span', array('class' => ''), $Grouppage->get_description() );
			}
		}
	}
	
	
	protected function build_participating_groups( rsContainer $Container ) {
		$participating_groups = $this->groupmembersdb->get( 'SELECT * FROM `%TABLE` WHERE `userid` = '.$this->Benutzer->get('id') );
		if( count($participating_groups) > 0 ) {
			$Container->subordinate( 'p', 'Mitglied in folgenden Gruppen...' );
			$Groups = $Container->subordinate( 'ul' );
			foreach( $participating_groups as $row ) {
				$groupdata = $this->groupsdb->getRow( '`id` = '. $row['groupid'] );
				$Li = $Groups->subordinate( 'li' );
				$Li->subordinate( 'a', array('href' => '?i='.$this->docid.'&g='.$groupdata['id'] ), $groupdata['name'] );
				$Grouppage = new rsPage( $groupdata['docid'], $this->groupsdb );
				$Li->subordinate( 'span', array('class' => ''), $Grouppage->get_description() );
			}
		}
	}
	

}