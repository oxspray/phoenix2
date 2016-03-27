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

$occurrenceIds = getOccurrenceIDs('sans');

$withContext = true;
$success = true;

foreach ($occurrenceIds as $occurrenceId) {
    $occOld = getOccurrenceDetailsOld($occurrenceId, $withContext);
    $occNew = getOccurrenceDetails($occurrenceId, $withContext);

    // we compare based on soap object enc_value since php '==' operator is buggy; seriously shitty php
    if ($occOld->enc_value != $occNew->enc_value){
        echo "Failure!\n";
        $success = false;
        break;
    }
}

if ($success) {
    echo "Success! \n";
}


?>