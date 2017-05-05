<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Graphematic Subgroups
Framework File Signature: com.ph2.framework.php.entities.graphgroup
Description:
Classes for handling Graphematic Subgroups (Objects for Annotation)
---
/*/

//+
class Graphgroup
{
	// INSTANCE VARS
	// -------------
	protected $_id; /// The ID of the Graphgroup
	protected $_number; /// The Number of the Graphgroup (unique for each Graph)
	protected $_name; /// The Name of the Graphgroup
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_number , $name=NULL )
	/*/
	---
	@param id_or_number: If a Graphgroup is constructed with an ID, it is loaded from the DB. 
	If a Graphgroup is constructed with a name, it is created in the DB. Note that the name 
	field is unique among all Graph-entries in the DB.
	@type  id_or_number: int
	@param name: The (optinal) name for the new Graphgroup
	@type  name: string
	/*/
	{
		if (is_int($id_or_number)) {
			// when an ID is provided, load Graph from the DB
			$this->_id = $id_or_number;
			$this->_loadFromDB();
		} else {
			assert(endsWith($id_or_number, '.'));
			$dao = new Table('GRAPHGROUP');
			$this->_id = $dao->checkAdd( array('Number' => $id_or_number, 'Name' => $name) );
			$this->_loadFromDB();
		}
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function addOccurrence ( $occurrenceID , $deleteExistingAssignments=TRUE, $graphgroupsToOverwrite=NULL )
	/*/
	add an Occurrence to this Graphgroup
	---
	@param occurrenceID: the ID of the Occurrence to be added. Can be an array of IDs; in this 
	case, they are all added.
	@type  occurrenceID: int
	@param deleteExistingAssignments: By default, all existing assignments of an occurrence 
	are deleted before it is added to a new Graphgroup.
	@type  deleteExistingAssignments: bool
	@param graphgroupsToOverwrite: If an array of GraphgroupIDs is given, all GRAPHGROUP_OCCURRENCE entries
	with the given OccurrenceID and the corresponding GraphgroupID are deleted before it is added to a new Graphgroup.
	@type  graphgroupsToOverwrite: array
	/*/
	{
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		if (!is_array($occurrenceID)) {
			$occurrenceID = array ($occurrenceID);
		}
		foreach ($occurrenceID as $id) {
			if ($deleteExistingAssignments == TRUE) {
				// delete all existing assignments
				$dao->delete("OccurrenceID=$id");
			}
			if ($graphgroupsToOverwrite != null) {
				foreach ($graphgroupsToOverwrite as $gg_id) {
					$relevant_db_entry = array('GraphgroupID' => $gg_id, 'OccurrenceID' => $id);
					$dao->delete($relevant_db_entry);
				}
			}
			$dao->insert( array('GraphgroupID' => $this->_id, 'OccurrenceID' => $id) );
		}
	
	} //addOccurrence
	
	//+ 
	function removeOccurrence ( $occurrenceID )
	/*/
	remove an Occurrence from this Graphgroup
	---
	@param occurrenceID: the ID of the Occurrence to be removed. Can be an array of IDs; in 
	this case, they are all removed.
	@type  occurrenceID: int
	/*/
	{
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		if (!is_array($occurrenceID)) {
			$occurrenceID = array ($occurrenceID);
		}
		foreach ($occurrenceID as $id) {
			$dao->remove( array('GraphgroupID' => $this->_id, 'OccurrenceID' => $id) );
		}
	} //removeOccurrence
	
	//+ 
	function getAssignedOccurrenceIDs ( )
	/*/
	returns the IDs of all Occurrences assigned to this Graphgroup
	-
	@return: the IDs of all Occurrences assigned to this Graphgroup
	@rtype:  array(int)
	/*/
	{
		$assignedOccurrenceIDs = array();
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		$result = $dao->get('GraphgroupID=' . $this->getID());
		foreach ($result as $row) {
			$assignedOccurrenceIDs[] = $row['OccurrenceID'];
		}
		return $assignedOccurrenceIDs;
				
	} //getAssignedOccurrenceIDs
	
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: this Graphgroup's ID
	@rtype:  int
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getName ( )
	/*/
	getter
	-
	@return: this Graphgroup's name
	@rtype:  string
	/*/
	{
		return $this->_name;
	} //getName
	
	//+ 
	function getNumber ( )
	/*/
	getter
	-
	@return: this Graphgroup's number
	@rtype:  string
	/*/
	{
		return $this->_number;
	} //getNumber
	
	//+ 
	function setName ( $name )
	/*/
	setter
	---
	@param name: the new name of the Graphgroup
	@type  name: string
	/*/
	{
		$this->_name = $name;
		$this->_writeToDB();
	} //setName
	
	//+ 
	function setNumber ( $number )
	/*/
	setter
	---
	@param number: the new number of the Graphgroup
	@type  number: string
	/*/
	{
		$this->_number = $number;
		$this->_writeToDB();
	} //setNumber
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on
	this Graphgroup from the database (by $this->_id) and writes it into this
	object's instance variables.
	/*/
	{
		$dao = new Table('GRAPHGROUP');
		$result = $dao->get( array('GraphgroupID' => $this->_id) );
		$data = $result[0];
		// fill in the data
		$this->_number = $data['Number'];
		$this->_name = $data['Name'];
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on
	this Graphgroup from this instance into the database. If this Project
	instance allready has an ID, the corresponding DB-entry is updated.
	Otherwise, a new entry is created in the database and the new ID is
	stored in this objects _id variable.
	/*/
	{
		$dao = new Table('GRAPHGROUP');
		$dao->where = array('GraphgroupID' => $this->_id);
		$dao->update( array('Number' => $this->_number, 'Name' => $this->_name) );
	} //_writeToDB
	
	
}

?>