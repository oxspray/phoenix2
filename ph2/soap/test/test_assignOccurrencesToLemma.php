<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */

// run relative to parent dir
chdir('..');

require_once('ph2deafel.php');

// Set our assert options
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_WARNING, true);

if (strpos(PH2_DB_NAME, 'test') === false) {
    die('Use with test db only.'); // the test write to the db, therefore we do not use the production db
}

function result($method, $failed) {
    if ($failed) {
        echo "failed: $method.\n";
    } else {
        echo "succeeded: $method.\n";
    }
}

define('CONCEPT_SHORT_C', 'c');
define('CONCEPT_SHORT_P', 'p');

function setUp() {
    $dao = new Table('OCCURRENCE');
    $dao->delete("where OccurrenceID = OCCURRENCE_ID");

    $dao = new Table('Lemma_Occurrence');
    $dao->delete("true");

    $dao = new Table('Lemma');
    $dao->delete("true");

    $dao = new Table('Concept');
    $dao->insert(array('Short' => CONCEPT_SHORT_C, 'Name' => 'concept'));
    $dao->insert(array('Short' => CONCEPT_SHORT_P, 'Name' => 'person'));
}

function _getLemma($mainLemma, $lemma) {
    $dao = new Table('LEMMA'); // dao for the new lemma
    $q = "select * from lemma where mainLemmaIdentifier = '$mainLemma'
      and lemmaIdentifier = '$lemma'";
    $lemmaRows = $dao->query($q);

    $newLemmaCount = count($lemmaRows);
    if ($newLemmaCount == 0) {
        return null;
    } else if ($newLemmaCount == 1) {
        return new Lemma((int)$lemmaRows[0]['LemmaID']);
    }
}

function test_occurrence_does_not_exist() {
    $failed = true;
    setup();

    $errorOccs = assignOccurrencesToLemma(array('123456'), "blub", "blub");
    assert($errorOccs[0] = '123456');

    result(__FUNCTION__, false);
}

function test_occurrence_does_not_exist_but_other_occ_does()
{
    $failed = true;
    setup();

    $existingOcc = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $idNonExistingOcc = 123456;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($existingOcc->getID());

    $newLemma = new Lemma('blub', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blubMain');

    $result = assignOccurrencesToLemma(array($idNonExistingOcc, $existingOcc->getID()), "blub", "blub");

    assert($existingOcc->getLemmaID() != $oldLemma->getID(), "oldLemma == newLemma");
    assert($existingOcc->getLemma()->getIdentifier() == "blub", "new lemma for existing occ must be 'blub'");

    assert(sizeof($result['nonExistentOccurrenceIds']) == 1);
    assert($result['nonExistentOccurrenceIds'][0] == $idNonExistingOcc);

    result(__FUNCTION__, false);
}



function test_occurrence_was_not_assigned_before() {
    setUp();
    $failed = true;
    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is

    $dao = new Table('LEMMA_OCCURRENCE');
    $lemma_occ = $dao->get(array('OccurrenceID' => $occ->getID()));

    assert($lemma_occ == null, "Occurrence must not be assigned to a lemma.\n");

    $failed = true;

    assignOccurrencesToLemma(array($occ->getID()), "blub", "blub");
    assert($occ->getLemma()->getIdentifier() == 'blub', "new lemma must be blub");
    assert($occ->getLemma()->getMainLemmaIdentifier() == 'blub', "new main lemma must be blub");

    $failed = false;
    result(__FUNCTION__, $failed);
}

function test_occurrence_assigned_to_more_than_one_old_lemma() {
    setUp();
    $failed = true;

    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;
    $mainLemmaIdentifier = "blub";

    $lemma1 = new Lemma('blub1', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, $mainLemmaIdentifier);
    $lemma2 = new Lemma('blub2', CONCEPT_SHORT_P, $projectId, $surface, $morphVals, $mainLemmaIdentifier);

    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is

    $dao = new Table('LEMMA_OCCURRENCE');
    $dao->insert(array('OccurrenceID' => $occ->getID(), 'LemmaID' => $lemma1->getID()));
    $dao->insert(array('OccurrenceID' => $occ->getID(), 'LemmaID' => $lemma2->getID()));

    assignOccurrencesToLemma(array($occ->getID()), "blub", "blub");
    assert($occ->getLemma()->getIdentifier() == 'blub', "new lemma must be blub");
    assert($occ->getLemma()->getMainLemmaIdentifier() == 'blub', "new main lemma must be blub");

    result(__FUNCTION__, false);
}

function test_newLemma_not_unique() {
    $failed = true;

    $lemma1 = new Lemma('blub', CONCEPT_SHORT_C, 1, null, null, "blubMain");
    $lemma2 = new Lemma('blub', CONCEPT_SHORT_P, 1, null, null, "blubMain");

    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is

    try {
        assignOccurrencesToLemma(array($occ->getID()), "blubMain", "blub");
    } catch (Exception $e) {
        assert($e->getCode() == 0);
        $failed = false;
    }
    result(__FUNCTION__, $failed);
}

function test_new_lemma_does_not_exist() {
    setUp();
    $failed = true;

    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($occ->getID());

    $beforeLemma = $occ->getLemma();
    assert($beforeLemma->getID() == $oldLemma->getID(), "precondition: occ must be assigned to blub, blub");
    assert(!_getLemma('blubMain', 'blub'), 'precondition: lemma blub, blub must not exist');

    assignOccurrencesToLemma(array($occ->getID()), 'blubMain', 'blub');

    $newLemma = $occ->getLemma();
    _assertNewLemmaCorrectIdentifiers($newLemma, $oldLemma->getID(), 'blubMain', 'blub');

    // other values must have been copied from old assigned lemma
    assert($newLemma->getProjectID() == DEFAULT_PROJECT_ID, "projectId");
    assert($newLemma->getConcept() == DEFAULT_CONCEPT, "concept");
    assert($newLemma->getSurface() == DEFAULT_SURFACE, "surface");
    $failed = false;

    result(__FUNCTION__, $failed);

}

function test_new_lemma_exists() {
    setUp();
    $failed = true;

    $occ = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($occ->getID());
    $oldLemma->assignOccurrenceID($occ->getID());

    // create new lemma
    $newLemma = new Lemma('blub', CONCEPT_SHORT_P, 2, 'newSurface', $morphVals, 'blubMain');

    $beforeLemma = $occ->getLemma();
    assert($beforeLemma->getID() == $oldLemma->getID(), "precondition: occ must be assigned to blub, blub");
    assert(_getLemma('blubMain', 'blub'), 'precondition: lemma blubMain, blub must exist');

    assignOccurrencesToLemma(array($occ->getID()), 'blubMain', 'blub');

    _assertNewLemmaCorrectIdentifiers($occ->getLemma(), $oldLemma->getID(), 'blubMain', 'blub');

    // other values must be the same as before
    assert($newLemma->getProjectID() == 2, "projectId");
    assert($newLemma->getConcept() == CONCEPT_SHORT_P, "concept");
    assert($newLemma->getSurface() == newSurface, "surface");
    $failed = false;

    result(__FUNCTION__, $failed);

}

function test_new_lemma_exists_multiple_occs() {
    setUp();
    $failed = true;

    $occ1 = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $occ2 = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($occ1->getID());
    $oldLemma->assignOccurrenceID($occ2->getID());

    // create new lemma
    $newLemma = new Lemma('blub', CONCEPT_SHORT_P, 2, 'newSurface', $morphVals, 'blubMain');

    assignOccurrencesToLemma(array($occ1->getID(), $occ2->getID()), 'blubMain', 'blub');

    _assertNewLemmaCorrectIdentifiers($occ1->getLemma(), $oldLemma->getID(), 'blubMain', 'blub');
    _assertNewLemmaCorrectIdentifiers($occ2->getLemma(), $oldLemma->getID(), 'blubMain', 'blub');

    $failed = false;

    result(__FUNCTION__, $failed);
}

function test_new_lemma_exists_multiple_occs_one_with_error() {
    setUp();
    $failed = true;

    $occ1 = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $occ2 = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($occ1->getID());
    $oldLemma->assignOccurrenceID($occ2->getID());

    // create new lemma
    new Lemma('blub', CONCEPT_SHORT_P, 2, 'newSurface', $morphVals, 'blubMain');

    assignOccurrencesToLemma(array($occ1->getID(), $occ2->getID()), 'blubMain', 'blub');

    _assertNewLemmaCorrectIdentifiers($occ1->getLemma(), $oldLemma->getID(), 'blubMain', 'blub');
    _assertNewLemmaCorrectIdentifiers($occ2->getLemma(), $oldLemma->getID(), 'blubMain', 'blub');

    $failed = false;

    result(__FUNCTION__, $failed);
}

function test_was_already_assigned_to_the_new_lemma() {
    setUp();
    $failed = true;

    $occ1 = new Occurrence(1234, 140, 1); // 1234 is the token id, whatever that is
    $projectId = 1;
    $surface = "bla";
    $morphVals = null;

    $oldLemma = new Lemma('bla', CONCEPT_SHORT_C, $projectId, $surface, $morphVals, 'blaMain');
    $oldLemma->assignOccurrenceID($occ1->getID());

    assignOccurrencesToLemma(array($occ1->getID()), 'blaMain', 'bla');

    assert($occ1->getLemmaID() == $oldLemma->getID(), "oldLemma != newLemma");

    $failed = false;

    result(__FUNCTION__, $failed);
}

/**
 * Asserts that $newLemma is has a different ID then $oldLemmaId, and that the $newLemma has the specified
 * $mainLemmaIdentifier, $lemmaIdentifier.
 */
function _assertNewLemmaCorrectIdentifiers($newLemma, $oldLemmaId, $mainLemmaIdentifier, $lemmaIdentifier) {
    assert($newLemma->getID() != $oldLemmaId, "new lemma id must not equal old lemma id");
    assert($newLemma->getMainLemmaIdentifier() == $mainLemmaIdentifier, "expected mainLemma $mainLemmaIdentifier");
    assert($newLemma->getIdentifier() == $lemmaIdentifier, "expected lemmaIdentifier $lemmaIdentifier");
}

test_occurrence_does_not_exist();
test_occurrence_does_not_exist_but_other_occ_does();
test_occurrence_was_not_assigned_before();
test_occurrence_assigned_to_more_than_one_old_lemma();
test_newLemma_not_unique();
test_new_lemma_does_not_exist();
test_new_lemma_exists();
test_new_lemma_exists_multiple_occs();
test_was_already_assigned_to_the_new_lemma();

echo "tests done";

?>