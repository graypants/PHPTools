<?php 

require 'validator.php';

function get_client_ip() {
	return PrettyIP::value();
}

function ip_address_info($ip) {

}

class PrettyIP {

	public static function value() {

		if( !empty($_SERVER['HTTP_CLIENT_IP']) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}

		if ( !empty($_SERVER['HTTP_X_FORWARD_FOR'] ) {
			$ips = explode(',', $_SERVER['HTTP_X_FORWARD_FOR']);
			$ip  = $ips[0];
		}

		if ( !empty($_SERVER['REMOTE_ADDR']) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		if( isset($ip) ) {
			$ip = trim($ip);
			return is_valid_ip($ip) ? $ip : null;
		}

		return null;
	}


}

?>
