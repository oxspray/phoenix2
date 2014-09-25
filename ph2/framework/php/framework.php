<?php
/*/
Phoenix2
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: framework.php
Description:
Combines all *.php-files (=framework subparts) in subfolders (recursively).
Note:
This file is NOT managed via XML/ph2repo
---
/*/

function endsWith ( $haystack , $needle )
/*
Checks whether a string ends with a given substring
---
@param haystack: the string to be checked
@type  haystack: string
@param needle: the string that should be contained in $haystack's end
@type  needle: string
-
@return: TRUE if $needle matches the end of $haystack, FALSE otherwise
@rtype:  bool
/*/
{
	$length = strlen($needle);
	$start  = $length * -1; //negative
	return (substr($haystack, $start) === $needle);
} //endsWith

function include_fw_files ($dir) 
/* crawls a directory (and all its subdirectories recursively) and includes all .php files via
require-once */
{
	$list = scandir($dir);
	// remove first two entries (.) and (..)
	array_shift($list);
	array_shift($list);
	foreach($list as $elem) {
		if(endsWith($elem, '.php') and !($dir . DIRECTORY_SEPARATOR . $elem == __FILE__)) require_once($dir . DIRECTORY_SEPARATOR . $elem);
		else if(is_dir($dir . DIRECTORY_SEPARATOR .$elem)) include_fw_files($dir . DIRECTORY_SEPARATOR . $elem);
	}
}

//apply
$current_file_path = dirname(__FILE__);
include_fw_files($current_file_path);


?>