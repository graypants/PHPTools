<?php 


/* my test */
function first_no_repeat_char($str) {
	$counts = count_chars($str, 1);
	$asicc  = array_search(1, $counts);
	$char   = chr($asicc);
	return $char;
}


function get_first_char($str) {
	preg_match_all('/[a-z]/i', $str, $matches);
	$counts = array_count_values($matches[0]);
	$key    = array_search(1, $counts);
	return $key;
}


$str = '1,2,3,4,5,6';
$arr = explode(',', $str);


function random_sort($arr) {
	shuffle($arr);
	$index1 = array_search(5, $arr);
	$index2 = array_search(6, $arr);
	if($index1 != 2 && abs($index1 - $index2) > 1) {
		return $arr;
	}else {
		return random_sort($arr);
	}
}

$data = random_sort($arr);

print_r($data);

?>