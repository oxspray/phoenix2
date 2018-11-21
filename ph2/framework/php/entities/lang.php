<?php

/* /
  Phoenix2
  Version 0.7 alpha, Build 11
  ===
  Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
  Code by: Samuel Laeubli, University of Zurich
  Contact: samuel.laeubli@uzh.ch
  ===
  Framework File Name: Lemma
  Framework File Signature: com.ph2.framework.php.entities.lemma
  Description:
  Class for handling Lemma objects
  ---
  / */

//+
class Lang {

	// INSTANCE VARS
	// -------------
	protected $_id; /// the ID of the Lang
	protected $_code; /// the unique code of the Lang
	protected $_name; /// the name (label) of the Lang
	protected $_description; /// the description of the Lang

	// CONSTRUCTOR
	// -----------
	//+ 

	function __construct($id_or_code, $name = NULL, $description = NULL) {
		/* /
		  @param id_or_code: the ID (existing) or unique code (new) of the Lang
		  @type  id_or_code: int/string
		  @param name: the name (label) of the new Lang
		  @type  name: string
		  @param description: the description of the new Lang
		  @type  description: string
		  / */
	
		if (is_int($id_or_code)) {
			// existing lang
			$this->_id = $id_or_code;
			$this->_loadFromDB();
		} else {
			// new Lang
			$this->_code = $id_or_code;
			$this->_name = $name;
			$this->_description = $description;

			// if a lang with this code already exists, load it instead of creating a new one
			$dao = new Table('LANG');
			$rows = $dao->get(array('Code' => $this->_code));
			if (count($rows) > 0) {
				$this->_id = (int) $rows[0]['LangID'];
				$this->_loadFromDB();
			} else {
				$this->_writeToDB();
			}
		}
	}

	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID()
	/* /
	  getter
	  -
	  @return: the ID of this Lang
	  @rtype:  int
	  / */ {
		return $this->_id;
	}
	//getID
	
	//+ 
	function getCode()
	/* /
	  getter
	  -
	  @return: the code of this Lang
	  @rtype:  string
	  / */ {
		return $this->_code;
	}
	//getCode
	
	//+ 
	function getName()
	/* /
	  getter
	  -
	  @return: the name (label) of this Lang
	  @rtype:  string
	  / */ {
		return $this->_name;
	}
	//getName
	//+
	function getDescription()
	/* /
	  getter
	  -
	  @return: the description of this Lang
	  @rtype:  string
	  / */ {
		return $this->_description;
	}
	//getDescription
	
	//+ 
	function setCode ( $code )
	/*/
	setter
	---
	@param code: the new code for this Lang
	@type  code: string
	/*/
	{
		$this->_code = $code;
		$this->_writeToDB();
	} //setCode
	
	//+ 
	function setName ( $name )
	/*/
	setter
	---
	@param name: the new name for this Lang
	@type  name: string
	/*/
	{
		$this->_name = $name;
		$this->_writeToDB();
	} //setName

    function setDescription ($description )
        /*/
        setter
        ---
        @param description: the new description for this Lang
        @type  description: string
        /*/
    {
        $this->_description = $description;
        $this->_writeToDB();
    } //setDescription
	
	//+ 
	function assignOccurrenceID ( $occurrence_id )
	/*/
	Assigns an Occurrence to this Lang, given the Occurrence's ID.
	---
	@param occurrence_id: the ID of the Occurrence to be assigned to this Lang
	@type  occurrence_id: int
	/*/
	{
		// delete existing assignments
		$dao = new Table('LANG_OCCURRENCE');

		$existing_lo = $dao->get(array( 'OccurrenceID' => $occurrence_id ));
		if ($existing_lo[0]['LangID'] == $this->_id) {
		    return; // $occurrence_id alread assigned to this lang; no change needed
        }

		$dao->delete( array( 'OccurrenceID' => $occurrence_id) );
		// write new assignment
		$result = $dao->insert( array( 'OccurrenceID' => $occurrence_id, 'LangID' => $this->getID() ) );
        if (is_string($result) && 0 === strpos($result, 'MYSQL ERROR')) {
            throw new Exception($result);
        }
		
	} //assignOccurrenceID
	
	//+ 
	function _loadFromDB ( )
	/*/
	selects all information on this Lang from the database (by $this->_id) and writes it into 
	this object's instance variables.
	/*/
	{
		$dao = new Table('LANG');
		
		$rows = $dao->get( array( 'LangID' => $this->_id ) );
		if (count($rows) > 0 ) {
			$this->_code = $rows[0]['Code'];
			$this->_name = $rows[0]['Name'];
			$this->_description = $rows[0]['Description'];
		} else {
			die("ERROR: There is no lang with LangID=" . $this->_id . ".");
		}
	} //_loadFromDB
	
	//+ 
	function _writeToDB ( )
	/*/
	writes all information on this Lang from this instance into the database. If an instance already exists with this id, the corresponding DB-entry is updated. Otherwise, a new entry 
	is created in the database and the new ID is stored in this objects _id variable.
	/*/
	{
		// prepare entry
		$row = array( 'Code' => $this->_code, 'Name' => $this->_name, 'Description' => $this->_description);
		// write to db
		$dao = new Table('Lang');
		if (empty($this->_id)) {
			// if this Occurrence does not have an ID yet, it is created on the database.
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// otherwise, update existing occurrence
			$dao->where = array('LangID' => $this->_id);
			$dao->update($row);
		}
	} //_writeToDB
	
}
