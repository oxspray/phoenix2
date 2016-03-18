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

//$occurrenceIds = array(608709, 391345,443133);
$occurrenceIds = getOccurrenceIDs('sans');

$withContext = true;
$success = true;

foreach ($occurrenceIds as $occurrenceId) {
    $occ1 = getOccurrenceDetailsOld($occurrenceId, $withContext);
    $occ2 = getOccurrenceDetails($occurrenceId, $withContext);

    // we compare based on soap object enc_value since php '==' operator is buggy; seriously shitty php
    if ($occs1 != $occs2 || $occ1->enc_value != $occ2->enc_value){
        echo "Failure!\n";
        $success = false;
        break;
    }
}

if ($success) {
    echo "Success! \n";
}


?>