<?php	/* rsBackstage 2.0 */

class rsBackstage extends rsCore {


	private function templates() {
		return array(
			0 => "Dashboard",
			1 => "Docs",
			2 => "Media",
			3 => "User",
			4 => "Plugins",
			5 => "Backup",
			6 => "Settings"
		);
	}


	protected function detect_requested_page() {
		$templates = $this->templates();
		return $templates[ ( isset($_GET['i']) ? intval($_GET['i']) : 0 ) ];
	}


	protected function load_template() {
		$template = $this->detect_requested_page();
		define( 'TEMPLATE', $template );
		return new $template( $this->db, $this->head, $this->body );
	}


}
