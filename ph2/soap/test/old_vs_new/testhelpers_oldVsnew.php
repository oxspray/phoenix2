<?php
/**
 * Created by PhpStorm.
 * User: ckuehne
 * Date: 05.09.15
 * Time: 19:55
 */


function toNewOccurrence($occOld) {
    $occNew = new PH2Occurrence($occOld->occurrenceID);

    $occNew->occurrenceID = $occOld->occurrenceID;
    $occNew->surface = $occOld->surface;
    $occNew->lemma = $occOld->lemma;
    $occNew->lemmaPOS = $occOld->lemmaPOS;
    $occNew->morphology = $occOld->morphology;
    $occNew->divisio = $occOld->divisio;
    $occNew->sigel = $occOld->sigel;
    $occNew->year = $occOld->year;
    $occNew->date = $occOld->date;
    $occNew->scripta = $occOld->scripta;
    $occNew->scriptorium = $occOld->scriptorium;
    $occNew->url = $occOld->url;

    // dynamically set properties
    if($occOld->type)
        $occNew->type = $occOld->type;
    if($occOld->contextRight)
        $occNew->contextRight = $occOld->contextRight;
    if($occOld->contextLeft)
        $occNew->contextLeft = $occOld->contextLeft;

    return $occNew;
}

function compareOccsArrays($occsOld, $occsNew) {
    if (count($occsOld) !== count($occsNew)) {
        echo "count(occs1)=", count($occsOld), "!== count(occs2)=", count($occsNew);
        return false;
    }

    for ($i = 0; $i < count($occsOld); $i++) {
	if (!compareOccs($occsOld[$i], $occsNew[$i])) {
            return false;
        }
    }
    return true;
}

function compareOccs($occOld, $occNew) {
    $occOldNew = toNewOccurrence($occOld);
    if ($occOldNew != $occNew) {
        echo "old vs new \n";
        print_r($occOldNew);
        print_r($occNew);
        echo "Failure!\n";
        return false;
    }
    return true;
}

?>