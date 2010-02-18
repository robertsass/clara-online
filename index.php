<?php
error_reporting(0);	// Keine Fehler ausgeben!


//	UNCOMMENT THE FOLLOWING LINE IF SHOULD REDIRECT TO UNDER-CONSTRUCTION-PAGE
#	$underconstruction = true;



if( $underconstruction || isset($_GET['simulate-uc']) ) {
	if( isset($_GET['getstatus']) )
		echo "underconstruction";
	else
		include 'onemoment.html';
	break;
}




$Core = new rsCore();	// rsCore instanziieren



/* Auto-Load fuer rsCore-Klassen */
function __autoload( $class_name ) {
	$possible_locations = array(
		'backstage/rsCore/'. $class_name .'.class.php',
		'classes/'. $class_name .'.class.php',
		'templates/'. $class_name .'.template.php' );
	foreach( $possible_locations as $possible_location )
		if( file_exists( $possible_location ) )
			require_once( $possible_location );
}