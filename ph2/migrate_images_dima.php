<?php
// PH2 UNMANAGED SCRIPT

/*
Migrates DIMA's images contained in the data/media folder, according to texts stored in the DB and image file names.
*/

require_once('../settings.php');
require_once('framework/php/framework.php');

$img_dir = PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_MEDIA;
$project_id = 1;

$counter = 0;

//get the relevant corpora
$corpora = array( new Corpus(7), new Corpus(8), new Corpus(9), new Corpus(11) );

//iterate over corpora
foreach($corpora as $corpus) {
	switch ($corpus->getName()) {
		case 'chJu': $corpus_prefix = 'Ju';
			break;
		case 'chHS': $corpus_prefix = 'Hs';
			break;
		case 'chN':  $corpus_prefix = 'chN';
			break;
		case 'chSL': $corpus_prefix = 'SL';
			break;
		default: die('CORPUS NOT RECOGNIZED. STOPPING SCRIPT.');
	}
	$texts = $corpus->getAssignedTexts();
	//iterate over texts
	foreach($texts as $text) {
		// get the text's Zitf
		$text_citeID = $text->getCiteID();
		// adapt the zitf
		preg_match('/\d{3}/',$text_citeID,$matches);
		$img_number = $matches[0];
		// compose filename with wildcard ending
		$potential_filename = $corpus_prefix . ' ' . $img_number . '*.*';
		// look for images in $img_dir
		echo "\n\n<br/><br/>";
		echo $potential_filename;
		foreach( glob($img_dir . DIRECTORY_SEPARATOR . $potential_filename) as $full_filepath) {
			// extract img name
			$img_name = end( explode( DIRECTORY_SEPARATOR, $full_filepath) );
			$img_name = str_replace("\n", '', $img_name);
			$img_descr = $img_name;
			echo "\n<br/> - $img_name";
			// compose filepath to be stored in the DB
			$img_filepath_for_db = PH2_FP_MEDIA . DIRECTORY_SEPARATOR . $img_name;
			// assign image to the text
			$img = new Image($img_filepath_for_db, $img_name);
			$img->linkToText($text->getID());
			$counter++;
		}
	}
}

echo "Successful. Linked $counter images to their corresponding text.";

//get all texts currently stored in the DB, alongside with their 

?>