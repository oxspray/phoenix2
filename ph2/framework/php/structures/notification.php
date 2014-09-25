<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Notification
Framework File Signature: com.ph2.framework.php.structures.notification
Description:
Notifications are stored in PH2Sessions and transport information on
various system events.
---
/*/

//+
class Notification
{
	// INSTANCE VARS
	// -------------
	protected $_text;
	protected $_type; /*/
	0:std, 1:ok, 2:note, 3:err,
	4:fatal
	/*/
	protected $_scope; /*/
	the system section where this
	notification is aimed at; all: general
	/*/
	protected $_origin; /*/
	the module signature where this
	notification was issued
	/*/
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $text , $type=0 , $scope=all , $origin=NULL )
	/*/
	A Notification is constructed with at least a text; other
	instance vars receive default values if no information is provided.
	---
	@param text: the text of the
	Notification.
	@type  text: string
	@param type: the Notification
	type (see instance vars)
	@type  type: int/string
	@param scope: the scope of the
	Notification
	@type  scope: string
	@param origin: the module
	signature where the Notification was issued
	@type  origin: string
	/*/
	{
		// check if $text is valid
		if (!is_string($text) || strlen($text) < 1) {
			die("FrwError: Invalid text for Notification construction");
		}
		// check (and convert) $type
		if (!checkNotificationType($type)) {
			die("FrwError: Invalid Notification type for Notification construction");
		}
		if (is_string($type)) {
			// convert string type representation to int code
			switch ($type) {
				case 'std'  : $type = 0; break;
				case 'ok'   : $type = 1; break;
				case 'note' : $type = 2; break;
				case 'err'  : $type = 3; break;
				case 'fatal': $type = 4; break;
			}
		}
		// add submitted values to the object
		$this->_text = $text;
		$this->_type = $type;
		$this->_scope = $scope;
		$this->_origin = $origin;
		
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getText ( )
	/*/
	accessor
	-
	@return: the text of
	this Notification
	@rtype:  string
	/*/
	{
		return $this->_text;
	} //getText
	
	//+ 
	function getType ( )
	/*/
	accessor
	-
	@return: the type of this
	Notification
	@rtype:  int
	/*/
	{
		return $this->_type;
	} //getType
	
	//+ 
	function getScope ( )
	/*/
	accessor
	-
	@return: the scope of
	this Notification
	@rtype:  string
	/*/
	{
		return $this->_scope;
	} //getScope
	
	//+ 
	function getOrigin ( )
	/*/
	accessor
	-
	@return: the origin of
	this Notification
	@rtype:  string
	/*/
	{
		return $this->_origin;
	} //getOrigin
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

//+ 
function checkNotificationType ( $type )
/*/
Checks if a given type variable is a
valid type representation of a Notification. Valid are: 0-4 and 'std',
'ok', 'note', 'err', 'fatal'
---
@param type: the
type candidate
@type  type: int/string
-
@return: TRUE if the submitted variable is a valid type
representation, FALSE otherwise
@rtype:  bool
/*/
{
	if (is_string($type)) {
		switch ($type) {
			case 'std'  : $type = 0; break;
			case 'ok'   : $type = 1; break;
			case 'note' : $type = 2; break;
			case 'err'  : $type = 3; break;
			case 'fatal': $type = 4; break;
			default: return FALSE;
		}
	}
	if (!is_int($type)) {
		return FALSE;
	}
	if ($type > 4) {
		return FALSE;
	} else {
		return TRUE;
	}
} //checkNotificationType

?>