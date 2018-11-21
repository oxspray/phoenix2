<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Occurrence
Framework File Signature: com.ph2.framework.php.entities.occurrence
Description:
Class for modelling Occurrence representations.
---
/*/

//+
class Occurrence
{
	// INSTANCE VARS
	// -------------
	private $_id; /// this Occurrence's ID
	private $_token_id; /*/
	this Occurrence's Token ID (pointing to the actual textual surface, 
	i.e. its type)
	/*/
	private $_token_type; /// this Occurrence's Token type (occ or punct)
	private $_text_id; /// the ID of the Text this Occurrence is assigned to
	private $_order; /// the order number of this occurrence relative to its Text
	private $_div; /// the div this occurrence is assigned to
	private $_comment; /// a comment on this Occurrence (does not affect the xml!)
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id , $text_id=NULL , $order=NULL , $div=NULL , $comment=NULL , $textsection_ids=NULL )
	/*/
	a new constructor can be constructed with an ID or a (Token Surface, Token Type, Text ID, 
	Order [, Div assignment] [, array(TextsectionIDs)]. In the latter case, it is constructed 
	as a new Entity in the database. Otherwise, an existing Occurrence is loaded.
	---
	@param id: the Occurrence OR (!) tokenID; the latter case is assumed if the other 
	parameters are not NULL
	@type  id: int
	@param text_id: None
	@type  text_id: int
	@param order: None
	@type  order: int
	@param div: None
	@type  div: int
	@param comment: None
	@type  comment: string
	@param textsection_ids: None
	@type  textsection_ids: array(int)
	/*/
	{
		if ($id && $text_id && $order) {
			// new Occurrence
			$this->_token_id = $id;
			$this->_text_id = $text_id;
			$this->_order = $order;
			$this->_div = $div;
			$this->_comment = $comment;
			// write to db
			$this->_writeToDB();
			// add textsection entries to the database
			if ($textsection_ids) {
				$this->_addTextsectionID($textsection_ids);
			}
		} else if (is_int($id)) {
			// existing occurrence; load from db
			$this->_id = $id;
			$this->_loadFromDB();
		} else die("Error creating Occurrence object"); //TODO: clean
		
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	accessor
	-
	@return: the ID
	@rtype:  int
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getTokenID ( )
	/*/
	accessor
	-
	@return: the TokenID
	@rtype:  int
	/*/
	{
		return $this->_token_id;
	} //getTokenID
	
	//+ 
	function getTokenType ( )
	/*/
	accessor
	-
	@return: the Token's type
	@rtype:  string
	/*/
	{
		return $this->_token_type;
	} //getTokenType
	
	//+ 
	function getTextID ( )
	/*/
	accessor
	-
	@return: the TextID
	@rtype:  int
	/*/
	{
		return $this->_text_id;
	} //getTextID
	
	//+ 
	function getOrder ( )
	/*/
	accessor
	-
	@return: the Order number relative to the assigned Text
	@rtype:  int
	/*/
	{
		return $this->_order;
	} //getOrder
	
	//+ 
	function getDiv ( )
	/*/
	accessor
	-
	@return: the Div number
	@rtype:  int
	/*/
	{
		return $this->_div;
	} //getDiv
	
	//+ 
	function getTextsectionIDs ( )
	/*/
	accessor
	-
	@return: the IDs of the covering textsections
	@rtype:  array(int)
	/*/
	{
	} //getTextsectionIDs
	
	//+ 
	function getTextsectionNames ( )
	/*/
	returns the names of the Textsections this Occurrence is assigned to
	-
	@return: the names of the Textsections
	@rtype:  array(array(name, descr))
	/*/
	{
		$dao = new Table('TEXTSECTION');
		$dao->from = 'OCCURRENCE_TEXTSECTION natural join TEXTSECTION';
		$rows = $dao->get(array('OccurrenceID' => $this->_id));
		$textsection_names = array();
		foreach ($rows as $row) {
			$textsection_names[] = array($row['XMLTagName'], $row['Descr']);
		}
		return $textsection_names;
	} //getTextsectionNames
	
	//+ 
	function getComment ( )
	/*/
	accessor
	-
	@return: the comment
	@rtype:  string
	/*/
	{
		return $this->_comment;
	} //getComment
	
	//+ 
	function getLemmaID ( )
	/// Returns the ID of the Lemma this Occurrence is assigned to
	{
		$dao = new Table('LEMMA_OCCURRENCE');
		$rows = $dao->get( array( 'OccurrenceID' => $this->getID() ) );
		if (count($rows) > 0) {
			return (int)$rows[0]['LemmaID'];
		} else {
			return FALSE;
		}
	} //getLemmaID
	
	//+ 
	function getLemma ( )
	/// Returns the Lemma object that this Occurrence is assigned to
	{
		$lemma_id = $this->getLemmaID();
		if ($lemma_id) {
			return new Lemma($lemma_id);
		} else {
			return FALSE;
		}
	} //getLemma
	
	//+ 
	function getMorphAttributes ( )
	/*/
	returns all morphological category/value-pairs that are assigned to this Occurrence
	-
	@return: array( 'pos' => 'OUT', 'gen' = ... )
	@rtype:  array
	/*/
	{
		$attributes = array();
		
		$dao = new Table('OCCURRENCE_MORPHVALUE');
		$dao->select = "OccurrenceID, XMLTagName, Value";
		$dao->from = "OCCURRENCE_MORPHVALUE natural join MORPHVALUE join MORPHCATEGORY on MORPHVALUE.MorphcategoryID=MORPHCATEGORY.MorphcategoryID";
		$rows = $dao->get( array( 'OccurrenceID' => $this->getID() ) );
		
		foreach ($rows as $row) {
			$attributes[ $row['XMLTagName'] ] = $row['Value'];
		}
		
		return $attributes;
		
	} //getMorphAttributes
	
	//+ 
	function getLangID ( )
	/// Returns the ID of the Lang this Occurrence is assigned to
	{
		$dao = new Table('LANG_OCCURRENCE');
		$rows = $dao->get( array( 'OccurrenceID' => $this->getID() ) );
		if (count($rows) > 0) {
			return (int)$rows[0]['LangID'];
		} else {
			return FALSE;
		}
	} //getLangID
	
	//+ 
	function getLang ( )
	/// Returns the Lang object that this Occurrence is assigned to
	{
		$lang_id = $this->getLangID();
		if ($lang_id) {
			return new Lang($lang_id);
		} else {
			return FALSE;
		}
	} //getLang
	
	//+ 
	function setComment ( $comment )
	/*/
	setter
	---
	@param comment: the new comment on this Occurrence
	@type  comment: string
	/*/
	{
		$this->_comment = $comment;
		$this->_writeToDB();
	} //setComment
	
	//+ 
	function getSurface ( )
	/*/
	returns the actual Occurrence surface, i.e. its type
	-
	@return: the surface of the Occurrence
	@rtype:  string
	/*/
	{
		$dao = new Table('TOKEN');
		$row = $dao->get(array('TokenID' => $this->_token_id));
		return $row[0]['Surface'];
	} //getSurface
	
	//+ 
	function getType ( )
	/*/
	returns the type of the Occurrence (via Token), e.g. 'occ' or 'punct'
	-
	@return: the type of the Occurrence
	@rtype:  string
	/*/
	{
		$dao = new Table('TOKEN');
		$dao->from = 'TOKEN natural join TOKENTYPE';
		$row = $dao->get(array('TokenID' => $this->_token_id));
		return $row[0]['Name'];
	} //getType
	
	//+ 
	function getContext ( $ratio=100 )
	/*/
	returns a string of words occurring before and after this Occurrence. The result is 
	returned as an array (left/right).
	---
	@param ratio: specifies how many occurrences should be included in the text string on each 
	side
	@type  ratio: int
	-
	@return: the array containing (0) the left- and (1) right-hand-side context
	@rtype:  array(string left, string right)
	/*/
	{
		
		$dao = new Table('OCCURRENCE');
		$delimiter = ' ';
		
		// calculate borders
		// 0 <= $left_left_border < $left_right_border < $this->_order < $right_left_border < $right_right_border
		if ($this->_order >= $ratio) {
			$left_left_border = $this->_order - $ratio;
		} else {
			$left_left_border = 0;
		}
		$left_right_border = $this->_order;
		$right_left_border = $this->_order;
		$right_right_border = $this->_order + $ratio;
		
		$base_query  = "SELECT Surface FROM OCCURRENCE natural join TOKEN WHERE TextID=" . $this->_text_id;
		$base_query .= " AND ";
		
		// left context
		$left_occs = $dao->query($base_query . "`Order` > $left_left_border AND `Order` < $left_right_border ORDER BY `Order` ASC");
		$left_context = '';
		foreach ($left_occs as $row) {
			$left_context .= $row['Surface'] . $delimiter;
		}
		
		// right context
		$right_occs = $dao->query($base_query . "`Order` > $right_left_border AND `Order` < $right_right_border ORDER BY `Order` ASC");
		$right_context = '';
		foreach ($right_occs as $row) {
			$right_context .= $row['Surface'] . $delimiter;
		}
		
		return array($left_context, $right_context);
		
	} //getContext
	
	//+ 
	function getAssignedCorpusID ( )
	/*/
	returns the ID of the Corpus this Occurrence is assigned to (via TEXT)
	-
	@return: the ID of the Corpus this Occurrence is assigned to
	@rtype:  int
	/*/
	{
		$dao = new Table('TEXT');
		$row = $dao->get(array('TextID' => $this->_text_id));
		return $row[0]['CorpusID'];
	} //getAssignedCorpusID
	
	//+ 
	function setLemma ( $surface , $concept=NULL , $project_id=NULL )
	/*/
	Assigns this Occurrence to a Lemma. If the Lemma does not exist allready, it is created in 
	the Database. If the Occurrence is currently assigned to another lemma, the old 
	association is replaced by the new one.
	---
	@param surface: the surface string of the Lemma
	@type  surface: string
	@param concept: the short name of the Concept
	@type  concept: string
	@param project_id: the ID of the relevant Project
	@type  project_id: int
	/*/
	{
		$lemma = new Lemma( $surface, $concept, $project_id );
		$lemma->assignOccurrenceID( $this->getID() );		
	} //setLemma
	
	//+ 
	function setLemmaID ( $lemma_id )
	/*/
	Assigns this Occurrence to a Lemma, given the Lemma's ID. If the Occurrence is currently 
	assigned to another lemma, the old association is replaced by the new one.
	---
	@param lemma_id: the ID of the Lemma that this Occurrence should be assigned to.
	@type  lemma_id: int
	/*/
	{
		$lemma = new Lemma( (int)$lemma_id );
		$lemma->assignOccurrenceID( $this->getID() );
	} //setLemmaID
	
	//+ 
	function setMorphAttribute ( $category , $value )
	/*/
	Adds a morphological attirbute (category, value) to this Occurrence. If there is an 
	existing entry for the category given, this value will be overwritten.
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
				$dao_assignment = new Table('OCCURRENCE_MORPHVALUE');
				// delete existing entry (if exists)
				$dao_val = new Table('MORPHVALUE');
				$potential_existing_rows = $dao_val->get( array('MorphcategoryID' => $categories[$category]) );
				$potential_existing_morphvalue_ids = array();
				foreach ($potential_existing_rows as $id) {
					$potential_existing_morphvalue_ids[] = $id['MorphvalueID'];
				}
				$dao_assignment->delete( "MorphvalueID in (".expandArray($potential_existing_morphvalue_ids, ",").") and OccurrenceID=" . $this->getID() );
				// check if entry allready exists
				$dao_assignment->insert( array( 'OccurrenceID' => $this->getID(), 'MorphvalueID' => $values[$value] ) );
			} else {
				die("ERROR: $value is not a valid value for the morphological category '$category'."); #TODO
			}
		} else {
			die("ERROR: $category is no valid morphological category."); #TODO
		}
	} //setMorphAttribute
	
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
			$dao_assignment = new Table('OCCURRENCE_MORPHVALUE');
			$dao_assignment->delete( "MorphvalueID in (" . expandArray($values, ',') . ")" );
		} else {
			die("ERROR: $category is no valid morphological category."); #TODO
		}
	} //removeMorphAttribute
	
	//+ 
	function removeMorphAttributes ( )
	/// Removes all morphological attributes that are currently assigned to this Occurrence.
	{
		$dao = new Table('OCCURRENCE_MORPHVALUE');
		$dao->delete( array( 'OccurrenceID' => $this->getID() ) );
	} //removeMorphAttributes
	
	//+ 
	function removeLemma ( )
	/// De-assigns this Occurrence from the Lemma it is currently assigned to.
	{
		$dao = new Table('LEMMA_OCCURRENCE');
		$dao->delete( array( 'OccurrenceID' => $this->getID() ) );
	} //removeLemma
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _loadFromDB ( )
	/// loads an existing occurrence from the database
	{
		$dao = new Table('OCCURRENCE');
		$row = $dao->get(array('OccurrenceID' => $this->_id));
		$occ = $row[0];
		// store values in this object
		$this->_token_id = $occ['TokenID'];
		$this->_text_id = $occ['TextID'];
		$this->_order = $occ['Order'];
		$this->_div = $occ['Div'];
		$this->_comment = $occ['Comment'];
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/// writes this occurrence to the database
	{
		// prepare entry
		$row = array( 'TokenID' => $this->_token_id, 'TextID' => $this->_text_id, 'Order' => $this->_order, 'Div' => $this->_div, 'Comment' => $this->_comment);
		// write to db
		$dao = new Table('OCCURRENCE');
		if (empty($this->_id)) {
			// if this Occurrence does not have an ID yet, it is created on the database.
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// otherwise, update existing occurrence
			$dao->where = array('OccurrenceID' => $this->_id);
			$dao->insert($row);
		}
	} //_writeToDB
	
	//+ 
	private function _addTextsectionID ( $textsection_id )
	/*/
	connects this Occurrence to a Textsection by it's corresponding ID
	---
	@param textsection_id: None
	@type  textsection_id: int
	/*/
	{
		if (!is_array($textsection_id)) {
			$textsection_id = array($textsection_id);
		}
		$dao = new Table('OCCURRENCE_TEXTSECTION');
		foreach ($textsection_id as $ts_id) {
			$dao->insert( array('OccurrenceID' => $this->_id, 'TextsectionID' => $ts_id) );
		}
	} //_addTextsectionID
	
	
}

?>