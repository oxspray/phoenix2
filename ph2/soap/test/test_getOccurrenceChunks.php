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

// lemma to test
$lemma = 'mo%';

$numChunks = getNumberOfOccurrenceChunks($lemma);
echo "numChunks ", $numChunks, "\n";

// get individual chunks and merge in one array
$mergedOccsFromChunks = array();
for ($i = 0; $i < $numChunks; $i++) {
    $newOccs = getOccurrencesChunk($lemma, False, $i);
    $mergedOccsFromChunks = array_merge($mergedOccsFromChunks, $newOccs);
}

// get lemma unchunked
$excpectedOccs = getOccurrences($lemma, False);

$success = compareOccsArrays($excpectedOccs, $mergedOccsFromChunks);

if ($success) {
    echo "\nSuccess! \n";
} else {
    echo "\nFailure! \n";
}
echo "done";

?>