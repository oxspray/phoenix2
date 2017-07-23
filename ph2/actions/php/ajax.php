<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** This is the ph2 basic white-page ajax-request handler. This site can be called directly, echoing HTML content
** requested by $_GET['action'] and further params.
** ---
*/
session_start();
require_once('../../../settings.php');
require_once('../../framework/php/framework.php');
isset($_SESSION[PH2_SESSION_KEY]) ? $ps = unserialize($_SESSION[PH2_SESSION_KEY]) : $ps = new PH2Session();

if($_GET['action']) {
	call_user_func($_GET['action'], fixBoolArray($_GET), $_POST);
}

function searchTypes ($get, $post) {
/*  searches the database for types (of type occ) matching the given string and returns a list with
    links pointing to them */

	$regexp_query = $get['q'];

	$dao = new Table('TOKEN');
	$dao->select = 'Surface, count(*) as Count, TokenID';
	$dao->from = 'TOKEN natural join OCCURRENCE natural join TOKENTYPE';
	$dao->where = "Surface REGEXP '$regexp_query' AND Name='occ'";
	$dao->groupby = 'Surface';
	$dao->orderby = 'Surface ASC';

   	$result = array();
	foreach($dao->get() as $occ_type) {
		$result[] = array('tokenID' => $occ_type['TokenID'], 'surface' => $occ_type['Surface'], 'count' => $occ_type['Count']);
	}

    echo json_encode($result);
}

function getTokens ($get, $post) { global $ps;
/* retrieves a list of all TOKENs from the database, grouped by their Tokentype */

	$dao = new Table('TOKEN');
	$dao->select = 'distinct(Surface), TokenID, TokentypeID';
	$dao->orderby = "SURFACE COLLATE utf8_roman_ci";

	if ($ps->filterIsActive()) {
		// if only selected texts should be used
		$dao->from = 'TOKEN natural join TOKENTYPE natural join OCCURRENCE';

		// convert text ids to sql string
		$text_ids = '';
		foreach ($ps->getFilterIncludedTexts() as $text_id) {
			$text_ids .= $text_id . ',';
		}
		$text_ids = rtrim($text_ids, ',');

		$dao->where = "TextID in ($text_ids)";
		$rows = $dao->get();
	} else {
		// if all texts should be used
		$dao->from = 'TOKEN natural join TOKENTYPE';
		$rows = $dao->get();
	}

	$result = array();
	foreach ($rows as $row) {
		$result[$row['TokentypeID']][] = array($row['TokenID'], $row['Surface']);
	} // result: [ 1: [TokenID, Surface], ..., 2: ..., n:, ... ]

	echo json_encode($result);

}

function getTokentypes ($get, $post) { global $ps;
	/* retrieves a list of all TOKENTYPEs from the database */

	$dao = new Table('TOKENTYPE');

	$result = array();
	foreach ($dao->get() as $row) {
		$result[] = array($row['TokentypeID'], $row['Name'], $row['Descr']);
	} // result: [ [TokentypeID, Name, Descr], ... ]

	echo json_encode($result);

}

function getLemmata ($get, $post) { global $ps;
/* retrieves a list of all LEMMAta from the database, grouped by their CONCEPT */

	$dao = new Table('LEMMA');
	$dao->select = 'distinct(LemmaID), MainLemmaIdentifier, LemmaIdentifier, ConceptID';
	$dao->orderby = "LemmaIdentifier COLLATE utf8_roman_ci";

	if ($ps->filterIsActive()) {
		// if only selected texts should be used
		$dao->from = 'LEMMA natural join LEMMA_OCCURRENCE natural join OCCURRENCE';

		// convert text ids to sql string
		$text_ids = '';
		foreach ($ps->getFilterIncludedTexts() as $text_id) {
			$text_ids .= $text_id . ',';
		}
		$text_ids = rtrim($text_ids, ',');

		$dao->where = "TextID in ($text_ids)";
		$rows = $dao->get();
	} else {
		// if all texts should be used
		$dao->from = 'LEMMA';
		$rows = $dao->get();
	}

	$result = array();
	foreach ($rows as $row) {
		$result[$row['ConceptID']][] = array($row['LemmaID'], $row['MainLemmaIdentifier'], $row['LemmaIdentifier']);
	} // result: [ 1: [LemmaID, MainLemmaIdentifier, LemmaIdentifier], ..., 2: ..., n:, ... ]
	echo json_encode($result);

}

function cleanEmptyLemmata ($get, $post) { global $ps;
/* removes all 'empty' lemmata form the database i.e. all lemmata without any occurences assigned to them */

	/* delete all LEMMA entries without any Occurrence assigned to them */
	$dao = new Table('LEMMA');
	$dao->select = 'LemmaID';
	$dao->where = "LemmaID not in (SELECT LemmaID from LEMMA_OCCURRENCE)";
	foreach ($dao->get() as $row) {
		$dao->delete( array('LemmaID' => $row['LemmaID']) );
	}

	/* do the counter-operation as well:
	delete all LEMMA_OCCURRENCE entries with invalid LemmaIDs
	(i.e. Lemmata which don't exist anymore) */
	$dao = new Table('LEMMA_OCCURRENCE');
	$dao->select = 'LemmaID';
	$dao->where = "LemmaID not in (SELECT LemmaID from LEMMA)";
	foreach ($dao->get() as $row) {
		$dao->delete( array('LemmaID' => $row['LemmaID']) );
	}

}

function cleanEmptyTokens ($get, $post) { global $ps;
/* removes all 'empty' tokens from the database i.e. all Tokens without any occurences assigned to them */

	// delete Tokens, which aren't mentioned in OCCURRENCE
	$dao = new Table('TOKEN');
	$dao->select = 'TokenID';
	$dao->where = "TokenID not in (SELECT TokenID from OCCURRENCE)";
	foreach ($dao->get() as $row) {
		$dao->delete( array('TokenID' => $row['TokenID']) );
	}

}

function updateGraphgroupSelectionWithID ($get, $post) { global $ps;
	/* returns a dropdown-selection (string, html) containing all graphgroups with the given graphID */

	$id = $get['graphID']; 		// selected graphID
	$id = (int)$id; 					//cast into int to ensure correct constructing of the Graph
	$selected_graph = new Graph($id);
	$graphgroups = $selected_graph->getGraphgroups();

	$opts = "";
	foreach($graphgroups as $gg) {
		$name = $gg->getName();
		$val = $gg->getNumber();
		// concatenate the option line and add it to $opts
		$opts .= '<option value="' . $val . '" name="' . $name .'">' . $val . " (" . $name . ")" . '</option>';

	}

	echo json_encode($opts);

}

function getGraphgroupsWithID ($get, $post) { global $ps;
	/* retrieves a list of all GraphgroupIDs of a given Grapheme from the database */
	$id = $get['graphID']; 		// selected graphID
	$id = (int)$id; 					//cast into int to ensure correct constructing of the Graph
	$selected_graph = new Graph($id);
	$graphgroups = $selected_graph->getGraphgroups();
	$out = array();

	foreach($graphgroups as $gg) {
		$out[] = $gg->getID();
	}

	echo json_encode($out);

}

function getLemmatypes ($get, $post) { global $ps;
	/* retrieves a list of all CONCEPTs from the database */

	$dao = new Table('CONCEPT');

	$result = array();
	foreach ($dao->get() as $row) {
		$descr = $row['Name'];
		if ($row['Descr']) {
			$descr .= ': ' . $row['Descr'];
		}
		$result[] = array($row['ConceptID'], $row['Short'], $descr);
	} // result: [ [ConceptID, Name(short), Descr], ... ]

	echo json_encode($result);

}

function getTextsAssignedToCorpus ($get, $post) {
/*  Returns the IDs of all Texts assigned to a Text in the Database
    Format: JSON (array) */

	$corpus_id = $get['corpusID'];

	$dao = new Table('TEXT');

	$ids = array();
	foreach ($dao->get( array('CorpusID' => $corpus_id) ) as $row) {
		$ids[] = $row['TextID'];
	}

	echo json_encode($ids);

}

function getTextDetails ($get, $post) {
/*  Returns Details on a text selected by its TextID
	Format: JSON(array) */

	$text_id = $get['textID'];

	// basic text infos
	$dao = new Table('TEXT');
	$results_text = $dao->get( array('TextID' => $text_id) );

	// number of assigned Occurrences
	$dao = new Table('OCCURRENCE');
	$dao->select = "count(*) as Count";
	$results_occ = $dao->get( array('TextID' => $text_id) );

	// number of assigned Lemmata
	$dao = new Table('LEMMA_OCCURRENCE');
	$dao->from = "LEMMA_OCCURRENCE natural join OCCURRENCE";
	$dao->select = "count(*) as Count";
	$results_lem = $dao->get( array('TextID' => $text_id) );

	$details = array();
	$details['ID'] = $results_text[0]['TextID'];
	$details['Name'] = $results_text[0]['Name'];
	$details['Description'] = $results_text[0]['TextDescr'];
	$details['# Occ.'] = $results_occ[0]['Count'];
	$details['# Lem.'] = $results_lem[0]['Count'];

	echo json_encode($details);

}

function getOccurrenceContextAJAX ($get, $post) {
/*	Returns the Surface and Context of an Occurrence, given its ID
	Format: JSON(array) */

	echo json_encode(getOccurrenceContext($get['id']));

}

function getOccurrences ($get, $post) { global $ps;
/*  Returns the IDs of all Occurrences assigned to a Token OR Lemma in the Database (~Type)
    Format: JSON (array) */

	$token_id = $get['tokenID'];
	$lemma_id = $get['lemmaID'];

	if ($token_id) {
		$dao = new Table('OCCURRENCE');
		$dao->where = "TokenID = $token_id";
	} else if ($lemma_id) {
		$dao = new Table('LEMMA_OCCURRENCE');
		$dao->where = "LemmaID = $lemma_id";
	}

	// in case only certain texts should be considered
	if ($ps->filterIsActive()) {
		// convert text ids to sql string
		$text_ids = '';
		foreach ($ps->getFilterIncludedTexts() as $text_id) {
			$text_ids .= $text_id . ',';
		}
		$text_ids = rtrim($text_ids, ',');
		if ($lemma_id) {
			$dao->from = 'LEMMA_OCCURRENCE natural join OCCURRENCE';
		}
		$dao->where .= " and TextID in ($text_ids)";
	}

	$ids = array();
	foreach ($dao->get() as $row) {
		$ids[] = $row['OccurrenceID'];
	}

	echo json_encode($ids);

}

function getNumberOfOccurrencesByEntityList ($get, $post) { global $ps;
/* Returns the Number of Occurrences that have the Surface of the provided TokenIDs */

	$exclude_lemmatized = $post['exclude_lemmatized'];

	if ($post['type'] == 'token') {
		$sql_ids_list = '(' . expandArray($post['ids'],',') . ')';
		$dao = new Table('OCCURRENCE');
		$dao->select = "TokenID as ItemID, count(*) as count";
		$dao->groupby = "TokenID";
		$dao->where = "TokenID in $sql_ids_list";
	} else if ($post['type'] == 'lemma') {
		$sql_ids_list = '(' . expandArray($post['ids'],',') . ')';
		$dao = new Table('LEMMA_OCCURRENCE');
		$dao->select = "LemmaID as ItemID, count(*) as count";
		$dao->groupby = "LemmaID";
		$dao->where = "LemmaID in $sql_ids_list";
	}

	// in case only certain texts should be considered
	if ($ps->filterIsActive() || $exclude_lemmatized == 'true') {
		if ($post['type'] == 'lemma') {
			$dao->from = 'LEMMA_OCCURRENCE natural join OCCURRENCE';
		}
		if ($ps->filterIsActive()) {
			// convert text ids to sql string
			$text_ids = '';
			foreach ($ps->getFilterIncludedTexts() as $text_id) {
				$text_ids .= $text_id . ',';
			}
			$text_ids = rtrim($text_ids, ',');
			$dao->where .= " and TextID in ($text_ids)";
		}
		if ($exclude_lemmatized == 'true' and $post['type'] == 'token') {
			$dao->from = 'OCCURRENCE left join LEMMA_OCCURRENCE on OCCURRENCE.OccurrenceID=LEMMA_OCCURRENCE.OccurrenceID';
			$dao->where .= " and LemmaID is NULL";
		}
	}

	$result = array();
	foreach ($dao->get() as $row) {
		$result[] = array( 'id' => $row['ItemID'], 'count' => $row['count'] );
	}

	echo json_encode($result);

}

function getMorphGroupsAssignedToLemma ($get, $post) {
/*  Returns (ID, Surface, OccurrenceCount) of all Morphological Groups assigned to a Lemma (via Occurrences)
    Format: JSON (array) */

	# TODO

	# STUB
	$stub = array( array( '' ), array( '' ) );
	echo json_encode($stub);

}

function sortOccurrences ($get, $post) {
/* 	takes a list of Occurrences (OccurrenceIDs) and orders them by:
		Corpus > Text > Surface > Order
	returns the list of OccurrenceIDs in an order matching the above criteria. */
	$occ_ids = json_encode($post['occurrenceIDs']);
	$field = $post['field'];
	$sql_occ_ids_list = '(' . trim($occ_ids, "[]") . ')';

	switch ($field) {

		case 'citeform':
			$dao = new Table('OCCURRENCE');
			$dao->select = "OccurrenceID";
			$dao->from = "OCCURRENCE natural join TOKEN join TEXT on OCCURRENCE.TextID = TEXT.TextID join CORPUS on TEXT.CorpusID = CORPUS.CorpusID";
			$dao->where = "OccurrenceID in $sql_occ_ids_list";
			$dao->orderby = "TEXT.CiteID COLLATE utf8_roman_ci ASC, TOKEN.Surface COLLATE utf8_roman_ci, OCCURRENCE.`Order` ASC";
		break;

		case 'd0':
		case 'rd0':
			$dao = new Table('OCCURRENCE');
			$dao->select = "OccurrenceID";
			$dao->from = "OCCURRENCE natural join TOKEN join TEXT on OCCURRENCE.TextID = TEXT.TextID join CORPUS on TEXT.CorpusID = CORPUS.CorpusID join TEXT_DESCRIPTOR on TEXT.TextID=TEXT_DESCRIPTOR.TextID join DESCRIPTOR on TEXT_DESCRIPTOR.DescriptorID=DESCRIPTOR.DescriptorID";
			$dao->where = "OccurrenceID in $sql_occ_ids_list AND XMLTagName='" . $field . "'";
			$dao->orderby = "TEXT_DESCRIPTOR.`Value` COLLATE utf8_roman_ci ASC, TOKEN.Surface COLLATE utf8_roman_ci, OCCURRENCE.`Order` ASC";
		break;
	}


	$ordered_occ_list = array();
	foreach($dao->get() as $row) {
		$ordered_occ_list[] = $row['OccurrenceID'];
	}

	echo json_encode($ordered_occ_list);

}

function assignOccurrencesToLemma ($get, $post) { global $ps;
/* Assigns a selection of occurrences to a Lemma @param graphgroup_id. Creates the lemma if it doesn't exist yet. */

	$lemma_id = json_decode($post['lemmaID']);
	$lemma_identifier = $post['lemmaIdentifier'];
	$lemma_concept = $post['conceptShort'];
	if ($post['morphvalues'] == 'null') {
		$lemma_morphvalues = NULL;
	} else {
		$lemma_morphvalues = $post['morphvalues'];
	}
	$occurrence_ids = json_decode($post['occurrenceIDs']);

	assert($occurrence_ids);

	if ($lemma_id) {
		$lemma = new Lemma( (int)$lemma_id );
	} else {
		$lemma = new Lemma( $lemma_identifier, $lemma_concept, $ps->getActiveProject(), NULL, $lemma_morphvalues );
	}

	foreach( $occurrence_ids as $occurrence_id) {
		$lemma->assignOccurrenceID($occurrence_id); //existing lemma assignment is deleted!
	}

}

function assignOccurrencesToGraph ($get, $post) { global $ps;
/* Assigns a selection of occurrences to a graph @param graph_id. Creates the graph if it doesn't exist yet.
	 graphIdentifier is either a 'name' or an 'ID', depending on if the graph exists already or not

	 returns the created/used graphID */
	$created_id = null;
	$graph_id = json_decode($post['graphIdentifier']);
	$graph_identifier = $post['graphIdentifier'];
	$description = $post['descr'];
	$occurrence_ids = $post['occurrenceIDs'];

	assert($occurrence_ids);

	if ($graph_id) {
		$graph = new Graph( (int)$graph_id );
		$created_id = $graph->getID();
	} else {
		$graph = new Graph( $graph_identifier, $description, $ps->getActiveProject() );
		$created_id = $graph->getID();
	}

	foreach( $occurrence_ids as $occurrence_id) {
		$graph->assignOccurrenceID($occurrence_id); //existing graph assignment is deleted!
	}

	echo json_encode($created_id);

}

function assignOccurrencesToGraphgroup ($get, $post) { global $ps;
/* assigns a selection of occurrences to a graphgroup and connects it to the given graph */

	$graphgroup_number = json_decode($post['graphgroupNumber']);
	$occurrence_ids = json_decode($post['occurrenceIDs']);
	$graph_identifier = json_decode($post['graphIdentifier']);
	$graphgroup_name = $post['graphgroupName'];
	$overwrite = $post['relevantGraphgroups'];

	assert($occurrence_ids);

	$graph = new Graph ( (int)$graph_identifier );
	// addGraphgroup creates an ID of the newly created Graphgroup (assigned to graphgroup_id)
	$graphgroup_id = $graph->addGraphgroup($graphgroup_number, $name=$graphgroup_name);
	// add occurence IDs to Graphgroup (visible in table GRAPHGROUP_OCCURRENCE)
	$graphgroup = new Graphgroup ( $graphgroup_id ); // access this newly created Graphgroup
	if ($overwrite) {
		$graphgroup->addOccurrence($occurrence_ids, FALSE, $overwrite); // 3rd parameter: overwrite existing graph-assignments first
	} else {
		$graphgroup->addOccurrence($occurrence_ids, FALSE); //2nd parameter: delete existing assignments first
	}

	echo json_encode($graphgroup_id);

}

function reassignOccurrencesToGraphgroup ($get, $post) { global $ps;
/* reassigns a selection of occurrences to a graphgroup */

	$new_graphgroup_number = json_decode($post['newGraphgroupNumber']);
	$occurrence_ids = json_decode($post['occurrenceIDs']);
	$active_graph_id = json_decode($post['activeGraphID']);
	$new_graphgroup_id = null;

	assert($occurrence_ids);

	$dao = new Table('GRAPHGROUP');
	$dao->select = "`GraphgroupID`";
	$dao->where = "`GraphID` = " . $active_graph_id . " AND `Number` = " . $new_graphgroup_number ;
	$rows = $dao->get();
	$new_graphgroup_id = $rows[0]['GraphgroupID'];

	if ($new_graphgroup_id) {
		$dao = new Table('GRAPHGROUP_OCCURRENCE');
		$dao->from = "`GRAPHGROUP_OCCURRENCE` natural join `GRAPHGROUP`";
		$dao->where = "`OccurrenceID` in (" . expandArray($occurrence_ids, ',') . ") AND `GraphID` = " . $active_graph_id;
		$rows = $dao->get();

		foreach($rows as $row) {
			$existing_graphgroup_id = $row['GraphgroupID'];
			$occ_id = $row['OccurrenceID'];
			if ($existing_graphgroup_id != $new_graphgroup_id) {
				$dao = new Table('GRAPHGROUP_OCCURRENCE');
				$dao->delete( array('OccurrenceID' => (int)$occ_id, 'GraphgroupID' => (int)$existing_graphgroup_id) );
				$new_graphgroup = new Graphgroup( (int)$new_graphgroup_id );
				$new_graphgroup->addOccurrence( (int)$occ_id, FALSE);
			}
		}
		die(json_encode(array('message' => 'SUCCESS')));
	} else {
		die(json_encode(array('message' => 'ERROR')));
	}
}

function checkNameValidity ($get, $post) { global $ps;
	/* function to check if a name is already given in the DB for the provided table */
	$table = $get['table'];
	$graphID = $get['graphID'];
	$name = $get['name'];

	$dao = new Table($table);
	$dao->select = "Name";
	$dao->from = $table;
	$dao->where = "graphID = $graphID";

	if ( $dao != null) {
		return false;
	} else {
		return true; //returns true if no entries were found
	}
}

function lemmaExists ($get, $post) { global $ps;
/* Checks whether a lemma with @param identifier and @param type exists in the database */

	$identifier = $get['identifier'];
	$concept = $get['concept'];

	$exists = FALSE;

	$dao_concept = new Table('CONCEPT');
	$rows = $dao_concept->get( array( 'Short' => $concept ) );
	if (count($rows) > 0) {
		$concept_id = $rows[0]['ConceptID'];
		// if a lemma with this (identifier/concept/project_id) allready exists, load it instead of creating a new one
		$dao_lemma = new Table('LEMMA');
		$rows = $dao_lemma->get( array( 'ProjectID' => $ps->getActiveProject(), 'LemmaIdentifier' => $identifier, 'ConceptID' => $concept_id ) );
		if (count($rows) > 0) {
			$exists = TRUE;
		}
	}

	echo json_encode($exists);

}

function graphExists ($get, $post) { global $ps;
/* Checks whether a graph with @param identifier exists in the database */

	$identifier = $get['identifier'];

	$exists = FALSE;

	$dao_graph = new Table('GRAPH');
	$rows = $dao_graph->get( array( 'ProjectID' => $ps->getActiveProject(), 'Name' => $identifier ) );
	if (count($rows) > 0) {
		$exists = TRUE;
	}
	echo json_encode($exists);
}


function countLemmaAssignments ($get, $post) { global $ps;
/* returns the number of Occurrences in @param occurrenceIDs that have a Lemma assignment */

	$occurrence_ids = json_decode($post['occurrenceIDs']);
	assert($occurrence_ids);

	$dao = new Table('LEMMA_OCCURRENCE');
	$dao->select = "count(*) as c";
	$dao->where = "OccurrenceID in (" . expandArray($occurrence_ids, ',') . ")";

	$rows = $dao->get();
	$count = $rows[0]['c'];

	echo json_encode($count);

}

function countGraphAssignments ($get, $post) { global $ps;
/* returns the number of Occurrences in @param occurrenceIDs that have a Graphgroup assignment */

	$occurrence_ids = json_decode($post['occurrenceIDs']);
	assert($occurrence_ids);

	// graph occurences are stored in graphgroups only -> every graph is part of a graphgrop automatically.
	$dao = new Table('GRAPHGROUP_OCCURRENCE');
	$dao->select = "count(*) as c";
	$dao->where = "OccurrenceID in (" . expandArray($occurrence_ids, ',') . ")";

	$rows = $dao->get();
	$count = $rows[0]['c'];

	echo json_encode($count);

}

function getGraphDetails ($get, $post) {
/* 	returns a selection of details assiciated with a Graph:
	- its short description
	- all associated subgroups, i.e. (GraphgroupID, Number, Name)
	returns FALSE if the submitted graph surface (GraphName) does not exist	*/

	$graph_id = $get['id'];
	$result = FALSE;

	$graph = new Graph( (int)$graph_id );
	if ($graph->getName()) { // if the Graph exists, it has a name in the Database
		$result['description'] = $graph->getDescription();
		$result['graphgroups'] = array();
		foreach ($graph->getGraphgroups() as $graphgroup) {
			$result['graphgroups'][] = array( 'ID' => $graphgroup->getID(), 'number' => $graphgroup->getNumber(), 'name' => $graphgroup->getName() );
		}
	}

	echo json_encode($result);

}

function createGraph ($get, $post) { global $ps;
/*	creates a new Graph entity in the database. If @param graphgroup is provided, a new subgroup
	will furthermore be associated with it.
	returns the new id of the graph and (if applicable) the graphgroup */

	$graph_name = $get['graphName'];
	$graph_descr = $get['graphDescr'];
	$graphgroup_number = $get['graphgroupNumber'];
	$graphgroup_variant_name = $get['graphgroupVariantName'];

	assert($graph_name);

	$graph = new Graph($graph_name, $graph_descr);
	$graph_id = $graph->getID();
	if ($graphgroup_number) {
		$graphgroup_id = $graph->addGraphgroup($graphgroup_number, $graphgroup_variant_name);
	}

	$result = array();
	$result['graphID'] = $graph_id;
	if ($graphgroup_id) {
		$result['graphgroupID'] = $graphgroup_id;
	}

	echo json_encode($result);

}

function createGraphgroup ($get, $post) { global $ps;
/*	appends a new graphgroup to an existing graph.
	returns the id of the new graphgroup. */

	$graph_id = $get['graphID'];
	$graphgroup_number = $get['graphgroupNumber'];
	$graphgroup_variant_name = $get['graphgroupVariantName'];

	assert($graph_id);
	assert($graphgroup_number!=0);

	$graph = new Graph( (int)$graph_id );
	if ($graph->graphgroupExists($graphgroup_number)) {
		// graphgroup with given number already exists for this graph; abort
		echo json_encode('number_exists');
	} else {
		// new graphgroup
		$graphgroup_id = $graph->addGraphgroup($graphgroup_number, $graphgroup_variant_name);
		echo json_encode( array('graphgroupID' => $graphgroup_id) );
	}

}

function getActiveGraphemeID ($get, $post) { global $ps;
/* returns the Sessions Active Grapheme ID */

	echo json_encode( $ps->getActiveGrapheme() );

}

function setActiveGraphemeID ($get, $post) { global $ps;
/* sets the Sessions Active Grapheme ID
   #TODO: Fix */

	$graph_id = $get['graphID'];
	assert($graph_id);

	$ps->setActiveGrapheme($graph_id);

}

function getOccurrenceIDsByGrapheme ($get, $post) { global $ps;
/* returns all OccurrenceIDs assigned to a Grapheme */

	$grapheme_id = (int)$get['graphID'];

	assert($grapheme_id);

	$graph = new Graph($grapheme_id);
	echo json_encode( $graph->getOccurrenceIDs() );

}

function getSurfaceByOccurrenceID ($get, $post) { global $ps;
/* removes a selection of Occurrences from a Graph */

	// $occ_ids = json_decode($get['occurrenceIDs']);
	$occ_id = $get["OccurrenceID"];
	$occ_id = (int)$occ_id;

	$occ = new Occurrence( $occ_id );
	$surface = $occ->getSurface( );
	echo json_encode($surface);

}

function getDivByOccurrenceID ($get, $post) { global $ps;
/* removes a selection of Occurrences from a Graph */

	$occ_id = $get["OccurrenceID"];
	$occ_id = (int)$occ_id;

	$occ = new Occurrence( $occ_id );
	$div = $occ->getDiv( );
	echo json_encode($div);

}

function getOccurrenceIDsByGraphgroup ($get, $post) { global $ps;
/* returns all OccurrenceIDs assigned to a Graphgroup */

	$graphgroup_id = (int)$get['graphgroupID'];

	assert($graphgroup_id);

	$graphgroup = new Graphgroup($graphgroup_id);
	echo json_encode( $graphgroup->getAssignedOccurrenceIDs() );
}

function getGraphSelectionDropdownHTML ($get, $post) { global $ps;
/* returns the HTML code of a graph selection combobox */

	echo htmlGraphSelectionDropdown($ps->getActiveProject(), 'graph_id', array('modulefield', 'text', 'small', 'combobox'), 'select_graph', $ps->getActiveGrapheme());

}

function getGraphgroupSelectionDropdownHTML ($get, $post) { global $ps;
/* returns the HTML code of a graph selection combobox */

	echo htmlGraphgroupSelectionDropdown($ps->getActiveProject(), 'graphgroup_id', array('modulefield', 'text', 'small', 'combobox'), 'select_graphgroup', $ps->getActiveGrapheme());

}

function removeOccurrencesFromGraph ($get, $post) { global $ps;
/* removes a selection of Occurrences from a Graph */

	$graph_id = $get['graphID'];
	$occ_ids = json_decode($get['occurrenceIDs']);

	assert($graph_id);
	assert($occ_ids);

	$graph = new Graph( (int)$graph_id );
	$graph->removeOccurrences($occ_ids);

}

function getGraphgroupsFromGraphID ($get, $post) { global $ps;
/* returns all graphgroups (and according meta information) for a given graphID */

	$graph_id = (int)$get['graphID'];
	$project_id = $ps->getActiveProject();

	assert($graph_id);

	$dao = new Table('GRAPH');
	$result = $dao->query("select A.GraphgroupID, A.Number, A.Name, CountOcc, ProjectID from GRAPHGROUP as A left join (select count(*) as CountOcc, GraphgroupID from GRAPHGROUP_OCCURRENCE group by GraphgroupID) as B on A.GraphgroupID=B.GraphgroupID left join GRAPH as C on A.GraphID=C.GraphID where A.GraphID=$graph_id and C.ProjectID=$project_id order by Number ASC");

	echo json_encode( $result );

}

function deleteGraphgroup ($get, $post) { global $ps;
/* deletes a graphgroup and removes all assigned occurrences from its parent graph */

	$graph_id = (int)$get['graphID'];
	$graphgroup_id = (int)$get['graphgroupID'];
	assert($graph_id);
	assert($graphgroup_id);

	$graph = new Graph($graph_id);
	$graph->deleteGraphgroup($graphgroup_id);
}

function addImageToText ($get, $post) { global $ps;
/* reads the submitted form data (file input), stores the new image in the filesystem and database. */

	$text_id = $post['textID'];
	$title = ''; // can be adjusted afterwards
	$order = 0;  // can be adjusted afterwards
	$description = ''; // can be adjusted afterwards
	//assert($_FILES['file']['name']);
	assert($text_id);

	$db_filepath = PH2_FP_MEDIA . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
	$target_filepath = PH2_FP_BASE . DIRECTORY_SEPARATOR . $db_filepath;

	if (move_uploaded_file($_FILES['file']['tmp_name'], $target_filepath)) {
		// upload successful; write to db
		// register image
		$image = new Image($db_filepath, $title, $description, $order);
		// link image to text
		$image->linkToText($text_id);
		echo $image->getID();
	} else {
		echo "error";
	}
}

function getImagesAssignedToText ($get, $post) { global $ps;
/* gets the image url, title, etc. for all Media of type IMG assigned to a @param textID */

	#TODO: Convert: Use Image() entity

	$text_id = $get['textID'];
	assert($text_id);

	$dao = new Table('TEXT_MEDIUM');
	$dao->from = "TEXT_MEDIUM natural join MEDIUM";
	$dao->orderby = "`Order` ASC";
	$assigned_images = $dao->get( array('TextID' => $text_id, 'Type' => 'IMG') );

	echo json_encode($assigned_images);

}

function loadImageDetails ($get, $post) { global $ps;
/* gets an image's title, description, and order number, given its MediumID */

	#TODO: Convert: Use Image() entity

	$medium_id = $get['mediumID'];
	assert($medium_id);

	$dao = new Table('MEDIUM');
	$rows = $dao->get( array('MediumID' => $medium_id) );

	echo json_encode( $rows[0] );

}

function saveImageDetails ($get, $post) { global $ps;
/* saves an image's title, description, and order number, according to its MediumID */

	#TODO: Convert: Use Image() entity

	$medium_id = $get['mediumID'];
	$title = $get['title'];
	$order = $get['order'];
	if ( empty($order) or $order == '' ) {
		$order = 0;
	}

	assert($medium_id);
	#assert($title != '');

	$dao = new Table('MEDIUM');
	$dao->where = array('MediumID' => $medium_id);
	$dao->update( array('Title' => $title, 'Order' => $order, 'Descr' => $get['description']) );

}

function deleteImage ($get, $post) { global $ps;
/* deletes an image, including all DB entries and files it consists of */

	$image = new Image( (int)$get['mediumID'] );
	$image->delete();
}

function uploadCorpus ($get, $post) { global $ps;
/* handles the form data from upload_corpus.modal.php. Generates a new Corpus and returns a pointer to an XML file to be parsed. */

	// ASSERTIONS
	assert($_POST);
	assert($_FILES);

	// ROUTINE
	// check if no fields are empty
	if ($_POST['name'] && $_FILES['corpusfile']) {

		// create new corpus
		$corpus = new Corpus($post['name'], $ps->getActiveProject(), $post['comment']);
		$corpus_id = $corpus->getID();

		if ($_FILES['corpusfile']['error'] > 0)
		{
			$ps->notifications->push(new Notification($_FILES["file"]["error"], 'err'));
		} else {
			$tmp_name = $_FILES['corpusfile']['tmp_name'];
			$file_name = $_FILES['corpusfile']['name'];
			$target_filepath = PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_TEMP_TEXT . DIRECTORY_SEPARATOR . $file_name;
			if( move_uploaded_file($tmp_name, $target_filepath) ) {
				echo( json_encode( array( 'temp_file_name' => $file_name, 'corpus_id' => $corpus_id, 'tokenize' => $post['tokenize'], 'migrate' => $post['migrate'], 'corpus_name' => $post['name'], 'auto_comment' => $post['auto_comment'] ) ) );
			} else {
				echo 'error';
			}
		}
	}
}

function checkinCorpus ($get, $post) { global $ps;
/* checks in a corpus and returns a status (check) */

	$corpus_id = $get['corpus_id'];
	$xml = $GLOBALS["HTTP_RAW_POST_DATA"];

	$corpus = new Corpus( (int)$corpus_id );
	$success = $corpus->checkin($xml);

	echo $success; //don't json_encode!

}

function AddTextFromXMLInputAJAX ( $get, $post ) { global $ps;
/* takes an xml text submitted via form input and creates a corresponding entity on the system */

	$xml = $GLOBALS["HTTP_RAW_POST_DATA"];

	if($get['migrate'] == 'null') $get['migrate'] = FALSE;
	if($get['tokenize'] == 'null') $get['tokenize'] = FALSE;

	$status = addTextFromXMLInput ( $xml , $get['name'] , $get['comment'] , $get['corpus_id'] , $get['migrate'] , $get['tokenize'] );
	echo $status;
}

function importTextFromXMLInputAJAX ( $get, $post ) { global $ps;
/* takes an xml text submitted via form input and creates (ENTRY, STORAGE) or updates (EDIT) a corresponding entity on the system */

	assert( !empty( $GLOBALS["HTTP_RAW_POST_DATA"] ) );
	assert( !empty( $get['xsd_type'] ) );
	assert( !empty( $get['corpus_id'] ) );

	$xml = $GLOBALS["HTTP_RAW_POST_DATA"];

	if($get['tokenize'] == 'null') $get['tokenize'] = FALSE;
	if($get['comment'] == 'null') $get['comment'] = FALSE;
	if($get['overwrite'] == 'null') $get['overwrite'] = FALSE;

	if ( $get['xsd_type'] == 'edit' ) {
		// get the Text's ID
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$checkout_id = $dom->documentElement->getAttribute('checkout_id');

		$dao = new Table('CHECKOUT');
		$rows = $dao->get( array('Identifier' => $checkout_id) );
		$text_id = (int)$rows[0]['TextID'];

		$text = new Text($text_id);
		$status = $text->checkin($dom); //gets array( success?, log )

		echo $status[0];

	} else {
		// use adequate XML-Parser
		if ( $get['xsd_type'] == 'entry' ) {
			$migrate = TRUE;
		} else {
			$migrate = FALSE;
		}

		$status = addTextFromXMLInput ( $xml, $get['corpus_id'] , $get['migrate'] , $get['tokenize'] );
		echo $status;
	}



}

function uploadFile ($get, $post) { global $ps;
/* handles the file upload from import.modal.php. Stores a file in a temp directory and eturns a pointer to the file for further processing. */

	// ASSERTIONS
	assert($_FILES);

	// ROUTINE
	// check if file is not empty
	if ($_FILES['uploadfile']) {

		if ($_FILES['uploadfile']['error'] > 0)
		{
			$ps->notifications->push(new Notification($_FILES["file"]["error"], 'err'));
		} else {
			$tmp_name = $_FILES['uploadfile']['tmp_name'];
			$file_name = $_FILES['uploadfile']['name'];
			$target_filepath = PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_TEMP_TEXT . DIRECTORY_SEPARATOR . $file_name;
			if( move_uploaded_file($tmp_name, $target_filepath) ) {
				//check if file is single text or corpus
				$params = analyseXMLFile($target_filepath);
				if (is_string($params)) {
					// xml file is not valid
					echo json_encode( array( 'success' => FALSE, 'error' => $params ) ); // the error message of the file validation
					unlink( PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_TEMP_TEXT . DIRECTORY_SEPARATOR . $file_name );

				} else {
					echo json_encode( array_merge( array( 'success' => TRUE, 'temp_file_name' => $file_name), $params ) );
				}
			} else {
				echo json_encode( array( 'success' => FALSE, 'error' => 'The file could not be uploaded.' ) );
			}
		}
	}
}

function DeleteTempFile ( $get, $post ) { global $ps;
/* delete a file in the temp text directory */

	unlink( PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_TEMP_TEXT . DIRECTORY_SEPARATOR . $get['filename'] );

}

function GetFilter ($get, $post) { global $ps;
/* takes a filter identifier (string) and returns a list of associated values, saying whehter each value is allready active in the current SESSION */

	$filter_identifier = $get['filter'];

	$active_values = $ps->getFilterValues($filter_identifier);

	$dao = new Table('DESCRIPTOR');
	$available_descriptors = array();
	foreach ($dao->get() as $descriptor) {
		$available_descriptors[$descriptor['XMLTagName']] = $descriptor['DescriptorID'];
	}

	$filter = array(); // Values


	if ($filter_identifier == 'd0') {

		// FROM

		$checked = '';
		$value = '';

		$filter_from = $ps->getFilterValues('d0-from');
		if ($filter_from) {
			$value = $filter_from[0];
			$checked = 'checked="checked"';
		}
		$filter[] = array(FALSE, '<tr><td width="20"><input type="checkbox" id="checkbox_d0_from" class="value_checkbox"' . $checked . ' name="d0-from" value="' . $value . '" /></td><td class="leftalign value">from <input type="text" id="input_d0_from" class="inline_textfield small" value="' . $value . '" /><input type="button" id="update_d0_from" value="update" class="hidden" /></td></tr>');

		// TO

		$checked = '';
		$value = '';

		$filter_to = $ps->getFilterValues('d0-to');
		if ($filter_to) {
			$value = $filter_to[0];
			$checked = 'checked="checked"';
		}
		$filter[] = array(FALSE, '<tr><td width="20"><input type="checkbox" id="checkbox_d0_to" class="value_checkbox"' . $checked . ' name="d0-to" value="' . $value . '" /></td><td class="leftalign value">to&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="input_d0_to" class="inline_textfield small" value="' . $value . '" /><input type="button" id="update_d0_to" value="update" class="hidden" /></td></tr>');

	} else if (array_key_exists($filter_identifier, $available_descriptors)) {

		$dao = new Table('TEXT_DESCRIPTOR');
		$dao->select= "distinct Value";
		$dao->where = array('DescriptorID' => $available_descriptors[$filter_identifier]);
		$dao->orderby = 'Value ASC';
		foreach ($dao->get() as $row) {
			$filter_value = $row['Value'];
			$filter_is_active = FALSE;
			if ($active_values) {
				if (in_array($filter_value, $active_values)) {
					$filter_is_active = TRUE;
				}
			}
			$filter[] = array($row['Value'], $filter_is_active);
		}

	} else if ($filter_identifier == 'corpus') {

		$dao = new Table('CORPUS');
		foreach ($dao->get() as $row) {
			$filter_value = $row['Name'];
			$filter_is_active = FALSE;
			if ($active_values) {
				if (in_array($filter_value, $active_values)) {
					$filter_is_active = TRUE;
				}
			}
			$filter[] = array($row['Name'], $filter_is_active);
		}

	} else {



	}

	return $filter;

}

function GetFilterJSON ($get, $post) { global $ps;
/* forms a JSON-Array of checkboxes from the GetFilter()-Output */

	echo json_encode(GetFilter($get, $post));

}

function GetFilterHTML ($get, $post) { global $ps;
/* forms a HTML-block of checkboxes from the GetFilter()-Output */

	$filter_values = GetFilter($get, $post);

	$html = '';

	foreach ($filter_values as $value) {
		if ($value[0]) {
			// marke active values as checked
			if ($value[1]) {
				$value[1] = ' checked="checked"';
			}
			$html .= '<tr><td width="20"><input type="checkbox" class="value_checkbox"' . $value[1] . ' name="' . $value[0] . '" value="' . $value[0] . '" /></td><td class="leftalign value">' . $value[0] . '</td></tr>';
		} else {
			$html .= $value[1];
		}
	}

	echo $html; // for use via JS

}

function AddFilter ($get, $post) { global $ps;
/* adds a filter to the current session */

	$ps->addFilter($get['filter'], $get['value']);
	$ps->setFilterIsActive(TRUE);

}

function RemoveFilter ($get, $post) { global $ps;
/* removes a filter to the current session */

	$ps->removeFilter($get['filter'], $get['value']);
	if ($ps->filterIsEmpty()) {
		$ps->setFilterIsActive(FALSE);
	}

}

function getActiveFilterValues ($get, $post) { global $ps;
/* returns all values that are currently active in the SESSION's filter */

	echo json_encode( $ps->getFilterValues( $get['filter'] ) );

}

function SetFilterIsActive ($get, $post) { global $ps;
/* turns ON or OFF the filter in the current SESSION */

	$ps->setFilterIsActive( json_decode( $get['active'] ) );

}

function GetActiveFilterIDs ($get, $post) { global $ps;
/* returns the IDs (=names) of all currently active filters */

	echo json_encode($ps->getActiveFilterIDs());
}

function createCorpus ($get, $post) { global $ps;
/* creates a Corpus and returns its ID */

	assert( ! empty( $get['name'] ) );

	$corpus = new Corpus( $get['name'], $ps->getActiveProject() );
	echo $corpus->getID();

}

function getCorpusName ($get, $post) { global $ps;
/* returns the name of a corpus, given its ID */

	assert( ! empty( $get['id'] ) );
	$corpus = new Corpus( (int)$get['id'] );
	echo $corpus->getName();

}

function getCorpusNameByTextID ($get, $post) { global $ps;
/* returns the name of the corpus a text, given its TextID, is assigned to */

	assert( ! empty( $get['id'] ) );

	$text = new Text( (int)$get['id'] );
	$corpus_id = $text->getCorpusID();

	$corpus = new Corpus( (int)$corpus_id );
	echo $corpus->getName();

}

function deleteCorpus ($get, $post) { global $ps;
/* delete a corpus, given its ID */

	assert( ! empty( $get['id'] ) );
	$corpus = new Corpus( (int)$get['id'] );
	echo $corpus->delete();

}

function updateTextOrderNumber ($get, $post) { global $ps;
/* update the order number of a text, given its ID */

	assert( ! empty( $get['id'] ) );
	$text = new TEXT( (int)$get['id'] );
	echo $text->setOrderNumber((int)$get['order']);

}

function isGuest  ($get, $post) { global $ps;
/* returns TRUE if the user of this session is a guest; FALSE otherwise */

	if ($ps->getNickname() == 'guest') {
		echo json_encode(TRUE);
	} else {
		echo json_encode(FALSE);
	}

}


/// SAVE MODIFIED SESSION
$ps->save(); //do not remove!
?>
