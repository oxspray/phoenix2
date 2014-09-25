<?php
// PH2 UNMANAGED SCRIPT

/*
Migrates PAUL VIDESOTT's images contained in the data/media folder, according to texts stored in the DB and image file names.
The script assumes that all original corpus names (e.g., 55552) are stored as a comment in the corpora currently stored in the system's DB.
*/

require_once('../settings.php');
require_once('framework/php/framework.php');

$img_dir = PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_MEDIA;
$project_id = 1;

$counter = 0;

//get Paul's corpora
$corpora = array( new Corpus(15), new Corpus(16) );

//iterate over corpora
foreach($corpora as $corpus) {
	$texts = $corpus->getAssignedTexts();
	//iterate over texts
	foreach($texts as $text) {
		// get the text's Zitf
		$text_citeID = $text->getCiteID();
		// adapt the zitf
		$filename = str_replace(' ', '', $text_citeID);
		// compose filename with wildcard ending
		$potential_filename = $filename . '*.jpg';
		// look for images in $img_dir
		echo "\n\n<br/><br/>";
		echo $potential_filename;
		foreach( glob($img_dir . DIRECTORY_SEPARATOR . $potential_filename) as $full_filepath) {
			// extract img name
			$img_name = end( explode( DIRECTORY_SEPARATOR, $full_filepath) );
			$img_name = str_replace("\n", '', $img_name);
			$img_descr = $img_name;
			echo "\n<br/>— $img_name";
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