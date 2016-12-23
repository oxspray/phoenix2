<?php
/*/
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
/*/

//+
class Lemma
{
	// INSTANCE VARS
	// -------------
	protected $_id; /// the ID of the Lemma
	protected $_project_id; /// the ID of the project this Lemma is assigned to
	protected $_identifier; /// the identifier of this Lemma
	protected $_mainLemmaIdentifier; /// the main lemma
	protected $_lemmastring_id; /// the ID of the Lemma's surface
	protected $_lemmastring; /// the string (surface) of the Lemma
	protected $_concept_id; /// the ID of the Lemma's Concept
	protected $_concept_short; /// the short name of this Lemma's concept
	protected $_is_lexicon_relevant; /// whether the Lemma is relevant for the lexicon

	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_identifier , $concept_short=NULL , $project_id=NULL , $surface=NULL , $morph_params=NULL,
                            $mainLemmaIdentifier=NULL)
	/*/
	A Lemma can be constructed either with an ID (=load an existing Lemma) or with an 
	identifier and a Concept (=create new Lemma on the Database). If a Lemma with the given 
	(identifier/concept/project_id/mainLemmaIdentifier) already exists, it is loaded from the DB rather than
	re-created.
	---
	@param id_or_identifier: the ID (existing) or name (new) of the Lemma
	@type  id_or_identifier: int/string
	@param concept_short: the short name of the new Lemma
	@type  concept_short: string
	@param project_id: the Project that the new Lemma should be assigned to
	@type  project_id: int
	@param surface: the surface of the new Lemma
	@type  surface: string
	@param morph_params: an array with morphological categories (key) and values (value)
	@type  morph_params: array
	/*/
	{
		if (is_int($id_or_identifier)) {
			// existing lemma
			$this->_id = $id_or_identifier;
			$this->_loadFromDB();
		} else {
			// new lemma
			if ($project_id) {
				$this->_project_id = $project_id;
			} else {
				global $ps;
				$this->_project_id = $ps->getActiveProject();
			}
			$this->_identifier = $id_or_identifier;
            $this->_mainLemmaIdentifier = $mainLemmaIdentifier;
			$this->_is_lexicon_relevant = 1;
			// check if the submitted concept is valid
			$dao_concept = new Table('CONCEPT');
			$rows = $dao_concept->get( array( 'Short' => $concept_short ) );
			if (count($rows) > 0) {
				$this->_concept_id = $rows[0]['ConceptID'];
				$this->_concept_short = $concept_short;
				// if a lemma with this (identifier/concept/project_id/mainLemmaIdentifier) already exists,
                // load it instead of creating a new one
				$dao_lemma = new Table('LEMMA');
				$rows = $dao_lemma->get( array( 'ProjectID' => $this->_project_id,
                    'LemmaIdentifier' => $this->_identifier, 'ConceptID' => $this->_concept_id,
                    'MainLemmaIdentifier' => $this->_mainLemmaIdentifier) );
				if (count($rows) > 0) {
					$this->_id = (int)$rows[0]['LemmaID'];
					$this->_loadFromDB();
				} else {
					$this->_writeToDB();
				}
				// write surface if provided
				if ($surface) {
					$this->setSurface($surface);
				}
				// add morphological annotations if provided
				if ($morph_params) {
					foreach ($morph_params as $morph_cat => $morph_val) {
						$this->setMorphAttribute($morph_cat, $morph_val);
					}
				}
			} else {
				die("ERROR constructing lemma $id_or_identifier: $concept_short is not a valid concept");
			}
		}
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: the ID of this Lemma
	@rtype:  int
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getProjectID ( )
	/*/
	getter
	-
	@return: the ID of the Project this Lemma is assigned to
	@rtype:  int
	/*/
	{
		return $this->_project_id;
	} //getProjectID
	
	//+ 
	function getIdentifier ( )
	/*/
	getter
	-
	@return: the identifier of this Lemma
	@rtype:  string
	/*/
	{
		return $this->_identifier;
	} //getIdentifier

    //+
    function getMainLemmaIdentifier ( )
        /*/
        getter
        -
        @return: the main lemma identifier of this Lemma
        @rtype:  string
        /*/
    {
        return $this->_mainLemmaIdentifier;
    } //getMainLemmaIdentifier


    //+
	function getLemmaString ( )
	/*/
	getter
	-
	@return: the surface of this Lemma
	@rtype:  string
	/*/
	{
		return $this->_lemmastring;
	} //getLemmaString
	
	//+ 
	function getSurface ( )
	/*/
	convenience function for getLemmaString()
	-
	@return: the surface of this Lemma
	@rtype:  string
	/*/
	{
		return $this->getLemmaString();
	} //getSurface
	
	//+ 
	function getLemmaStringID ( )
	/*/
	getter
	-
	@return: the ID of this Lemma's surface
	@rtype:  string
	/*/
	{
		return $this->_lemmastring_id;
	} //getLemmaStringID
	
	//+ 
	function getConceptShort ( )
	/*/
	getter
	-
	@return: the short form of this Lemma's Concept, e.g., 'c'
	@rtype:  string
	/*/
	{
		return $this->_concept_short;
	} //getConceptShort
	
	//+ 
	function getConcept ( )
	/*/
	convenience function for getConceptShort()
	-
	@return: the short form of this Lemma's Concept, e.g., 'c'
	@rtype:  string
	/*/
	{
		return $this->getConceptShort();
	} //getConcept
	
	//+ 
	function getConceptID ( )
	/*/
	getter
	-
	@return: the ID of this Lemma's Concept
	@rtype:  int
	/*/
	{
		return $this->_concept_id;
	} //getConceptID
	
	//+ 
	function isLexiconRelevant ( )
	/*/
	getter
	-
	@return: whether this Lemma is relevant for the lexicon
	@rtype:  bool
	/*/
	{
		return (bool)$this->_is_lexicon_relevant;
	} //isLexiconRelevant
	
	//+ 
	function getMorphAttributes ( )
	/*/
	returns all morphological category/value-pairs that are assigned to this Lemma
	-
	@return: array( 'gen' => 'f', 'num' = ... )
	@rtype:  array
	/*/
	{
		$attributes = array();
		
		$dao = new Table('LEMMA_MORPHVALUE');
		$dao->select = "LemmaID, XMLTagName, Value";
		$dao->from = "LEMMA_MORPHVALUE natural join MORPHVALUE join MORPHCATEGORY on MORPHVALUE.MorphcategoryID=MORPHCATEGORY.MorphcategoryID";
		$rows = $dao->get( array( 'LemmaID' => $this->getID() ) );
		
		foreach ($rows as $row) {
			$attributes[ $row['XMLTagName'] ] = $row['Value'];
		}
		
		return $attributes;
	} //getMorphAttributes
	
	//+ 
	function setProjectID ( $project_id )
	/*/
	setter
	---
	@param project_id: the new project ID
	@type  project_id: int
	/*/
	{
		$this->_project_id = $project_id;
		$this->_writeToDB();
	} //setProjectID
	
	//+ 
	function setIdentifier ( $identifier )
	/*/
	setter
	---
	@param identifier: the new identifier for this Lemma
	@type  identifier: string
	/*/
	{
		$this->_identifier = $identifier;
		$this->_writeToDB();
	} //setIdentifier

    function setMainLemmaIdentifier ($mainLemmaIdentifier )
        /*/
        setter
        ---
        @param mainLemmaIdentifier: the new mainLemmaIdentifier for this Lemma
        @type  mainLemmaIdentifier: string
        /*/
    {
        $this->_mainLemmaIdentifier = $mainLemmaIdentifier;
        $this->_writeToDB();
    } //setMainLemmaIdentifier
	
	//+ 
	function setSurface ( $surface_string )
	/*/
	updates the surface of this Lemma
	---
	@param surface_string: the new surface of the Lemma
	@type  surface_string: string
	/*/
	{
		$dao = new Table('LEMMASTRING');
		$this->_lemmastring_id = $dao->checkAdd( array('Surface' => $surface_string) );
		$this->_lemmastring = $surface_string;
		$this->_writeToDB();
	} //setSurface
	
	//+ 
	function setConcept ( $concept_short )
	/*/
	updates the Concept of this Lemma
	---
	@param concept_short: the short name of the Lemma's new Concept
	@type  concept_short: string
	/*/
	{
		// check if the submitted concept is valid
		$dao_concept = new Table('CONCEPT');
		$rows = $dao_concept->get( array( 'Short' => $concept_short ) );
		if (count($rows) > 0) {
			$this->_concept_id = $rows[0]['ConceptID'];
			$this->_concept_short = $concept_short;
			$this->_writeToDB();
		} else {
			die("ERROR updating lemma " . $this->getID() . ": $concept is not a valid concept");
		}
	} //setConcept
	
	//+ 
	function setMorphAttribute ( $category , $value )
	/*/
	Adds a morphological attirbute (category, value) to this Lemma. If there is an existing 
	entry for the category given, this value will be overwritten.
	---
	@param category: the category of the new morphological annotation
	@type  category: string
	@param value: the value of the new morphological annotation
	@type  value: string
	/*/
	{
		// get all morphological categories that are available in the system (DB)
		$dao_cat = new Table('MORPHCATEGORY');
		$rows = $dao_cat->get();
		$categories = array();
		foreach ($rows as $row) {
			$categories[ $row['XMLTagName'] ] = $row['MorphcategoryID'];
		}
		// check if $category is valid (= an existing morphological category) according to the DB
		if (in_array($category, array_keys($categories))) {
			// get all values that are available for $category
			$dao_cat = new Table('MORPHVALUE');
			$rows = $dao_cat->get( array( 'MorphcategoryID' => $categories[$category] ) );
			$values = array();
			foreach ($rows as $row) {
				$values[ $row['Value'] ] = $row['MorphvalueID'];
			}
			// check if $value is valid for $category
			if (in_array($value, array_keys($values))) {
				// write the entry
				$dao_assignment = new Table('LEMMA_MORPHVALUE');
				// delete existing entry (if exists)
				$dao_val = new Table('MORPHVALUE');
				$potential_existing_rows = $dao_val->get( array('MorphcategoryID' => $categories[$category]) );
				$potential_existing_morphvalue_ids = array();
				foreach ($potential_existing_rows as $id) {
					$potential_existing_morphvalue_ids[] = $id['MorphvalueID'];
				}
				$dao_assignment->delete( "MorphvalueID in (".expandArray($potential_existing_morphvalue_ids, ",").") and LemmaID=" . $this->getID() );
				// check if entry allready exists
				$dao_assignment->insert( array( 'LemmaID' => $this->getID(), 'MorphvalueID' => $values[$value] ) );
			} else {
				die("ERROR: $value is not a valid value for the morphological category '$category'."); #TODO
			}
		} else {
			die("ERROR: $category is no valid morphological category."); #TODO
		}
	} //setMorphAttribute
	
	//+ 
	function setIsLexiconRelevant ( $is_relevant )
	/*/
	setter
	---
	@param is_relevant: whether this Lemma is relevant for the lexicon or not
	@type  is_relevant: bool
	/*/
	{
		if ($is_relevant) {
			$this->_is_lexicon_relevant = 1;
		} else {
			$this->_is_lexicon_relevant = 0;
		}
	} //setIsLexiconRelevant
	
	//+ 
	function assignOccurrenceID ( $occurrence_id )
	/*/
	Assigns an Occurrence to this Lemma, given the Occurrence's ID.
	---
	@param occurrence_id: the ID of the Occurrence to be assigned to this Lemma
	@type  occurrence_id: int
	/*/
	{
		// delete existing assignments
		$dao = new Table('LEMMA_OCCURRENCE');
		$dao->delete( array( 'OccurrenceID' => $occurrence_id ) );
		// write new assignment
		$result = $dao->insert( array( 'OccurrenceID' => $occurrence_id, 'LemmaID' => $this->getID() ) );
        if (is_string($result) && 0 === strpos($result, 'MYSQL ERROR')) {
            throw new Exception($result);
        }
		
	} //assignOccurrenceID
	
	//+ 
	function removeMorphAttribute ( $category )
	/*/
	Removes a morphological attribute with a given category.
	---
	@param category: the category of the morphological attribute to be removed
	@type  category: string
	/*/
	{
		// get all morphological categories that are available in the system (DB)
		$dao_cat = new Table('MORPHCATEGORY');
		$rows = $dao_cat->get();
		$categories = array();
		foreach ($rows as $row) {
			$categories[ $row['XMLTagName'] ] = $row['MorphcategoryID'];
		}
		// check if $category is valid (= an existing morphological category) according to the DB
		if (in_array($category, array_keys($categories))) {
			// get all values that are available for $category
			$dao_cat = new Table('MORPHVALUE');
			$rows = $dao_cat->get( array( 'MorphcategoryID' => $categories[$category] ) );
			$values = array();
			foreach ($rows as $row) {
				$values[ $row['Value'] ] = $row['MorphvalueID'];
			}
			// remove entries
			$dao_assignment = new Table('LEMMA_MORPHVALUE');
			$dao_assignment->delete( "MorphvalueID in (" . expandArray($values, ',') . ")" );
		} else {
			die("ERROR: $category is no valid morphological category."); #TODO
		}
	} //removeMorphAttribute
	
	//+ 
	function removeMorphAttributes ( )
	/// Removes all morphological attributes that are currently assigned to this Lemma.
	{
		$dao = new Table('LEMMA_MORPHVALUE');
		$dao->delete( array( 'LemmaID' => $this->getID() ) );
	} //removeMorphAttributes
	
	//+ 
	function _loadFromDB ( )
	/*/
	selects all information on this Lemma from the database (by $this->_id) and writes it into 
	this object's instance variables.
	/*/
	{
		$dao = new Table('LEMMA');
		$dao->from = "LEMMA left join LEMMASTRING on LEMMA.LemmastringID=LEMMASTRING.LemmastringID join CONCEPT on LEMMA.ConceptID=CONCEPT.ConceptID";
		$rows = $dao->get( array( 'LemmaID' => $this->_id ) );
		if (count($rows) > 0 ) {
			$this->_project_id = $rows[0]['ProjectID'];
			$this->_identifier = $rows[0]['LemmaIdentifier'];
			$this->_mainLemmaIdentifier = $rows[0]['MainLemmaIdentifier'];
			$this->_lemmastring_id = $rows[0]['LemmastringID'];
			$this->_lemmastring = $rows[0]['Surface'];
			$this->_concept_id = $rows[0]['ConceptID'];
			$this->_concept_short = $rows[0]['Short'];
			$this->_is_lexicon_relevant = $rows[0]['isLexiconRelevant'];
		} else {
			die("ERROR: There is no lemma with LemmaID=" . $this->_id . ".");
		}
	} //_loadFromDB
	
	//+ 
	function _writeToDB ( )
	/*/
	writes all information on this Lemma from this instance into the database. If this Project 
	instance allready has an ID, the corresponding DB-entry is updated. Otherwise, a new entry 
	is created in the database and the new ID is stored in this objects _id variable.
	/*/
	{
		// prepare entry
		$row = array( 'ProjectID' => $this->_project_id, 'LemmastringID' => $this->_lemmastring_id,
            'LemmaIdentifier' => $this->_identifier, 'ConceptID' => $this->_concept_id,
            'isLexiconRelevant' => $this->_is_lexicon_relevant, 'MainLemmaIdentifier' => $this->_mainLemmaIdentifier);
		// write to db
		$dao = new Table('LEMMA');
		if (empty($this->_id)) {
			// if this Occurrence does not have an ID yet, it is created on the database.
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// otherwise, update existing occurrence
			$dao->where = array('LemmaID' => $this->_id);
			$dao->update($row);
		}
	} //_writeToDB
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>