<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: XML Text Parser
Framework File Signature: com.ph2.framework.php.entities.xmltextparser
Description:
Class for Parsing XML documents and import translate their content to the Phoenix2 
relational database schema.
---
/*/

//+
class XMLTextParser
{
	// INSTANCE VARS
	// -------------
	public $input_xml; /// the xml string of the document to be parsed
	public $text_corpusID; /*/
	the ID of the corpus that the new text should be assigned to (will not 
	affect xml!)
	/*/
	public $convert_punctuation; /*/
	whether untagged punctuation marks should be wrapped by a punct-type 
	token (see _parse_txt for details)
	/*/
	protected $_mode; /// if set to 'update', this parser will re-import a text in EDIT format
	protected $_import_db_entries; /*/
	stores the rows for new entries to be inserted in exchange for their 
	old equivalent when an EDIT text is imported
	/*/
	protected $_import_token_order_mappings; /*/
	stores which token number (Order) of the old text (key) corresponds to 
	which token number of the new text (value)
	/*/
	protected $_output_xml; /*/
	the DOM Document containing the new xml representation out of 
	_input_xml
	/*/
	protected $_xsd_path; /*/
	the path pointing to the XSD file on the filesystem. If specified, the 
	_input_xml is validated against it.
	/*/
	protected $_log; /// the log string
	protected $_unknown_tags; /*/
	array of tags in the document that could not be parsed with a 
	dedicated function. Also mentioned in the log.
	/*/
	protected $_document_descriptors; /*/
	array of meta tags for this document, e.g. "translated" if old wn-tags 
	were translated to token-tags etc.
	/*/
	public $_created_text_entity; /// the Text entity that was created during the parsing
	protected $_parse_function_prefix; /*/
	the prefix of all functions associated with parsing a node with a 
	specific name
	/*/
	protected $_paranthese_open; /*/
	boolean indicating whether a [-paranthese was opened previously. See 
	_parse_token-implementation for details on its use.
	/*/
	protected $__sectionIDs; /*/
	the array containing all active sectionIDs at the current step of 
	parsing
	/*/
	protected $__div; /// the text division (div) number at the current step of parsing
	protected $__token_counter; /// the token counter that is increased with every token
	public $_cached_dao_OCCURRENCE; /// the cached data access object to the OCCURRENCE table
	public $_cached_dao_mig_OCCURRENCE; /*/
	the cached data access object to the translation table for word 
	OCCURRENCEs (XMLSchemaMigration)
	/*/
	public $_cached_dao_TOKEN; /// the cached data access object to the TOKEN table
	public $_cached_dao_TOKENTYPE; /// the cached data access object to the TOKENTYPE table
	public $_cached_dao_DESCRIPTOR; /// the cached data access object to the DESCRIPTOR table
	public $_cached_dao_TEXT_DESCRIPTOR; /// the cached data access object to the TEXT_DESCRIPTOR table
	public $_STATIC_textsection_starters; /// the tag names that start a textsection
	public $_STATIC_textsection_terminators; /// the tag names that terminate a textsection
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $input_xml=NULL , $xsd_path=NULL )
	/*/
	---
	@param input_xml: the xml string that should be parsed
	@type  input_xml: string
	@param xsd_path: the path pointing to the xsd file that the input_xml should be validated 
	against.
	@type  xsd_path: string
	/*/
	{
		// initialize instance vars
		if ($input_xml) {
			$this->input_xml = $input_xml;
		}
		if ($xsd_path) {
			$this->_xsd_path = $xsd_path;
		} else {
			// default XSD path
			$this->_xsd_path = $xsd = PH2_WP_RSC . '/xsd/storage/storage.xsd';
		}
		$this->convert_punctuation = FALSE;
		$this->_output_xml = new DOMDocument();
		$this->_log = array();
		$this->_unknown_tags = array();
		$this->_document_descriptors = array();
		$this->_parse_function_prefix = '_parse_';
		// prepare token order
		$this->__token_counter = 0;
		// prepare static textsection markers
		$this->_STATIC_textsection_starters = array();
		$this->_STATIC_textsection_terminators = array();
		$sections = getTextsections();
		foreach ($sections as $id => $name) {
			$this->_STATIC_textsection_starters[$id] = $name;
			//$this->_STATIC_textsection_terminators[$id] = $name . '.e';
		}
	
		// init cached data access objects
		$this->_cached_dao_OCCURRENCE = new Table('OCCURRENCE', TRUE);
		$this->_cached_dao_mig_OCCURRENCE = new Table('mig_OCCURRENCE', TRUE);
		$this->_cached_dao_TOKEN = new Table('TOKEN', TRUE);
		$this->_cached_dao_TOKENTYPE = new Table('TOKENTYPE', TRUE);
		$this->_cached_dao_DESCRIPTOR = new Table('DESCRIPTOR', TRUE);
		$this->_cached_dao_TEXT_DESCRIPTOR = new Table('TEXT_DESCRIPTOR', TRUE);
		
		// if an input xml string is provided, start the parsing immediately
		if ($input_xml) $this->parse();
			
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function getOutputXML ( )
	/*/
	returns the output xml generated by a successful parsing.
	-
	@return: the output xml
	@rtype:  DOMDocument
	/*/
	{
		/*
		** This is only necessary when files are imported without the <?xml version="1.0" encoding="UTF-8"?> header
		*/
		$output_xml = html_entity_decode($this->_output_xml->saveXML(), ENT_NOQUOTES, 'UTF-8');
		// ugly but true: ampersands have to be re-encoded
		$output_xml = str_replace('&', '&amp;', $output_xml);
		// hack to get rid of <default: > tags
		$output_xml = str_replace('<default:', '<', $output_xml);
		$output_xml = str_replace('</default:', '</', $output_xml);
		return $output_xml;
		
	} //getOutputXML
	
	//+ 
	function getLog ( )
	/*/
	returns the log of the current parser state
	-
	@return: the log lines
	@rtype:  array
	/*/
	{
		return $this->_log;
	} //getLog
	
	//+ 
	function getUnknownTags ( )
	/*/
	returns the tags that no specific parser was defined for; they were only copied, but have 
	no effect on the PH2 database.
	-
	@return: the unknown tags
	@rtype:  array(string
	/*/
	{
		return $this->_unknown_tags;
	} //getUnknownTags
	
	//+ 
	function parse ( )
	/*/
	Parses the input_xml (string) in STORAGE format and returns the output_xml (DOMDocument). 
	The input xml string must be stored in this object in order to call this method.
	-
	@return: the output_xml
	@rtype:  DOMDocument
	/*/
	{
		// convert xml input string to DOMDocument
		$dom_input_xml = $this->_prepare_input_xml();
		
		// parse the xml
		$this->_parse_loop($dom_input_xml, $this->_output_xml);
		
		// (re-) add the default namespace to the root node
		$this->_output_xml->documentElement->setAttribute('xmlns', PH2_URI_STORAGE);
		
		// write warning to log if brackets are unbalanced inside the text section
		if ($this->_paranthese_open) {
			$this->_log('Unbalanced parantheses in textsection detected.', '2');
			echo "Unbalanced parantheses in<br />";
		}
		// write xml to filesystem
		return $this->_write_output_xml_to_file();
	} //parse
	
	//+ 
	function import ( )
	/*/
	Parses the input_xml (string) in EDIT format and returns the output_xml (DOMDocument). The
	original Text that input_xml belongs to is deleted after the import, both in the DB and on
	the filesystem.
	/*/
	{
		// put the parser into update mode
		$this->_mode = 'update'; // this causes all db entries to be stored in $this->_import_db_entries rather than being written to the DB directly

		// get the old

		// convert xml input string to DOMDocument
		$dom_input_xml = $this->_prepare_input_xml();

		// parse the xml
		$this->_parse_loop($dom_input_xml, $this->_output_xml);

		// (re-) add the default namespace to the root node
		$this->_output_xml->documentElement->setAttribute('xmlns', PH2_URI_STORAGE);


		// MAKE TRANSFORMATIONS IN DATABASE (delete old text, transfer annotations, ...)

		// 1: Delete annotations, but keep the old Order number of the affected Occurrences to re-map the Graphgroup annotations to the new Occurrences later
		$dao = new Table('LEMMA_OCCURRENCE');
		$rows = $dao->query( "select l.OccurrenceID, o.Order, l.LemmaID from TEXT as t join OCCURRENCE as o on t.TextID=o.TextID join LEMMA_OCCURRENCE as l on o.OccurrenceID=l.OccurrenceID where t.TextID=" . $this->_created_text_entity->getID() );
		$mappings_order_lemma = array();
		$affected_occ_ids = array();
		foreach ($rows as $row) {
			$mappings_order_lemma[ $row['Order'] ] = $row['LemmaID'];
			$affected_occ_ids[] = $row['OccurrenceID'];
		}
		if (count($affected_occ_ids) > 0) {
			$dao->delete( "OccurrenceID in (" . expandArray( $affected_occ_ids, ',' ) . ")" );
		}
		unset($dao);

		$dao = new Table('OCCURRENCE_MORPHVALUE');
		$rows = $dao->query( "select l.OccurrenceID, o.Order, l.MorphvalueID from TEXT as t join OCCURRENCE as o on t.TextID=o.TextID join OCCURRENCE_MORPHVALUE as l on o.OccurrenceID=l.OccurrenceID where t.TextID=" . $this->_created_text_entity->getID() );
		$mappings_order_morphvalue = array();
		$affected_occ_ids = array();
		foreach ($rows as $row) {
			$mappings_order_morphvalue[ $row['Order'] ] = $row['MorphvalueID'];
			$affected_occ_ids[] = $row['OccurrenceID'];
		}
		if (count($affected_occ_ids) > 0) {
			$dao->delete( "OccurrenceID in (" . expandArray( $affected_occ_ids, ',' ) . ")" );
		}
		unset($dao);

		$dao = new Table('OCCURRENCE_TEXTSECTION');
		$rows = $dao->query( "select l.OccurrenceID, o.Order, l.TextsectionID from TEXT as t join OCCURRENCE as o on t.TextID=o.TextID join OCCURRENCE_TEXTSECTION as l on o.OccurrenceID=l.OccurrenceID where t.TextID=" . $this->_created_text_entity->getID() );
		$mappings_order_textsection = array();
		$affected_occ_ids = array();
		foreach ($rows as $row) {
			$mappings_order_textsection[ $row['Order'] ] = $row['TextsectionID'];
			$affected_occ_ids[] = $row['OccurrenceID'];
		}
		if (count($affected_occ_ids) > 0) {
			$dao->delete( "OccurrenceID in (" . expandArray( $affected_occ_ids, ',' ) . ")" );
		}
		unset($dao);

		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		$rows = $dao->query( "select l.OccurrenceID, o.Order, l.GraphgroupID from TEXT as t join OCCURRENCE as o on t.TextID=o.TextID join GRAPHGROUP_OCCURRENCE as l on o.OccurrenceID=l.OccurrenceID where t.TextID=" . $this->_created_text_entity->getID() );
		$mappings_order_graphgroup = array(); // key: old order number, value: graphgroupdID
		$affected_occ_ids = array();
		foreach ($rows as $row) {
			$mappings_order_graphgroup[ $row['Order'] ] = $row['GraphgroupID'];
			$affected_occ_ids[] = $row['OccurrenceID'];
		}
		if (count($affected_occ_ids[0]) > 0) {
			$dao->delete( "OccurrenceID in (" . expandArray( $affected_occ_ids, ',' ) . ")" );
		}
		unset($dao);

		// 2: Delete old TEXT_DESCRIPTOR entries and insert the new ones (<an> section)
		$dao = new Table('TEXT_DESCRIPTOR');
		$dao->delete( array( 'TextID' => $this->_created_text_entity->getID() ) );
		foreach($this->_import_db_entries['TEXT_DESCRIPTOR'] as $text_descriptor) {
			$dao->insert($text_descriptor);
		}
		unset($dao);
		
		// 3: Delete all Occurrences that are associated to the old Text
		// OccurrenceIDs are preserved for all tokens that haven't changed in a text
		$dao = new Table('OCCURRENCE');
		// 3.1.: Get the mapping of Order -> OccurrenceID for all Occurrences of the old Text
		$dao->select = '`OccurrenceID`, `Order`';
		$rows = $dao->get( array( 'TextID' => $this->_created_text_entity->getID() ) );
		$mappings_old_order_occ_id = array();
		foreach ($rows as $row) {
			$mappings_old_order_occ_id[ $row['Order'] ] = $row['OccurrenceID'];
		}
		// 3.2.: Delete the Occurrences of the old Text
		$dao->delete( array( 'TextID' => $this->_created_text_entity->getID() ) );

		// 4: Insert the new Occurrences that were prepared during $this->_parse_loop

		// create the new Occurrence in the database
		// - overrides the Occurrence object for performance reasons
		// - uses existing OccurrenceIDs for tokens that have not changed; lowest available OccurrenceID otherwise
		$mappings_new_order_old_order = array_flip($this->_import_token_order_mappings);
		$new_order_numbers_with_old_order_number = array_keys($mappings_new_order_old_order); //for performance
		$new_occurrences = array(); // the Occurrences that have been inserted or updated externally
		foreach ( $this->_import_db_entries['Occurrences'] as $new_occurrence ) {
			if ( in_array($new_occurrence['Order'], $new_order_numbers_with_old_order_number) ) {
				// echo $new_occurrence['Order'] . " in " . $new_order_numbers_with_old_order_number[$new_occurrence['Order']];
				// Occurrence is EXISTING: It has not been changed in the Text
				$old_order_nr = $mappings_new_order_old_order[ $new_occurrence['Order'] ];
				$old_occurrence_id = $mappings_old_order_occ_id[ $old_order_nr ];
				$new_occurrence['OccurrenceID'] = $old_occurrence_id; // i.e., the Occurrence ID will be preserved for Occurrences that have not been changed externally

				// insert existing tokens
				$dao->insert($new_occurrence);
			} else {
				// Occurrence is NEW: It has been inserted or updated in the Text
				$new_occurrences[] = $new_occurrence;
			}
		}
		// insert new tokens
		$new_occurrence_ids = $dao->insertRowsAtLowestPossibleID('OccurrenceID', $new_occurrences);

		// get an updated mapping of new_order_nr -> new_occ_id
		$rows = $dao->get( array( 'TextID' => $this->_created_text_entity->getID() ) );
		$mappings_new_order_new_occ_id = array();
		foreach ($rows as $row) {
			$mappings_new_order_new_occ_id[ $row['Order'] ] = $row['OccurrenceID'];
		}
		
		// 5: Re-link annotations that were not exported to the XML (new: all except "n" and "type")
		$dao_occ = new Table('OCCURRENCE');
		// Graphgroup
		$mappings_new_order_graphgroup = array();
		foreach ($mappings_order_graphgroup as $old_order => $graphgroup) {
			if ($this->_import_token_order_mappings[$old_order]) {
				$mappings_new_order_graphgroup[ $this->_import_token_order_mappings[$old_order] ] = $graphgroup;
			}
		}
		if (count($mappings_new_order_graphgroup) > 0 ) {
			// get the OccurrenceIDs that are associated with the new OrderIDs
			$rows = $dao_occ->query( "select OccurrenceID, `Order` from OCCURRENCE where `Order` in (" . expandArray( array_keys($mappings_new_order_graphgroup), ',' ) . ") and TextID=" . $this->_created_text_entity->getID() );
			$mappings_new_order_new_occ_id_graphgroup = array();
			foreach ($rows as $row) {
				$mappings_new_order_new_occ_id_graphgroup[ $row['Order'] ] = $row['OccurrenceID'];
			}
			// insert
			$dao = new Table('GRAPHGROUP_OCCURRENCE');
			foreach ($mappings_new_order_graphgroup as $new_order => $graphgroup_id) {
				$dao->insert( array( 'OccurrenceID' => $mappings_new_order_new_occ_id_graphgroup[ $new_order ], 'GraphgroupID' => $graphgroup_id) );
			}
			unset($dao);
		}
		
		// Lemma
		$mappings_new_order_lemma = array();
		foreach ($mappings_order_lemma as $old_order => $lemma) {
			if ($this->_import_token_order_mappings[$old_order]) {
				$mappings_new_order_lemma[ $this->_import_token_order_mappings[$old_order] ] = $lemma;
			}
		}
		if (count($mappings_new_order_lemma) > 0 ) {
			// get the OccurrenceIDs that are associated with the new OrderIDs
			$rows = $dao_occ->query( "select OccurrenceID, `Order` from OCCURRENCE where `Order` in (" . expandArray( array_keys($mappings_new_order_lemma), ',' ) . ") and TextID=" . $this->_created_text_entity->getID() );
			$mappings_new_order_new_occ_id_lemma = array();
			foreach ($rows as $row) {
				$mappings_new_order_new_occ_id_lemma[ $row['Order'] ] = $row['OccurrenceID'];
			}
			// insert
			$dao = new Table('LEMMA_OCCURRENCE');
			foreach ($mappings_new_order_lemma as $new_order => $lemma_id) {
				$dao->insert( array( 'OccurrenceID' => $mappings_new_order_new_occ_id[ $new_order ], 'LemmaID' => $lemma_id) );
			}
			unset($dao);
		}
		
		// Morphvalues
		$mappings_new_order_morphvalue = array();
		foreach ($mappings_order_morphvalue as $old_order => $morphvalue) {
			if ($this->_import_token_order_mappings[$old_order]) {
				$mappings_new_order_morphvalue[ $this->_import_token_order_mappings[$old_order] ] = $morphvalue;
			}
		}
		if (count($mappings_new_order_morphvalue) > 0 ) {
			// get the OccurrenceIDs that are associated with the new OrderIDs
			$rows = $dao_occ->query( "select OccurrenceID, `Order` from OCCURRENCE where `Order` in (" . expandArray( array_keys($mappings_new_order_morphvalue), ',' ) . ") and TextID=" . $this->_created_text_entity->getID() );
			$mappings_new_order_new_occ_id_morphvalue = array();
			foreach ($rows as $row) {
				$mappings_new_order_new_occ_id_morphvalue[ $row['Order'] ] = $row['OccurrenceID'];
			}
			// insert
			$dao = new Table('OCCURRENCE_MORPHVALUE');
			foreach ($mappings_new_order_morphvalue as $new_order => $morphvalue_id) {
				$dao->insert( array( 'OccurrenceID' => $mappings_new_order_new_occ_id_morphvalue[ $new_order ], 'MorphvalueID' => $morphvalue_id) );
			}
			unset($dao);
		}

		// Textsection
		$mappings_new_order_textsection = array();
		foreach ($mappings_order_textsection as $old_order => $textsection) {
			if ($this->_import_token_order_mappings[$old_order]) {
				$mappings_new_order_textsection[ $this->_import_token_order_mappings[$old_order] ] = $textsection;
			}
		}
		if (count($mappings_new_order_textsection) > 0 ) {
			// get the OccurrenceIDs that are associated with the new OrderIDs
			$rows = $dao_occ->query( "select OccurrenceID, `Order` from OCCURRENCE where `Order` in (" . expandArray( array_keys($mappings_new_order_textsection), ',' ) . ") and TextID=" . $this->_created_text_entity->getID() );
			$mappings_new_order_new_occ_id_textsection = array();
			foreach ($rows as $row) {
				$mappings_new_order_new_occ_id_textsection[ $row['Order'] ] = $row['OccurrenceID'];
			}
			// insert
			$dao = new Table('OCCURRENCE_TEXTSECTION');
			foreach ($mappings_new_order_textsection as $new_order => $textsection_id) {
				$dao->insert( array( 'OccurrenceID' => $mappings_new_order_new_occ_id_textsection[ $new_order ], 'TextsectionID' => $textsection_id) );
			}
			unset($dao);
		}

		unset($dao_occ);
		// echo $this->_output_xml->saveXML();

		// write xml in STORAGE format to filesystem
		return $this->_write_output_xml_to_file();

	} //import
	
	//+ 
	function reset ( $input_xml=NULL , $xsd_path=NULL )
	/*/
	Resets the parser by re-calling the constructor. If an input_xml (and, optionally, an xsd 
	path) is provided, it is parsed immediately as upon construction.
	---
	@param input_xml: the xml string that should be parsed
	@type  input_xml: string
	@param xsd_path: the path pointing to the xsd file that the input_xml should be validated 
	against.
	@type  xsd_path: string
	/*/
	{
	} //reset
	
	//+ 
	function _log ( $message , $type=1 , $affected_node=NULL , $timestamp=NULL )
	/*/
	adds an entry to the log file.
	---
	@param message: the log message
	@type  message: string
	@param type: the type of this log line. 1: standard (OK), 2: warning (but the parsing 
	proceeds), 3: error (parsing is aborted)
	@type  type: int
	@param affected_node: the name of the affected node that raised the log line. may be empty 
	if the message does not concern a specific node type.
	@type  affected_node: string
	@param timestamp: the current timestamp
	@type  timestamp: string
	/*/
	{
		if (!$timestamp) $timestamp = now();
		$this->_log[] = array($message, $type, $affected_node, $timestamp);
	} //_log
	
	//+ 
	protected function _prepare_input_xml ( )
	/*/
	Converts the input xml string to a DOMDocument and checks it against the xsd if 
	applicable.
	-
	@return: the DOMDocument-representation of the input xml
	@rtype:  DOMDocument
	/*/
	{
		// convert xml input string to DOMDocument
		assert (isset($this->input_xml));
		
		$dom = new DOMDocument();
		@$dom->loadXML($this->input_xml);
		if ($dom) {
			return $dom;
		} else {
			$this->_log('The provided XML is not well-formed and thus cannot be parsed.', 3);
			$this->_abort();
		}
	
	} //_prepare_input_xml
	
	//+ 
	function _write_output_xml_to_file ( )
	/*/
	writes the output xml into the data folder on the filesystem specified in the settings. 
	The filepath is stored in the Text entity on the database.
	/*/
	{
		// write XML to file
		$filepath_db = PH2_FP_TEXT . '/text' . $this->_created_text_entity->getID() . '.xml';
		$filepath_filesystem = PH2_FP_BASE . '/' . $filepath_db;
		
		// overwrite file in update mode
		$overwrite = FALSE;
		if ($this->_mode == 'update') {
			$overwrite = TRUE;
		}
		
		//$xml_string = html_entity_decode($xml->asXML(),ENT_NOQUOTES,'UTF-8');
		$this->_output_xml->formatOutput = TRUE;
		$xml_string = xmlpp($this->getOutputXML());
		if (writeFile($filepath_filesystem, $xml_string, $overwrite)) {
			// write filepath to TEXT entry in database
			$this->_created_text_entity->setFilepath( $filepath_db );
			// SUCCESSFULLY ADDED THE TEXT
			$this->_log("Output XML successfully written to $filepath", 1);
			return 1;
		} else {
			$this->_log("Fatal Error: Output XML could not be written to $filepath", 3);
			return 0;
		}
	} //_write_output_xml_to_file
	
	//+ 
	protected function _parse_loop ( $input_node , $root )
	/*/
	Iterates over each node of the DOMDocument-representation of the input xml (converted by
	_prepare_xml_input) and passes it to the respective parser.
	---
	@param input_node: the DOMDocument-representation of the input node to parse
	@type  input_node: DOMNode
	@param root: the node where the result of the input_node parsing should be appended to
	@type  root: DOMNode
	/*/
	{
		foreach ($input_node->childNodes as $child) {
			// compose parser name
			$parser_name = $this->_parse_function_prefix . $child->nodeName;
			// try to call a parser associated with the node's name
			if (method_exists($this, $parser_name)) {
				$root->appendChild($this->{$parser_name}($child));
			} else if ( in_array($child->nodeName, $this->_STATIC_textsection_starters) ) {
				// MILESTONES: int, exp, ...
				$root->appendChild($this->_default_parse_textsection($child));
			} else {
				$root->appendChild($this->_default_parse($child));
			}
		}

	} //_parse_loop
	
	//+ 
	protected function _abort ( )
	/*/
	Aborts the parsing and deletes all created entites from the database (rollback). Returns 
	the false. The parser is not automatically reset, thus $this->getLog() may be called in 
	order to access the log file. Use $this->reset to reset the parser.
	-
	@return: returns FALSE, indicating that the parsing process was not successful
	@rtype:  bool
	/*/
	{
		//TODO!
		return 0;
	} //_abort
	
	//+ 
	protected function _getNextTokenOrderNumber ( )
	/*/
	increases the __token_counter by one and returns its current number
	-
	@return: the occurrence order number
	@rtype:  int
	/*/
	{
		$this->__token_counter++;
		return $this->__token_counter;
	} //_getNextTokenOrderNumber
	
	//+ 
	protected function _getTextContentOfNode ( $node , $stripTags )
	/*/
	Returns the text content of the given node.
	---
	@param node: The input node
	@type  node: DOMNode
	@param stripTags: If TRUE, the tags inside the node text will be ommitted
	@type  stripTags: bool
	-
	@return: the text content of the input node
	@rtype:  string
	/*/
	{
		$simple_xml = simplexml_import_dom($node);
		$node_xml_string = stripOutermostTag($simple_xml->asXML());
		
		if ($stripTags) {
			return strip_tags($node_xml_string);
		} else {
			return $node_xml_string;
		}
	} //_getTextContentOfNode
	
	//+ 
	protected function _default_parse ( $input_node )
	/*/
	If there is no specific parser function for a document node, it is passed to this 
	function. It delegates specific routines to dedicated agregation functions and specifies a 
	standard routine for all other nodes.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		if ($input_node->nodeName != "#text") {
			
			switch ($input_node->parentNode->nodeName) {
				case 'an': 
					$this->_default_parse_textdescriptor($input_node);
					$is_known = TRUE;
				break;
			}
		
			if (in_array($input_node->nodeName, $this->_STATIC_textsection_starters) || 
				in_array($input_node->nodeName, $this->_STATIC_textsection_terminators)) {
				$this->_default_parse_textsection($input_node);
				$is_known = TRUE;
			}
		
			// if no routine was found for the tag, write log and append its name to $this->_unknown_tags
			if (!$is_known) {
				// log
				$this->_log("Not Phoenix2-relevant tag skipped.", 1, $affected_node=$input_node->nodeName);
				// unknown tags
				if (!in_array($input_node->nodeName, $this->_unknown_tags)) {
					$this->_unknown_tags[] = $input_node->nodeName;
				}
			}
		
		}
		
		// XML: do nothing but append the whole remaining node and do not pass it as new parent
		// insert node copy
		$cloned_node = $this->_copy_node($input_node, TRUE);
		
		return $cloned_node; // the parent node is not changed in this case
	} //_default_parse
	
	//+ 
	function _default_parse_textdescriptor ( $node )
	/*/
	Handles all text descriptors inside an an-node. It is 'additive': all node names are added 
	to the database if they don't exist yet.
	---
	@param node: the node to be parsed
	@type  node: DOMNode
	/*/
	{
		// ENTITY: Link text to descriptors in the database
		$tag = $node->nodeName;
		$value = $node->nodeValue;
		$value = trim($value); //strip whitespace from the beginning and end of the value
		// add xml-tag to DESCRIPTOR
		$tag_id = $this->_cached_dao_DESCRIPTOR->checkAdd(array('XMLTagName' => $tag));
		// add value and tag_id to TEXT_DESCRIPTOR
		$new_entry = array('TextID' => $this->_created_text_entity->getID(), 'DescriptorID' => $tag_id, 'Value' => $value);
		if ($this->_mode == 'update') {
			$this->_import_db_entries['TEXT_DESCRIPTOR'][] = $new_entry;
		} else {
			$this->_cached_dao_TEXT_DESCRIPTOR->insert($new_entry, TRUE);
		}
	} //_default_parse_textdescriptor
	
	//+ 
	function _default_parse_textsection ( $node )
	/*/
	Handles all textsections. They are static: only nodes named after entries provided in the 
	TEXTSECTION table are considered. E.g. 'int.s'/'int.e' is considered if 'int' exists as a 
	TEXTSECTION.
	---
	@param node: the node to be parsed
	@type  node: DOMNode
	/*/
	{
		$tag = $node->nodeName;
		
		// make the section marker active for (int, exp, ... = milestones)
		if (in_array($tag, $this->_STATIC_textsection_starters)) {
			$this->__sectionIDs[0] = array_search($tag, $this->_STATIC_textsection_starters);
		}
		
		// XML: insert section tag
		$new_root = $this->_output_xml->createElement($tag);
		
		// recur with the children of $input_node
		$this->_parse_loop($node, $new_root);
		
		return $new_root;
		
	} //_default_parse_textsection
	
	//+ 
	protected function _copy_node ( $input_node , $include_children=FALSE )
	/*/
	Takes a DOMNode and creates a copy based in the output_xml.
	---
	@param input_node: the node to be copied
	@type  input_node: DOMNode
	@param include_children: whether to recursively include all children or just the node 
	itself
	@type  include_children: bool
	-
	@return: the copy the input node
	@rtype:  DOMNode
	/*/
	{
		return $this->_output_xml->importNode($input_node, $include_children);
	} //_copy_node
	
	//+ 
	protected function _parse_gl ( $input_node )
	/*/
	parser for gl-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// Remove the default namespace (and re-add it later)
		// this is a stupid hack required as PHP DOM is a wicked library
		$input_node->removeAttributeNS($input_node->getAttributeNode("xmlns")->nodeValue,"");
		
		$zitf = $input_node->getAttribute('zitf');
		
		// ENTITY: create Text
		if ($this->_mode == 'update') {
			// get the TextID from the checkout_id
			$checkout_id = $input_node->getAttribute('checkout_id');
			$dao = new Table('CHECKOUT');
			$rows = $dao->get( array( 'Identifier' => $checkout_id ) );
			$text_id = (int)$rows[0]['TextID'];
			$this->_created_text_entity = new Text($text_id); // load the existing text from the DB
		} else {
			$this->_created_text_entity = new Text($zitf, $this->text_corpusID);
		}
		
		// XML: insert <gl> (= root tag)
		$new_root = $this->_output_xml->createElement('gl');
		
		// Add the CiteID (zitf) as an attribute to the new root
		$new_root->setAttribute('zitf', $zitf);
		
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_gl
	
	//+ 
	protected function _parse_an ( $input_node )
	/*/
	parser for an-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: insert <an> (= root tag for a text metasection)
		$new_root = $this->_output_xml->createElement('an');
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_an
	
	//+ 
	protected function _parse_txt ( $input_node )
	/*/
	parser for txt-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: insert <txt> (= root tag for the actual text part)
		$new_root = $this->_output_xml->createElement('txt');
		
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_txt
	
	//+ 
	protected function _parse_div ( $input_node )
	/*/
	parser for div-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		$this->__div = $input_node->getAttribute('n');
		
		// XML: copy <div>
		$new_root = $this->_copy_node($input_node, FALSE);
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_div
	
	//+ 
	protected function _parse_lat ( $input_node )
	/*/
	parser for lat-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: copy <lat>
		$new_root = $this->_copy_node($input_node, FALSE);
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_lat
	
	//+ 
	protected function _parse_vid ( $input_node )
	/*/
	parser for vid-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: copy <vid>
		$new_root = $this->_copy_node($input_node, FALSE);
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_vid
	
	//+ 
	protected function _parse_token ( $input_node )
	/*/
	parser for token-tag
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// SURFACE ADJUSTMENT: ][-Parantheses -> Masking, e.g. abr[eviation => abr[eviation] (includes following words)
		#$token_surface = $input_node->textContent; //changed on 2012-10-04: inner tags get lost in this way; do not strip tags inside
		$occ_surface = $input_node->textContent; // for the new token in the database
		$token_surface = $this->_getTextContentOfNode($input_node, FALSE); // for the new XML node
		$left_bracket = '';
		$right_bracket = '';
		// opening bracket
		if (preg_match("/\[/", $token_surface)) {
			$this->_paranthese_open = TRUE;
		} else {
			if ($this->_paranthese_open) {
				$left_bracket = '[';
			}
		}
		// closing bracket
		if (preg_match("/\]/", $token_surface)) {
			$this->_paranthese_open = FALSE;
		} else {
			if ($this->_paranthese_open) {
				$right_bracket = ']';
			}
		}

		$input_node->nodeValue = $left_bracket . $token_surface . $right_bracket;
		$occ_surface = $left_bracket . $occ_surface . $right_bracket;
		#echo($input_node->nodeValue ."<br/>\n");

		// ENTITY: create Occurrence
		$occ_order = $this->_getNextTokenOrderNumber();
		$occ_surface = trim($occ_surface); // so trailing whitespaces and linebreaks are deleted
		$occ_type = $input_node->getAttribute('type');
		$token_id = checkAddToken($occ_surface, $occ_type, $this->_cached_dao_TOKEN, $this->_cached_dao_TOKENTYPE);

		if ($this->_mode == 'update') {
			$annotations = array();
			$annotations['Order'] = $occ_order;
			$annotations['Lemma_like_object'] = array();
			$annotations['Morph'] = array();
			// store the values for the new occurrences to be created later on
			$this->_import_db_entries['Occurrences'][] = array( 'TokenID' => $token_id, 'TextID' => $this->_created_text_entity->getID(), 'Order' => $occ_order, 'Div' => $this->__div, 'SectionIDs' => $this->__sectionIDs );
			// iterate over attributes of token
			for ($i = $input_node->attributes->length - 1; $i >= 0; --$i) {
				switch ($input_node->attributes->item($i)->nodeName) {
					// token number (and mapping)
					case 'n':
						// map the old occurrence number to the new occurrence number
						$old_occ_order = $input_node->attributes->item($i)->nodeValue;
						$this->_import_token_order_mappings[$old_occ_order] = $occ_order;
						break;
					case 'type':
						break;
					// morphology (all other attributes)
					default:
						$annotations['Morph'][ $input_node->attributes->item($i)->nodeName ] = $input_node->attributes->item($i)->nodeValue;
						$input_node->removeAttributeNode($input_node->attributes->item($i)); // delete the attribute (conversion to STORAGE format)
						break;
				}
			}
			$this->_import_db_entries['Annotations'][] = $annotations;

		} else {
			// write to db
			$occ = new Occurrence($token_id, $this->_created_text_entity->getID(), $occ_order, $this->__div, NULL, $this->__sectionIDs);
		}

		// XML: copy <token> and adjust its attributes
		$new_token_node = $this->_copy_node($input_node, TRUE);

		// XMLSchemaMigration: Save old word number(s) in Migration Table for Occurrences (mig_OCCURRENCE)
		if ($new_token_node->getAttribute('oldn')) {
			// if the token has an old word number parameter, add all of its parts to the translation table
			$old_word_numbers = preg_split('/\,/', $new_token_node->getAttribute('oldn'));
			foreach($old_word_numbers as $o) {
				$this->_cached_dao_mig_OCCURRENCE->insert( array( 'wn' => $o, 'OccurrenceID' => $occ->getID()) );
			}
			$new_token_node->removeAttribute('oldn');
		}

		// the new Occurrence order number inside the document
		$new_token_node->setAttribute('n', $occ_order);

		return $new_token_node;

	} //_parse_token

	//+
	protected function _parse_fue ( $input_node )
	/*/
	parser for fue-tag. The comment will be appended to the Occurrence proceeding it.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// ENTITY: write comment to preceeding Occurrence
		/*
		if ($this->__token_counter > 0) {
			$occ = new Occurrence($this->__token_counter);
			$occ->setComment($input_node->textContent);
		}
		*/

		// XML: copy <fue>
		$new_root = $this->_copy_node($input_node, FALSE);
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);

		return $new_root;
	} //_parse_fue

	// PRIVATE FUNCTIONS
	// -----------------

}

?>