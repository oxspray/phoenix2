<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: XML Migration Parser
Framework File Signature: com.ph2.framework.php.entities.z_xmlmigrationparser
Description:
Class for Migrating and Parsing XML documents (old format) and import their content to the 
Phoenix2 relational database schema.
---
/*/

//+
class XMLMigrationParser extends XMLTextParser
{
	// INSTANCE VARS
	// -------------
	protected $_zitf; /*/
	the CiteID of the text to be appended to the root node in the new 
	format
	/*/
	protected $_prev_node; /// the previously parsed DOM Node (= direct preceeding sibling)
	protected $_current_num; /*/
	the currently active DOM Node of a number conversion (i.e. reordering 
	of wn- and token[numpart]-tags to a signle token[num] node)
	/*/
	protected $_tokenizer; /*/
	the Tokenizer to use for tokenization (if constroctor->tokenize == 
	TRUE)
	/*/
	
	// CONSTRUCTOR
	// -----------
	//+ 
	function __construct ( $input_xml=NULL , $xsd_path=NULL , $convert_punctuation=FALSE , $tokenize=FALSE )
	/*/
	---
	@param input_xml: the xml string that should be parsed
	@type  input_xml: string
	@param xsd_path: the path pointing to the xsd file that the input_xml should be validated 
	against.
	@type  xsd_path: string
	@param convert_punctuation: if true, the parser searches for unbound punctuation marks 
	(see function body for details) and converts them into a token tag with type=punct.
	@type  convert_punctuation: bool
	@param tokenize: Whether to automatically tokenize the input_xml
	@type  tokenize: bool
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
			$this->_xsd_path = PH2_WP_RSC . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR . 'entry.xsd';
		}
		// initialize tokenizer
		if ($tokenize) {
			$this->_tokenizer = new XMLTextTokenizer();
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
		// if an input xml string is provided, start the parsing immediately
		if ($input_xml) $this->parse();
	} //__construct
	
	// PUBLIC FUNCTIONS
	// ----------------
	//+ 
	function parse ( )
	/*/
	This migration parser does not automatically write the converted XML document to the 
	filesystem. Instead, it returns the XML DOM representation of the output XML.
	-
	@return: the output_xml DOM representation
	@rtype:  DOMDocument
	/*/
	{
		// convert xml input string to DOMDocument
		$dom_input_xml = $this->_prepare_input_xml();
		
		// parse the xml
		$this->_parse_loop($dom_input_xml, $this->_output_xml);
		
		// write warning to log if brackets are unbalanced inside the text section
		if ($this->_paranthese_open) {
			$this->_log('Unbalanced parantheses in textsection detected.', '2');			
			echo "Unbalanced parantheses<br />";
		}
		
		// add a temporary namespace to the root node
		$this->_output_xml->documentElement->setAttribute('xmlns', 'http://www.rose.uzh.ch/phoenix/schema/transform');
		
		// add the zitf attribute to the root node
		$this->_output_xml->documentElement->setAttribute('zitf', $this->_zitf);
		
		// return migrated XML DOM representation
		return $this->_output_xml;
		
	} //parse
	
	//+ 
	protected function _parse_loop ( $input_node , $root )
	/*/
	Identical to superclass; only for redirecting the recursive routine.
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
				
				$parsed_child = $this->{$parser_name}($child);
				if ($parsed_child) {
					/* if the parser returns a node, append it (not the case if the node is merged, 
					/* e.g. into a new number) */
					$parsed_child = $root->appendChild($parsed_child);
				}
			} else if (method_exists($super, $parser_name)) {
								
				// THIS IS THE CLUE: if the method isn't present in this class (= extension), use the parent class (= default parser)
				$parsed_child = $root->appendChild($super->{$parser_name}($child));
			
			} else if ( in_array($child->nodeName, $this->_STATIC_textsection_starters) ) {
				// MILESTONES: int, exp, ...	
					
					$root->appendChild($this->_default_parse_textsection($child));
			
			} else {
				
				$parsed_child = $root->appendChild($this->_default_parse($child));
			}
			// NUMBER CHECKING
			$this->_checkNumber($parsed_child);
		}
		
	} //_parse_loop
	
	//+ 
	protected function _default_parse ( $input_node )
	/*/
	Identical to superclass; only for redirecting the recursive routine.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		if ($input_node->nodeName != "#text") {
			
			// #EV DELETE?
			switch ($input_node->parentNode->nodeName) {
				case 'an': 
					$this->_default_parse_textdescriptor($input_node);
					$is_known = TRUE;
				break;
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
	SKIP DB
	---
	@param node: the node to be parsed
	@type  node: DOMNode
	/*/
	{
		return;
	} //_default_parse_textdescriptor
	
	//+ 
	function _default_parse_textsection ( $node )
	/*/
	SKIP DB
	---
	@param node: the node to be parsed
	@type  node: DOMNode
	/*/
	{
		// XML: insert <gl> (= root tag)
		$new_root = $this->_output_xml->createElement( $node->nodeName );
		
		// recur with the children of $input_node
		$this->_parse_loop($node, $new_root);
		
		return $new_root;
		
	} //_default_parse_textsection
	
	//+ 
	protected function _parse_gl ( $input_node )
	/*/
	SKIP DB
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
		
		// XML: insert <gl> (= root tag)
		$new_root = $this->_output_xml->createElement('gl');
		
		// recur with the children of $input_node
		$this->_parse_loop($input_node, $new_root);
		
		return $new_root;
	} //_parse_gl
	
	//+ 
	protected function _parse_txt ( $input_node )
	/*/
	REMOVE ASTERISK, TOKENIZE INTERPUNCTION (NOT REPAIR YET)
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
		
		// Replacements on Text Surface (Migration; inside <txt>)
		$temp_doc = simplexml_import_dom($input_node);
		$temp_string = $temp_doc->asXML();
		
		// Tokenize textsection if set in the constructor
		if($this->_tokenizer) {
			$temp_string = $this->_tokenizer->tokenize($temp_string);
		}
		
		// Remove *-Signs (Asterisk)
		$temp_string = preg_replace('/\*/', '', $temp_string);
		
		// Various migration replacements
		$pre_migration_replace_rules = array (
			"<\/wn>\.\,\/\/\." => ".</wn>,//.",
			"<\/wn>\.\;\/\/\." => ".</wn>;//.",
			"<\/wn>\/\.\," => "/.</wn>,",
			"<\/wn>\/\.\;" => "/.</wn>;",
			"\, \/\." => ",//.",
			'<\/wn>\/\.\|<wn n="\d+">(.+?)<\/wn>' => "/.$1</wn>?"
		);
		foreach ($pre_migration_replace_rules as $original => $replacement) {
			$temp_string = preg_replace('/'.$original.'/', $replacement, $temp_string);
		}
		
		// Tokenize Punctuation
		$punctuation_pattern = '/\/(?!(abr|maj))(\w*)>(\s*)([\/\.\,\'\-\:\;\?\|]+)(\s*)/';
		$punctuation_replacement = "/$2>\n<token n=\"\" type=\"punct\">$4</token>";
		// 3 times; match 3 subsequent punctuations
		$temp_string = preg_replace($punctuation_pattern, $punctuation_replacement, $temp_string);
		$temp_string = preg_replace($punctuation_pattern, $punctuation_replacement, $temp_string);
		$temp_string = preg_replace($punctuation_pattern, $punctuation_replacement, $temp_string);
		/*
		** surrounds any n-length sequence of the following characters in
		** <token type="punct">...</token>:
		** (whitespace) / . , ' - ;
		** PREVIOUS VERSION: '/>(\s*)([\/\.\,\'\-\:\;\?\|]+)(\s*)<(?!\/(fue|ful|abr|maj))/', ...
		*/
		
		// Remove line breaks
		$temp_string = preg_replace('/[\r\n\t]/', '', $temp_string);
		
		// Remove multiple whitespaces beteween tags
		$temp_string = preg_replace('/>\s+</', "> <", $temp_string);
		
		$new_dom = new DOMDocument();
		$new_dom->loadXML($temp_string);
		// select new element as DOMElement
		$new_elem = $new_dom->getElementsByTagName('txt')->item(0);
		
		// recur with the children of $input_node
		$this->_parse_loop($new_elem, $new_root);
		
		return $new_root;
	} //_parse_txt
	
	//+ 
	protected function _parse_id ( $input_node )
	/*/
	Remove the id-tag as it is not part of the STORAGE XSD
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// ommit the id node for the new format
		return;
		
	} //_parse_id
	
	//+ 
	protected function _parse_zitf ( $input_node )
	/*/
	Remove the zitf-tag and add its content as an attribute to the root node (gl)
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// the value of this node is added as an attribute to the root node (gl), according to the STORAGE XSD
		$zitf = $input_node->textContent;
		$this->_zitf = trim($zitf);
		
		// ommit the zitf node for the new format
		return;
		
	} //_parse_zitf
	
	//+ 
	protected function _parse_token ( $input_node )
	/*/
	Only punctuation is represented as token-tags so far. Correct wrong interpunctuation here.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: copy <token>
		$new_token_node = $this->_copy_node($input_node, TRUE);
		
		if ($input_node->getAttribute('type') == 'punct') {
			// Auto-correct wrong interpunctuation
			$interpunctuation_replace_rules = array (
				"\?+" => "?", // ???? -> ?
				"(\.\.\.+)" => "[$1]", // ..... -> [.....]
				"\,\."  => ",//.",
				"\.\,"  => ".//,",
				"\.\;"  => ".//;",
				"\/\.\." => "/./.",
				"\,\/\/\.\|" => ",//.?",
				"\/\;\," => ",?"
			);
			foreach ($interpunctuation_replace_rules as $original => $replacement) {
				$new_token_node->nodeValue = preg_replace('/'.$original.'/', $replacement, $new_token_node->textContent);
			}
		}
		
		return $new_token_node;
	} //_parse_token
	
	//+ 
	protected function _parse_wn ( $input_node )
	/*/
	CONVERT TAG NAME wn->token, MARK ][-PARANTHESES (TEXT SURFACE)
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		// XML: translate tag and append it
		// rename node
		// note: this is a very ugly workaround as the PHP DOM extension does not support the renameNode()-method for DOMDocuments yet
		$token_surface = $this->_getTextContentOfNode($input_node, TRUE);
		$token_xml_string = $this->_getTextContentOfNode($input_node, FALSE);
		
		// create new dom node with old wn content
		$new_node_xml_string  = '<token n="" type="occ">';
		$new_node_xml_string .= $token_xml_string;
		$new_node_xml_string .= '</token>';
		$new_dom = new DOMDocument();
		$new_dom->loadXML($new_node_xml_string);
		// select new element as DOMElement
		$new_elem = $new_dom->getElementsByTagName('token')->item(0);		
		
		// extraction of old word number
		if ($input_node->getAttribute('n')) {
			$new_elem->setAttribute('oldn', $input_node->getAttribute('n'));
		}
		
		//append new token node to document
		$new_token_node = $this->_output_xml->importNode($new_elem, TRUE);
		
		unset($new_dom);
		return $new_token_node;
	} //_parse_wn
	
	//+ 
	protected function _parse_fue ( $input_node )
	/*/
	parser for fue-tag. Subsequent whitespaces will be deleted.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		return $this->_copyTrimNode($input_node, 'fue');		
	} //_parse_fue
	
	//+ 
	protected function _parse_ful ( $input_node )
	/*/
	parser for ful-tag. Subsequent whitespaces will be deleted.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	-
	@return: the result of the parsing (new node)
	@rtype:  DOMNode
	/*/
	{
		return $this->_copyTrimNode($input_node, 'ful');
	} //_parse_ful
	
	//+ 
	protected function _copyTrimNode ( $input_node , $new_node_name )
	/*/
	returns a copy of the node with all 2+ whitespaces merged to 1 and without trailing 
	whitespaces, tabs etc.
	---
	@param input_node: the node to be parsed
	@type  input_node: DOMNode
	@param new_node_name: the name of the new node. caution: the old nodeName will be 
	replaced!
	@type  new_node_name: string
	-
	@return: the new node without the whitespaces
	@rtype:  DOMNode
	/*/
	{
		//get the text content of the fue-node
		$text_content = $this->_getTextContentOfNode($input_node, FALSE);
		// replace 2+ whitespaces by 1
		$text_content = trim(preg_replace("/\s+/", " ", $text_content));
		
		//append new token node to document
		$new_dom = new DOMDocument();
		$new_dom->loadXML("<$new_node_name>" . $text_content . "</$new_node_name>");
		$new_node = $new_dom->getElementsByTagName($new_node_name)->item(0);
		$new_node = $this->_output_xml->importNode($new_node, TRUE);
		unset($new_dom);
		return $new_node;
	} //_copyTrimNode
	
	//+ 
	protected function _isNumberSurface ( $string )
	/*/
	Takes a string and checks if it is a roman number
	---
	@param string: The string to be checked
	@type  string: string
	-
	@return: TRUE if the string is a number; FALSE otherwise.
	@rtype:  bool
	/*/
	{
		if (empty($string)) {
			return FALSE;
		}
		
		preg_match('/([IVXLCDM]+[IVXLCDM\.\|\/\,\;\?\-\:\']*|[IVXLCDM\.\|\/\,\;\?\-\:\']*[IVXLCDM]+)/', $string, $match);
		if ($match[0] == $string) {
			return TRUE;
		} else {
			return FALSE;	
		}
	} //_isNumberSurface
	
	//+ 
	protected function _isNumberInterpunctuation ( $string )
	/*/
	Takes a string and checks if it is a punctuation sequence that can start or terminate 
	numbers
	---
	@param string: The string to be checked
	@type  string: string
	-
	@return: TRUE if the string is a matching punctuation sequence; FALSE otherwise.
	@rtype:  bool
	/*/
	{
		if (empty($string)) {
			return FALSE;
		}
		
		preg_match('/[\.\/]+/', $string, $match);
		if ($match[0] == $string) {
			return TRUE;
		} else {
			return FALSE;	
		}
	} //_isNumberInterpunctuation
	
	//+ 
	protected function _mergeInto ( $node_to_be_merged , $target_node )
	/*/
	Merges a subject DOM Node into a target DOM Node.
	The Structure of the target node is thouroughly preserved; only its text content is 
	appended the whole text content of the subject node.
	---
	@param node_to_be_merged: The node to be merged into $target_node
	@type  node_to_be_merged: DOMNode
	@param target_node: The node whose text content will be appended the text content of 
	$node_to_be_merged
	@type  target_node: DOMNode
	-
	@return: the target node (that $node_to_be_merged has been merged into)
	@rtype:  DOMNode
	/*/
	{
		//append the text (before original content!)
		$existing_text = $target_node->nodeValue;
		$new_text = $node_to_be_merged->nodeValue;
		$existing_text_element = $this->_output_xml->createTextNode($existing_text);
		$new_text_element = $this->_output_xml->createTextNode($new_text);
		
		$target_node->nodeValue = '';
		$target_node->appendChild($new_text_element);
		$target_node->appendChild($existing_text_element);
		
		//append old word number if applicable
		if ($node_to_be_merged->getAttribute('oldn')) {
			if ($target_node->getAttribute('oldn')) {
				$existing_oldn = $target_node->getAttribute('oldn');
				$target_node->setAttribute('oldn', $node_to_be_merged->getAttribute('oldn') . ',' . $existing_oldn);
			} else {
				$target_node->setAttribute('oldn', $node_to_be_merged->getAttribute('oldn'));
			}
		}
		
		// delete the merged node
		$node_to_be_merged->parentNode->removeChild($node_to_be_merged);
		
		return $target_node;
		
	} //_mergeInto
	
	//+ 
	protected function _getNewNumNode ( )
	/*/
	Returns a new node of type token[num] with an empty text content
	-
	@return: the new number node
	@rtype:  DOMNode
	/*/
	{
		$new_number_node = $this->_output_xml->createElement('token');
		$new_number_node->setAttribute('n', '');
		$new_number_node->setAttribute('type', 'num');
		
		return $new_number_node;
	} //_getNewNumNode
	
	//+ 
	protected function _checkNumber ( $candidate_node )
	/*/
	Takes a node and checks whether it is an end point of a number. If so, a number conversion 
	routine is invoked.
	---
	@param candidate_node: The node to be checked
	@type  candidate_node: DOMNode
	/*/
	{
		if ($candidate_node->nodeName == 'token' && $candidate_node->getAttribute('type') == 'punct') {
			if ($this->_isNumberInterpunctuation($candidate_node->nodeValue)) {
				if ($candidate_node->previousSibling) {
					$preceding_node = $candidate_node->previousSibling;
					if ($this->_isNumberSurface($preceding_node->nodeValue)) {
						// NUMBER INVOCATION: number occurrence with preceding number surface occurrence found
						$new_number_node = $preceding_node->parentNode->insertBefore($this->_getNewNumNode(), $preceding_node);
						$new_number_node = $this->_mergeInto($candidate_node, $new_number_node);
						$new_number_node = $this->_mergeInto($preceding_node, $new_number_node);
						$this->_checkNumberLookback($new_number_node);
						return TRUE;
					}
				} else {
					return FALSE;
				}
			}
		}
	} //_checkNumber
	
	//+ 
	protected function _checkNumberLookback ( $candidate_number_node )
	/*/
	Takes a number node and checks whether it is preceeded by other number or interpunctuation 
	fragments that make up a whole number
	---
	@param candidate_number_node: The number node (token[num]) to be checked
	@type  candidate_number_node: DOMNode
	/*/
	{
		// check for preceeding number fragments
		$new_candidate_number_node = $this->_checkNumberLookbackLoop($candidate_number_node);
		
		// check for preceding interpunctuation
		if ($candidate_number_node->previousSibling && $candidate_number_node->previousSibling->nodeName != '#text') {
			$previous_node = $candidate_number_node->previousSibling;
			// attributes of preceding sibling
			$preceeding_type = $previous_node->nodeName;
			$preceeding_subtype = $previous_node->getAttribute('type');
			$preceding_has_num_interpunctuation = $this->_isNumberInterpunctuation($previous_node->nodeValue);
			if ($preceeding_type == 'token' && $preceeding_subtype == 'punct' && $preceding_has_num_interpunctuation) {
				$new_candidate_number_node = $this->_mergeInto($candidate_number_node->previousSibling, $candidate_number_node);
			}
		}
		
		// check for preceding number
		if ($candidate_number_node->previousSibling && $candidate_number_node->previousSibling->nodeName != '#text') {
			$previous_node = $candidate_number_node->previousSibling;
			// attributes of preceding sibling
			$preceeding_type = $previous_node->nodeName;
			$preceeding_subtype = $previous_node->getAttribute('type');
			if ($preceeding_type == 'token' && $preceeding_subtype == 'num') {
				$new_candidate_number_node = $this->_mergeInto($candidate_number_node->previousSibling, $candidate_number_node);
			}
		}
		
		
	} //_checkNumberLookback
	
	//+ 
	protected function _checkNumberLookbackLoop ( $candidate_number_node )
	/*/
	Recursively looks for token[occ] number fragments preceeding an existing token[num] 
	element
	---
	@param candidate_number_node: The number node to check for preceeding fragments
	@type  candidate_number_node: DOMNode
	-
	@return: The final node with no preceeding number fragments (excluding interpunctuation)
	@rtype:  DOMNode
	/*/
	{
		// check type of preceding occurrences
		if ($candidate_number_node->previousSibling && $candidate_number_node->previousSibling->nodeName != '#text') {
			$previous_node = $candidate_number_node->previousSibling;
			// attributes of preceding sibling
			$preceeding_type = $previous_node->nodeName;
			$preceeding_subtype = $previous_node->getAttribute('type');
			$preceding_has_num_surface = $this->_isNumberSurface($previous_node->nodeValue);
			
			if ($preceeding_type == 'token' && $preceeding_subtype == 'occ' && $preceding_has_num_surface) {
				$candidate_number_node = $this->_mergeInto($candidate_number_node->previousSibling, $candidate_number_node);
				// recur
				return $this->_checkNumberLookbackLoop($candidate_number_node);
			} else {
				return $candidate_number_node;
			}
		}
	} //_checkNumberLookbackLoop
	
	// PRIVATE FUNCTIONS
	// -----------------
	
}

?>