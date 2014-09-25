<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Notification Stack
Framework File Signature: com.ph2.framework.php.structures.x_stack-notification
Description:
A Stack for Notifications
---
/*/

//+
class NotificationStack extends Stack
{
	// INSTANCE VARS
	// -------------
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function push ( $item )
	/*/
	Only items of type Notification can be pushed
	onto a NotificationStack
	---
	@param item: The
	Notification to be pushed onto the stack.
	@type  item: Notification
	/*/
	{
		if (get_class($item) == 'Notification') {
			parent::push($item);
		} else {
			// so the submitted item is no Notification
			die("FrwError: Only Notifications can be pushed onto a NofiticationStack");
		}
	} //push
	
	//+ 
	function popType ( $type )
	/*/
	Returns all Notifications with a given type
	and removes them from the stack.
	---
	@param type: the type of the notifications to be
	returned
	@type  type: int/string
	-
	@return: array of Notifications that match the given
	type criterium
	@rtype:  array
	/*/
	{
		// check if $type is a valid Notification type
		if (!checkNotificationType($type)) {
			die("FrwError: No valid type to filter Nofications");
			return;
		}
		// sort notifications
		$matching_notifications = array(); // the notifications matching the type criterium
		$remaining_notifications = array(); // the other notifications that will remain on the stack
		foreach($this->_items as $item) {
			if ($item->getType() == $type) {
				$matching_notifications[] = $item;
			} else {
				$remaining_notifications[] = $item;
			}
		}
		// store remaining notifications
		$this->_items = $remaining_notifications;
		// return mathcing notifications
		return $matching_notifications;
		
	} //popType
	
	//+ 
	function popScope ( $scope )
	/*/
	Returns all Notifications with a given scope
	and removes them from the stack.
	---
	@param scope: the scope of the notifications to be returned
	@type  scope: string
	-
	@return: array of Notifications that match the given
	scope criterium
	@rtype:  array
	/*/
	{
		// sort notifications
		$matching_notifications = array(); // the notifications matching the type criterium
		$remaining_notifications = array(); // the other notifications that will remain on the stack
		foreach($this->_items as $item) {
			if ($item->getScope() == $scope) {
				$matching_notifications[] = $item;
			} else {
				$remaining_notifications[] = $item;
			}
		}
		// store remaining notifications
		$this->_items = $remaining_notifications;
		// return mathcing notifications
		return $matching_notifications;
		
	} //popScope
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>