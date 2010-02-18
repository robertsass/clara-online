<?php /* rsLeaf 1.1 */

class rsLeaf {

	protected $documentId;
	protected $parentTreeClass;
	protected $DB;
	
	public $name;
	public $beschreibung;
	public $timestamp;
	public $lastedit;
	public $content;
	public $template;
	
	public function __construct( $id, $parentTreeClass ) {
		$this->documentId = $id;
		$this->parentTreeClass = $parentTreeClass;
		$this->DB = $parentTreeClass->DB;
		
		$this->get();
	}
	
	private function get() {
		$rows = $this->DB->get( 'SELECT `name`, `beschreibung`, `lastedit`, `content`, `template` FROM `' . $this->parentTreeClass->table . '` WHERE `id` = ' . $this->documentId );
		foreach($rows as $row) {
			$this->name = $row['name'];
			$this->beschreibung = $row['beschreibung'];
			$this->timestamp = $row['lastedit'];
			$this->lastedit = date('d.m.Y \u\m H:i:s \U\h\r', $row['lastedit']);
			$this->content = $row['content'];
			$this->template = $row['template'];
		}
	}
	
	private function getName() {
		return $this->name;
	}
	
	private function getBeschreibung() {
		return $this->beschreibung;
	}
	
}