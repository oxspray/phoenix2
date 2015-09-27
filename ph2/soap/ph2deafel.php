<?php

include('ph2_occurrence.class.php');

require_once('../../settings.php');
require_once('../framework/php/framework.php');

# CONSTANTS
# ---------

$NAMESPACE = "http://www.rose.uzh.ch/phoenix/schema/ph2deafel.xsd";

/** String size of the left lemma context. */
define('LEFT_CONTEXT_WIDTH', 220);

/** String size of the right lemma context. */
define ('RIGHT_CONTEXT_WIDTH', 225);


# HELPER FUNCTIONS
# -----------------

function object_to_soap_response( $object ) {
	# encodes an object in a propper SOAP XML (WSDL compliant) format
	return new SoapVar($object, SOAP_ENC_OBJECT, "SOAPStruct", $NAMESPACE);
}

function getContextQueryString($lemmaWildcard, $left = true) {
	/*/
	Returns the mysql query string that retrieves the (left or right) context for all lemmata matched by the specified
	lemma wildcard.
	---
	@param lemmaWildcard string: The sql like-wildcard that matches the lemmata to be retrieved. E.g., 'a%'
	to retrieve all lemmata starting with 'a'.
	@param bool|true $left: Specifies the context. true: left context, false: right context.
	@return string: The mysql query string.
	/*/
	if ($left) {
		$contextWidth = LEFT_CONTEXT_WIDTH - 1;
		$contextName = "context_left";
		$context_substr = "substr($contextName, greatest(-$contextWidth, -char_length($contextName)))";
		$borders = "greatest(CAST( O.`Order` as signed ) - 100,0) as lborder, O.`Order`-1 as rborder";
	} else {
		$contextWidth = RIGHT_CONTEXT_WIDTH;
		$contextName = "context_right";
		$context_substr = "substr($contextName, 1, $contextWidth)";
		$borders = "O.`Order` + 1 as lborder, O.`Order` + 100 as rborder";
	}
	$queryString =
		"select OccurrenceId, $context_substr as $contextName
		from
		(select X.OccurrenceId, group_concat(T.Surface order by X.oorder separator ' ') as $contextName
		from
			(select occborder.OccurrenceId, O.TextID, O.TokenID, occborder.lborder, occborder.rborder, O.Order as oorder
			from
				(select O.OccurrenceID, TextID, $borders
				from LEMMA L join LEMMA_OCCURRENCE LC on L.LemmaID = LC.LemmaID
				join OCCURRENCE O on LC.OccurrenceID = O.OccurrenceID
				where L.LemmaIdentifier like '$lemmaWildcard') as occborder
			join OCCURRENCE O on occborder.TextID = O.TextID
			where `Order` >= lborder and `Order` <= rborder) as X
		join TOKEN T on T.TokenID = X.TokenID
		group by X.OccurrenceId, TextID) Y";
	return $queryString;
}

# WEBSERVICE FUNCTIONS
# --------------------

function getOccurrenceIDs ($lemma) {
	$occurrence_ids = array();
	// search the database for Lemmata with the given identifier
	$dao = new Table('LEMMA');
	$dao->from = 'LEMMA_OCCURRENCE join LEMMA on LEMMA_OCCURRENCE.LemmaID=LEMMA.LemmaID';
	$results = $dao->get( array('LemmaIdentifier' => $lemma) );
	foreach ($results as $occurrence) {
		$occurrence_ids[] = $occurrence['OccurrenceID'];
	}
	return $occurrence_ids;
}

function getOccurrencesOld ($lemma, $withContext) {
  	$occurrences = array();
  	$occurrence_ids = getOccurrenceIDs($lemma);
  	foreach ($occurrence_ids as $occurrence_id) {
	  	$occurrences[] = object_to_soap_response( new PH2Occurrence( $occurrence_id, $withContext ) );
  	}
  	return $occurrences;
}

function getOccurrences ($lemma, $withContext) {

	$dao = new Table('OCCURRENCE');
	// TODO: test for injecection-proofeness
	// TODO: maybe move to misc entity functions

	$contextLeftQueryString = getContextQueryString($lemma, true);
	$contextRightQueryString = getContextQueryString($lemma, false);

	$occsWithContext = "select * from (select O.OccurrenceID, O.TextID, O.Order, O.Div, T.Surface, TE.CiteID,
		XMLTagName as Descriptor,
		TD.Value as DescriptorValue, LemmaIdentifier, M.Value as MorphValue,
		max(IF(XMLTagName = 'd0', substr(TD.Value,1,4), null)) AS Year,
		max(IF(XMLTagName = 'd0', TD.Value, null)) as Date,
		max(IF(XMLTagName = 'type', TD.Value, null)) as Type,
		max(IF(XMLTagName = 'scripta', TD.Value, null)) as Scripta,
		max(IF(XMLTagName = 'rd0', TD.Value, null)) as Scriptorium
		from OCCURRENCE as O join TOKEN as T on O.TokenID=T.TokenID
		join TEXT as TE on O.TextID=TE.TextID
		join TEXT_DESCRIPTOR as TD on O.TextID=TD.TextID
		join DESCRIPTOR as D on TD.DescriptorID=D.DescriptorID
		left join LEMMA_OCCURRENCE as LO on O.OccurrenceID=LO.OccurrenceID
		left join LEMMA as L on LO.LemmaID=L.LemmaID
		left join LEMMA_MORPHVALUE as LM on L.LemmaID=LM.LemmaID
		left join MORPHVALUE as M on LM.MorphvalueID = M.MorphvalueID
		where L.LemmaIdentifier like '$lemma'
		group by O.OccurrenceID) A ";
		if ($withContext) {
			$occsWithContext = $occsWithContext.
			"left join " .
			"($contextLeftQueryString) B on (A.OccurrenceId = B.OccurrenceId) " .
			"left join " .
			"($contextRightQueryString) C on (A.OccurrenceId = C.OccurrenceId)";
		}

	$occurrences = array();
	foreach ($dao->query($occsWithContext) as $row) {
		// fill properties from row
        $occ = new PH2Occurrence(null, TRUE, TRUE);
		$occ->occurrenceID = $row['OccurrenceID'];
		$occ->surface = $row['Surface'];
		$occ->lemma = $row['LemmaIdentifier'];
		$occ->divisio = $row['Div'];
		$occ->sigel = $row['CiteID'];
		$occ->year = $row['Year'];
		$occ->date = $row['Date'];
		$occ->scripta = $row['Scripta'];
		$occ->scriptorium = $row['Scriptorium'];
		$occ->type = $row['Type'];
		$occ->url = 'http://www.rose.uzh.ch/docling/charte.php?t=' . $row['TextID'] . '&occ_order_number=' . $row['Order'];
		$occ->morphology = ''; // TODO
		//	$occ->lemmaPOS = $row['OccurrenceID']; TODO: how to get lemmaPOS?
		if ($withContext) {
			$occ->contextLeft = trim($row['context_left']);
			$occ->contextRight = trim($row['context_right']);
		}

		$occurrences[] = object_to_soap_response($occ);

    }
    return $occurrences;
}

function getOccurrenceDetails ($occurrenceID, $withContext) {
	$occurrence = new PH2Occurrence($occurrenceID, $withContext);
	return object_to_soap_response( $occurrence );
}

function getAllLemmata () {
	$lemma_identifiers = array();
	// get all Lemmata that have at least one Occurrence assigned from the database
	$dao = new Table('LEMMA');
	$dao->select = "distinct(LemmaID), LemmaIdentifier";
	$dao->from = "LEMMA natural join LEMMA_OCCURRENCE";
	$dao->orderby = "LemmaIdentifier COLLATE utf8_roman_ci";
	$results = $dao->get();
	foreach ($results as $lemma) {
		$lemma_identifiers[] = $lemma['LemmaIdentifier'];
	}
	return $lemma_identifiers;
}

# SOAP SERVER
# -----------

ini_set("soap.wsdl_cache_enabled", "0"); // ENABLE FOR TESTING!

$server = new SoapServer("ph2deafel.wsdl");
$server->addFunction("getOccurrences");
$server->addFunction("getOccurrenceDetails");
$server->addFunction("getOccurrenceIDs");
$server->addFunction("getAllLemmata");
// run the server
$server->handle();

?>