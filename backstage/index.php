<?php
error_reporting(0);	// Keine Fehler ausgeben!

require_once( '../config.php' );


define( 'BACKSTAGE_VERSION', "2.0" );


$Core = new rsBackstage();	// rsBackstage instanziieren



/* Auto-Load fuer rsCore-Klassen */
function __autoload( $class_name ) {
	$possible_locations = array(
		'rsCore/'. $class_name .'.class.php',
		'templates/'. $class_name .'.template.php',
		'../rsCore/'. $class_name .'.class.php' );
	foreach( $possible_locations as $possible_location )
		if( file_exists( $possible_location ) ) {
			require_once( $possible_location );
			return true;
		}
}