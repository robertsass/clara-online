<?php /* rsTree 2.1 */

class rsTree extends rsMysql {
	
	protected $NestedSetDB;
	protected $NestedSet;
	
	public function __construct( $dbtable ) {
		parent::__construct( $dbtable );
		$this->NestedSetDB = new NestedSetBaum_MySQL( $this->connection, '', TRUE );
		$this->NestedSet = new NestedSetBaum ($this->dbtable, 'lft', 'rgt', 'moved', 'id', '', $this->connection, TRUE );
	}
	
	public function getCompleteTree() {
		$sql = 'SELECT n.name,
			COUNT(*)-1 AS level,
			ROUND((n.rgt - n.lft - 1) / 2) AS offspring,
			n.lft AS Linkswert, n.rgt AS Rechtswert,
			n.id AS ID
			FROM `' . $this->dbtable . '` AS n, `' . $this->dbtable . '` AS p
			WHERE n.lft BETWEEN p.lft AND p.rgt
			GROUP BY n.lft
			ORDER BY n.lft;';
		return $this->get( $sql );
	}
		

	public function get_sublevel_documents( $rootid ) {
		$docs = array();
		$knoten = $this->getOne( 'SELECT `lft`, `rgt` FROM `' . $this->dbtable . '` WHERE `id` = ' . $rootid . ';' );
		foreach( $this->get('SELECT *, COUNT(*)-1 AS level, ROUND((`rgt` - `lft` - 1) / 2) AS offspring FROM `' . $this->dbtable . '` WHERE `lft` > ' . $knoten[0] . ' AND `rgt` < ' . $knoten[1] . ' GROUP BY `lft` ORDER BY `lft`;') as $leaf ) {
			if($leaf['rgt'] > $lastRgt) {
				$docs[] = $leaf;
				$lastRgt = $leaf['rgt'];
			}
		}
		return $docs;
	}


	public function selectLeaf( $id ) {
		return new rsLeaf( $id, $this );
	}
	
	public function updateLeaf( $id, $array ) {
		$this->update( $this->dbtable, $array, 'WHERE `id` = "' . $id. '"');
	}
	
	
	// Alias-Methoden zur NSC
	public function createRoot()					{	return $this->NestedSet->insertRoot();						}
	public function createChild( $parentId )		{	return $this->NestedSet->insertChild( $parentId );			}
	public function createBrotherLeft( $leafId )	{	return $this->NestedSet->insertBrotherLft( $leafId );		}
	public function createBrotherRight( $leafId )	{	return $this->NestedSet->insertBrotherRgt( $leafId );		}
	public function deleteLeaf( $leafId )			{	return $this->NestedSet->deleteOne( $leafId );				}
	public function deleteAllChildren( $leafId )	{	return $this->NestedSet->deleteAll( $leafId );				}
	public function moveLeaf( $direction, $leafId )	{
		$success = true;	// Gehe davon aus, dass erfolgreich verlŠuft
		if($direction == 'up')							return $this->NestedSet->moveUp( $leafId );
		elseif($direction == 'down')					return $this->NestedSet->moveDown( $leafId );
		elseif($direction == 'left')					return $this->NestedSet->moveLft( $leafId );
		elseif($direction == 'right')					return $this->NestedSet->moveRgt( $leafId );
		else $success = false;	// Nichts traf zu -> 2. Parameter falsch angegeben
		return $success;
	}
	public function getErrorNo()					{	return $this->NestedSet->getErrorNo();		}
	public function getErrorString()				{	return $this->NestedSet->getErrorStr();	}

}