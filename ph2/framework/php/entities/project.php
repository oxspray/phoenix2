<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Project
Framework File Signature: com.ph2.framework.php.entities.project
Description:
Class for modelling Project represenations.
---
/*/

//+
class Project
{
	// INSTANCE VARS
	// -------------
	private $_id; /// the project's id
	private $_name; /// the project's name
	private $_descr; /// the project's description
	private $_created; /// the project's creation date
	private $_corpora; /*/
	the Corpus instances assigned to
	this Project
	/*/
	private $_n_corpus; /*/
	the number of Corpora assigned to
	this Project
	/*/
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_name , $description='' )
	/*/
	A Project can be constructed with an existing ID. In this
	case, it is loaded from the databse. Otherwise, a new Project with a
	given name is instantiated and written to the Database. The assigned
	corpora are not automatically retrieved from the database (into
	_corpora) for better performance. However, calling the method
	getAssignedCorpora() will store them in this instance (and return
	them).
	---
	@param id_or_name: the id of the
	PROJECT database table entry / the name of the new project to be
	created
	@type  id_or_name: int/string
	@param description: the description
	of the project. Only relevant if this object is newly created
	with a name in the first argument.
	@type  description: string
	/*/
	{
		// check if submitted argument is ID or Name
		assert(!empty($id_or_name));
		if (is_int($id_or_name)) {
			// case ID: existing project
			$this->_id = $id_or_name;
			$this->_loadFromDB();
		} else {
			// case name: new project
			assert(is_string($id_or_name));
			$this->_name = $id_or_name;
			$this->_descr = $description;
			$this->_created = now();
			$this->_writeToDB();
		}
		// default values
		$this->_corpora = array();
	
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: this Project's
	ID
	@rtype:  string
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getName ( )
	/*/
	getter
	-
	@return: this Project's
	name
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
	@return: this
	Project's Description
	@rtype:  string
	/*/
	{
		return $this->_descr;
	} //getDescription
	
	//+ 
	function getCreationDate ( )
	/*/
	getter
	-
	@return: this
	Project's creation date
	@rtype:  string
	/*/
	{
		return $this->_created;
	} //getCreationDate
	
	//+ 
	function setName ( $name )
	/*/
	setter
	---
	@param name: the
	new name of the Project
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
	@param description: the new description of the Project
	@type  description: string
	/*/
	{
		$this->_descr = $description;
		$this->_writeToDB();
	} //setDescription
	
	//+ 
	function _loadNumberOfCorpora ( )
	/*/
	loads the number of Corpora
	assigned to this Project and stores it into this Project's _n_corpus
	instance variable.
	/*/
	{
		$dao = new Table('CORPUS');
		$dao->select = 'count(*) as n_corpus';
		$rows = $dao->get( array('ProjectID' => $this->_id));
		$this->_n_corpus = $rows[0]['n_corpus'];
	} //_loadNumberOfCorpora
	
	//+ 
	function assignCorpus ( $corpus )
	/*/
	takes a Corpus instance or ID and adds it
	(as Corpus instance) to this Project. If the Corpus is allready
	assigned to another corpus, this assignement will be overwritten (as
	each Corpus must be assigned to exactly one Project).
	---
	@param corpus: the Corpus instance or
	ID
	@type  corpus: Corpus/int
	/*/
	{
		// check if submitted item is id or Corpus instance
		if (is_int($corpus)) {
			// id
			$id = (int) $corpus;
		} else {
			// instance
			assert(get_class($corpus) == 'Corpus');
			$id = (int) $corpus->getID();
		}
		// write changes into DB
		$dao = new Table('CORPUS');
		$dao->where = array('CorpusID' => $id);
		$dao->update( array('ProjectID' => $this->_id) );
	
	} //assignCorpus
	
	//+ 
	function getAssignedCorpora ( $as_resultset=FALSE )
	/*/
	returns the assigned Corpora
	instances of this Project.
	---
	@param as_resultset: if TRUE, the Corpora are returned row-wise to be
	passed to a ResultSetTransformer
	@type  as_resultset: bool
	-
	@return: the Corpus instances
	@rtype:  array(Corpus)
	/*/
	{
		// update (NOTE: only once, namely if emtpy)
		if (empty($this->_corpora)) {
			foreach ($this->_getAssignedCorporaIDs() as $corpus_id) {
				$this->_corpora[] = new Corpus($corpus_id);
			}
		}
		if ($as_resultset) {
			// return as result set
			$resultset = array();
			foreach ($this->_corpora as $corpus) {
				// prepare row
				$row = array();
				$row['ID'] = $corpus->getID();
				$row['Name'] = $corpus->getName();
				$row['Description'] = $corpus->getDescription();
				$row['# Texts'] = $corpus->getNumberOfTexts();
				// append row to resultset
				$resultset[] = $row;
			}
			return $resultset;
		} else {
			// return as array of Corpus objects
			return $this->_corpora;
		}
	} //getAssignedCorpora
	
	//+ 
	function getAssignedCorporaIDs ( )
	/*/
	returns the ID of all Corpora assigned to this Project.
	-
	@return: the Corpus IDs
	@rtype:  array(int)
	/*/
	{
		return $this->_getAssignedCorporaIDs();
	} //getAssignedCorporaIDs
	
	//+ 
	function getNumberOfCorpora ( )
	/*/
	returns the number of Corpora
	currently assigned to this Project
	-
	@return: the number of
	corpora currently assigned to this Project
	@rtype:  int
	/*/
	{
		// load number of corpora if not stored yet
		if (empty($this->_n_corpus)) {
			if (empty($this->_corpora)) {
				$this->_loadNumberOfCorpora();
			} else {
				$this->_n_corpus = count($this->_corpora);
			}
		}
		return $this->_n_corpus;
	} //getNumberOfCorpora
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _getAssignedCorporaIDs ( )
	/*/
	returns the ids
	of all corpora that are assigned to this Project by querying the
	CORPUS table in the database
	-
	@return: the ids af
	all assigned corpora
	@rtype:  array(int)
	/*/
	{
		// select corpora from database
		$dao = new Table('CORPUS');
		$dao->where = array('ProjectID' => $this->_id);
		$rows = $dao->get();
		// prepare result
		$assigned_corpus_ids = array();
		foreach ($rows as $row) {
			$assigned_corpus_ids[] = (int) $row['CorpusID'];
		}
		return $assigned_corpus_ids;
	} //_getAssignedCorporaIDs
	
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on
	this Project from the database (by $this->_id) and writes it into
	this object's instance variables.
	/*/
	{
		// retrieve information from DB
		$dao = new Table('PROJECT');
		$rows = $dao->get( array('ProjectID' => $this->_id) );
		$data = $rows[0];
		// write information to this instance
		$this->_name = $data['Name'];
		$this->_descr = $data['ProjectDescr'];
		$this->_created = $data['Created'];
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on
	this Project from this instance into the database. If this Project
	instance allready has an ID, the corresponding DB-entry is updated.
	Otherwise, a new entry is created in the database and the new ID is
	stored in this objects _id variable.
	/*/
	{
		// prepare dao and data
		$dao = new Table('PROJECT');
		$row = array('Name' => $this->_name, 'ProjectDescr' => $this->_descr, 'Created' => $this->_created);
		
		if (empty($this->_id)) {
			// new Project, create new DB entry
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// existing Project, update DB entry identified by $this->_id
			$dao->where = array('ProjectID' => $this->_id);
			$dao->update($row);
		}
	} //_writeToDB
	
	
}

?>