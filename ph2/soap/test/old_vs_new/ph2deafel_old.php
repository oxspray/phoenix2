<?php

include('ph2_occurrence_old.class.php');

require_once('../../../../settings.php');
require_once('../../../framework/php/framework.php');

# CONSTANTS
# ---------

$NAMESPACE = "http://www.rose.uzh.ch/phoenix/schema/ph2deafel.xsd";


# HELPER FUNCTIONS
# -----------------

//function object_to_soap_response( $object ) {
//	# encodes an object in a propper SOAP XML (WSDL compliant) format
//	return new SoapVar($object, SOAP_ENC_OBJECT, "SOAPStruct", $NAMESPACE);
//}

# WEBSERVICE FUNCTIONS
# --------------------

/**
 * Returns a list of occurrence ids for the specified $lemma. The occurrences are ordered by OccurrenceID asc.
 * @param $lemma
 * @return array
 */
function getOccurrenceIDsOld ($lemma) {

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

function getOccurrencesOld ($lemma, $withContext) {
  	$occurrences = array();
  	$occurrence_ids = getOccurrenceIDsOld($lemma);
  	foreach ($occurrence_ids as $occurrence_id) {
	  	$occurrences[] = object_to_soap_response( new PH2OccurrenceOld( $occurrence_id, $withContext ) );
  	}
  	return $occurrences;
}

function getOccurrenceDetailsOld ($occurrenceID, $withContext) {
	$occurrence = new PH2OccurrenceOld($occurrenceID, $withContext);
	return object_to_soap_response( $occurrence );
}

function getAllLemmataOld () {
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

$server = new SoapServer("../../ph2deafel.wsdl");
$server->addFunction("getOccurrencesOld");
$server->addFunction("getOccurrenceDetailsOld");
$server->addFunction("getOccurrenceIDsOld");
$server->addFunction("getAllLemmataOld");

// run the server
$server->handle();

?>