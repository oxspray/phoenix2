<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */

require_once('ph2deafel_old.php');

chdir('../..');
require_once('ph2deafel.php');

$withContext = true;
$occurrenceIds = getOccurrenceIDs(null, 'metre');

$start = microtime(true);
foreach ($occurrenceIds as $occurrenceId) {
    $occ1 = getOccurrenceDetailsOld($occurrenceId, $withContext);
}
$time_elapsed_secs = microtime(true) - $start;
echo "time taken old $time_elapsed_secs s\n";

$start = microtime(true);
foreach ($occurrenceIds as $occurrenceId) {
    $occ1 = getOccurrenceDetails($occurrenceId, $withContext);
}
$time_elapsed_secs = microtime(true) - $start;
echo "time taken new $time_elapsed_secs s";


?>