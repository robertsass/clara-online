<?php

/**
 * @name        classDB
 * @deprecated  Datenbank anbindung
 * 
 * @since       11.08.2003       
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
 
class NestedSetBaum_MySQL {
/**
 * @access      public
 **/ 
    /**
     * @param       int     ID          ID der MySQL-Verbindung
     * @param       string  adminmail   Mailadresses des Admins fuer Fehlermeldung
     * @param       bool    show_error  Fehlermeldungen anzeigen
     * @return      void
     * @deprecated  Konstruktor
     **/ 
    function NestedSetBaum_MySQL ($ID, $adminmail, $show_error=TRUE) {
        $this->linkId    = $ID;
        $this->showError = $show_error;
        $this->adminmail = $adminmail;
    }


    /**
     * @param       string  query_string    MySQL-Abfragestring
     * @return      int     ID des Abfrageergebnisses
     * @deprecated  Sendet eine Abfrage an die MySQL-DB und gibt die ID der Antwort zurueck
     **/     
    function query($query_string) {
        $this->query_id = mysql_query($query_string, $this->linkId);
        if (!$this->query_id) {
           $this->error("Invalid SQL: ".$query_string);
        }
        return $this->query_id;
    }    


    /**
     * @param       string  query_string    MySQL-Abfragestring
     * @return      int     ID des Abfrageergebnisses
     * @deprecated  Sendet eine Abfrage an die MySQL-DB und gibt die 1. Ergebniszeile zurueck
     *              Kein Parameter uebergeben => Bearbeitung des zuletzt bearbeiteten Ergebnisses
     **/     
    function query_first($query_string) {
        $this->query($query_string);
        $returnarray=$this->fetch_array($this->query_id);
        $this->free_result($this->query_id);
        return $returnarray;
    }    
    

    /**
     * @param       string  query_string    MySQL-Abfragestring
     * @return      array   2D-Feld des Abfrageergebnisses
     * @error       bool    FALSE   wenn keine Daten im Feld
     * @deprecated  Sendet eine Abfrage an die MySQL-DB und gibt das Ergebnis zurueck
     **/     
    function query_array($query_string) {
        $this->queryId = $this->query($query_string);
        unset($this->record);
        while ($result = mysql_fetch_array($this->queryId)){
            $this->record[] = $result;
        }
        $this->free_result($this->queryId);
        if(isset($this->record))  return $this->record;
        else                      return FALSE;
    }    


    /**
     * @param       void
     * @return      int     ID des letzte eingetrages
     * @error       int     -1
     * @deprecated  Sendet eine Abfrage an die MySQL-DB und gibt das Ergebnis zurueck
     **/   
    function insert_id() {
        $this->record = $this->query_first("SELECT LAST_INSERT_ID() AS id;");
        if($this->record)   return $this->record["id"];
        else                return -1;
    }
     
     
/**
 * @access      privat
 **/ 
    
    /**
     * @var         int
     **/
    var $linkId = 0;    // @deprecated ID der MySQL-Verbindung
    var $queryId = 0;   // @deprecated ID des Abfrageergebnisses
    /**
     * @var         array
     **/
    var $record = array();  // @deprecated aus einer Abfrage erzeugtes Array
    /**
     * @var         string  
     **/
    var $adminmail = "";    // @deprecated Mailadresse des Admins
    /**
     * @var         bool    TRUE
     **/
    var $showError = TRUE;  // @deprecated Sollen fehler als HTML-Seite sichtbar ausgegeben werden

    
    /**
     * @param       int     query_id    ID des Abfrageergebnisses
     * @return      array   Daten des Abfrageergebnisses
     * @deprecated  Gibt die erste Zeile des Abfrageergebnisses zurück
     *              Löscht diese Zeile aus dem Ergebnis
     **/         
    function fetch_array($queryId=-1) {
        if ($queryId!=-1) {
            $this->queryId=$queryId;
        }
        $this->record = mysql_fetch_array($this->query_id);
        return $this->record;
    }    
    /**
     * @param       int     query_id    ID des Abfrageergebnisses
     * @return      bool    erfolgreich?
     * @deprecated  Loescht das Abfrageergebnis aus dem Speicher
     *              Kein Parameter uebergeben => Bearbeitung des zuletzt bearbeiteten Ergebnisses
     **/     
    function free_result($query_id=-1) {
        if ($query_id!=-1) {
            $this->query_id=$query_id;
        }
        return @mysql_free_result($this->query_id);
    }    


    /**
     * @param       string  Hinweis zum Fehler
     * @deprecated  Gibt eine Fehlermeldung aus
     *              Beendet danach das Script
     **/ 
    function error($msg="") {
        $message ="Error in MySQL-DB: $msg\n<br>";
        $message.="error: ".mysql_error()."\n<br>";
        $message.="error number: ".mysql_errno()."\n<br>";
        $message.="Date: ".date("d.m.Y @ H:i")."\n<br>";
        $message.="Script: ".getenv("REQUEST_URI")."\n<br>";
        $message.="Referer: ".getenv("HTTP_REFERER")."\n<br><br>";
        $message.="Admin: <a href=\"mailto:$this->adminmail\">$this->adminmail</a>\n<br><br>";

        if($this->showError)
            die($message);
        exit();
    }      
}
 

?>