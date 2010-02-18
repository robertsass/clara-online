<?php /* rsContainer 3.7 */

class rsContainer {

	# Der Name (Tag) dieses Containers
	private $container;
	
	# Die Attribute dieses Containers
	private $container_attributes = array();
	
	# Das Array in dem alle Inhalte in ihrer Reihenfolge gespeichert sind
	public $subelements = array();
	
	# Enthalte ich weitere Container? Interessant fuer die Einrueckung
	private $contains_subcontainer = false;
	
	# Verweist auf den nŠchst-hšheren Container, dem dieser untergeordnet ist.
	public $parent_container = null;
	
	# Speichert die Position im uebergeordneten Container, sprich meinen Schluessel im uebergeordneten Array
	public $position_in_parent = null;
	
	# Speichert den Index des zuletzt entgegengenommenen Inhalts
	private $last_swallowed_index = null;
	
	# Stellt der Container einen 'selfclosing' Tag (input, img, ...) dar?
	private $selfclosing_container = false;
	
	# Blockiert die Auslšsfunktion
	private $blocked = false;
	
	
	public function __construct( $containername, $var=false, $var2=false, $selfclosing=null ) {
		$this->container = $containername;
		if( $var ) {
			if( is_array( $var ) )	// Interpretation als Attribute-Parameter: Wenn es ein Array ist
				$this->set_attributes( $var );
			else	// Interpretation als Erster Inhalt: Wenn es eben kein Array ist
				$this->swallow( $var );
		}
		if( $var2 ) {
			if( is_array( $var2 ) )	// Interpretation als Attribute-Parameter: Wenn es ein Array ist
				$this->set_attributes( $var2 );
			else	// Interpretation als Erster Inhalt: Wenn es eben kein Array ist
				$this->swallow( $var2 );
		}
		if( $selfclosing || ($selfclosing != false && $this->iselfclosing_recognition()) )
			$this->selfclosing_container = true;
		return $this;
	}
	
	
	# Entscheidet bei einigen Tags selber, ob sie 'selfclosing' sind
	private function iselfclosing_recognition() {
		$typically_selfclosing_tags = array( 'img', 'input', 'br', 'link', 'meta' );
		if( in_array( $this->container, $typically_selfclosing_tags ) )
			return true;
		return false;
	}
	
	
	# Gibt den Ausloesbefehl an die Untercontainer weiter und rueckt den Inhalt ein
	private function assemble( $ebene ) {
		$content = '';
		foreach( $this->subelements as $k => $p ) {
			if( is_object($p) ) {
				$content .= ( ($k > 0 && count($p->subelements) > 0) ? "\n" : '' ) . $p->summarize($ebene+1);
			}
			elseif( $p != false )
				$content .= ( ($this->contains_subcontainer) ? $this->indent_code($ebene+1) : '' ) . $p . ( (count($this->subelements) > 1) ? "\n" : '' );
		}
		return $content;
	}
	
	
	# Rueckt den Inhalt ein
	private function indent_code( $ebene ) {
		$einrueckung = '';
		for( $i = 0; $i < $ebene; $i++ ) {
			$einrueckung .= '  ';
		}
		return $einrueckung;
	}
	
	
	# Setzt alle Elemente des Attribute-Arrays zu HTML-Code zusammen
	private function build_attributes_string() {
		$AttributesString = '';
		if( is_array($this->container_attributes) ) {
			foreach( $this->container_attributes as $attribute => $value )
				$AttributesString .= ' ' . $attribute . '="' . $value . '"';
		}
		return $AttributesString;
	}
	
	
	# Bildet den Eroeffnungstag (<tag attribut1="wert1">)
	private function build_opening_tag( $ebene ) {
		$openingTag = $this->indent_code( $ebene ) . '<' . $this->container;
		$openingTag .= $this->build_attributes_string();
		$openingTag .= ($this->selfclosing_container ? ' /' : '') . '>';
		if( count($this->subelements) > 1 || $this->contains_subcontainer || $this->selfclosing_container ) $openingTag .= "\n";
		return $openingTag;
	}
	
	
	# Bildet den Schlusstag (</tag>)
	private function build_closing_tag( $ebene ) {
		$closingTag = ((count($this->subelements) > 1 || $this->contains_subcontainer) ? $this->indent_code($ebene) : '') . '</' . $this->container . '>' . "\n";
		return $closingTag;
	}
	
	
	# Loest den Zusammenpack-Mechanismus dieses und aller untergeordneten Container aus und gibt den erzeugten HTML-Code zurueck
	public function summarize( $ebene ) {
		if( $this->blocked )
			return null;
		if( !$this->selfclosing_container )
			$assembledContent = $this->assemble($ebene);
		return $this->build_opening_tag($ebene) . ( $this->selfclosing_container ? '' : $assembledContent . $this->build_closing_tag($ebene) );
	}
	
	
	# "Schluckt" Inhalt (falls es ein Objekt ist, wird es als Container angesehn und diesem untergeordnet)
	public function swallow( $p ) {
		$this->subelements[] = $p;
		if(is_object($p)) {
			$this->contains_subcontainer = true;
		}
		$this->last_swallowed_index = (count($this->subelements)-1);
		return $this;
	}
	
	
	# Ordnet einen neuen Container unter
	public function subordinate( $containername, $var=false, $var2=false, $selfclosing=null ) {
		$new_container = new rsContainer( $containername, $var, $var2, $selfclosing );
		$new_container->position_in_parent = $this->swallow($new_container);
		$new_container->parent_container = $this;
		if( $new_container->is_selfclosing() )
			return $this;
		return $new_container;
	}
	
	
	# Ordnet neuen Inhalt am Anfang unter
	public function pre_subordinate( $p, $var=false, $var2=false, $selfclosing=null ) {
		$original_subelements = $this->subelements;
		$this->subelements = array();
		$return = $this->subordinate( $p, $var, $var2, $selfclosing );
		$this->subelements = array_merge( $this->subelements, $original_subelements );
		return $return;
	}
	
	
	# Ordnet Inhalt neben dran (dem uebergeordneten Container unterordnen)
	public function append( $p, $var=false, $var2=false, $selfclosing=null ) {
		if( !$var && !$var2 )
			return $this->parent()->swallow( $p );
		return $this->parent()->subordinate( $p, $var, $var2, $selfclosing );
	}
	
	
	# Ordnet Inhalt voran (dem uebergeordneten Container an den Anfang einordnen)
	public function prepend( $p, $var=false, $var2=false, $selfclosing=null ) {
		$original_subelements = $this->parent()->subelements;
		$this->parent()->subelements = array();
		$return = $this->append( $p, $var, $var2, $selfclosing );
		$this->parent()->subelements = array_merge( $this->parent()->subelements, $original_subelements );
		return $return;
	}
	
	
	# Ordnet Inhalt neben dran (dem uebergeordneten Container unterordnen)
	public function parent_subordinate( $containername, $var=false, $var2=false, $selfclosing=null ) {
		return $this->parent()->subordinate( $containername, $var, $var2, $selfclosing );
	}
	
	
	# Gibt den uebergeordneten Container zurueck
	public function parent() {
		return $this->parent_container;
	}
	
	
	# Setzt die Attribute dieses Containers; kann auch zum Ueberschreiben verwendet werden
	public function set_attributes( $array ) {
		if(is_array($array)) {
			$this->container_attributes = $array;
		}
		else
			die('Attributes of parent element have to be given in an array.');
	}
	
	
	# Fuegt diesem Container ein Attribut hinzu
	public function add_attribute( $name, $value ) {
		if( isset( $this->container_attributes[$name] ) )
			$this->container_attributes[$name] = $this->container_attributes[$name] .' '. $value;
		else
			$this->container_attributes[$name] = $value;
	}
	
	
	# Gibt den Containernamen (Tag) zurueck
	public function get_tag() {
		return $this->container;
	}
	
	
	# Gibt den Wert eines Attributes zurueck
	public function get_attribute( $name ) {
		if( isset( $this->container_attributes[ $name ] ) )
			return $this->container_attributes[ $name ];
		else
			return false;
	}
	
	
	public function get_last_index() {
		return $this->last_swallowed_index;
	}
	
	
	# Setzt alle Inhalte zurueck, sprich der Container leert sich
	public function clear() {
		$this->subelements = array();
		return $this;
	}
	
	
	# Blockiert die Auslšsfunktion des Containers - provisorische Eigenlšschung
	public function block( $state=true ) {
		$this->blocked = $state;
	}
	
	
	# Gibt ein Array nur aller Untercontainer zurueck (die Objekte, nicht die Inhalte)
	public function get_subcontainer() {
		$subcontainer = array();
		foreach( $this->subelements as $subelement )
			if( is_object($subelement) )
				$subcontainer[] = $subelement;
		return $subcontainer;
	}
	
	
	# Durchsucht diesen und alle Untercontainer auf ein bestimmtes Kriterium (Tagname / Attribut)
	public function search( $array, rsContainer $Container=null ) {
		if(!$Container)	$Container = $this;
		if(is_string($array)) $array = array('tag' => $array);
		$finds = array();
		foreach( $Container->get_subcontainer() as $subcontainer ) {
			foreach( $array as $kriterium => $sollwert ) {
				if(	$kriterium == 'tag' && $subcontainer->get_tag() == $sollwert )
					$finds[] = $subcontainer;
				elseif( $subcontainer->get_attribute($kriterium) == $sollwert )
					$finds[] = $subcontainer;
			}
			$finds = array_merge( $finds, $this->search( $array, $subcontainer ) );
		}
	#	if( count($finds) == 1 )
	#		return $finds[0];
		return $finds;
	}
	
	
	# Gibt zurueck ob der Container 'selfclosing' ist
	public function is_selfclosing() {
		if( $this->selfclosing_container )
			return true;
		return false;
	}


}