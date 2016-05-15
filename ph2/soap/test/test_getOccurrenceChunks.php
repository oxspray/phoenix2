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

$lemma = 'mot';

$numChunks = getNumberOfOccurrenceChunks($lemma);
echo "numChunks ", $numChunks, "\n";

$occs = array();
for ($i = 0; $i < $numChunks; $i++) {
    $newOccs = getOccurrencesChunk($lemma, False, $i);
    $occs = array_merge($occs, $newOccs);
}

$excpectedOccs = getOccurrences($lemma, False);

$success = compareOccsArrays($excpectedOccs, $occs);

if ($success) {
    echo "Success! \n";
} else {
    echo "Failure! \n";
}
echo "done";

?>