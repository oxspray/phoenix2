<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */

require_once('testhelpers.php');

// run relative to parent dir
chdir('..');

define('CHUNK_SIZE', 10);
require_once('ph2deafel.php');

$lemma = 'tab%';
$mainLemma = "";

$numChunks = getNumberOfOccurrenceChunks($mainLemma, $lemma);
echo "\nnumChunks ", $numChunks, "\n";

$occs = array();
for ($i = 0; $i < $numChunks; $i++) {
    $newOccs = getOccurrencesChunk($mainLemma, $lemma, False, $i);
    $occs = array_merge($occs, $newOccs);
}
$excpectedOccs = _getOccurrencesForLemmaOrOccurrenceId($mainLemma, $lemma, null, False);
$success = compareOccsArrays($excpectedOccs, $occs);

if ($success) {
    echo "\nSuccess! \n";
} else {
    echo "\nFailure! \n";
}
echo "done";

?>