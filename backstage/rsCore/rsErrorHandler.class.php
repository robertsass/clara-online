<?php /* rsErrorHandler 1.1 */

class rsErrorHandler {

	public $log = array();
	
	public function logError( $number, $message=null ) {
		if( !is_string($number) )
			$this->log[$number] = $message;
		else
			$this->log[] = $number;
	}
	
	public function report() {
		$str;
		foreach($this->log as $logentry) {
			$str .= '<p>' . $logentry . '</p>' . "\n";
		}
		return $str;
	}
	
}