<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */

function compareOccsArrays($occs1, $occs2) {
    if (count($occs1) !== count($occs2)) {
        echo "\ncount(occs1)=", count($occs1), " !== count(occs2)=", count($occs2);
        return false;
    }

    for ($i = 0; $i < count($occs1); $i++) {
        $occ1 = $occs1[$i];
        $occ2 = $occs2[$i];

        // we compare based on soap object enc_value since php '==' operator is buggy; seriously shitty php
        if ($occ1->enc_value != $occ2->enc_value) {
            print_r($occ1->enc_value);
            print_r($occ2->enc_value);
            echo "\nFailure!\n";
            return false;
        }
    }
    return true;
}

?>