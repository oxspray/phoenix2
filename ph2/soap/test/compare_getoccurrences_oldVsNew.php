<?php
/**
 * Test getOccurrencesOld vs. getOccurrences. If the occurrence arrays returned by the two functions are not equal,
 * this test prints out the fields where the individual occurrences differ.
 *
 * Author: conny.kuehne@gmail.com
 **/

// for lemma 'de' we need all the memory we can get ;)
ini_set('memory_limit', '-1');

require_once('testhelpers.php');

// run relative to parent dir
chdir('..');

require_once('ph2deafel.php');


$lemma = 'de'; // many occs
//$lemma = 'mot'; // fast test, only a few occs
$withContext = true;

$occsOld = getOccurrencesOld($lemma, $withContext);
$occsNew = getOccurrences($lemma, $withContext);

/**
 * Compares two occurrences $a and $b based on their occurrenceId.
 *
 * @return int see strcmp
 */
function cmp($a, $b) {
    // to test with raw objects (without soap): remove wrapping into soap var from getOccurrences
    // and remove ->enc_value in the following line
    return strcmp($a->enc_value->occurrenceID, $b->enc_value->occurrenceID);
}

// sort both arrays by occurrenceId
usort($occsOld, "cmp");
usort($occsNew, "cmp");

$success = compareOccsArrays($occsOld, $occsNew);

if ($success) {
    echo "Success! \n";
} else {
    echo "Failure! \n";
}
echo "done";


?>