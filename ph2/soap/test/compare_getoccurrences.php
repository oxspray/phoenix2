<?php
/**
 * Test getOccurrencesOld vs. getOccurrences. If the occurrence arrays returned by the two functions are not equal,
 * this test prints out the fields where the individual occurrences differ.
 *
 * Author: conny.kuehne@gmail.com
 **/

// run relative to parent dir
chdir('..');

require_once('ph2deafel.php');

//$lemma = 'de'; // fast test, only a few occs
$lemma = 'l\''; // many occs
$withContext = false;
$occs1 = getOccurrencesOld($lemma, $withContext);
$occs2 = getOccurrences($lemma, $withContext);

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

usort($occs1, "cmp");
usort($occs2, "cmp");

function detailed_diff($ocss1, $ocss2) {
    for ($i = 0; $i < count($ocss1); $i++) {
        $occsarr1 = (array)$ocss1[$i];
        $occsarr2 = (array)$ocss2[$i];
        echo "occ $i: \n";
        echo "=============\n";
        foreach ($occsarr1 as $key => $value) {
            if ($occsarr1[$key] != $occsarr2[$key]) {
                echo "differences in $key\n";
                var_dump($occsarr1[$key]);
                var_dump($occsarr2[$key]);
            }
        }

        echo "equal: ";
        var_dump($occsarr1 == $occsarr2);
    }
}


if ($occs1 == $occs2){
    echo "Success!";
} else {
    echo "Failure!\n";
    detailed_diff($occs1, $occs2);
}


?>