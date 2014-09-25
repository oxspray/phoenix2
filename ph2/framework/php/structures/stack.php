<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Stack
Framework File Signature: com.ph2.framework.php.structures.stack
Description:
A simple stack data structure.
---
/*/

//+
class Stack
{
	// INSTANCE VARS
	// -------------
	protected $_items;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( )
	/*/
	A stack is always constructed with an empty array of items
	($_item).
	/*/
	{
		$this->_items = array();
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function push ( $item )
	/*/
	Adds an element onto the stack.
	---
	@param item: The element to be pushed onto the
	stack.
	@type  item: mixed
	/*/
	{
		array_push($this->_items, $item);
	} //push
	
	//+ 
	function pop ( )
	/*/
	Pops the topmost item of the stack (removes it
	from the stack, then returns it).
	-
	@return: the topmost
	element from the stack
	@rtype:  mixed
	/*/
	{
		return array_pop($this->_items);
	} //pop
	
	//+ 
	function popAll ( )
	/*/
	Returns all elements from the Stack (removes
	them from the stack, then returns them, leaving an empty stack).
	-
	@return: The array containing all stack
	elements
	@rtype:  array
	/*/
	{
		$all_items = array();
		for ($i=0; $i <= count($this->_items); $i++) {
			$all_items[] = $this->pop();
		}
		return $all_items;
	} //popAll
	
	//+ 
	function reset ( )
	/*/
	Resets the stack by removing all its items
	without returning them.
	/*/
	{
		$this->_items = array();
	} //reset
	
	//+ 
	function isEmpty ( )
	/*/
	-
	@return: TRUE if there are no items on the stack, FALSE
	otherwise.
	@rtype:  bool
	/*/
	{
		return empty($this->_items);
	} //isEmpty
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>