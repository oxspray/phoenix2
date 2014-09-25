<?php

include('ph2_occurrence.class.php');

require_once('../../settings.php');
require_once('../framework/php/framework.php');

# CONSTANTS
# ---------

$NAMESPACE = "http://www.rose.uzh.ch/phoenix/schema/ph2deafel.xsd";


# PRIVATE FUNCTIONS
# -----------------

function object_to_soap_response( $object ) {
	# encodes an object in a propper SOAP XML (WSDL compliant) format
	return new SoapVar($object, SOAP_ENC_OBJECT, "SOAPStruct", $NAMESPACE);
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

function getOccurrences ($lemma, $withContext) {
  	$occurrences = array();
  	$occurrence_ids = getOccurrenceIDs($lemma);
  	foreach ($occurrence_ids as $occurrence_id) {
	  	$occurrences[] = object_to_soap_response( new PH2Occurrence( $occurrence_id, $withContext ) );
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