<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Image
Framework File Signature: com.ph2.framework.php.entities.image
Description:
Image: A Medium with Type=IMG
---
/*/

//+
class Image
{
	// INSTANCE VARS
	// -------------
	protected $_id; /// The ID of the Image
	protected $_filepath; /// The filepath to the actual image file
	protected $_title; /// The title of the Image
	protected $_description; /// The description of the Image
	protected $_order; /// The ordering number of the Image
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_filepath , $title='' , $description=NULL , $order=0 )
	/*/
	An Image can be constructed with a filepath (a; new Image entity will be created) or an ID 
	of an existing Image (b; which will then be loaded).
	---
	@param id_or_filepath: See cases (a) and (b) above
	@type  id_or_filepath: int/string
	@param title: The (optinal) title for the new Image
	@type  title: string
	@param description: The (optinal) description for the new Image
	@type  description: string
	@param order: The (optinal) ordering number for the new Image
	@type  order: string
	/*/
	{
		if( is_int($id_or_filepath) ) {
			// load existing entity
			$this->_id = $id_or_filepath;
			$this->_loadFromDB();
		} else {
			// create new entity
			$this->_filepath = $id_or_filepath;
			$this->_title = $title;
			$this->_description = $description;
			$this->_order = $order;
			$this->_writeToDB();
		}
	
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: the ID of the Image
	@rtype:  string
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getTitle ( )
	/*/
	getter
	-
	@return: the title of the Image
	@rtype:  string
	/*/
	{
		return $this->_title;
	} //getTitle
	
	//+ 
	function getDescription ( )
	/*/
	getter
	-
	@return: the description of the Image
	@rtype:  string
	/*/
	{
		return $this->_description;
	} //getDescription
	
	//+ 
	function getOrder ( )
	/*/
	getter
	-
	@return: the ordering number of the Image
	@rtype:  string
	/*/
	{
		return $this->_order;
	} //getOrder
	
	//+ 
	function setTitle ( $title )
	/*/
	setter
	---
	@param title: the new title of the Image
	@type  title: string
	/*/
	{
		$this->_title = $title;
		$this->_writeToDB();
	} //setTitle
	
	//+ 
	function setDescription ( $description )
	/*/
	setter
	---
	@param description: the new description of the Image
	@type  description: string
	/*/
	{
		$this->_description = $description;
		$this->_writeToDB();
	} //setDescription
	
	//+ 
	function setOrder ( $order )
	/*/
	setter
	---
	@param order: the new ordering number of the Image
	@type  order: string
	/*/
	{
		$this->_order = $order;
		$this->_writeToDB();
	} //setOrder
	
	//+ 
	function delete ( )
	/// deletes this image entity, including all files and DB entries
	{
		// delete image from all meta tables (connectors)
		$dao = new Table('TEXT_MEDIUM');
		$dao->delete( array('MediumID' => $this->_id) );
		// delete image from MEDIUM table
		$dao = new Table('MEDIUM');
		$dao->delete( array('MediumID' => $this->_id) );
		// delete image file
		$filepath = PH2_FP_BASE . DIRECTORY_SEPARATOR . $this->_filepath;
		unlink( $filepath );
		// delete this object
		//unset($this);
		
	} //delete
	
	//+ 
	function linkToText ( $text_id )
	/*/
	links the image to a text (via TEXT_MEDIUM)
	---
	@param text_id: the text's ID
	@type  text_id: int
	/*/
	{
		$dao = new Table('TEXT_MEDIUM');
		$dao->insert( array('TextID' => $text_id, 'MediumID' => $this->_id) );
		
	} //linkToText
	
	//+ 
	function removeFromText ( $text_id )
	/*/
	removes the image from a text (via TEXT_MEDIUM)
	---
	@param text_id: the text's ID
	@type  text_id: int
	/*/
	{
		$dao = new Table('TEXT_MEDIUM');
		$dao->delete( array('TextID' => $text_id, 'MediumID' => $this->_id) );
	} //removeFromText
	
	//+ 
	function getAssignedTexts ( )
	/*/
	returns the IDs of all Texts this image is linked to (via TEXT_MEDIUM)
	-
	@return: an array of TextIDs this Image is linked to
	@rtype:  array(id)
	/*/
	{
		$dao = new Table('TEXT_MEDIUM');
		$rows = $dao->get( array('MediumID' => $this->_id) );
		$text_ids = array();
		foreach ($rows as $row) {
			$text_ids[] = $row['TextID'];
		}
		return $text_ids;
	} //getAssignedTexts
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on this Image from the database (by $this->_id) and writes it into 
	this object's instance variables.
	/*/
	{
		$dao = new Table('MEDIUM');
		$rows = $dao->get( array('MediumID' => $this->_id) );
		$fields = $rows[0];
		
		$this->_title = $fields['Title'];
		$this->_filepath = $fields['Filepath'];
		$this->_order = $fields['Order'];
		$this->_description = $fields['Descr'];
		
	
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on this Image from this instance into the database. If this Project 
	instance allready has an ID, the corresponding DB-entry is updated. Otherwise, a new entry 
	is created in the database and the new ID is stored in this objects _id variable.
	/*/
	{
		$dao = new Table('MEDIUM');
		$fields = array('Title' => $this->_title, 'Filepath' => $this->_filepath, 'Type' => 'IMG', 'Order' => $this->_order, 'Descr' => $this->_description);
		
		if ($this->_id) {
			// update existing entity
			$dao->where = array('MediumID' => $this->_id);
			$dao->update( $fields );
		} else {
			// create new entity
			$dao->insert( $fields );
			$this->_id = $dao->getLastID();
		}
	
	} //_writeToDB
	
	
}

?>