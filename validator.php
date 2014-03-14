<?php 

//验证是否为有效的IPV4地址
function is_valid_ip($value) {
	return Validator::filter('ip', $value);
}

//验证是否为合法的email
function is_valid_email($value) {
	return Validator::filter('email', $value);
}

class Validator {

	public static function filter($type, $value) {

		$type = strtolower($type);

		switch ($type) {
			case 'ip':
				$filter = FILTER_VALIDATE_IP;
				$options = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
				break;
			case 'email':
				$filter = FILTER_VALIDATE_EMAIL;
				$options = array();
				break;
			default:
				return false;
		}

		return (bool)filter_var($value, $filter, $options);
	}
}

var_dump(is_valid_ip('8.8.8.8'));

?>