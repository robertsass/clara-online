<?php	/* Template Meine_Clara_Dateien */

class Meine_Clara_Dateien extends Meine_Clara {


	protected $filesdb;
	protected $needed_space;
	
	
	protected function build_head() {
		parent::build_head();
		$this->head->link_javascript( 'static/js/jquery.tablesorter.js' );
		$this->head->link_javascript( 'static/js/jquery.searchable.js' );
	}
	
	
	protected function build_content( rsContainer $Container ) {
		parent::build_content( $Container );
		if( $this->Benutzer->auth() ) {
			$this->filesdb = new rsMysql( 'files' );
			$this->build_uploadform( $Container );
			$this->upload_files( $Container );
			if( substr( $_POST['button'], 0, 1 ) == 'L' )
				$this->delete_files( $Container );
			$this->list_myfiles( $Container );
		}
	}
		
	protected function build_uploadform( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Dateien hochladen' );
		$Form = $Container->subordinate( 'form', array('enctype' => 'multipart/form-data', 'method' => 'POST', 'action' => '#menu') );
		$Form->subordinate( 'input', array('type' => 'file', 'name' => 'file0', 'class' => 'multiplefiles') )->swallow( '(Dateigr&ouml;&szlig;e darf max. 2MB betragen.)' );
		$Form->subordinate( 'ul', array('class' => 'files-to-upload') );
		$Form->subordinate( 'input', array('type' => 'hidden', 'name' => 'filecount', 'value' => '0') );
		$Form->subordinate( 'input', array('type' => 'submit', 'value' => 'Hochladen') );
	}
	
	
	protected function upload_files( rsContainer $Container ) {
		for( $i=0; $i<=$_POST['filecount'] && $i<500; $i++ )
			if( isset($_FILES['file'.$i]) )
				$this->save_uploaded_file( $this->store_uploaded_file( $_FILES['file'. $i] ) );
	}
	
	
	protected function save_uploaded_file( $filedata ) {
		if( $filedata != false ) {
			if( $this->filesdb->insert( array('filename' => $filedata['filename'], 'timestamp' => time(), 'title' => $filedata['title'], 'owner' => $this->Benutzer->get('id'), 'rights' => 'p', 'attachedto' => $this->docid) ) )
				return true;
		}
		return false;
	}
	
	
	protected function store_uploaded_file( $pfile ) {
		$uploaddir = './media/';
		$file = basename( $pfile['name'] );
		$fileext = explode( '.', $file );
		$suffix = $fileext[ count($fileext)-1 ];
		$fileid = md5( $file . time() . $this->Benutzer->get('id') );
		$filename = $fileid . '.' . $suffix;
		$uploadfile = $uploaddir . $filename;
		if( move_uploaded_file( $pfile['tmp_name'], $uploadfile ) )
			return array(
				'filename' => $filename,
				'title' => mysql_escape_string( str_replace( '.'.$suffix, '', $pfile['name'] ) )
			);
		return false;
	}
	
	
	protected function delete_files( rsContainer $Container ) {
		$my_files = $this->filesdb->get( 'SELECT `id`, `filename` FROM `%TABLE` WHERE `owner` = '. $this->Benutzer->get('id') .' ORDER BY `timestamp` DESC' );
		foreach( $my_files as $file )
			if( isset($_POST['f'.$file['id']]) && $_POST['f'.$file['id']] == 'on' )
				if( $this->filesdb->delete( '`id`='. $file['id'] ) )
					unlink( './media/'. $file['filename'] );
	}
	
	
	protected function list_myfiles( rsContainer $Container ) {
		$Container->subordinate( 'h2', 'Alle meine Dateien' )->subordinate( 'span', array('class' => 'tablesearch'), 'Dateien durchsuchen:' )->subordinate( 'input', array('type' => 'text', 'value' => '') );
		$Form = $Container->subordinate( 'form', array('method' => 'post', 'action' => '#menu', 'onSubmit' => 'if(!confirm(\'Bist Du sicher, dass Du die markierten Dateien l&ouml;schen willst?\'))return false;') );
		$Table = $Form->subordinate( 'table', array('class' => 'list') );
		$Table->subordinate( 'thead' )->subordinate( 'tr' )
			->subordinate( 'th', 'Name' )
			->append( 'th', 'Art' )
			->append( 'th', 'Beschreibung' )
			->append( 'th', 'Gr&ouml;&szlig;e' )
			->append( 'th', array('class' => 'sorted-asc'), 'Datum' )
			->append( 'th', 'Angeh&auml;ngt an' )
			->append( 'th', '&nbsp;' );
		$Table = $Table->subordinate( 'tbody' );
		$my_files = $this->filesdb->get( 'SELECT * FROM `%TABLE` WHERE `owner` = '. $this->Benutzer->get('id') .' ORDER BY `timestamp` DESC' );
		foreach( $my_files as $file ) {
			if( $file['attachedto'] == $this->docid )
				$this->sum_space( filesize('./media/'.$file['filename']) );
			$Table->subordinate( 'tr' )
				->subordinate( 'td' )
					->subordinate( 'img', array('src' => 'static/images/symbols/'. strtolower( $this->name_filetype( substr($file['filename'], 33) ) ) .'.png') )
					->subordinate( 'a', array('href' => '?f='. $file['id']), $file['title'] )->parent()
				->append( 'td', $this->name_filetype( substr($file['filename'], 33), true ) )
				->append( 'td', ($file['description']=='' ? '(keine)' : $file['description']) )
				->append( 'td', $this->decode_size( filesize('./media/'.$file['filename']) ) )
				->append( 'td', date('d.m.Y', $file['timestamp']) )
				->append( 'td', ($file['attachedto']==$this->docid ? '-' : '<a href="?i='. $file['attachedto'] .'">'. $this->db->getColumn('name','`id`='.$file['attachedto']) .'</a>' ) )
				->parent_subordinate( 'td' )->subordinate( 'input', array('type' => 'checkbox', 'name' => 'f'.$file['id']) );
		}
		$Form->subordinate( 'p', array('class' => 'tableoptions') )
				->subordinate( 'input', array('type' => 'submit', 'name' => 'button', 'value' => 'L&ouml;schen') );
		$Form->subordinate( 'div', array('class' => 'progressindicator') )->subordinate( 'div', array('class' => 'progress', 'style' => 'width: '. round($this->needed_space / USERSPACE *100,0) .'%') )->parent()
			->append( 'span', 'Belegter Speicherplatz: '. $this->decode_size( $this->needed_space ) .' von '. $this->decode_size( USERSPACE ) );
	}
	
	
	protected function sum_space( $bytes ) {
		$this->needed_space += $bytes;
		return $bytes;
	}
	
	
	protected function decode_size( $bytes ) {
	    $types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	    for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
	    return( round( $bytes, 2 ) . " " . $types[$i] );
	}
	
	
	protected function name_filetype( $suffix, $printsuffix=false ) {
		$suffix = strtolower( $suffix );
		$dateitypen = array(
			'Bild' => array( 'jpg', 'jpeg', 'jp2', 'png', 'gif', 'tif', 'tiff', 'bmp', 'pict', 'sgi', 'tga', 'dib', 'epi', 'eps', 'eps2', 'epsf', 'epsi', 'ept', 'jng', 'jpc', 'miff', 'mif', 'mng', 'mpc', 'otb', 'palm', 'pam', 'pbm', 'pcx', 'pdb', 'pgm', 'pnm', 'ppm', 'ps', 'ps2', 'ptif', 'ptiff', 'sun', 'uyvy', 'vicar', 'viff', 'wbmp', 'xbm', 'xpm', 'yuv' ),
			'Lied' => array( 'mp3', 'aif', 'aiff', 'm4a', 'wav', 'wma', 'au', 'aac' ),
			'Video' => array( 'mov', 'mp4', 'mpeg4', 'm4v', 'mpg', 'mpeg', 'avi', 'wmv', 'qtl', '3gp', 'asf', 'divx' ),
			'Dokument' => array( 'pdf', 'txt', 'rtf', 'rtfd', 'doc', 'xls', 'ppt', 'docx', 'pages', 'key', 'numbers', 'odt', 'xml', 'html', 'htm', 'js', 'css', 'php', 'c', 'cpp', 'sql', '' ),
			'Programm' => array( 'app', 'exe' ),
			'Archiv' => array( 'zip', 'bzip2', 'bz', 'bz2', 'tar', 'tbz2', 'tbz', 'tgz', 'tar-gz', 'gzip', 'gz', 'gtar', 'z', 'taz', 'cab', 'jar', 'rar', '7z', 'sit', 'hqx', 'bin', 'dmg', 'iso', 'img', 'sparseimage' )
		);
		$dateityp = 'Unbekannt';
		foreach( $dateitypen as $typfamilie => $suffixe )
			if( in_array( $suffix, $suffixe ) )
				$dateityp = $typfamilie;
		if( $printsuffix )
			$dateityp .= ' (.'. $suffix .')';
		return $dateityp;
	}
	

}