<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Text
Framework File Signature: com.ph2.framework.php.entities.text
Description:
Class for modelling Text representations.
---
/*/

//+
class Text
{
	// INSTANCE VARS
	// -------------
	private $_id;
	private $_corpus_id;
	private $_cite_id;
	private $_filepath;
	private $_order_number;
	private $_text_descriptors;
	private $_n_occurrence;
	private $_n_lemma;
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $id_or_citeid=NULL , $corpus_id=NULL , $description=NULL )
	/*/
	A Text can be constructed with an ID. In this case, it is loaded from the database. If it 
	is constructed with a CiteID and a CorpusID, it is created in the database.
	---
	@param id_or_citeid: the id of the TEXT database table entry / the CiteID for the new text 
	in the database
	@type  id_or_citeid: int/string
	@param corpus_id: the corpusID the new text should be assigned to
	@type  corpus_id: int
	@param description: the optional description for the text (only affects system/database, 
	not xml)
	@type  description: string
	/*/
	{
		if ($id_or_citeid && $corpus_id) {
			// new text: create from submitted xml
			$this->_cite_id = $id_or_citeid;
			$this->_corpus_id = $corpus_id;
			$this->_writeToDB();
		} else {
			// existing text: load information from database
			$this->_id = $id_or_citeid;
			$this->_loadFromDB();
		} // else do nothing; an empty instance is created
		
		
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getID ( )
	/*/
	getter
	-
	@return: this Text's
	ID
	@rtype:  int
	/*/
	{
		return (int) $this->_id;
	} //getID
	
	//+ 
	function getCorpusID ( )
	/*/
	getter
	-
	@return: the ID of the
	Corpus that this Text is assigned to
	@rtype:  int
	/*/
	{
		return (int) $this->_corpus_id;
	} //getCorpusID
	
	//+ 
	function getFilepath ( )
	/*/
	getter
	-
	@return: the path on
	the filesystem to this Text's xml representation
	@rtype:  string
	/*/
	{
		return $this->_filepath;
	} //getFilepath
	
	//+ 
	function getCiteID ( )
	/*/
	getter
	-
	@return: the ZITF of the text
	@rtype:  string
	/*/
	{
		return $this->_cite_id;
	} //getCiteID
	
	//+ 
	function getOrderNumber ( )
	/*/
	getter
	-
	@return: the order number of the text
	@rtype:  string
	/*/
	{
		return $this->_order_number;
	} //getOrderNumber
	
	//+ 
	function getName ( )
	/*/
	#OUTDATED: REMOVE
	-
	@return: this Text's
	name
	@rtype:  string
	/*/
	{
		return '#OLD';
	} //getName
	
	//+ 
	function getDescription ( )
	/*/
	#OUTDATED: REMOVE
	-
	@return: this
	Text's description
	@rtype:  string
	/*/
	{
		return '#OLD';
	} //getDescription
	
	//+ 
	function setCiteID ( $citeid )
	/*/
	setter
	---
	@param citeid: the CiteID of the text
	@type  citeid: string
	/*/
	{
		$this->_cite_id = $citeid;
		#TODO: Change zitf=""-Attribute in the XML!
		$this->_writeToDB();
		
	} //setCiteID
	
	//+ 
	function setOrderNumber ( $order_number )
	/*/
	setter
	---
	@param order_number: the new order number
	@type  order_number: int
	/*/
	{
		$this->_order_number = $order_number;
		$this->_writeToDB();
	} //setOrderNumber
	
	//+ 
	function setFilepath ( $filepath )
	/*/
	setter
	---
	@param filepath: the new filepath pointing to the
	xml representation of this text on the filesystem
	@type  filepath: string
	/*/
	{
		$this->_filepath = $filepath;
		$this->_writeToDB();
	} //setFilepath
	
	//+ 
	function setName ( $name )
	/*/
	#OUTDATED: REMOVE
	---
	@param name: the
	new name of the Text
	@type  name: string
	/*/
	{
		#OUTDATED: REMOVE
	} //setName
	
	//+ 
	function setDescription ( $description )
	/*/
	#OUTDATED: REMOVE
	---
	@param description: the new description of the Text
	@type  description: string
	/*/
	{
		#OUTDATED: REMOVE
	} //setDescription
	
	//+ 
	function setCorpusID ( $corpus_id )
	/*/
	setter
	---
	@param corpus_id: the ID of the new Corpus that this Text should be assigned
	to
	@type  corpus_id: int
	/*/
	{
		//TODO: ev. assertions etc. or only callable via Corpus instance?
		assert(is_int($corpus_id));
		$this->_corpus_id = $corpus_id;
	} //setCorpusID
	
	//+ 
	function isCheckedOut ( )
	/*/
	setter
	-
	@return: whether the text is currently checked out
	@rtype:  bool
	/*/
	{
		$dao = new Table('CHECKOUT');
		$rows = $dao->get( "TextID=" . $this->getID() . " AND Checkin is NULL and IsInvalid=0" );
		return !empty($rows);
	} //isCheckedOut
	
	//+ 
	function getXML ( $as_DOM=0 )
	/*/
	Returns the XML representation of the text (STORRAGE format).
	---
	@param as_DOM: if TRUE, the text will be returned as a DOMDocument rather than a string
	@type  as_DOM: bool
	-
	@return: the Text xml
	@rtype:  string/DOMDocument
	/*/
	{
		$path = PH2_FP_BASE . '/' . $this->_filepath;
		$handle = fopen($path, 'r');
		$xml_string = fread($handle, filesize($path));
		
		// add the standard XML header if not stored yet
		if ( ! startsWith($xml_string, '<?xml version="1.0" encoding="UTF-8"?>') ) {
			$xml_string = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml_string;
		}
		
		if ($as_DOM) {
			$DOM = new DOMDocument();
			$DOM->loadXML($xml_string);
			return $DOM;
		} else {
			return $xml_string;
		}
		
	} //getXML
	
	//+ 
	function getTextDescriptors ( $filter=NULL )
	/*/
	returns an array of key/value-pairs
	with meta information on the Text, derived from the xml an-Section.
	If a filter is provided, the function only returns the
	text_descriptors whose name is provided in the filter array.
	---
	@param filter: the filter. If
	provided, only text_descriptors whose name is contained in this
	array will be returned
	@type  filter: array(str)
	-
	@return: the key/value pairs: descriptor
	name => value
	@rtype:  array(str => str)
	/*/
	{
		// check whether the text_descriptors are allready loaded into this object
		if (empty($this->_text_descriptors)) {
			$this->_loadTextDescriptors(); // NOTE: text descriptors are not reloaded on further calls
		}
		if ($filter) {
			// if a filter is provided, only return text_descriptors provided in the filter
			assert (is_array($filter));
			$filtered_result = array();
			foreach ($filter as $f) {
				if (array_key_exists($f, $this->_text_descriptors)) {
					$filtered_result[$f] = $this->_text_descriptors[$f];
				}
			}
			return $filtered_result;
		} else {
			// if no filter is provided, return all text descriptors
			return $this->_text_descriptors;
		}
		
	} //getTextDescriptors
	
	//+ 
	function getNumberOfOccurrences ( )
	/*/
	returns the total number of
	Occurrences (= wn-tags) of this Text.
	-
	@return: the number
	of Occurrences assigned to this Text
	@rtype:  int
	/*/
	{
		if (empty($this->_n_occurrence)) {
			$this->_loadNumberOfOccurrences();
		}
		return $this->_n_occurrence;
	} //getNumberOfOccurrences
	
	//+ 
	function getNumberOfLemmata ( )
	/*/
	returns the total number of Lemmata
	connected to Occurrences of this Text.
	-
	@return: the number
	of Lemmata connected to Occurrences of this Text
	@rtype:  int
	/*/
	{
		if (empty($this->_n_lemma)) {
			$this->_loadNumberOfLemmata();
		}
		return $this->_n_lemma;
	} //getNumberOfLemmata
	
	//+ 
	function checkout ( $user_id=NULL )
	/*/
	Returns a DOMNode representation of the Text according to the PH2 EDIT XSD and marks the 
	text as checked out.
	---
	@param user_id: the ID of the user who checks out the text
	@type  user_id: int
	-
	@return: the string representation of the current TEXT in EDIT format
	@rtype:  DOMNode
	/*/
	{
		
		// get the text's XML in EDIT format
		$edit_xml = $this->_getEditXML();
		// checkout the text; generate its checkout identifier
		$identifier = checkoutTextOrCorpus('text', $this->getID(), $edit_xml, $user_id);
		// add checkout identifier to XML
		$gl_element = $edit_xml->getElementsByTagName('gl')->item(0);
		$gl_element->setAttribute('checkout_id', $identifier);
		return $edit_xml; //Type: DOMNode
		
	} //checkout
	
	//+ 
	function checkin ( $edited_text , $overwrite_annotations=0 )
	/*/
	Updates this Text according to an edited DOMNode in PH2 EDIT XSD. The CHECKOUT mark is 
	removed for this text upon success.
	---
	@param edited_text: The text to be imported. If a strign is provided, it is converted to a 
	DOMNode (valid EDIT XML assumed).
	@type  edited_text: string/DOMNode
	@param overwrite_annotations: Whether existing annotations in the database should be kept 
	(0) or replaced by those given in the text to be imported (1).
	@type  overwrite_annotations: bool
	/*/
	{
		
		$success = FALSE;
		$log = '';
		
		// convert $edited_text to DOMNode if an XML string is provided
		if (is_string($edited_text)) {
			$dom = new DOMDocument();
			$dom->loadXML($edited_text);
			$editet_text_as_string = $edited_text;
			$edited_text = $dom;
			unset($dom);
		} else {
			$editet_text_as_string = $edited_text->saveXML();
		}
		
		// get the text's checkout ID
		$checkout_id = $edited_text->documentElement->getAttribute('checkout_id');
		
		// see if the checkout ID is valid, i.e., if there has been no later check-out of the same text
		if ( ! validateCheckoutIdentifier($checkout_id) ) {
			
			// set return values
			$success = FALSE;
			$log = 'Not checked-in. The file is outdated; please use the most recently checked-out version of this text.';
			
		} else {
		
			// check what parts, if any, of the text have been changed externally
			// get the checksums
			$current_checksums = getSectionChecksums($edited_text); //the checksums of the sections (0=>an,1=>txt) of the text to be checked-in
			$dao = new Table('CHECKSUM');
			$rows = $dao->get( array('CheckoutIdentifier' => $checkout_id) );
			$ref_checksums = array( $rows[0]['an'], $rows[0]['txt'] );
			
			// compare the checksums
			
			if ( $current_checksums[0] == $ref_checksums[0] && $current_checksums[1] == $ref_checksums[1] ) {
				// (0) nothing has changed: leave the text as it is
				// set return values
				$success = TRUE;
				$log = 'Checked-in. No change needed.';
				
			} else if ( $current_checksums[1] == $ref_checksums[1] ) {
				// (1) only the header has changed: update header information
				$this->_updateHeaderSection($edited_text);
				// set return values
				$success = TRUE;
				$log = 'Checked-in. The &lt;an&gt; header section has been updated.';
				// adjust the new file to fit the STORAGE format
				$edited_text->documentElement->removeAttributeNS($edited_text->documentElement->getAttributeNode("xmlns")->nodeValue,"");
				$edited_text->documentElement->removeAttribute('checkout_id');
				$edited_text->documentElement->setAttributeNS('', 'xmlns', PH2_URI_STORAGE);
				// write the new xml to file (in STORRAGE format)
				writeFile(PH2_FP_BASE . DIRECTORY_SEPARATOR . $this->getFilepath(), $edited_text->saveXML($edited_text->documentElement), TRUE); // write the new file, overwriting the old one
				
			} else {
				// (2) the body has changed: create a new text entity, re-link existing annotations, delete the old text entity
				
				$p = new XMLTextParser();
				$p->input_xml= $editet_text_as_string;
				$p->text_corpusID = $this->getCorpusID();
				$p->import(); // the new file is written to file within XMLTextParser->import()
				
				$success = TRUE;
				$log = 'Checked-in. The &lt;an&gt; and &lt;txt&gt; sections have been updated.';
				
				
			}
			
			// update the zitf-tag
			$zitf = $edited_text->documentElement->getAttribute('zitf');
			$this->setCiteID($zitf);
			
			// mark the text as checked in
			checkinTextOrCorpus($checkout_id);
			
		}
		
		return array($success, $log, $use_new_file);
		
		
	} //checkin
	
	//+ 
	function delete ( )
	/*/
	Deletes this text. All assignments (Graphgroup, Lemma, Morph) are also deleted. Deleting a 
	text cannot be undone.
	/*/
	{
		$this->_deleteFromDatabase();
		$this->_deleteFromFilesystem();
		
		unset($this);
			
	} //delete
	
	//+ 
	function _updateHeaderSection ( $new_gl_node )
	/*/
	Takes a complete DOMNode representing an updated version of the text and updates the an 
	section entries stored in TEXT_DESCRIPTOR. All existing entries are overwritten.
	---
	@param new_gl_node: the DOMNode representation of the updated version, including the an 
	section
	@type  new_gl_node: DOMNode
	/*/
	{
		
		$dao_descriptor = new Table('DESCRIPTOR');
		$dao_text_descriptor = new Table('TEXT_DESCRIPTOR');
		
		// delete all old annotations of this text
		$dao_text_descriptor->delete(array( 'TextID' => $this->getID() ));
		
		// get the an section node
		$an_element = $new_gl_node->getElementsByTagName('an')->item(0);
		
		// iterate over child nodes of the an section and add new annotations to the DB
		foreach ($an_element->childNodes as $annotation) {
			// Analogous to XMLTextParser::_default_parse_textdescriptor
			$tag = $annotation->nodeName;
			// only consider non-text nodes
			if ($tag != '#text') {
				$value = $annotation->nodeValue;
				$value = trim($value); //strip whitespace from the beginning and end of the value
				// add xml-tag to DESCRIPTOR
				$tag_id = $dao_descriptor->checkAdd(array('XMLTagName' => $tag));
				// add value and tag_id to TEXT_DESCRIPTOR
				$dao_text_descriptor->insert(array('TextID' => $this->getID(), 'DescriptorID' => $tag_id, 'Value' => $value));
			}
		}
		
	} //_updateHeaderSection
	
	//+ 
	function _updateTextSection ( $new_gl_node , $overwrite_annotations )
	/*/
	Updates the current text in the database according to an updated version.
	---
	@param new_gl_node: the DOMNode representation of the updated version, including the txt 
	section
	@type  new_gl_node: DOMNode
	@param overwrite_annotations: whether existing annotations linked to occurrences of the 
	old version should be transfered to the new version (0) or overwritten (1)
	@type  overwrite_annotations: bool
	/*/
	{
	} //_updateTextSection
	
	// PRIVATE FUNCTIONS
	// -----------------
	//+ 
	private function _loadFromDB ( )
	/*/
	selects all information on
	this Text from the database (by $this->_id) and writes it into this
	object's instance variables.
	/*/
	{
		// retrieve information from DB
		$dao = new Table('TEXT');
		$rows = $dao->get( array('TextID' => $this->_id) );
		$data = $rows[0];
		// write information to this instance
		$this->_corpus_id = $data['CorpusID'];
		$this->_filepath = $data['Filepath'];
		$this->_cite_id = $data['CiteID'];
		$this->_order_number = $data['Order'];
	} //_loadFromDB
	
	//+ 
	private function _writeToDB ( )
	/*/
	writes all information on
	this Text from this instance into the database. If this Project
	instance allready has an ID, the corresponding DB-entry is updated.
	Otherwise, a new entry is created in the database and the new ID is
	stored in this objects _id variable.
	/*/
	{
		// prepare instance vars
		if (empty($this->_filepath)) {
			$this->_filepath = 'TEMP';
		}
		// prepare dao and data
		$dao = new Table('TEXT');
		$row = array('CorpusID' => $this->_corpus_id, 'Filepath' => $this->_filepath, 'CiteID' => $this->_cite_id, 'Order' => $this->_order_number);
		// write to DB		
		if (empty($this->_id)) {
			// new Text, create new DB entry
			$dao->insert($row);
			$this->_id = $dao->getLastID();
		} else {
			// existing Text, update DB entry identified by $this->_id
			$dao->where = array('TextID' => $this->_id);
			$dao->update($row);
		}
	} //_writeToDB
	
	//+ 
	private function _loadTextDescriptors ( )
	/*/
	loads all text descriptors from
	the DB and stores them into this Text's _text_descriptors instance
	variable.
	/*/
	{
		$dao = new Table('TEXT_DESCRIPTOR');
		$dao->from = 'TEXT_DESCRIPTOR natural join DESCRIPTOR';
		$rows = $dao->get( array('TextID' => $this->_id) );
		foreach ($rows as $row) {
			$this->_text_descriptors[$row['XMLTagName']] = $row['Value'];
		}
	} //_loadTextDescriptors
	
	//+ 
	private function _loadNumberOfOccurrences ( )
	/*/
	loads the number of
	Occurrences assigned to this Text from the DB and stores it into
	this Text's _n_occurrence instance variable.
	/*/
	{
		$dao = new Table('OCCURRENCE');
		$dao->select = 'count(*) as n_occurrence';
		$rows = $dao->get( array('TextID' => $this->_id) );
		$this->_n_occurrence = $rows[0]['n_occurrence'];
		
	} //_loadNumberOfOccurrences
	
	//+ 
	private function _loadNumberOfLemmata ( )
	/*/
	loads the number of Lemmata
	assigned to Occurrences of this Text and stores it into this Text's
	_n_lemma instance variable.
	/*/
	{
		$dao = new Table('OCCURRENCE');
		$dao->select = 'count(*) as n_lemma';
		$dao->from = 'OCCURRENCE natural join LEMMA_OCCURRENCE';
		$rows = $dao->get( array('TextID' => $this->_id) );
		$this->_n_lemma = $rows[0]['n_lemma'];
	} //_loadNumberOfLemmata
	
	//+ 
	private function _deleteFromDatabase ( )
	/*/
	Deletes this text
	from the database.
	-
	@return: 1 on success, 0
	otherwise
	@rtype:  bool
	/*/
	{
		// by now, this procedure only deletes occurrences associated with this text and the text entry itself
		// TODO: Refine
		/* - TOKEN cleanup: delete TOKEN entries that are not linked with an OCCURRENCE no more
		   - DESCRIPTOR cleanup: dito
		   - ...
		*/
		
		// get all assigned occurrences (OCCURRENCE)
		$tb_OCC = new Table('OCCURRENCE');
		$rows = $tb_OCC->get(array('TextID' => $this->_id));
		$text_occurrence_ids = array();
		foreach ($rows as $row) {
			$text_occurrence_ids[] = $row['OccurrenceID'];
		}
		$old_occurrence_ids = '(' . expandArray($text_occurrence_ids, ',') . ')';
		// delete graphematic assignments
		$tb_GG = new Table('GRAPHGROUP_OCCURRENCE');
		$tb_GG->delete("OccurrenceID in $old_occurrence_ids");
		// delete lemma assignments
		$tb_LM = new Table('LEMMA_OCCURRENCE');
		$tb_LM->delete("OccurrenceID in $old_occurrence_ids");
		// delete morphological assignments
		$tb_MP = new Table('OCCURRENCE_MORPHVALUE');
		$tb_MP->delete("OccurrenceID in $old_occurrence_ids");
		// delete textsection assignments
		$tb_TS = new Table('OCCURRENCE_TEXTSECTION');
		$tb_TS->delete("OccurrenceID in $old_occurrence_ids");
		// delete all assigned occurrences
		$tb_OCC->delete("OccurrenceID in $old_occurrence_ids");
		// delete assigned text descriptions (TEXT_DESCRIPTOR)
		$tb_TD = new Table('TEXT_DESCRIPTOR');
		$tb_TD->delete(array('TextID' => $this->_id));
		// delete assigned media (TEXT_MEDIUM)
		$tb_TD = new Table('TEXT_MEDIUM');
		$tb_TD->delete(array('TextID' => $this->_id));
		// delete assigned checkout/-in markers
		$tb_CHECKOUT = new Table('CHECKOUT');
		$tb_CHECKSUM = new Table('CHECKSUM');
		$rows = $tb_CHECKOUT->get(array('TextID' => $this->_id));
		foreach ($rows as $row) {
			$tb_CHECKSUM->delete(array('CheckoutIdentifier' => $row['Identifier']));
		}
		$tb_CHECKOUT->delete(array('TextID' => $this->_id));
		// delete TEXT entity (TEXT)
		$tb_TEXT = new Table('TEXT');
		$tb_TEXT->delete(array('TextID' => $this->_id));
		
		return 1;
		
	} //_deleteFromDatabase
	
	//+ 
	private function _deleteFromFilesystem ( )
	/*/
	Deletes this
	text from the filesystem.
	-
	@return: 1 on success, 0
	otherwise
	@rtype:  bool
	/*/
	{
		
		unlink( PH2_FP_BASE . DIRECTORY_SEPARATOR . $this->getFilepath() );
		
	} //_deleteFromFilesystem
	
	//+ 
	private function _getCheckoutAnnotations ( )
	/*/
	Collects all annotations relevant for checking out the text in EDIT format (via 
	Text->checkout()).
	Returns a datastructure like:
	Attr. Name               Order Value          ...
	array( "lemma"   => array( array(13,   "abbe"), array(14, "por"), ...),
	"concept" => array( array(13, "c"), array(14, "c"), ...),
	"morph"   => array( array(17, "s.m."), ...)
	)
	-
	@return: the annotation categories with their Token-Order/Value-pairs
	@rtype:  array
	/*/
	{
		$annotations = array();
		
		# Lemma (LemmaIdentifier), Concept (Short)
		#EV-TODO: Move to separate function
		$dao = new Table('LEMMA');
		$dao->select = "LemmaIdentifier, Short, `Order`";
		$dao->from = "LEMMA join CONCEPT on LEMMA.ConceptID=CONCEPT.ConceptID join LEMMA_OCCURRENCE on LEMMA.LemmaID=LEMMA_OCCURRENCE.LemmaID join OCCURRENCE on LEMMA_OCCURRENCE.OccurrenceID=OCCURRENCE.OccurrenceID";
		$dao->orderby="`Order` ASC";
		$rows = $dao->get( array( 'TextID' => $this->getID() ) );
		if (is_array($rows)) {
			foreach( $rows as $row) {
				$annotations['lemma'][$row['Order']] = $row['LemmaIdentifier'];
				$annotations['concept'][$row['Order']] = $row['Short'];
			}
		}
		
		# Morphological Annotations: Lemmata
		#EV-TODO: Move to separate function
		$dao = new Table('MORPHVALUE');
		$dao->select = "XMLTagName, `Value`, `Order`";
		$dao->from = "MORPHVALUE join MORPHCATEGORY on MORPHVALUE.MorphcategoryID = MORPHCATEGORY.MorphcategoryID join LEMMA_MORPHVALUE on LEMMA_MORPHVALUE.MorphvalueID=MORPHVALUE.MorphvalueID join LEMMA on LEMMA_MORPHVALUE.LemmaID=LEMMA.LemmaID join LEMMA_OCCURRENCE on LEMMA.LemmaID=LEMMA_OCCURRENCE.LemmaID natural join OCCURRENCE";
		$dao->orderby="`Order` ASC";
		$rows = $dao->get( array( 'TextID' => $this->getID() ) );
		foreach( $rows as $row) {
			$annotations[$row['XMLTagName']][$row['Order']] = $row['Value'];
		}
		
		# Morphological Annotations: Occurrences
		#EV-TODO: Move to separate function
		$dao = new Table('MORPHVALUE');
		$dao->select = "XMLTagName, `Value`, `Order`";
		$dao->from = "MORPHVALUE join MORPHCATEGORY on MORPHVALUE.MorphcategoryID = MORPHCATEGORY.MorphcategoryID natural join OCCURRENCE_MORPHVALUE join OCCURRENCE on OCCURRENCE_MORPHVALUE.OccurrenceID=OCCURRENCE.OccurrenceID";
		$dao->orderby="`Order` ASC";
		$rows = $dao->get( array( 'TextID' => $this->getID() ) );
		foreach( $rows as $row) {
			$annotations[$row['XMLTagName']][$row['Order']] = $row['Value'];
		}
		
		return $annotations;
		
		
	} //_getCheckoutAnnotations
	
	//+ 
	private function _getEditXML ( )
	/*/
	Creates a DOMDocument representation with all export annotations (via 
	Text->_getCheckoutAnnotations()) of this Text.
	-
	@return: the DOMDocument EDIT XSD representation of this Text
	@rtype:  DOMDocument
	/*/
	{
		$dom = $this->getXML($as_DOM=TRUE);
		
		// remove the STORAGE namespace
		$dom->documentElement->removeAttributeNS($dom->documentElement->getAttributeNode("xmlns")->nodeValue,"");
		$xml_string_without_namespace = $dom->saveXML();
		unset($dom);
		
		// reload the document without namespace
		$dom = new DOMDocument();
		$dom->loadXML($xml_string_without_namespace);
		
		// set the new namespace
		$dom->documentElement->setAttribute('xmlns', PH2_URI_EDIT);
		
		#NOTE: Ensure <?xml version="1.0" encoding="UTF-8"... header to prevent UTF8 characters from being encoded as entities
		#NOTE: This representation does not validate against the EDIT XSD yet as the checkoud_id-attribute of the root element is missing. It will be added when checking out a text via $this->checkout().
		return $dom;
		
		
	} //_getEditXML
	
	
}

?>