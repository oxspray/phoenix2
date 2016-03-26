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

//  array that getChunkRange returns  if there exists no chunk range
$null_array = array(0, -1);

function testExpectedRanges($expectedRanges, $chunkSize, $listSize) {
    for ($i = 0; $i < count($expectedRanges); $i++) {
        $chunkRange = getChunkRange($i, $chunkSize, $listSize);
        assert($chunkRange == $expectedRanges[$i]);
    }

    $chunkRange = getChunkRange(-1, $chunkSize, $listSize);
    assert($chunkRange == array(0, -1));
}

function testNormalCase() {
    $listSize = 26;
    $chunkSize = 10;
    $expectedRanges = array(
        array(0, 9),
        array(10, 19),
        array(20, 25),
        array(0, -1));
    testExpectedRanges($expectedRanges, $chunkSize, $listSize);

}

function testChunkSizeDividesListSize() {

    $listSize = 12;
    $chunkSize = 5;
    $expectedRanges = array(
        array(0, 4),
        array(5, 9),
        array(10, 11),
        array(0, -1));

    testExpectedRanges($expectedRanges, $chunkSize, $listSize);
}

testNormalCase();
testChunkSizeDividesListSize();

$listSize = 2;
$chunkSize = 1;
$expectedRanges = array(
    $null_array,
    $null_array);
testExpectedRanges($expectedRanges, $chunkSize, $listSize);

$listSize = 10;
$chunkSize = 20;
$expectedRanges = array(
    array(0, 9),
    $null_array);
testExpectedRanges($expectedRanges, $chunkSize, $listSize);

echo "done";

?>