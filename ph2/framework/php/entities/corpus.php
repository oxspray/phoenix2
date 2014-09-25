<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Corpus
Framework File Signature: com.ph2.framework.php.entities.corpus
Description:
Class for modelling Corpus representations.
---
/*/

//+
class Corpus
{
	// INSTANCE VARS
	// -------------
	private $_id; /// the Corpus id
	private $_project_id; /*/
	the ID of the Project this
	Corpus is assigned to
	/*/
	private $_name; /// the Corpus name
	private $_descr; /// the Corpus description
	private $_texts; /*/
	the Text instances assigned to this
	Corpus
	/*/
	private $_n_text; /*/
	the number of Text instances
	assigned to this Corpus
	/*/
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_name , $project_id=NULL , $description='' )
	/*/
	A Corpus can be constructed with an existing ID. In this
	case, it is loaded from the databse. Otherwise, a new Corpus with a
	given name is instantiated and written to the Database. The assigned
	Texts are not automatically retrieved from the database (into
	_texts) for better performance. However, calling the method
	getAssignedTexts() will store them in this instance (and return
	them).
	---
	@param id_or_name: the id of the
	CORPUS database table entry / the name of the new Corpus to be
	created
	@type  id_or_name: int/string
	@param project_id: the ID of the
	Project that this Corpus shall be assigned to
	@type  project_id: int
	@param description: the description
	of the Corpus. Only relevant if this object is newly created
	with a name in the first argument.
	@type  description: string
	/*/
	{
		// check if submitted argument is ID or Name
		assert(!empty($id_or_name));
		if (is_int($id_or_name)) {
			// case ID: existing corpus
			$this->_id = $id_or_name;
			$this->_loadFromDB();
		} else {
			// case name: new corpus
			assert(is_string($id_or_name) && !empty($project_id));
			$this->_project_id = $project_id;
			$this->_name = $id_or_name;
			$this->_descr = $description;
			$this->_writeToDB();
		}
		// default values
		$this->_texts = array();
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: this Corpus'
	ID
	@rtype:  string
	/*/
	{
		return $this->_id;
	} //getID
	
	//+ 
	function getProjectID ( )
	/*/
	getter
	-
	@return: the ID of
	this Corpus' Project assignment
	@rtype:  string
	/*/
	{
	} //getProjectID
	
	//+ 
	function getName ( )
	/*/
	getter
	-
	@return: this Corpus'
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
	Corpus' Description
	@rtype:  string
	/*/
	{
		return $this->_descr;
	} //getDescription
	
	//+ 
	function setName ( $name )
	/*/
	setter
	---
	@param name: the
	new name of the Corpus
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
	@param description: the new description of the Corpus
	@type  description: string
	/*/
	{
		$this->_descr = $description;
		$this->_writeToDB();
	} //setDescription
	
	//+ 
	function getXML ( $as_DOM=0 )
	/*/
	Returns the XML representation of the Corpus (STORAGE format).
	---
	@param as_DOM: if TRUE, the Corpus will be returned as a DOMDocument rather than a string
	@type  as_DOM: bool
	-
	@return: the Corpus XML
	@rtype:  string/DOMDocument
	/*/
	{
		$texts = $this->getAssignedTexts();
		
		$DOM = new DOMDocument();
		$doc->formatOutput = TRUE;
		$DOM->loadXML( '<?xml version="1.0" encoding="UTF-8"?><corpus xmlns="http://www.rose.uzh.ch/phoenix/schema/storage"/>' );
		
		$text_separator = "\n\n"; 
		
		foreach ($texts as $text) {
			$DOM->documentElement->appendChild( $DOM->createTextNode($text_separator) );
			$DOM->documentElement->appendChild( $DOM->importNode( $text->getXML(TRUE)->documentElement, TRUE ) );
		}
		
		$DOM->documentElement->appendChild( $DOM->createTextNode($text_separator) );
		
		if ($as_DOM) {
			return $DOM;
		} else {
			return $DOM->saveXML();
		}
		
	} //getXML
	
	//+ 
	function isCheckedOut ( )
	/*/
	getter
	-
	@return: whether the Corpus is currently checked out for editing
	@rtype:  bool
	/*/
	{
		$dao = new Table('CHECKOUT');
		$rows = $dao->get( "CorpusID=" . $this->getID() . " AND Checkin is NULL and IsInvalid=0" );
		return !empty($rows);
	} //isCheckedOut
	
	//+ 
	function assignText ( $text )
	/*/
	takes a Text instance or ID and adds it (as
	Text instance) to this Project. If the Text is allready assigned to
	another corpus, this assignement will be overwritten (as each Text
	must be assigned to exactly one Corpus).
	---
	@param text: the Corpus instance or ID
	@type  text: Text/int
	/*/
	{
		// check if submitted item is id or Text instance
		if (is_int($text)) {
			// id
			$id = $text;
		} else {
			// instance
			assert(get_class($text) == 'Text');
			$id = $text->getID();
		}
		// write changes into DB
		$dao = new Table('TEXT');
		$dao->where = array('TextID' => $id);
		$dao->update( array('CorpusID' => $this->_id) );
	} //assignText
	
	//+ 
	function getAssignedTexts ( $as_resultset=FALSE , $include_links=FALSE )
	/*/
	returns the assigned Text instances of this Project.
	---
	@param as_resultset: if TRUE, the Texts are returned row-wise to be passed to a 
	ResultSetTransformer
	@type  as_resultset: bool
	@param include_links: if TRUE, the name and description fields will be wrapped by links 
	pointing to the detail view of each text. Only applicable if the texts are returned as 
	resultest.
	@type  include_links: bool
	-
	@return: the Text instances
	@rtype:  array(Text)
	/*/
	{
		// update (NOTE: only once, namely if emtpy)
		if (empty($this->_texts)) {
			foreach ($this->_getAssignedTextIDs() as $text_id) {
				$this->_texts[] = new Text($text_id);
			}
		}
		
		if ($as_resultset) {
			// return as result set
			$resultset = array();
			foreach ($this->_texts as $text) {
				// prepare row
				$row = array();
				$row['ID'] = $text->getID();
				$row['Name'] = $text->getName();
				$row['Description'] = $text->getDescription();
				$row['# Occ.'] = '...'; //$text->getNumberOfOccurrences(); # currently left out for performance reasons
				$row['# Lem.'] = '...'; //$text->getNumberOfLemmata();     # currently left out for performance reasons
				// append links if applicable
				if ($include_links) {
					// prepare link
					if ($include_links) {
						$a_start = '<a href="' . getModal('view_text') . '&textID=' . $text->getID() . '" rel="facebox" class="viewtext" title="view this text">';
						$a_end = '</a>';
					}
					$row['Name'] = $a_start . $row['Name'] . $a_end;
					$row['Description'] = $a_start . $row['Description'] . $a_end;
				}
				// append row to resultset
				$resultset[] = $row;
			}
			return $resultset;
		} else {
			// return as array of Corpus objects
			return $this->_texts;
		}
	} //getAssignedTexts
	
	//+ 
	function getNumberOfTexts ( )
	/*/
	returns the number of Texts currently
	assigned to this Corpus
	-
	@return: the number of Texts
	currently assigned to this Corpus
	@rtype:  int
	/*/
	{
		// load number of corpora if not stored yet
		if (empty($this->_n_text)) {
			if (empty($this->_texts)) {
				$this->_loadNumberOfTexts();
			} else {
				$this->_n_text = count($this->_texts);
			}
		}
		return $this->_n_text;
	} //getNumberOfTexts
	
	//+ 
	function checkout ( $user_id=NULL )
	/*/
	Checks out a whole corpus.
	1.) the checkout()-method is applied to all Texts assigned to this Corpus
	2.) the DOM-Node representations of the assigned Texts are combined into a Corpus-Document 
	(DOM-Node) which satisfies the PH2 EDIT_CORPUS XSD
	3.) the Corpus is marked as checked out on the system
	4.) the Corpus DOM-Node is returned
	---
	@param user_id: the ID of the user who checks out the corpus
	@type  user_id: int
	-
	@return: the DOM-Node of the whole corpus ready for editing
	@rtype:  DOMNode
	/*/
	{
		// checkout all assigned Texts and gather them in a new DOMDocument
		$texts = $this->getAssignedTexts();
		
		$DOM = new DOMDocument();
		$doc->formatOutput = TRUE;
		$DOM->loadXML( '<?xml version="1.0" encoding="UTF-8"?><corpus xmlns="http://www.rose.uzh.ch/phoenix/schema/edit"/>' );
		
		$text_separator = "\n\n"; 
		
		foreach ($texts as $text) {
			$DOM->documentElement->appendChild( $DOM->createTextNode($text_separator) );
			$DOM->documentElement->appendChild( $DOM->importNode( $text->checkout( $user_id )->documentElement, TRUE ) );
		}
		
		$DOM->documentElement->appendChild( $DOM->createTextNode($text_separator) );
		
		// mark the Corpus as checked-out in the DB
		$identifier = checkoutTextOrCorpus('corpus', $this->getID(), NULL, $user_id);
		// add checkout identifier to XML
		$root_element = $DOM->getElementsByTagName('corpus')->item(0);
		$root_element->setAttribute('checkout_id', $identifier);
		
		return $DOM;
		
	} //checkout
	
	//+ 
	function checkin ( $edited_corpus )
	/*/
	Checks if the submitted EDIT-XML contains all Texts that have been exportet. If so, the 
	CHECKOUT mark is removed for this Corpus. Note that the texts contained in the EDIT-XML 
	are NOT checked-in; they need to be checked-in via a TEXT object.
	---
	@param edited_corpus: The Corpus to be imported. If a strign is provided, it is converted 
	to a DOMNode (valid EDIT XML assumed).
	@type  edited_corpus: string/DOMNode
	/*/
	{
		
		$error = '';
		
		// convert string to DOMDocument if necessary
		if (is_string($edited_corpus)) {
			$dom = new DOMDocument();
			$dom->loadXML($edited_corpus);
			$edited_corpus = $dom;
			unset($dom);
		}
		
		// get all checked-out texts that are associated with this corpus
		$associated_text_checkout_ids = array();
		$dao = new Table('CHECKOUT');
		$dao->from = "CHECKOUT join CHECKSUM on CHECKOUT.Identifier = CHECKSUM.CheckoutIdentifier join TEXT on CHECKOUT.TextID=TEXT.TextID";
		$dao->where = "Checkin is NULL and IsInvalid=0 and TEXT.CorpusID=" . $this->getID();
		foreach ($dao->get() as $row) {
			$associated_text_checkout_ids[] = $row['Identifier'];
		}
		
		// get all checkout_ids that have been submitted in the edited Corpus XML
		$text_elements = $edited_corpus->getElementsByTagName('gl');
		$present_text_checkout_ids = array();
		foreach ($text_elements as $text_element) {
			$present_text_checkout_ids[] = $text_element->getAttribute('checkout_id');
		}
		
		// check if all of these checked-out texts, i.e., their checkout_id, are present in the edited Corpus XML
		if (count($associated_text_checkout_ids) != count($present_text_checkout_ids)) {
			$error .= "The number of texts has been changed. Texts cannot be added to or deleted from a corpus externally.";
		}
		
		// check if Texts any are missing
		$missing_text_checkout_ids = array();
		foreach ($associated_text_checkout_ids as $associated_text_checkout_id) {
			if( ! in_array($associated_text_checkout_id, $present_text_checkout_ids) ) {
				$missing_text_checkout_ids[] = $associated_text_checkout_id;
			}
		}
		
		if (count($missing_text_checkout_ids) > 0) {
			$dao = new Table('CHECKOUT');
			$dao->select = "CiteID";
			$dao->from = "CHECKOUT join TEXT on CHECKOUT.TextID = TEXT.TextID";
			$dao->where = "Identifier in ('" . expandArray($missing_text_checkout_ids, "','", "')");
			$error .= "\nThe following texts are missing: ";
			foreach ($dao->get() as $row) {
				$error .= $row['CiteID'] . ', ';
			}
			$error = rtrim($error,", ");
		}
		
		if ($error) {
			return $error;
		} else {
			checkinTextOrCorpus($edited_corpus->documentElement->getAttribute('checkout_id'));
			return TRUE;
		}		
		
	} //checkin
	
	//+ 
	function delete ( )
	/*/
	Removes this corpus from the database. All assigned texts (and thus occurrences, etc.) are 
	also deleted.
	/*/
	{
		// delete assigned texts
		foreach ($this->getAssignedTexts() as $text) {
			$text->delete();
		}
		// delete corpus
		$tb_CORPUS = new table('CORPUS');
		$tb_CORPUS->delete( array( 'CorpusID' => $this->_id ) );
		
		unset($this);
		
	} //delete
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _getAssignedTextIDs ( )
	/*/
	returns the ids of
	all Texts that are assigned to this Project by querying the TEXT
	table in the database
	-
	@return: the ids af all
	assigned corpora
	@rtype:  array(int)
	/*/
	{
		// select texts from database
		$dao = new Table('TEXT');
		$dao->where = array('CorpusID' => $this->_id);
		$rows = $dao->get();
		// prepare result
		$assigned_text_ids = array();
		foreach ($rows as $row) {
			$assigned_text_ids[] = (int) $row['TextID'];
		}
		return $assigned_text_ids;
	} //_getAssignedTextIDs
	
	//+ 
	private function _loadNumberOfTexts ( )
	/*/
	loads the number of
	Texts assigned to this Corpus and stores it into this Corpus'
	_n_text instance variable.
	/*/
	{
		$dao = new Table('TEXT');
		$dao->select = 'count(*) as n_text';
		$rows = $dao->get( array('CorpusID' => $this->_id));
		$this->_n_text = $rows[0]['n_text'];
	} //_loadNumberOfTexts
	
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on
	this Corpus from the database (by $this->_id) and writes it into
	this object's instance variables.
	/*/
	{
		// retrieve information from DB
		$dao = new Table('CORPUS');
		$rows = $dao->get( array('CorpusID' => $this->_id) );
		$data = $rows[0];
		// write information to this instance
		$this->_project_id = $data['ProjectID'];
		$this->_name = $data['Name'];
		$this->_descr = $data['CorpusDescr'];
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on
	this Corpus from this instance into the database. If this Corpus
	instance allready has an ID, the corresponding DB-entry is updated.
	Otherwise, a new entry is created in the database and the new ID is
	stored in this objects _id variable.
	/*/
	{
		// prepare dao and data
		$dao = new Table('CORPUS');
		$row = array('ProjectID' => $this->_project_id, 'Name' => $this->_name, 'CorpusDescr' => $this->_descr);
		
		if (empty($this->_id)) {
			// new Project, create new DB entry
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// existing Project, update DB entry identified by $this->_id
			$dao->where = array('CorpusID' => $this->_id);
			$dao->update($row);
		}
	} //_writeToDB
	
	
}

?>