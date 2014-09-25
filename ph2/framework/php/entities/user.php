<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: User
Framework File Signature: com.ph2.framework.php.entities.user
Description:
Class for modeling PH2 users.
---
/*/

//+
class User
{
	// INSTANCE VARS
	// -------------
	private $_dbFields;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $meta )
	/*/
	---
	@param meta:  int: the UserID of an existing
	user. All DB information is fetched and and assigned to the
	object. array: the DB fields array containing values for a new
	user to be created. NOT NUdLL fields must be provided.
	@type  meta: int/array
	/*/
	{
		// db connection
		$tb_USER = new Table('sys_USER');
		
		if (is_array($meta)) {
			// create new user
			$tb_USER->insert($meta);
			$id = $tb_USER->getLastID();
			if ($id) {
				// save the UserID
				$this->_dbFields['UserID'] = $id;
				// save all other DB fields
				array_push($this->_dbFields, $meta);
			} else {
				// TODO ERR: insertion/user creation failed
			}
		} else {
			// select db entries for existing user
			$user = $tb_USER->get(array('UserID' => $meta));
			if ($user[0]) {
				$this->_dbFields = $user[0];
			} else {
				// TODO ERR: could not select user from db
			}
		}
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	accessor
	-
	@return: this user's
	ID
	@rtype:  string
	/*/
	{
		return $this->_dbFields['UserID'];
	} //getID
	
	//+ 
	function getNickname ( )
	/*/
	accessor
	-
	@return: this user's
	nickname
	@rtype:  string
	/*/
	{
		return $this->_dbFields['Nickname'];
	} //getNickname
	
	//+ 
	function getFullname ( )
	/*/
	accessor
	-
	@return: this user's
	full name
	@rtype:  string
	/*/
	{
		return $this->_dbFields['Fullname'];
	} //getFullname
	
	//+ 
	function getPrivilege ( )
	/*/
	accessor
	-
	@return: this
	user's privilege type
	@rtype:  string
	/*/
	{
		return $this->_dbFields['Privilege'];
	} //getPrivilege
	
	//+ 
	function getMail ( )
	/*/
	accessor
	-
	@return: this user's
	mail address
	@rtype:  string
	/*/
	{
		return $this->_dbFields['Mail'];
	} //getMail
	
	//+ 
	function checkPassword ( $password )
	/*/
	Checks whether @param password is this user's valid password
	---
	@param password: the password to be checked
	@type  password: string
	-
	@return: TRUE if the password is valid, FALSE otherwise
	@rtype:  bool
	/*/
	{
		return checkPassword($password, $this->_dbFields['Password'], $this->_dbFields['PasswordSalt']);
		
	} //checkPassword
	
	//+ 
	function setPassword ( $password )
	/*/
	Sets this User's password to @param password
	---
	@param password: The user's new password
	@type  password: string
	/*/
	{
		$password = encodePassword($password);
		$this->change( array( 'Password' => $password[0], 'PasswordSalt' => $password[1] ));
	} //setPassword
	
	//+ 
	function change ( $fields )
	/*/
	mutator Takes an array of field/value pairs and
	updates this instance and the coresponding db entry.
	---
	@param fields: the
	new user information to replace existing entries
	@type  fields: associative array (field/value-pairs)
	/*/
	{
		// this user's ID (overwrite incorrect data)
		$fields['UserID'] = $this->_dbFields['UserID'];
		// write changes into db
		$tb_USER = new Table('sys_USER');
		$tb_USER->where = array( 'UserID' => $fields['UserID']);
		$tb_USER->update($fields);
		// update this instance's fields
		foreach ($fields as $field => $value) {
			$this->_dbFields[$field] = $value;
		}
	} //change
	
	//+ 
	function delete ( )
	/*/
	Deletes this user from the database. This also deletes checkout-markings for checked-out 
	texts and/or corpora of this user.
	/*/
	{
		// Delete all entries from CHECKOUT/CHECKSUM
		$dao_CHECKSUM = new Table('CHECKSUM');
		$dao_CHECKSUM->delete( 'CheckoutIdentifier in (select Identifier from CHECKOUT where UserID=' . $this->_dbFields['UserID'] . ')' );
		$dao_CHECKOUT = new Table('CHECKOUT');
		$dao_CHECKOUT->delete( array('UserID' => $this->_dbFields['UserID']) );
		
		$dao = new Table('sys_USER');
		$dao->delete( array( 'UserID' => $this->_dbFields['UserID'] ));
		
	} //delete
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>