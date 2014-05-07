<?php 

/* 文件相关 */


/**
 * Copy a file, or recursively copy a folder and its contents
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       string   $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755) {
	// Check for symlinks
	if (is_link($source)) {
		return symlink(readlink($source), $dest);
	}

	// Simple copy for a file
	if (is_file($source)) {
		return copy($source, $dest);
	}

	// Make destination directory
	if (!is_dir($dest)) {
		mkdir($dest, $permissions);
	}

	// Loop through the folder
	$dir = dir($source);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if ($entry == '.' || $entry == '..') {
			continue;
		}

		// Deep copy directories
		xcopy("$source/$entry", "$dest/$entry");
	}

	// Clean up
	$dir->close();
	return true;
}

/**
 * 删除目录
 * @param  string $dir 
 * @return boolean      
 */
function fullRmdir($dir) {
	if( !is_writable( $dir ) ) {
		if( !@chmod( $dir, 0777 ) ) {
			return FALSE;
		}
	}

	$d = dir($dir);
	while ( FALSE !== ( $entry = $d->read() ) ) {
		if ( $entry == '.' || $entry == '..' ) {
			continue;
		}
		$entry = $dir . '/' . $entry;
		if ( is_dir( $entry ) ) {
			if ( !fullRmdir( $entry ) ) {
				return FALSE;
			}
			continue;
		}
		if ( !@unlink( $entry ) ) {
			$d->close();
			return FALSE;
		}
	}

	$d->close();

	rmdir( $dir );

	return TRUE;
}

/**
 * 递归生成多级目录
 * @param string $dir
 * @return boolean
 */
function mkdirs($dir) {
	if(!is_dir($dir)) {
		if(!mkdirs(dirname($dir))) {
			return false;
		}
		if(!mkdir($dir,0755)) {
			return false;
		}
	}
	return true;
}



/* 日期相关 */



/**
 * 按日期区间生成一组日期
 *
 * @param string $from Y-m-d
 * @param string $to Y-m-d
 * @return array
 */
function make_days($from, $to) {
	$dates = array();
	$min   = min($from, $to);
	$days  = abs(strtotime($from) - strtotime($to))/86400 + 1;
	for ($i = 0; $i < $days; $i++) {
		$dates[] = date("Y-m-d", strtotime("+$i day", strtotime($min)));
	}
	return $dates;
}



/* 对象和数组间互相转换 */



function objectToArray($d) {
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}

	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $d);
	}
	else {
		return $d;
	}
}

function arrayToObject($d) {
	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return (object) array_map(__FUNCTION__, $d);
	}
	else {
		// Return object
		return $d;
	}
}
?>