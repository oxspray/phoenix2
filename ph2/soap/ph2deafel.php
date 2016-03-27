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

/** Maximum size of occurrence chunks. */
define('CHUNK_SIZE', 500);

# HELPER FUNCTIONS
# -----------------

function object_to_soap_response( $object ) {
	# encodes an object in a propper SOAP XML (WSDL compliant) format
	return new SoapVar($object, SOAP_ENC_OBJECT, "SOAPStruct", $NAMESPACE);
}

/**
 * Retrieves the occurrences for the specified lemma, or, if the lemma is null, for the specified occurrence id.
 *
 * @param $lemma the lemma. can contain mysql where like wildcards, e.g., 'fa%'. If null, use occurrence id.
 * @param $occurrenceId the id for the occurrence to retrieve
 * @param $withContext whether the occurrences should be retrieved with or without context
 * @return array of occurrences. The array has size <= 1 if we retrieve by occurrence id.
 */
function getOccurrencesForLemmaOrOccurrenceId ($lemma, $occurrenceId, $withContext) {


	$dao = new Table('OCCURRENCE');
	// TODO: test for injecection-proofeness
	// TODO: maybe move to misc entity functions

	$contextLeftQueryString = getContextQueryString($lemma, $occurrenceId, true);
	$contextRightQueryString = getContextQueryString($lemma, $occurrenceId, false);

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
		where ".
		($lemma != null ? "L.LemmaIdentifier like '$lemma'" : "O.OccurrenceId = $occurrenceId ").
		"group by O.OccurrenceID) A ";
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

function getContextQueryString($lemmaWildcard, $occurrenceId, $left = true) {
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
				where ".
				($lemmaWildcard != null ?
					"L.LemmaIdentifier like '$lemmaWildcard'"
					: "O.OccurrenceId = $occurrenceId").")
				as occborder
			join OCCURRENCE O on occborder.TextID = O.TextID
			where `Order` >= lborder and `Order` <= rborder) as X
		join TOKEN T on T.TokenID = X.TokenID
		group by X.OccurrenceId, TextID) Y";
	return $queryString;
}

/**
 * Returns the range values of the specified $chunk given the specified $chunkSize and the specified $listSize.
 * <p>
 * We want to partition a list of size $listSize into n+1 chunks c_0, c_1, ..., c_n, where each chunk
 * c_i, i!=n, has size $chunkSize, and the last chunk c_n has size <= $chunkSize.
 * This function computes the range of a chunk c_k, that is, the first and the last index of the chunk c_k
 * in the list. For example, setting $chunkSize=5, $listSize=12 yields the ranges [0, 4], [5, 9], and [10, 11]
 * for $chunk=0, $chunk=1, and $chunk=3, respectively.
 * @param $chunk the number of the chunk, zero-based
 * @param $chunkSize maximum size of a chunk.
 * @param $listSize the list size of the list that is to be sliced into chunks
 * @return array the chunk range, or array(0, -1) if there exists no chunk range for the
 * specified parameters
 */
function getChunkRange($chunk, $chunkSize, $listSize) {

	if ($chunk < 0 || $chunkSize < 2) {
		return array(0, -1);
	} else if ($chunk < floor($listSize / $chunkSize)) {
		return array($chunk * $chunkSize, $chunkSize * ($chunk + 1) - 1);
	} else if ($chunk == floor($listSize / $chunkSize) && $chunk * $chunkSize < $listSize) {
		return array($chunk * $chunkSize, $chunk * $chunkSize + $listSize % $chunkSize - 1);
	} else {
		return array(0, -1);
	}
}

function getNumberOfOccurrences($lemma) {

	$dao = new Table('Occurrence');
	$result = $dao->query("select count(*) as count
		from LEMMA l natural join LEMMA_OCCURRENCE lo
		where l.lemmaIdentifier like '$lemma';");
	return $result[0]['count'];
}

# WEBSERVICE FUNCTIONS
# --------------------


/**
 * Returns a list of occurrence ids for the specified $lemma. The occurrences are ordered by OccurrenceID asc.
 * @param $lemma
 * @return array
 */
function getOccurrenceIDs ($lemma) {

	$occurrence_ids = array();
	// search the database for Lemmata with the given identifier
	$dao = new Table('LEMMA');
	$dao->from = 'LEMMA_OCCURRENCE join LEMMA on LEMMA_OCCURRENCE.LemmaID=LEMMA.LemmaID';
    $dao->orderby = 'OccurrenceId asc';
	$results = $dao->get( array('LemmaIdentifier' => $lemma) );
	foreach ($results as $occurrence) {
		$occurrence_ids[] = $occurrence['OccurrenceID'];
	}
	return $occurrence_ids;
}

function getOccurrences ($lemma, $withContext) {
	return getOccurrencesForLemmaOrOccurrenceId($lemma, null, $withContext);
}

function getOccurrenceDetails ($occurrenceID, $withContext) {
	$occurrences = getOccurrencesForLemmaOrOccurrenceId(null, $occurrenceID, $withContext);
	return $occurrences[0];
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

/**
 * Gets the number of chunks for the occurrence list of the specified lemma. See also
 * getChunkeRange().
 * @param $lemma
 * @return float
 */
function getNumberOfOccurrenceChunks($lemma) {

	return ceil(getNumberOfOccurrences($lemma) / CHUNK_SIZE);
}

/**
 * Gets the specified $chunk for the specified $lemma with or without context.
 * <p>
 * We use chunking to avoid server timeouts. Some lemmas have such a high number of
 * occurrences that the time it takes to retrieve them with getOccurrences($lemma) takes longer
 * than the server timeout time. We use this function to transfer the occurrences of these lemmas
 * in multiple chunks, where each transfer takes less time than the server timeout.
 *
 * @param $lemma
 * @param $withContext
 * @param $chunk
 * @return array an array of occurrences.
 */
function getOccurrencesChunk($lemma, $withContext, $chunk) {

	$occurrenceIds = getOccurrenceIDs($lemma);

	$chunkRange = getChunkRange($chunk, CHUNK_SIZE, count($occurrenceIds));
	$occurrences = array();

	for ($i = $chunkRange[0]; $i <= $chunkRange[1]; ++$i) {
		$occurrenceId = $occurrenceIds[$i];
		$occurrences[] = getOccurrencesForLemmaOrOccurrenceId(null, $occurrenceId, $withContext)[0];
	}
	return $occurrences;

}

# SOAP SERVER
# -----------

ini_set("soap.wsdl_cache_enabled", "0"); // ENABLE FOR TESTING!

$server = new SoapServer("ph2deafel.wsdl");
$server->addFunction("getOccurrences");
$server->addFunction("getOccurrenceDetails");
$server->addFunction("getOccurrenceIDs");
$server->addFunction("getAllLemmata");
$server->addFunction("getOccurrencesChunk");
$server->addFunction("getNumberOfOccurrenceChunks");

// run the server
$server->handle();

?>