<?php
/**
 * Test getOccurrencesOld vs. getOccurrences. If the occurrence arrays returned by the two functions are not equal,
 * this test prints out the fields where the individual occurrences differ.
 *
 * Author: conny.kuehne@gmail.com
 **/

// for lemma 'de' we need all the memory we can get ;)
ini_set('memory_limit', '-1');

require_once('ph2deafel_old.php');
require_once('testhelpers_oldVsnew.php');
// run relative to grand parent dir
chdir('../..');
require_once('ph2deafel.php');

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


//$lemma = 'avoir'; // many occs
$lemma = 'bien';
$withContext = false;

$occsOld = getOccurrencesOld($lemma, $withContext);
$occsNew = getOccurrences($lemma, $withContext);
// sort both arrays by occurrenceId
usort($occsOld, "cmp");
usort($occsNew, "cmp");
$success = compareOccsArrays($occsOld, $occsNew);

$withContext = true;
$occsOld = getOccurrencesOld($lemma, $withContext);
$occsNew = getOccurrences($lemma, $withContext);
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