<?php
/*/
Phoenix2
Version 0.6 Alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Graphematics
Framework File Signature: com.ph2.framework.php.entities.graph
Description:
Classes for handling Graphematics (Objects for Annotation)
---
/*/

//+
class Graph
{
	// INSTANCE VARS
	// -------------
	protected $_id; /// The ID of the Grapheme
	protected $_name; /// The Name of the Grapheme
	protected $_description; /// The Description of the Grapheme
	protected $_comment; /// The Comment of the Grapheme
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_name , $descr=NULL , $comment=NULL )
	/*/
	---
	@param id_or_name: If a Graph is constructed with an ID, it is loaded from the DB. If a 
	Graph is constructed with a name (and optionally a description and/or a comment), it is 
	created in the DB. Note that the name field is unique among all Graph-entries in the DB.
	@type  id_or_name: int
	@param descr: the description to be written to the DB upon creation
	@type  descr: string
	@param comment: the comment to be written to the DB upon creation
	@type  comment: string
	/*/
	{
		global $ps;
		
		if (is_int($id_or_name)) {
			// when an ID is provided, load Graph from the DB
			$this->_id = $id_or_name;
			$this->_loadFromDB();
		} else {
			$dao = new Table('GRAPH');
			$this->_id = $dao->checkAdd( array('Name' => $id_or_name, 'Descr' => $descr, 'Comment' => $comment, 'ProjectID' => $ps->getActiveProject()) );
			$this->_loadFromDB();
		}
	
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getGraphgroups ( )
	/*/
	Returns all Graphgroups assigned to this Graph
	-
	@return: the Graphgroup objects
	@rtype:  array(Graphgroup)
	/*/
	{
		$assigned_graphgroups = array();
		
		$dao = new Table('GRAPHGROUP');
		$dao->orderby = 'Number ASC';
		$result = $dao->get( array('GraphID' => $this->_id) );
		foreach ($result as $graphgroup_row) {
			$assigned_graphgroups[] = new Graphgroup( (int)$graphgroup_row['GraphgroupID'] );
		}
		
		return $assigned_graphgroups;
	} //getGraphgroups
	
	//+ 
	function getOccurrenceIDs ( )
	/*/
	Returns the IDs of all Occurrences (transitively, via a Graphgroup) assigned to this Graph
	-
	@return: the IDs of the assigned Occurrences
	@rtype:  array(int)
	/*/
	{
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		$dao->select = "distinct OccurrenceID";
		$dao->from = "GRAPHGROUP natural join GRAPHGROUP_OCCURRENCE";
		$dao->where = "GraphID=" . $this->_id;
		$results = $dao->get();
		
		$result = array();
		foreach ($results as $row) {
			$result[] = (int)$row['OccurrenceID'];
		}
		
		return $result;
		
	} //getOccurrenceIDs
	
	//+ 
	function removeOccurrences ( $occurrenceIDs )
	/*/
	Removes the given Occurrences (transitively, via a Graphgroup) from this Graph
	---
	@param occurrenceIDs: the IDs of the Occurrences to be removed
	@type  occurrenceIDs: array(int)
	/*/
	{
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		$occ_list = expandArray($occurrenceIDs, ',');
		$where = "OccurrenceID in ($occ_list) and GraphgroupID in ( select GraphgroupID from GRAPHGROUP where GraphID=" . $this->_id . ")";
		$dao->delete($where);
	} //removeOccurrences
	
	//+ 
	function graphgroupExists ( $number )
	/*/
	Checks whether a subgroup with a given number exists for this Graph
	---
	@param number: the number of the subgroup to be checked
	@type  number: string
	-
	@return: True if the subgroup exists, False otherwise
	@rtype:  bool
	/*/
	{
		// assert(endsWith($number, '.'));
		
		// get all associated Graphgroups
		$dao = new Table('GRAPHGROUP');
		$result = $dao->get ( array('GraphID' => $this->_id, 'Number' => $number) );
		if ($result[0]) {
			return TRUE;
		} else {
			return FALSE;
		}
	} //graphgroupExists
	
	//+ 
	function addGraphgroup ( $number , $name=NULL )
	/*/
	Adds a Graphgroup to the DB and connects it to this Graph
	---
	@param number: the number of the Graphgroup to be created
	@type  number: string
	@param name: the name of the Graphgroup to be created
	@type  name: string
	-
	@return: the ID of the newly created Graphgroup
	@rtype:  int
	/*/
	{
		global $ps;
		assert(!$this->graphgroupExists($number));
		
		$dao = new Table('GRAPHGROUP');
		$new_graphgroup_id = (int) $dao->checkAdd( array ('GraphID' => $this->_id, 'Number' => $number, 'Name' => $name) );
		return $new_graphgroup_id;
	} //addGraphgroup
	
	//+ 
	function deleteGraphgroup ( $id )
	/*/
	Deletes a Graphgroup. Also removes all Occurrences that are assigned to the Graphgroup to 
	be deleted from the Graph.
	---
	@param id: the ID of the Graphgroup to be deleted
	@type  id: int
	/*/
	{
		// remove assigned occurrences from the graph
		$graphgroup = new Graphgroup($id);
		$occ_ids_to_be_deleted = $graphgroup->getAssignedOccurrenceIDs();
		if (!empty($occ_ids_to_be_deleted)) {
			$this->removeOccurrences($occ_ids_to_be_deleted);	
		}
		// delete the graphgroup
		$dao = new Table('GRAPHGROUP');
		$dao->delete("GraphgroupID=$id");
		
	} //deleteGraphgroup
	
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: this Graph's ID
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
	@return: this Graph's name
	@rtype:  string
	/*/
	{
		return $this->_name;
	} //getName
	
	//+ 
	function getDescription ( )
	/*/
	getter
	-
	@return: this Graph's description
	@rtype:  string
	/*/
	{
		return $this->_description;
	} //getDescription
	
	//+ 
	function getComment ( )
	/*/
	getter
	-
	@return: this Graph's comment
	@rtype:  string
	/*/
	{
		return $this->_comment;
	} //getComment
	
	//+ 
	function setName ( $name )
	/*/
	setter
	---
	@param name: the new name of the Graph
	@type  name: string
	/*/
	{
		$this->_name = $name;
		$this->_writeToDB();
	} //setName
	
	//+ 
	function setDescription ( $description )
	/*/
	setter
	---
	@param description: the new description of the Graph
	@type  description: string
	/*/
	{
		$this->_description = $description;
		$this->_writeToDB();
	} //setDescription
	
	//+ 
	function setComment ( $comment )
	/*/
	setter
	---
	@param comment: the new comment of the Graph
	@type  comment: string
	/*/
	{
		$this->_comment = $comment;
		$this->_writeToDB();
	} //setComment
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on
	this Graph from the database (by $this->_id) and writes it into this
	object's instance variables.
	/*/
	{
		$dao = new Table('GRAPH');
		$result = $dao->get( array('GraphID' => $this->_id) );
		if ($result[0]) {
			$data = $result[0];
			// fill in the data
			$this->_name = $data['Name'];
			$this->_description = $data['Descr'];
			$this->_comment = $data['Comment'];
		} else {
			return FALSE;
		}
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on
	this Graph from this instance into the database. If this Project
	instance allready has an ID, the corresponding DB-entry is updated.
	Otherwise, a new entry is created in the database and the new ID is
	stored in this objects _id variable.
	/*/
	{
		$dao = new Table('GRAPH');
		$dao->where = array('GraphID' => $this->_id);
		$dao->update( array('Name' => $this->_name, 'Descr' => $this->_description, 'Comment' => $this->_comment) );
	} //_writeToDB
	
	
}

?>