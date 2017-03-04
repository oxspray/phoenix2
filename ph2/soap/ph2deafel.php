<?php

include('ph2_occurrence.class.php');
include('ph2_lemma.class.php');

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

/** Maximum number of occurrences return per get request. */
define('MAX_OCCS', 2000);

/** Maximum number of occurrence ids return per get request. */
define('MAX_OCC_IDS', 100000);

/** enable soap -> disable rest, and vice versa.*/
define ('SOAP_ENABLED', False);

/** Default values for newly created lemmas. */
define('DEFAULT_CONCEPT', 'c');
define('DEFAULT_PROJECT_ID', 1);
define('DEFAULT_SURFACE', null);
define('DEFAULT_MORPH_PARAMS', null);


# HELPER FUNCTIONS
# -----------------

/**
 * @param $object object to wrap into SoapVar.
 * @return SoapVar if SOAP_ENABLED, the unmodified object otherwise.
 */
function object_to_soap_response( $object ) {
    if (SOAP_ENABLED) {
        # encodes an object in a propper SOAP XML (WSDL compliant) format
        return new SoapVar($object, SOAP_ENC_OBJECT, "SOAPStruct", $NAMESPACE);
    } else {
        return $object;
    }
}

/**
 * Retrieves the occurrences for the specified $mainLemma and $lemma, or, if the $mainLemma and the $lemma are
 * both null, for the specified occurrence id.
 *
 * @param $lemma the lemma. can contain mysql where like wildcards, e.g., 'fa%'.
 * @param $mainLemma the mainLemma identifier. Can contain mysql where like wildcards.
 * @param $occurrenceId the id for the occurrence to retrieve. Used if both $mainLemma and $lemma are null.
 * @param $withContext whether the occurrences should be retrieved with or without context
 * @return array of occurrences. The array has size <= 1 if we retrieve by occurrence id.
 */
function _getOccurrencesForLemmaOrOccurrenceId ($mainLemma, $lemma, $occurrenceId, $withContext) {
    // TODO: maybe move to misc entity functions

    $dao = new Table('OCCURRENCE');
    // TODO extract escaping to function with array arg
    $mainLemma = mysql_real_escape_string($mainLemma);
    $lemma = mysql_real_escape_string($lemma);
    $withContext = mysql_real_escape_string($withContext);

	$contextLeftQueryString = _getContextQueryString($mainLemma, $lemma, $occurrenceId, true);
	$contextRightQueryString = _getContextQueryString($mainLemma, $lemma, $occurrenceId, false);

	// TODO: the min(LemmaIdentifier) is still bad
	$occsWithContext = "select * from (select O.OccurrenceID, O.TextID, O.Order, O.Div, T.Surface, TE.CiteID,
		min(LemmaIdentifier) as LemmaIdentifier,
		min(MainLemmaIdentifier) as MainLemmaIdentifier,
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
        _whereClause($mainLemma, $lemma, $occurrenceId).
		" group by O.OccurrenceID) A ";
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
		$occ->mainLemma = empty($row['MainLemmaIdentifier']) ? null : $row['MainLemmaIdentifier']; // empty string -> null
		$occ->divisio = $row['Div'];
		$occ->sigel = $row['CiteID'];
		$occ->year = $row['Year'];
		$occ->date = $row['Date'];
		$occ->scripta = $row['Scripta'];
		$occ->scriptorium = $row['Scriptorium'];
		$occ->type = $row['Type'];
		$occ->url = 'http://www.rose.uzh.ch/docling/charte.php?t=' . $row['TextID'] . '&occ_order_number=' . $row['Order'];
		$occ->morphology = ''; // TODO: morphValue; concat in Samuel's old code:
								// foreach ($morphvalues as $morphvalue) {$this->morphology .= $morphvalue . '';
		//	$occ->lemmaPOS = $row['OccurrenceID']; TODO: lemmaPos: lemma_morphvalues concatenated:
								// while ( !empty($lemma_morph) ) $lemma_morph_string .= " " . array_shift($lemma_morph);
		if ($withContext) {
			$occ->contextLeft = trim($row['context_left']);
			$occ->contextRight = trim($row['context_right']);
		}

		$occurrences[] = object_to_soap_response($occ);

	}
	return $occurrences;
}

function _getContextQueryString($mainLemmaWildcard, $lemmaWildcard, $occurrenceId, $left = true) {
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
                  _whereClause($mainLemmaWildcard, $lemmaWildcard, $occurrenceId)
                .") as occborder
			join OCCURRENCE O on occborder.TextID = O.TextID
			where `Order` >= lborder and `Order` <= rborder) as X
		join TOKEN T on T.TokenID = X.TokenID
		group by X.OccurrenceId, TextID) Y";
	return $queryString;
}

function _whereClause($mainLemmaWildcard, $lemmaWildcard, $occurrenceId) {
    if ($lemmaWildcard == null && $mainLemmaWildcard == null) {
        return "O.OccurrenceId = $occurrenceId";
    }
    return toSQLStringOptional(array('MainLemmaIdentifier' => $mainLemmaWildcard, 'LemmaIdentifier' => $lemmaWildcard));
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
function _getChunkRange($chunk, $chunkSize, $listSize) {

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

function _getNumberOfOccurrences($mainLemma, $lemma) {

	$dao = new Table('Occurrence');
    $dao->select = "count(*) as occ_count";
    $dao->from = "LEMMA l natural join LEMMA_OCCURRENCE lo";
    $dao->where = toSQLStringOptional(array('MainLemmaIdentifier' => $mainLemma, 'LemmaIdentifier' => $lemma));
    return $dao->get()[0]['occ_count'];
}

# WEBSERVICE FUNCTIONS
# --------------------


/**
 * Returns a list of occurrence ids for the specified $mainLemma, $lemma combination.
 * The occurrences are ordered by OccurrenceID asc.
 * @param $mainLemma the main lemma identifier
 * @param $lemma the lemma identifier
 * @param $guarded if false, does not throw exception if number of occurrences is greater than MAX_OCCS
 * @return array
 * @throws Exception if occurrenceIDs for the specified $mainLemma, $lemma is greater than MAX_OCCS (only thrown if
 * guarded is true, which is the default value)
 */
function getOccurrenceIDs($mainLemma = null, $lemma = null) {

    $occurrence_ids = array();
    $dao = _guardedOccurrenceIdsForLemma($mainLemma, $lemma, MAX_OCC_IDS);

    $dao->select = "OccurrenceID";

    $results = $dao->get();
    foreach ($results as $occurrence) {
        $occurrence_ids[] = $occurrence['OccurrenceID'];
    }
    return $occurrence_ids;
}


function _guardedOccurrenceIdsForLemma($mainLemma, $lemma, $guardValue, $guarded = true) {
    $dao = new Table('LEMMA');
    $dao->select = "count(OccurrenceID) as count";
    $dao->from = 'LEMMA_OCCURRENCE natural join LEMMA natural join OCCURRENCE';
    $dao->orderby = 'OccurrenceId asc';
    $dao->where = toSQLStringOptional(array('MainLemmaIdentifier' => $mainLemma, 'LemmaIdentifier' => $lemma));

    // check for number of occurrences restriction
    $number = $dao->get()[0]["count"];
    if($guarded && $number > $guardValue) {
        throw new Ph2DeafelException("Too many occurrences ($number) for mainLemma: '$mainLemma', lemma: '$lemma'."
            ." Use more restrictive query or chunking.", $messageCode="too.manny.occurrences");
    }

    return $dao;
}

function getOccurrences ($mainLemma, $lemma, $withContext) {

    _guardedOccurrenceIdsForLemma($mainLemma, $lemma, MAX_OCCS);

	return _getOccurrencesForLemmaOrOccurrenceId($mainLemma, $lemma, null, $withContext);
}

function getOccurrenceDetails ($occurrenceID, $withContext) {
	$occurrences = _getOccurrencesForLemmaOrOccurrenceId(null, null, $occurrenceID, $withContext);
	return $occurrences[0];
}

function getAllLemmata () {
	$lemma_identifiers = array();
	// get all Lemmata that have at least one Occurrence assigned from the database
	$dao = new Table('LEMMA');
	$dao->select = "distinct(LemmaID), LemmaIdentifier, MainLemmaIdentifier";
	$dao->from = "LEMMA natural join LEMMA_OCCURRENCE";
	$dao->orderby = "LemmaIdentifier COLLATE utf8_roman_ci";
	$results = $dao->get();
	foreach ($results as $lemma) {
		$lemma_identifiers[] = array($lemma['MainLemmaIdentifier'], $lemma['LemmaIdentifier']);
	}
	return $lemma_identifiers;
}

/**
 * Gets the number of chunks for the occurrence list of the specified lemma. See also
 * getChunkeRange().
 * @param $lemma
 * @return float
 */
function getNumberOfOccurrenceChunks($mainLemma, $lemma) {
	return ceil(_getNumberOfOccurrences($mainLemma, $lemma) / CHUNK_SIZE);
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
function getOccurrencesChunk($mainLemma, $lemma, $withContext, $chunk) {

	$occurrenceIds = getOccurrenceIDs($mainLemma, $lemma);

	$chunkRange = _getChunkRange($chunk, CHUNK_SIZE, count($occurrenceIds));
	$occurrences = array();

	for ($i = $chunkRange[0]; $i <= $chunkRange[1]; ++$i) {
		$occurrenceId = $occurrenceIds[$i];
		$occurrences[] = _getOccurrencesForLemmaOrOccurrenceId(null, null, $occurrenceId, $withContext)[0];
	}
	return $occurrences;

}

# Write functions
# ---------------

/**
 * Assigns the occurrence identified by $occurrenceID to the lemma partially identified by combination of
 * $newMainLemmaIdentifier, $newLemmaIdentified. Removes the occurrence from its old lemma(s).
 *
 * If the new lemma does not exist, it creates it. In this case, it uses default values for the lemma attributes
 * projectId, concept, morphvalues, and surface. (DEFAULT_PROJECT_ID, DEFAULT_CONCEPT, , ...).
 *
 * @throws Exception with exception code 0 if the combination ($newMainLemmaIdentifier, $newLemmaIdentifier) is not
 * unique, i.e., there exist more than one lemma with this combination.
 */
function assignOccurrencesToLemma($occurrenceIDs, $newMainLemmaIdentifier, $newLemmaIdentifier) {

    $dao = new Table('LEMMA');
    $q = "select * from lemma where mainLemmaIdentifier = '$newMainLemmaIdentifier' 
          and lemmaIdentifier = '$newLemmaIdentifier'";
    $lemmaRows = $dao->query($q);
    $lemmaCount = count($lemmaRows);

    if ($lemmaCount == 0) {
        $lemma = new Lemma($newLemmaIdentifier, DEFAULT_CONCEPT, DEFAULT_PROJECT_ID, DEFAULT_SURFACE,
            DEFAULT_MORPH_PARAMS, $newMainLemmaIdentifier);
    } else if ($lemmaCount == 1) {
        $lemma = new Lemma((int)$lemmaRows[0]['LemmaID']);
    } else if ($lemmaCount > 1) {
        throw new Exception("Lemma ($newMainLemmaIdentifier, $newLemmaIdentifier) not unique.", 0);
    }

    foreach ($occurrenceIDs as $occurrenceID) {
        try {
            _assignOccurrenceToLemma($occurrenceID, $lemma);
        } catch (Exception $e) {
            if ($e->getCode() == 1) {
                $nonExistentOccurrenceIds[] = $occurrenceID;
            } else {
                $errorOccs[] = array('id' => $occurrenceID, 'error' => $e->getMessage());
            }
        }
    }
    $result['createdNewLemma'] = $lemmaCount == 0;
    $result['assignedToLemma'] = new PH2Lemma($lemma);
    $result['nonExistentOccurrenceIds'] = $nonExistentOccurrenceIds;
    $result['errorOccurrences'] = $errorOccs;
    return $result;
}

/**
 * Assigns the occurrence identified by $occurrenceID to $lemma. Removes occurrence from previously assigned lemma.
 *
 * @throws Exception with exception code 1 if the occurrence with $occurrenceID does not exist.
 */
function _assignOccurrenceToLemma($occurrenceID, $lemma) {

    $dao = new Table('OCCURRENCE');
    $occ = $dao->get("occurrenceId = $occurrenceID")[0];
    if ($occ == null) {
        throw new Exception("Occurrence with occurrenceID $occurrenceID does not exist.", 1);
    }

    $lemma->assignOccurrenceID($occurrenceID); // existing lemma assignment is deleted.
}

/**
 * Retrieves the lemma(s) assigned to $occurrenceID from the db.
 * @return the list of lemmas as mysql_query result set. null if no lemma could be retrieved.
 * @see Table::query()
 */
function _retrieveLemmaList($occurrenceID) {
    $dao = new Table('LEMMA_OCCURRENCE');
    $r = $dao->query("select l.LemmaId as LemmaID
      from Occurrence o join Lemma_Occurrence lo on (o.OccurrenceID = lo.OccurrenceID)
      join Lemma l on (lo.lemmaId = l.lemmaId)
      where o.OccurrenceID = $occurrenceID");
    return $r;
}

/**
 * Class: GearmanException
 *
 * @property-read  $ Prop description
 * @property-read  $ Prop description
 * @property-read  $ Prop description
 */
class Ph2DeafelException extends Exception {
    /**
     */
    public $messageCode;

    public function __construct($message, $messageCode, $code = 0, Exception $previous = null) {
        $this->messageCode = $messageCode;
        parent::__construct($message, $code, $previous);
    }


}


# SOAP SERVER
# -----------
if (SOAP_ENABLED) {
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
}

?>