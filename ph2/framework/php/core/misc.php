<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Miscellaneus Helper Functions
Framework File Signature: com.ph2.framework.php.core.misc
Description:
Common helpers aiming at routine simplifications
---
/*/

//+ 
function expandArray ( $arr , $sep , $last='' )
/*/
Converts an array to a string by expanding all
its values and connecting them with a seperator, optionally concluded by
a last element.
---
@param arr: the array to be
converted
@type  arr: array
@param sep: the string to be placed between the
array elements
@type  sep: string
@param last: the string to be placed at
the end of the expanded array string
@type  last: string
-
@return: the string representation of the submitted
array
@rtype:  string
/*/
{
	assert(is_array($arr));
	$result = "";
	foreach ($arr as $val) {
		$result .= $val . $sep;
	}
	// last element is not followed by the standard separator
	$result = rtrim($result, $sep);
	// but by a last element (if desired/provided)
	return ($result . $last);
	
} //expandArray

//+ 
function now ( )
/*/
Returns a string containing a current timestamp.
Format: YYYY-MM-DD HH:MM:SS
-
@return: string representation
of current time
@rtype:  string
/*/
{
	return date("Y-m-d H:i:s");
} //now

//+ 
function writeFile ( $path , $content , $overwrite=FALSE )
/*/
Takes a full path (incl. filename) and a string
and writes the string into that file.
---
@param path: the path to write to
@type  path: string
@param content: the content to be wirtten to the
file
@type  content: string
@param overwrite: whether to overwrite an existing file or not
@type  overwrite: bool
-
@return: 1 on success, 0 otherwise
@rtype:  bool
/*/
{
	/* source: php.net */
	
	// Sichergehen, dass die Datei existiert und beschreibbar ist
	
	if ($overwrite) {
		$mode = 'w';
	} else {
		$mode = 'a';
	}
	
		// Wir öffnen $filename im "Anhänge" - Modus.
		// Der Dateizeiger befindet sich am Ende der Datei, und
		// dort wird $somecontent später mit fwrite() geschrieben.
		if (!$handle = fopen($path, $mode)) {
			 print "FrwWarning: Cannot open file $path.";
			 return 0;
		}
	
		// Schreibe $somecontent in die geöffnete Datei.
		if (!fwrite($handle, $content)) {
			print "FrwWarning: Cannot write into $path.";
			return 0;
		}
		// successfully written!
		fclose($handle);
		return 1;
	
	

} //writeFile

//+ 
function getVersionInfo ( )
/*/
Returns the current version/status/build
signature from the database (sys_META).
-
@return: array(version => version, status => status, build =>
build)
@rtype:  array
/*/
{
	// select information from database
	$tb_sys_META = new Table('sys_META');
	$version = $tb_sys_META->get(array('tag' => 'version'));
	$status  = $tb_sys_META->get(array('tag' => 'status'));
	$build   = $tb_sys_META->get(array('tag' => 'build'));
	// return array
	return array('version' => $version[0]['value'], 'status' => $status[0]['value'], 'build' => $build[0]['value']);
	
} //getVersionInfo

//+ 
function getPathFromSignature ( $signature )
/*/
Transforms a (module) signature into
a relative file path. The prefix 'com.ph2.modules' must NOT be handed to
the function.
---
@param signature: the module signature
without prefix
@type  signature: string
-
@return: the relative path pointing to the corresponding
module.php-file
@rtype:  string
/*/
{
	assert(!empty($signature));
	return 'modules' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $signature) . DIRECTORY_SEPARATOR . 'module.php';
} //getPathFromSignature

//+ 
function savedSuccessfullyMsg ( )
/*/
Adds a notification like 'Changes saved successfully' to the Session's notifications 
array.
/*/
{
	global $ps;
	$ps->notifications->push( new Notification("Changes saved successfully.", 'ok') );
} //savedSuccessfullyMsg

//+ 
function fixBoolArray ( $array )
/*/
Iterates over an array. For each Key/Value-pair, the value is transformed into a valid 
boolean (TRUE or FALSE) if it is in javascript-style (true/false).
---
@param array: the array to be converted
@type  array: array
-
@return: the array with real boolean values
@rtype:  array
/*/
{
	$result = array();
	foreach ($array as $key => $value) {
		if ($value == 'true') {
			$value = TRUE;
		} else if ($value == 'false') {
			$value = FALSE;
		}
		$result[$key] = $value;
	}
	return $result;
} //fixBoolArray

//+ 
function removeEmptyArrayFields ( $array )
/*/
Removes all empty fields from an array
---
@param array: the input array from which empty fields should be removed
@type  array: array
-
@return: the input array with all empty fields removed
@rtype:  array
/*/
{
	assert(is_array($array));
	$result = array();
	
	foreach ($array as $key => $value) {
		if (!empty($value)) {
			$result[$key] = $value;
		}
	}
	return $result;
} //removeEmptyArrayFields

//+ 
function startsWith ( $haystack , $needle )
/*/
Checks whether a string starts with a given substring
---
@param haystack: the string to be checked
@type  haystack: string
@param needle: the string that should be contained in $haystack's beginning
@type  needle: string
-
@return: TRUE if $needle matches the start of $haystack, FALSE otherwise
@rtype:  bool
/*/
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
} //startsWith

//+ 
function generateCheckoutHash ( $text_or_corpus_id )
/*/
Generates a 32-character string to serve as Identifier in the CHECKOUT table. It is 
calculated by retrieving an md5-hash from TextID/CorpusID + the system's microtime().
---
@param text_or_corpus_id: the TextID or CorpusID of the item to be checked out
@type  text_or_corpus_id: int
-
@return: 32-character hash
@rtype:  string
/*/
{
	
	$microtime = microtime();
	return md5( "$text_or_corpus_id$microtime" );
	
} //generateCheckoutHash

?>