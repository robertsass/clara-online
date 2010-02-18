<?php /* rsCalendar 1.1 */

class rsCalendar {

	protected $timestamp;
	protected $months;
	protected $days;
	

	public function __construct( $timestamp=null ) {
		if( $timestamp )
			$this->timestamp = $timestamp;
		else
			$this->timestamp = time();
		$this->months = array(
			1 => "Januar",
			2 => "Februar",
			3 => "M&auml;rz",
			4 => "April",
			5 => "Mai",
			6 => "Juni",
			7 => "Juli",
			8 => "August",
			9 => "September",
			10 => "Oktober",
			11 => "November",
			12 => "Dezember"
		);
		$this->days = array(
			1 => "Montag",
			2 => "Dienstag",
			3 => "Mittwoch",
			4 => "Donnerstag",
			5 => "Freitag",
			6 => "Samstag",
			0 => "Sonntag"
		);
	}
	
	
	public function timestamp() {
		return $this->timestamp;
	}
	
	
	public function get_month_names() {
		return $this->months;
	}
	
	
	public function get_day_names() {
		return $this->days;
	}
	
	
	public function month() {
		return $this->months[ date( 'n', $this->timestamp ) ];
	}
	
	
	public function day() {
		return $this->days[ date( 'w', $this->timestamp ) ];
	}
	
	
	public function day_of_month() {
		return date( 'j', $this->timestamp );
	}
	
	
	public function year() {
		return date( 'Y', $this->timestamp );
	}
	
	
	public function day_beginning() {
		return mktime( 0, 0, 0, date('m', $this->timestamp), date('d', $this->timestamp), date('Y', $this->timestamp) );
	}
	
	
	public function day_ending() {
		return $this->day_beginning() + 86399;
	}
	
	
	public function months_days() {
		return date( 't', $this->timestamp );
	}
	
	
	public function months_first_day() {
		$first_days_timestamp = $this->timestamp - ( ( date( 'd', $this->timestamp )-1 ) * 86400 );
		return new rsCalendar( $first_days_timestamp );
	}
	
	
	public function months_last_day() {
		$last_days_timestamp = $this->months_first_day()->day_beginning() + ( ( $this->months_days()-1 ) * 86400 );
		return new rsCalendar( $last_days_timestamp );
	}
	
	
	public function next_day() {
		$next_days_timestamp = $this->timestamp + 86400;
		return new rsCalendar( $next_days_timestamp );
	}
	
	
	public function next_monday() {
		$next_mondays_timestamp = ( ( 7 - ( date( 'w', $this->timestamp )-1 ) ) * 86400 ) + $this->timestamp;
		return new rsCalendar( $next_mondays_timestamp );
	}
	
	
	public function next_month() {
		$next_months_timestamp = ( ( $this->months_days() - ( date( 'd', $this->timestamp )-1 ) ) * 86400 ) + $this->timestamp;
		return new rsCalendar( $next_months_timestamp );
	}
	
	
	public function prev_day() {
		$next_days_timestamp = $this->timestamp - 86400;
		return new rsCalendar( $next_days_timestamp );
	}
	
	
	public function prev_month() {
		$prev_months_timestamp = $this->months_first_day()->prev_day()->months_first_day()->timestamp();
		return new rsCalendar( $prev_months_timestamp );
	}
	
	
	public function new_week_begins() {
		if( date( 'w', $this->timestamp ) == 1 )
			return true;
		return false;
	}
	

}