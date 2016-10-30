<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */

function compareOccsArrays($occs1, $occs2) {
    if (count($occs1) !== count($occs2)) {
        echo "count(occs1)=", count($occs1), "!== count(occs2)=", count($occs2);
        return false;
    }

    for ($i = 0; $i < count($occs1); $i++) {
        $occ1 = $occs1[$i];
        $occ2 = $occs2[$i];

        if ($occ1 != $occ2) {
            print_r($occ1);
            print_r($occ2);
            echo "Failure!\n";
            return false;
        }
    }
    return true;
}

?>