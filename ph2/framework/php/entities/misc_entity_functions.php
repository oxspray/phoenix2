<?php
/*/
Phoenix2
Version 0.7 alpha, Build 11
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Miscellaneus Entity Functions
Framework File Signature: com.ph2.framework.php.entities.misc_entity_functions
Description:
handles functions for entities that are not assigned a dedicated class
---
/*/

//+ 
function getTextsections ( )
/*/
returns all Descriptors stored in the DESCRIPTION table
-
@return: array(DescriptorID => Name)
@rtype:  array
/*/
{
	$TEXTSECTION = new Table('TEXTSECTION');
	$rows = $TEXTSECTION->get();
	$sections = array();
	foreach ($rows as $section) {
		$sections[$section['TextsectionID']] = $section['XMLTagName'];
	}
	
	return $sections;
} //getTextsections

//+ 
function checkAddToken ( $surface , $type , $token_dao=NULL , $tokentype_dao=NULL )
/*/
takes a surface and type string and checks whether a corresponding entity allready exists 
in TOKEN / TOKENTYPE. If not so, it is created. After all, the function returns the 
corresponding TokenID.
---
@param surface: None
@type  surface: string
@param type: None
@type  type: string
@param token_dao: dao with enabled caching
@type  token_dao: Table
@param tokentype_dao: dao with enabled caching
@type  tokentype_dao: Table
-
@return: the TokenID referencing the TOKEN table
@rtype:  int
/*/
{
	// check / prepare daos
	if ($token_dao) {
		assert(get_class($token_dao) == 'Table');
	} else {
		$token_dao = new Table('TOKEN');
	}
	if ($tokentype_dao) {
		assert(get_class($tokentype_dao) == 'Table');
	} else {
		$tokentype_dao = new Table('TOKENTYPE');
	}
	
	// remove trailing whitespaces on surface
	$occ_text = trim($occ_text);
	
	// add token and get id
	$tokentype_id = $tokentype_dao->checkAdd(array('Name' => $type));
	$token_id = $token_dao->checkAdd(array('Surface' => $surface, 'TokentypeID' => $tokentype_id));
	
	return $token_id;
} //checkAddToken

//+ 
function findOccurrences ( $corpus_list , $query=NULL , $has_lemma=NULL , $not_lemma=NULL , $has_graph=NULL , $not_graph=NULL , $has_type=NULL , $not_type=NULL )
/*/
queries a given set of corpora for occurrences that match against various criteria (see 
parameters).
---
@param corpus_list: The IDs of the corpora to include in the search
@type  corpus_list: array(int)
@param query: the REGEX query that constraints the occurrence surface
@type  query: string
@param has_lemma: the LemmaIDs that the occurrences MUST be connected to
@type  has_lemma: array(int)
@param not_lemma: the LemmaIDs that the occurrences MUST NOT be connected to
@type  not_lemma: array(int)
@param has_graph: the GraphIDs that the occurrences MUST be connected to
@type  has_graph: array(int)
@param not_graph: the GraphIDs that the occurrences MUST NOT be connected to
@type  not_graph: array(int)
@param has_type: the TokenIDs that the occurrences MUST be connected to. Note: has_type 
refers to the table/entity TOKEN.
@type  has_type: array(int)
@param not_type: the TokenIDs that the occurrences MUST NOT be connected to. Note: 
has_type refers to the table/entity TOKEN.
@type  not_type: array(int)
-
@return: the OccurrenceIDs of occurrences that match all given criteria
@rtype:  array(int)
/*/
{
	/* **************************************************************** **
	** THIS ROUTINE IS COMPLEX AND REQUIRES BULLETPROOF FURTHER TESTING ** #TODO
	** **************************************************************** */
	
	$matching_occ_ids = array(); // the array containing all matching OccurrenceIDs
	
	// STEP 1: Get all OccurrenceIDs from the database where
	// 		   a) the connected CorpusID (via TEXT) is in $corpus_list
	//		   b) the query matches for the occurrence surface
	$dao_step1 = new Table('OCCURRENCE');
	$dao_step1->select = "OccurrenceID";
	$dao_step1->from = "OCCURRENCE natural join TEXT natural join TOKEN join TOKENTYPE on TOKEN.TokentypeID=TOKENTYPE.TokentypeID";
	$dao_step1->where = 'TOKENTYPE.Name="occ" AND CorpusID IN (' . expandArray($corpus_list, ', ') . ')';
	$dao_step1->orderby = "`Surface` COLLATE utf8_unicode_ci ASC, `TextID` ASC, `Div` ASC, `Order` ASC";
	if ($query) {
		$dao_step1->where .= " AND Surface REGEXP '$query'";
	}
	
	foreach ($dao_step1->get() as $row) {
		$matching_occ_ids[] = $row['OccurrenceID'];
	}
	
	unset($dao_step1);
	
	// STEP 2: Filters
	//		   For each filter list that is not empty, $matching_occ_ids is checked against it
	
	// HAS LEMMA
	if ($has_lemma && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array(); // the result of the comparison		
		// example query: select OccurrenceID from LEMMA_OCCURRENCE where LemmaID in (submitted lemma ids)
		$dao_has_lemma = new Table('LEMMA_OCCURRENCE');
		$dao_has_lemma->select = "OccurrenceID, LemmaID";
		$dao_has_lemma->where = 'LemmaID IN (' . expandArray($has_lemma, ', ') . ')';
		// get OccurrenceIDs of selected Lemmata
		// PRECONDITION: (LemmaID, OccurrenceID) is unique in LEMMA_OCCURRENCE
		// create new fancy structure: $has_lemma_occ_ids = array(asssgned_lemma-1, ... assigned_lemma-n)
		$has_lemma_occ_ids = array();
		foreach ($dao_has_lemma->get() as $row) {
			if (!isset($has_lemma_occ_ids[$row['OccurrenceID']])) {
				$has_lemma_occ_ids[$row['OccurrenceID']] = array();
			}
			$has_lemma_occ_ids[$row['OccurrenceID']][] = $row['LemmaID'];
		}
		// compare: notice AND concatenation of has-filter restrictions
		// for each lemma given in $has_lemma, check if it is part of the REGEX result from STEP 1
		// in order to satisfy the filter criteria, a candidate Occurrence must have an assignment to ALL Lemmata specified in $has_lemma
		foreach ($matching_occ_ids as $id) {
			if (array_key_exists($id, $has_lemma_occ_ids)) {
				// if the OccurrenceID is a candidate, check if there exists an entry for ALL lemmata of this filter
				$checker = TRUE;
				foreach ($has_lemma as $lemma_id) {
					if (!in_array($lemma_id, $has_lemma_occ_ids[$id])) {
						$checker = FALSE;
					}
				}
				if ($checker) {
					$new_matching_occ_ids[] = $id;
				} else {
					// the filter does only partially apply (satisfies OR- but not AND-concatenation), so drop this occurrence
				}
			} else {
				// the filter does not apply (satisfies neither OR nor AND-concatenation), so drop it
			}
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($new_matching_occ_ids, $has_lemma_occ_ids, $dao_has_lemma);
	}
	
	// HAS NOT LEMMA
	if ($not_lemma && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array();  // the result of the comparison
		// example query: select OccurrenceID from LEMMA_OCCURRENCE where LemmaID in (submitted lemma ids)
		$dao_not_lemma = new Table('LEMMA_OCCURRENCE');
		$dao_not_lemma->select = "OccurrenceID";
		$dao_not_lemma->where = 'LemmaID IN (' . expandArray($not_lemma, ', ') . ')';
		// get OccurrenceIDs of selected Lemmata
		$not_lemma_occ_ids = array();
		foreach ($dao_not_lemma->get() as $row) {
			$not_lemma_occ_ids[] = $row['OccurrenceID'];
		}
		// compare: if OccID is contained in list, drop it; keep it otherwise
		foreach ($matching_occ_ids as $id) {
			if (in_array($id, $not_lemma_occ_ids)) {
				// if an Occurrence ID is present in the List, drop it (=leave it out)
			} else {
				$new_matching_occ_ids[] = $id;
			}
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($dao_not_lemma, $not_lemma_occ_ids, $new_matching_occ_ids);
	}
	
	// HAS GRAPH
	if ($has_graph && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array();  // the result of the comparison
		// example query: select OccurrenceID from GRAPH_OCCURRENCE where GraphID in (submitted graph ids)
		$dao_has_graph = new Table('GRAPH_OCCURRENCE');
		$dao_has_graph->select = "OccurrenceID, GraphID";
		$dao_has_graph->where = 'GraphID IN (' . expandArray($has_graph, ', ') . ')';
		// get OccurrenceIDs of selected Graphs
		// PRECONDITION: (GraphID, OccurrenceID) is unique in GRAPH_OCCURRENCE
		// create new fancy structure: $has_graph_occ_ids = array(asssgned_graph-1, ... assigned_graph-n)
		$has_graph_occ_ids = array();
		foreach ($dao_has_graph->get() as $row) {
			if (!isset($has_graph_occ_ids[$row['OccurrenceID']])) {
				$has_graph_occ_ids[$row['OccurrenceID']] = array();
			}
			$has_graph_occ_ids[$row['OccurrenceID']][] = $row['GraphID'];
		}
		// compare: notice AND concatenation of has-filter restrictions
		// for each graph given in $has_graph, check if it is part of the REGEX result from STEP 1
		// in order to satisfy the filter criteria, a candidate Occurrence must have an assignment to ALL Graphs specified in $has_graph
		foreach ($matching_occ_ids as $id) {
			if (array_key_exists($id, $has_graph_occ_ids)) {
				// if the OccurrenceID is a candidate, check if there exists an entry for ALL lemmata of this filter
				$checker = TRUE;
				foreach ($has_graph as $graph_id) {
					if (!in_array($graph_id, $has_graph_occ_ids[$id])) {
						$checker = FALSE;
					}
				}
				if ($checker) {
					$new_matching_occ_ids[] = $id;
				} else {
					// the filter does only partially apply (satisfies OR- but not AND-concatenation), so drop this occurrence
				}
			} else {
				// the filter does not apply (satisfies neither OR nor AND-concatenation), so drop it
			}
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($dao_has_graph, $has_graph_occ_ids, $new_matching_occ_ids);
	}
	
	// HAS NOT GRAPH
	if ($not_graph && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array();  // the result of the comparison
		// example query: select OccurrenceID from GRAPH_OCCURRENCE where GraphID in (submitted graph ids)
		$dao_not_graph = new Table('GRAPH_OCCURRENCE');
		$dao_not_graph->select = "OccurrenceID";
		$dao_not_graph->where = 'GraphID IN (' . expandArray($not_graph, ', ') . ')';
		// get OccurrenceIDs of selected Graphs
		$not_graph_occ_ids = array();
		foreach ($dao_not_graph->get() as $row) {
			$not_graph_occ_ids[] = $row['OccurrenceID'];
		}
		// compare: if OccID is contained in list, drop it; keep it otherwise
		foreach ($matching_occ_ids as $id) {
			if (in_array($id, $not_graph_occ_ids)) {
				// if an Occurrence ID is present in the List, drop it (=leave it out)
			} else {
				$new_matching_occ_ids[] = $id;
			}
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($dao_not_graph, $not_graph_occ_ids, $new_matching_occ_ids);
	}
	
	// HAS TYPE
	// #NOTE: conjunctively implemented. Selecting multiple types yields ALL Occurrences matching either the given types.
	if ($has_type && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array(); // the result of the comparison		
		// example query: select OccurrenceID from OCCURRENCE where TokenID in (submitted has_type ids (TokenID))
		$dao_has_type = new Table('OCCURRENCE');
		$dao_has_type->select = "OccurrenceID";
		$dao_has_type->where  = 'TokenID IN (' . expandArray($has_type, ', ') . ')';
		$dao_has_type->where .= 'AND OccurrenceID IN (' . expandArray($matching_occ_ids, ', ') . ')';
		// get OccurrenceIDs that have the selected Type (Token)
		foreach ($dao_has_type->get() as $row) {
			$new_matching_occ_ids[] = $row['OccurrenceID'];
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($dao_has_type, $new_matching_occ_ids);
	}
	
	// HAS NOT TYPE
	if ($not_type && count($matching_occ_ids) > 0) {
		$new_matching_occ_ids = array(); // the result of the comparison		
		// example query: select OccurrenceID from OCCURRENCE where TokenID in (submitted has_type ids (TokenID))
		$dao_not_type = new Table('OCCURRENCE');
		$dao_not_type->select = "OccurrenceID";
		$dao_not_type->where  = 'TokenID NOT IN (' . expandArray($not_type, ', ') . ')';
		$dao_has_type->where .= 'AND OccurrenceID IN (' . expandArray($matching_occ_ids, ', ') . ')';
		// get OccurrenceIDs that have the selected Type (Token)
		foreach ($dao_not_type->get() as $row) {
			$new_matching_occ_ids[] = $row['OccurrenceID'];
		}
		// publish the result of this filter
		$matching_occ_ids = $new_matching_occ_ids;
		unset($dao_not_type, $new_matching_occ_ids);
	}
	
	return $matching_occ_ids;
	
} //findOccurrences

//+ 
function getOccurrenceContext ( $occurrence_id )
/*/
returns the left and right text context of an occurrence, as well as its metadata
---
@param occurrence_id: the ID of the Occurrence
@type  occurrence_id: int
-
@return: two sub-arrays containing 1) the context variables and 2) the meta variables of 
the given Occurrence
@rtype:  array
/*/
{
	$occ_id = $occurrence_id;
	
	$occ = new Occurrence( (int) $occ_id );
	$text = new Text( (int) $occ->getTextID() );
	$text_descriptors = $text->getTextDescriptors( array('d0', 'rd0', 'scripta', 'type') );
	// write meta section
	$d0_full = $text_descriptors['d0'];
	$d0 = substr($text_descriptors['d0'],0,4); // only return the first four digits of d0 (=year)
	
	$zitf_full = $text->getCiteID();
	if (strlen($zitf_full) > 8) {
		$zitf_short = substr($zitf_full,0,7) . '..';
	} else {
		$zitf_short = $zitf_full;
	}
	$zitf_short = str_replace(' ', '&nbsp;', $zitf_short); //make spaces non-braking
	
	$rd0_full = $text_descriptors['rd0'];
	if (strlen($rd0_full) > 8) {
		$rd0_short = substr($rd0_full,0,7) . '..';
	} else {
		$rd0_short = $rd0_full;
	}
	$rd0_short = str_replace(' ', '&nbsp;', $rd0_short); //make spaces non-braking
	
	$scripta = $text_descriptors['scripta'];
	
	// get lemma
	$lemma = $occ->getLemma();
	if ($lemma) {
		$lemma_morph = $lemma->getMorphAttributes();
		$lemma_morph_string = '';
		if ($lemma_morph) {
			while ( !empty($lemma_morph) ) {
				$lemma_morph_string .= " " . array_shift($lemma_morph);
			}
		}
	$lemma_string = $lemma->getIdentifier() . $lemma_morph_string;
	}
	
	$occ_matches_meta[] = array('zitfFull' => $zitf_full, 'zitfShort' => $zitf_short, 'd0' => $d0, 'd0Full' => $d0_full, 'rd0Full' => $rd0_full, 'rd0Short' => $rd0_short, 'rd0Full' => $rd0_full, 'scripta' => $scripta, 'divID' => $occ->getDiv(), 'occurrenceID' => $occ_id, 'textID' => $text->getID(), 'order' => $occ->getOrder(), 'lemma' => $lemma_string, 'lemma_pos' => $lemma_morph_string, 'type' => $text_descriptors['type']);
	// write match section (context line)
	$context = $occ->getContext();
	$left_context = $context[0];
	$right_context = $context[1];
	// adjust lines for even display
	$left_width = 220;
	$right_width = 225;
	for ($i=0; $i <= $left_width; $i++) {
		$left_context = ' ' . $left_context;
	}
	for ($i=0; $i <= $right_width; $i++) {
		$right_context .= ' ';
	}
	$left_context = mb_substr($left_context, -$left_width, $left_width, 'UTF-8');
	$right_context = mb_substr($right_context, 0, $right_width, 'UTF-8');
	$occ_matches[] = array('leftContext' => $left_context, 'surface' => $occ->getSurface(), 'rightContext' => $right_context, $occ_id);
	
	return array('meta' => $occ_matches_meta, 'match' => $occ_matches);
	
} //getOccurrenceContext

//+ 
function getTypes ( )
/*/
returns an array containing all Types (=Token entries) of any type.
-
@return: the Type ID, Surface and total number of associated Occurrences
@rtype:  array(array(TokenID, Surface, Count))
/*/
{
	$dao = new Table('OCCURRENCE');
	$dao->select="TokenID, Surface, count(*) as Count";
	$dao->from = "TOKEN natural join OCCURRENCE";
	$dao->groupby = "Surface COLLATE utf8_unicode_ci";
	
	return $dao->get();
} //getTypes

//+ 
function getOccTypes ( )
/*/
returns an array containing all Types (=Token entries) of type 'occ'.
-
@return: the Type ID, Surface and total number of associated Occurrences
@rtype:  array(array(TokenID, Surface, Count))
/*/
{
	$dao = new Table('OCCURRENCE');
	$dao->select="TokenID, Surface, count(*) as Count";
	$dao->from = "TOKEN natural join OCCURRENCE natural join TOKENTYPE";
	$dao->where = "Name='occ'";
	$dao->groupby = "Surface COLLATE utf8_unicode_ci";
	
	return $dao->get();
} //getOccTypes

//+ 
function getOccTypesDemo ( )
/*/
DEMO-SPECIFIC FOR PRESENTATION ON 2011-12-01
returns an array containing all Types (=Token entries) of type 'occ'.
-
@return: the Type ID, Surface and total number of associated Occurrences
@rtype:  array(array(TokenID, Surface, Count))
/*/
{
	$dao = new Table('OCCURRENCE');
	$dao->select="TokenID, Surface, count(*) as Count";
	$dao->from = "TOKEN natural join OCCURRENCE natural join TOKENTYPE";
	$dao->where = "Name='occ' AND Surface REGEXP '^(a|b|c|x|y|z)'";
	$dao->groupby = "Surface COLLATE utf8_unicode_ci";
	
	return $dao->get();
} //getOccTypesDemo

//+ 
function addTextFromXMLInput ( $xml , $corpus_id , $migrate , $tokenize )
/*/
Creates a new Text on the System, according to a given XML serialization of a text
---
@param xml: The XML representation of the Text
@type  xml: string
@param corpus_id: The ID of the Corpus the new Text should be assigned to
@type  corpus_id: int
@param migrate: Whether to migrate the XML into the new scheam via XMLMigraionParser
@type  migrate: bool
@param tokenize: Whether the txt section should be automatically tokenized
@type  tokenize: bool
/*/
{
	
	// ASSERTIONS
	if( $tokenize ) {
		$migrate = TRUE; // tokenization can only be done via XMLMigrationParser, so activate this option in case the text should be tokenized
	}
	
	// ROUTINE
	// check if no fields are empty
	if ($xml) {
		
		$input_xml = $xml;
		
		if( $migrate ) {
			
			if ($tokenize) {
				$convert_punctuation = FALSE;
			} else {
				$convert_punctuation = TRUE;
			}
			
			// migrate text into STORAGE XML schema if selected
			$mp = new XMLMigrationParser($input_xml,NULL,$convert_punctuation,$tokenize);
			$input_xml = $mp->getOutputXML();
			unset($mp);
		}
		
		// add text to the database and the filesystem
		$p = new XMLTextParser();
		$p->input_xml= $input_xml;
		$p->text_corpusID = (int)$corpus_id;
		
		// parse and notify
		if( $p->parse() ) {
			$status = TRUE;
		} else {
			$log = $p->getLog();
			$last_log_entry = array_pop($log);
			$status = $last_log_entry[0];
		}
		
		unset($p);
		
	} else {
		$status = 'Missing XML string or ZITF';
	}
	
	return $status;
	
	
} //addTextFromXMLInput

//+ 
function analyseXMLFile ( $filepath )
/*/
Analyses an XML file and checks (a) whether it is a corpus or a single text and (b) what 
format it is stored in, i.e., which XSD applies for validating it (ENTRY, STORAGE, EDIT). 
If the file validates against the according XSD, parameters (a) and (b) are returned; an 
error message (str) is returned otherwise.
---
@param filepath: The path to the file to be analysed
@type  filepath: string
-
@return: is_corpus => /0: single file, 1: corpus/, xsd_type = /entry, storage, edit/ OR 
error string
@rtype:  array(str)
/*/
{
	
	$type = '';
	$is_corpus = false;
	$entity_id = NULL; // only for EDIT formats; Null otherwise. Corresponds to the checked-out TextID or CorpusID.
	$timestamp_checkout = NULL; // only for EDIT formats; Null otherwise.
	
	$dom = new DOMDocument();
	@$dom->load($filepath);
	
	// check well-formedness
	if ( ! $dom ) {
		return 'The provided XML file is not well-formed.';
	}
	
	// figure out the type
	switch ($dom->documentElement->nodeName) {
		case 'gl': $is_corpus = FALSE;
			break;
		case 'corpus': $is_corpus = TRUE;
			break;
		default: return 'The root node of the document must be &lt;gl&gt; or &lt;corpus&gt;.';
	}
	
	// figure out the namespace (URI)
	switch ($dom->documentElement->namespaceURI) {
		case PH2_URI_ENTRY:
			$type = 'entry';
			$xsd = PH2_WP_RSC . '/xsd/entry';
			break;
		case PH2_URI_STORAGE:
			$type = 'storage';
			$xsd = PH2_WP_RSC . '/xsd/storage';
			break;
		case PH2_URI_EDIT:
			$type = 'edit';
			$xsd = PH2_WP_RSC . '/xsd/edit';
			break;
		default: return 'The document is missing a valid namespace definition (URI). One of the following must be provided: ' . PH2_URI_ENTRY . ', ' . PH2_URI_STORAGE . ', ' . PH2_URI_EDIT . '.';	
	}
	
	// validate
	if ($is_corpus) {
		$xsd .= '.xsd';
	} else {
		$xsd .= '.xsd';
	}
	
	libxml_use_internal_errors(true);
	if ($dom->schemaValidate($xsd)) {
		// pass
		// in case of EDIT format, check whether the checkout identifier (hash) is possible, i.e., whether the current file is the most recent checked-out version of this entity
		if ($type == 'edit') {
			// get the checkout identifier
			$checkout_id = $dom->documentElement->getAttribute('checkout_id');
			// check if checkout_id is valid
			if ( validateCheckoutIdentifier($checkout_id) == FALSE ) {
				return 'Only the latest version of a text or corpus exported for editing can be re-imported. This is an outdated version; please use the most recently checked-out file.';
			} else {
				$dao = new Table('CHECKOUT');
				$dao->select = "Identifier, Checkout, CORPUS.CorpusID as CorpusID, TEXT.TextID as TextID, Name, CiteID";
				$dao->from = "CHECKOUT left join CORPUS on CHECKOUT.CorpusID=CORPUS.CorpusID left join TEXT on CHECKOUT.TextID=TEXT.TextID";
				$rows = $dao->get( array('Identifier' => $checkout_id) );
				if ($is_corpus) {
					$entity_id = $rows[0]['CorpusID'];
					$entity_name = $rows[0]['Name'];
				} else {
					$entity_id = $rows[0]['TextID'];
					$entity_name = $rows[0]['CiteID'];
				}
				$timestamp_checkout = $rows[0]['Checkout'];
			}
		}
	} else {
		$error_msg = '<ul>';
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$error_msg .= "<li>XML validation error: $error->message (Code $error->code) on line $error->line column $error->column</li>";
		}
		$error_msg .= '</ul>';
		return $error_msg;
	}
	
	return array( 'is_corpus' => $is_corpus, 'xsd_type' => $type, 'entity_id' => $entity_id, 'timestamp_checkout' => $timestamp_checkout, 'entity_name' => $entity_name );
	
} //analyseXMLFile

//+ 
function checkoutTextOrCorpus ( $type , $id , $text_node=0 , $user_id=NULL )
/*/
Checks out a Text or Corpus and returns the corresponding Identifier (hash). Earlier 
checkouts of the same entity are marked as invalid.
---
@param type: the type of the entity to be checked out: /text, corpus/
@type  type: string
@param id: the ID of the entity to be checked out, i.e., a valid TextID or CorpusID
@type  id: int
@param text_node: The DOMDocument representation of the text to be checked out. Needed to 
calculate the checksums of its normalized an and txt section.
@type  text_node: DOMDocument
@param user_id: the ID of the user who checks out the entity
@type  user_id: int
-
@return: the 32-character hash to server as check-out identifier
@rtype:  string
/*/
{
	
	// get a random hash
	$identifier = generateCheckoutHash($id);	
	
	// get the ID key for the right entity
	if ($type == 'text') {
		$type = 'TextID';
		if ( ! $text_node) {
			echo $text_node;
			die('Fatal error: A text cannot be checked out without submitting its DOMDocument representation to checkoutTextOrCorpus()');
		}
	} else {
		$type = 'CorpusID';
	}
	
	// make older entries invalid (if any)
	$dao = new Table('CHECKOUT');
	$older_entries = $dao->get( array( $type => $id ) );
	foreach($older_entries as $older_entry) {
		$dao->where = "$type=$id AND Checkin is NULL";
		$dao->update( array( 'IsInvalid' => 1 ) );
	}
	unset($dao->where);
	
	// write DB entry in CHECKOUT table
	$dao->insert( array( 'Identifier' => $identifier, $type => $id, 'Checkout' => now(), 'IsInvalid' => '0', 'UserID' => $user_id ) );
	
	// in case of text: write checksums to CHECKSUM table
	if ($type == 'TextID') {

		$checksums = getSectionChecksums($text_node);
		
		$dao = new Table('CHECKSUM');
		$dao->insert( array( 'CheckoutIdentifier' => $identifier, 'an' => $checksums[0], 'txt' => $checksums[1] ) );
		
	}
	
	return $identifier;
	
	
} //checkoutTextOrCorpus

//+ 
function getSectionChecksums ( $text_node )
/*/
Calculates a hash sum for the an and txt sections of a Text.
---
@param text_node: the text node
@type  text_node: DOMDocument
-
@return: the checksum of the submitted text's an and txt section
@rtype:  array(an_checksum,txt_checksum)
/*/
{
	
	$an_element = $text_node->getElementsByTagName('an')->item(0);
	$an_checksum = md5( $text_node->saveXML($an_element) );
	$txt_element = $text_node->getElementsByTagName('txt')->item(0);
	$txt_checksum = md5( $text_node->saveXML($txt_element) );
	
	return array( $an_checksum, $txt_checksum );
	
} //getSectionChecksums

//+ 
function checkinTextOrCorpus ( $identifier )
/*/
Takes a check-out identifier hash and checks the concerned entity back in. Returns the ID 
of the checked-in entity.
---
@param identifier: the 32-character check-out identifier
@type  identifier: string
-
@return: the ID of the checked-in entity OR FALSE if the check-out identifier is invalid 
(outdated)
@rtype:  id
/*/
{
	if ( validateCheckoutIdentifier($identifier) ) {
		// get information from the DB
		$dao = new Table('CHECKOUT');
		$rows = $dao->get( array( 'Identifier' => $identifier ) );
		// mark the item as checked in
		$dao->where = array( 'Identifier' => $identifier );
		$dao->update( array( 'Checkin' => now() ) );
		// return its ID
		if ($rows[0]['TextID']) {
			return $rows[0]['TextID'];
		} else {
			return $rows[0]['CorpusID'];
		}
	} else {
		// an outdated corpus cannot be checked in
		return FALSE;
	}
	
} //checkinTextOrCorpus

//+ 
function validateCheckoutIdentifier ( $checkout_id )
/*/
Checks if the submitted checkout identifier is valid, i.e., there is no newer checked-out 
version of the corresponding entity.
---
@param checkout_id: the checkout id to validate (32-character string)
@type  checkout_id: string
-
@return: whether the submitted checkout id is valid
@rtype:  bool
/*/
{
	$dao = new Table('CHECKOUT');
	$rows = $dao->get( array( 'Identifier' => $checkout_id ) );
	
	if (empty($rows)) {
		// identifier does not exist and is hence not valid
		return FALSE;
	}
	
	return !(bool)$rows[0]['IsInvalid'];
	
} //validateCheckoutIdentifier

?>