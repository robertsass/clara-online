<?php

/**
 * @name        classBaum
 * @deprecated  Mit Hilfe dieser Klasse koennen NestedSetBaeume erstellt werden!
 *              Dabei greift die Klasse nur auf zwei Felder in der Datenbank zu (lft & rgt)
 *              dadurch ist die Klasse unabhaengig von der Struktur der abgespeicherten Daten.
 *              Mögliche Aktionen sind:
 *                  - neues Element an einer bestimmten Stelle einfuegen
 *                  - Element loeschen
 *                  - Element verschieben
 * @class       NestedSetBaum
 * @subpackage  NestedSetBaum_MySQL
 * @methods     NestedSetBaum (str DB_table, str lft, str rgt, str moved, str id [, str adminmail [, int ID [, bool show_error]]])
 *              // Knoten einfügen
 *                insertRoot()
 *                insertChild (int idVater)
 *                insertBrotherLft (int idKnoten)
 *                insertBrotherRgt (int idKnoten)
 *              // Knoten löschen
 *                deleteOne (int idKnoten)
 *                deleteAll (int idKnoten)
 *              // Bewegen eines Knotens inc. seiner Kinder
 *                moveLft (int idKnoten)
 *                moveRgt (int idKnoten)
 *                moveUp (int idKnoten)
 *                moveDown (int idKnoten)
 *              // Fehlermeldungen
 *                getErrorNo ()
 *                getErrorStr ()
 *
 * @start       11.08.2003
 * @since       18.10.2003     
 * @version     0.5
 * 
 * @link        www.thundernail.de
 * @author      Martin Rosekeit <martin.rosekeit@thundernail.de>
 * @copyright   (c) 2003 Thundernail
 * @GNU         This library is free software; you can redistribute it and/or 
 *              modify it under the terms of the GNU Lesser General Public 
 *              License as published by the Free Software Foundation; either 
 *              version 2.1 of the License, or (at your option) any later 
 *              version. 
 *              This library is distributed in the hope that it will be 
 *              useful, but WITHOUT ANY WARRANTY; without even the implied 
 *              warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR 
 *              PURPOSE. See the GNU Lesser General Public License for more 
 *              details. 
 *              You should have received a copy of the GNU Lesser General 
 *              Public License along with this library; if not, write to the 
 *              Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 *              Boston, MA 02111-1307 USA
 **/
 
class NestedSetBaum {
/**
 * @access      public
 **/  
    /**
     * @param       string  DB_table    Name der Table in der DB
     * @param       string  lft         Name des linken Tabellenfaldes
     * @param       string  rgt         Name des rechten Tabellenfeldes
     * @param       string  moved       Name des moved-Tabellenfeldes (wurde ein Element bewegt)
     * @param       string  id          Name des id-Tabellenfeldes
     * @param       string  admin_mail  Email-Adresse des Admins
     * @param       int     ID          ID der MySQL-Verbindung
     * @param       bool    show_error  Fehlermeldungen anzeigen 
     * @return      void
     * @deprecated  Konstruktor
     **/ 
    function NestedSetBaum($DB_table, $lft, $rgt, $moved, $id, $adminmail="", $ID=0, $show_error=TRUE) {
        $this->DbTable   = $DB_table;
        $this->lftFeld   = $lft;
        $this->rgtFeld   = $rgt;
        $this->movedFeld = $moved;
        $this->idFeld    = $id;
        
        $this->db   = new NestedSetBaum_MySQL($ID, $adminmail, $show_error);
    }
    
    
    /**
     * @return      int     id des Kindelements
     * @error       -1
     * @deprecated  Fügt den Root-Knoten ein
     **/ 
    function insertRoot() {
        // Tabelle sperren
        $this->DbLocked();
        // Tabelle leer?
        $anzKnoten = $this->getAnzKnoten();
        if($anzKnoten < 0) {
            $this->DbUnLocked();
            return -1;
        }
        if($anzKnoten > 0) {
            $this->errorNo  = 12;
            $this->errorStr = "Root-Knoten existiert schon.";
            $this->DbUnLocked();
            return -1;
        }
        // Knoten einfügen
        $this->db->query(" INSERT INTO $this->DbTable ($this->lftFeld, $this->rgtFeld)
                            VALUES                     (0,              1);");
        $this->DbUnLocked();
        return $this->db->insert_id();
    }    
    
    
    /**
     * @param       int     idVater     id des Vaterelements
     * @return      int     id des Kindelements
     * @error       -1
     * @deprecated  Fügt ein Kindelement Rechts in Vater ein
     **/ 
    function insertChild($idVater) {
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Vater
        $rgtVater = $this->getRgt($idVater);
        if($rgtVater < 0) {
            $this->DbUnLocked();
            return -1;
        }
        // Linke Grenzen verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld + 2
                            WHERE $this->lftFeld >= $rgtVater;");
        // Rechte Grenze verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + 2
                            WHERE $this->rgtFeld >= $rgtVater;");
        $this->db->query(" INSERT INTO $this->DbTable ($this->lftFeld, $this->rgtFeld)
                            VALUES                     ($rgtVater,      $rgtVater+1);");
        $this->DbUnLocked();
        return $this->db->insert_id();
    }
    
    
    /**
     * @param       int     idKnoten    id des Knotens
     * @return      int     id des neues bruders
     * @error       -1
     * @deprecated  Fügt einen Bruder links neben den Knoten ein
     **/ 
    function insertBrotherLft($idKnoten) {
        // Tabelle sperren
        $this->DbLocked();
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return -1;
        }
        // root-Knoten?
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return -1;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 8;
            $this->errorStr = "Root-Koten kann keinen Bruder haben.";
            $this->DbUnLocked();
            return -1;
        }        
        // Linke Grenzen verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld + 2
                            WHERE $this->lftFeld >= $lftKnoten;");
        // Rechte Grenze verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + 2
                            WHERE $this->rgtFeld >= $lftKnoten;");
        $this->db->query(" INSERT INTO $this->DbTable ($this->lftFeld, $this->rgtFeld)
                            VALUES                     ($lftKnoten,     $lftKnoten+1);");
        $this->DbUnLocked();
        return $this->db->insert_id();
    }
    
    
    /**
     * @param       int     idKnoten    id des Knotens
     * @return      int     id des neues bruders
     * @error       -1
     * @deprecated  Fügt einen Bruder rechts neben den Knoten ein
     **/ 
    function insertBrotherRgt($idKnoten) {
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return -1;
        }
        $levelKnoten = $this->getLevel($idKnoten);
        // root-Knoten?
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return -1;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 8;
            $this->errorStr = "Root-Koten kann keinen Bruder haben.";
            $this->DbUnLocked();
            return -1;
        }          
        // Linke Grenzen verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld + 2
                            WHERE $this->lftFeld >= $rgtKnoten+1;");
        // Rechte Grenze verschieben
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + 2
                            WHERE $this->rgtFeld >= $rgtKnoten+1;");
        $this->db->query(" INSERT INTO $this->DbTable ($this->lftFeld, $this->rgtFeld)
                            VALUES                     ($rgtKnoten+1,   $rgtKnoten+2);");
        $this->DbUnLocked();
        return $this->db->insert_id();
    }

    
    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Löscht einen Knoten, alle Kinder gelangen 1 Ebene nach oben
     **/ 
    function deleteOne($idKnoten) {    
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // root-Knoten?
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 9;
            $this->errorStr = "Root-Koten kann nicht genlöscht werden.";
            $this->DbUnLocked();
            return FALSE;
        }         

        $this->db->query(" DELETE FROM $this->DbTable WHERE $this->idFeld=$idKnoten;");
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld - 1,
                                $this->rgtFeld = $this->rgtFeld - 1
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten;");
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld - 2
                            WHERE $this->lftFeld > $rgtKnoten;");
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld - 2
                            WHERE $this->rgtFeld > $rgtKnoten;");
        $this->DbUnLocked();
        return TRUE;
    }

    
    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Löscht einen Knoten und seine Kinder
     **/ 
    function deleteAll($idKnoten) {    
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Raum unter dem Knoten bestimmen
        $diff = $rgtKnoten - $lftKnoten + 1;
        // root-Knoten?
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 9;
            $this->errorStr = "Root-Koten kann nicht genlöscht werden.";
            $this->DbUnLocked();
            return FALSE;
        }          

        $this->db->query(" DELETE FROM $this->DbTable
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten;");
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->lftFeld = $this->lftFeld - $diff
                            WHERE $this->lftFeld > $rgtKnoten;");
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld - $diff
                            WHERE $this->rgtFeld > $rgtKnoten;");
        $this->DbUnLocked();
        return TRUE;
    }


    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Knoten mit linkem Bruder Platz tauschen
     **/ 
    function moveLft($idKnoten) { 
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // root-Knoten?
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 6;
            $this->errorStr = "Root-Koten kann nicht verschoben werden.";
            $this->DbUnLocked();
            return FALSE;
        }          
        // ID des linken Bruders
        $idBrother = $this->getIdRgt($lftKnoten-1);
        if($idBrother < 0) {
            $this->errorNo = 4;
            $this->errorStr = "Keinen linken Bruder gefunden.";
            $this->DbUnLocked();
            return FALSE;
        }
        // Rechte Grenze von linkem Bruder
        $rgtBrother = $this->getRgt($idBrother);
        if($rgtBrother < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze von linkem Bruder
        $lftBrother = $this->getLft($idBrother);
        if($lftBrother < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        
        // differenz zur alten Position
        $diffRgt = $rgtKnoten-$rgtBrother;
        $diffLft = $lftKnoten-$lftBrother;

        // moved 0 setzen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->movedFeld = 0
                            WHERE $this->movedFeld <> 0;");
        // Einträge nach rechts bewegen (Platz machen)
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld   = $this->rgtFeld + $diffRgt,
                                $this->lftFeld   = $this->lftFeld + $diffRgt,
                                $this->movedFeld = 1
                            WHERE $this->lftFeld BETWEEN $lftBrother AND $rgtBrother;");
        // Einträge nach links bewegen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld - $diffLft,
                                $this->lftFeld = $this->lftFeld - $diffLft
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten
                              AND $this->movedFeld = 0;");
        // moved 0 setzen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->movedFeld = 0
                            WHERE $this->movedFeld <> 0;");
        $this->DbUnLocked();
        return TRUE;
    }  
    
      
    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Knoten mit rechtem Bruder Platz tauschen
     **/ 
    function moveRgt($idKnoten) {
        // Tabelle sperren
        $this->DbLocked();
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // root-Knoten?
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 6;
            $this->errorStr = "Root-Koten kann nicht verschoben werden.";
            $this->DbUnLocked();
            return FALSE;
        }        
        // ID des rechten Bruders
        $idBrother = $this->getIdLft($rgtKnoten+1);
        if($idBrother < 0) {
            $this->errorNo = 5;
            $this->errorStr = "Keinen Rechten Bruder gefunden.";
            $this->DbUnLocked();
            return FALSE;
        }
        // Rechte Grenze von rechtem Bruder
        $rgtBrother = $this->getRgt($idBrother);
        if($rgtBrother < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze von rechtem Bruder
        $lftBrother = $this->getLft($idBrother);
        if($lftBrother < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        
        // differenz zur alten Position
        $diffRgt = $rgtBrother-$rgtKnoten;
        $diffLft = $lftBrother-$lftKnoten;

        // moved 0 setzen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->movedFeld = 0
                            WHERE $this->movedFeld <> 0;");
        // Einträge nach links bewegen (Platz machen)
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld   = $this->rgtFeld - $diffLft,
                                $this->lftFeld   = $this->lftFeld - $diffLft,
                                $this->movedFeld = 1
                            WHERE $this->lftFeld BETWEEN $lftBrother AND $rgtBrother;");
                 
        // Einträge nach rechts bewegen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + $diffRgt,
                                $this->lftFeld = $this->lftFeld + $diffRgt
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten
                              AND $this->movedFeld = 0;");
        // moved 0 setzen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->movedFeld = 0
                            WHERE $this->movedFeld <> 0;");
        $this->DbUnLocked();
        return TRUE;
    }
    

    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Knoten um eine Ebene nach oben.
     *              Wird als rechter Bruder neben Vater gesetzter.
     **/     
    function moveUp($idKnoten) {
        // Tabelle sperren
        $this->DbLocked();
        // der root-Knoten oder in die root-Ebene kann nicht verschoben werden
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 6;
            $this->errorStr = "Root-Koten kann nicht verschoben werden.";
            $this->DbUnLocked();
            return FALSE;
        }
        if ($levelKnoten < 2) {
            $this->errorNo  = 7;
            $this->errorStr = "In die Root-Ebene nicht verschoben werden.";
            $this->DbUnLocked();
            return FALSE;            
        }

        // Knoten nach rechts Verscheiben, bis er ganz rechts steht
        do {
            $moved = $this->moveRgt($idKnoten);
            if ($moved < 0) {
                if ($this->errorNo == 4) {
                    $this->errorNo  = 0;
                    $this->errorStr = "";
                    break;
                }
                else {
                    return FALSE;
                }
            }
        } while($moved == TRUE);
        
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // ID des Vater Knotens
        $idVather = $this->getIdRgt($rgtKnoten+1);
        if($idVather < 0) {
            $this->errorNo = 10;
            $this->errorStr = "Keinen Vater gefunden.";
            $this->DbUnLocked();
            return FALSE;
        }
        // Rechte Grenze vom Vater
        $rgtVather= $this->getRgt($idVather);
        if($rgtVather < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Vater
        $lftVather = $this->getLft($idVather);
        if($lftVather < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // breite des Knotens
        $widthKnoten = $rgtKnoten-$lftKnoten+1;

        // Knoten um 1 nach rechts bewegen => Fällt aus Vaterknoten raus
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + 1,
                                $this->lftFeld = $this->lftFeld +1
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten");
        // Vaterknoten vorm Knoten schließen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld - $widthKnoten
                            WHERE $this->idFeld = $idVather;");
        $this->DbUnLocked();
        return TRUE;
    }


    /**
     * @param       int     idKnoten    id des Knotens
     * @return      bool    TRUE erfolgreich
     * @error       FALSE
     * @deprecated  Knoten um eine Ebene nach unten.
     *              Wird rechter Sohn seines linken Bruders.
     *                  K1     K2     K3
     *                 /  \   /  \   /  \
     *                K4..K5 K6..K7 K8..K9
     *              --moveDown(K2)----------
     *                    K1      K3 
     *                 /     \   /  \
     *                K4..K5 K2 K8..K9
     *                      /  \
     *                     K6..K7
     **/        
    function moveDown($idKnoten) {
        // Tabelle sperren
        $this->DbLocked();
        // der root-Knoten kann nicht verschoben werden
        $levelKnoten = $this->getLevel($idKnoten);
        if($levelKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        if($levelKnoten < 1) {
            $this->errorNo  = 6;
            $this->errorStr = "Root-Koten kann nicht verschoben werden.";
            $this->DbUnLocked();
            return FALSE;
        }
        
        // Rechte Grenze vom Knoten
        $rgtKnoten = $this->getRgt($idKnoten);
        if($rgtKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze vom Knoten
        $lftKnoten = $this->getLft($idKnoten);
        if($lftKnoten < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // ID des neuen Vater Knotens
        $idVather = $this->getIdRgt($lftKnoten-1);
        if($idVather < 0) {
            $this->errorNo = 04;
            $this->errorStr = "Keinen linken Bruder gefunden.";
            $this->DbUnLocked();
            return FALSE;
        }
        // Rechte Grenze von neuen Vater 
        $rgtVather= $this->getRgt($idVather);
        if($rgtVather < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        // Linke Grenze von neuen Vater 
        $lftVather = $this->getLft($idVather);
        if($lftVather < 0) {
            $this->DbUnLocked();
            return FALSE;
        }
        
        // breite des Knotens
        $widthKnoten = $rgtKnoten-$lftKnoten+1;

        // Knoten um 1 nach links bewegen => Member des neuen Vaterknotens
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld - 1,
                                $this->lftFeld = $this->lftFeld - 1
                            WHERE $this->lftFeld BETWEEN $lftKnoten AND $rgtKnoten");
        // Vaterknoten hinterm Knoten schließen
        $this->db->query(" UPDATE $this->DbTable
                            SET $this->rgtFeld = $this->rgtFeld + $widthKnoten
                            WHERE $this->idFeld = $idVather;");
        $this->DbUnLocked();
        return TRUE;
    }    

    
    /**
     * @return      int     Fehlernummer
     * @error       -1
     * @deprecated  Gibt die Fehlernummer zurück.
     **/     
    function getErrorNo() {
        if ($this->errorNo < 1) {
            $this->errorNo  = 13;
            $this->errorStr = "Kein Fehler aufgetreten";
            return -1;
        }
        return $this->errorNo;
    }

    
    /**
     * @return      string  Fehlermeldung
     * @error       ""
     * @deprecated  Gibt die Fehlermeldung zurück.
     **/     
    function getErrorMsg() {
        if ($this->errorNo < 1) {
            $this->errorNo  = 13;
            $this->errorStr = "Kein Fehler aufgetreten.";
            return "";
        }
        return $this->errorStr;
    }

/**
 * @access      privat
 **/ 
    /**
     * @var         string
     * @deprecated  MySQL-DB Daten
     **/
    var $DbTable    = "";   // @deprecated  Name der Table in der DB
    var $lftFeld    = "";   // @deprecated  Name des linken Tabellenfeldes
    var $rgtFeld    = "";   // @deprecated  Name des rechten Tabellenfeldes
    var $idFeld     = "";   // @deprecated  Name des id-Tabellenfeldes
    var $movedFeld  = "";   // @deprecated  Name des moved-Tabellenfeldes
    /**
     * @deprecated  Fehlerfall
     **/
    var $errorNo    = 0;    // @var         int
    var $errorStr   = "";   // @var         string
    /**
     * @package     NestedSetBaum_MySQL
     **/
    var $db;    // @deprecated  MySQL-Datenbankanbindung

    
    /**
     * @param       int     id  id des Knoten
     * @return      int     rechte Grenze
     * @error       -1
     * @deprecated  Bestimmt die rechte Grenze eines Knotens
     **/ 
    function getRgt($id) {
        $knoten = $this->db->query_first(" SELECT $this->rgtFeld as rgt 
                                            FROM $this->DbTable 
                                            WHERE $this->idFeld = $id;");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 1;
            $this->errorStr = "Der Knoten mit der ID=$id konnte nicht gefunden werden.";
            return -1;
        }
        return $knoten["rgt"];
    }
    

    /**
     * @param       int     id  id des Knoten
     * @return      int     linke Grenze
     * @error       -1
     * @deprecated  Bestimmt die linke Grenze eines Knotens
     **/ 
    function getLft($id) {
        $knoten = $this->db->query_first(" SELECT $this->lftFeld as lft 
                                            FROM $this->DbTable 
                                            WHERE $this->idFeld = $id;");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 1;
            $this->errorStr = "Der Knoten mit der ID=$id konnte nicht gefunden werden.";
            return -1;
        }
        return $knoten["lft"];
    }
    

    /**
     * @param       int     rgt  Rechte Grenze des Knoten
     * @return      int     id des Kontens
     * @error       -1
     * @deprecated  bestimmt die ID eines Knotens anhand des rechten Grenze
     **/ 
    function getIdRgt($rgt) {
        $knoten = $this->db->query_first(" SELECT $this->idFeld as id 
                                            FROM $this->DbTable 
                                            WHERE $this->rgtFeld = $rgt;");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 2;
            $this->errorStr = "Ein Knoten mit der rechten Grenze $rgt konnte nicht gefunden werden.";
            return -1;
        }
        return $knoten["id"];
    }
    

    /**
     * @param       int     lft  Linke Grenze des Knoten
     * @return      int     id des Kontens
     * @error       -1
     * @deprecated  bestimmt die ID eines Knotens anhand des linken Grenze
     **/ 
    function getIdLft($lft) {
        $knoten = $this->db->query_first(" SELECT $this->idFeld as id 
                                            FROM $this->DbTable 
                                            WHERE $this->lftFeld = $lft;");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 3;
            $this->errorStr = "Ein Knoten mit der linken Grenze $lft konnte nicht gefunden werden.";
            return -1;
        }
        return $knoten["id"];
    }


    /**
     * @param       int     idKnoten  ID des Knotens
     * @return      int     Ebene des Knotens (0: rootKnoten)
     * @error       -1
     * @deprecated  Bestimmt die Ebene des Knotens
     **/ 
    function getLevel($idKnoten) {
        $knoten = $this->db->query_first("  SELECT baum2.$this->idFeld AS id,
                                                   COUNT(*) AS level
                                            FROM $this->DbTable AS baum1,
                                                 $this->DbTable AS baum2
                                            WHERE baum2.lft BETWEEN baum1.lft AND baum1.rgt
                                            GROUP BY baum2.lft
                                            ORDER BY ABS(baum2.$this->idFeld - $idKnoten);");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 1;
            $this->errorStr = "Der Knoten mit der ID=$id konnte nicht gefunden werden.";
            return -1;
        }
        if ($knoten["id"] != $idKnoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 1;
            $this->errorStr = "Der Knoten mit der ID=$id konnte nicht gefunden werden.";
            return -1;
        }        
        return $knoten["level"]-1;
    }
    

    /**
     * @return      int     Anzahl der Knoten
     * @error       -1
     * @deprecated  Bestimmt die Anzahl der Knoten in der Tabelle
     **/ 
    function getAnzKnoten() {
        $knoten = $this->db->query_first(" SELECT COUNT(*) AS anz
                                            FROM $this->DbTable;");
        if (!$knoten) {
            // Knoten nicht gefunden
            $this->errorNo  = 11;
            $this->errorStr = "Anzahl der Knoten konnte nicht bestimmt werden.";
            return -1;
        }
        return $knoten["anz"];
    }    
    
    /**
     * @return      FALSE
     * @deprecated  Sperrt die DB-Tabelle
     **/ 
    function DbLocked() {
        $this->db->query("  LOCK TABLES $this->DbTable WRITE,
                                        $this->DbTable AS baum1 WRITE,
                                        $this->DbTable AS baum2 WRITE;");
    }
    
    
    /**
     * @return      FALSE
     * @deprecated  Hebt Sperrung der DB-Tabelle auf
     **/ 
    function DbUnLocked() {    
        $this->db->query("UNLOCK TABLES;");
    }
}

?>