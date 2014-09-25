<?php
// PH2 UNMANAGED SCRIPT

/*
Migrates images contained in the data/media folder, according to texts stored in the DB and image file names.
The script assumes that all original corpus names (e.g., 55552) are stored as a comment in the corpora currently stored in the system's DB.
*/

require_once('../settings.php');
require_once('framework/php/framework.php');

$img_dir = PH2_FP_BASE . DIRECTORY_SEPARATOR . PH2_FP_MEDIA;
$project_id = 1;

$counter = 0;

//get all corpora currently stored in the DB
$project = new Project($project_id);
$corpora = $project->getAssignedCorpora();

//iterate over corpora
foreach($corpora as $corpus) {
	$texts = $corpus->getAssignedTexts();
	$corpus_number = $corpus->getDescription();
	//iterate over texts
	foreach($texts as $text) {
		// get the text's Zitf
		$text_citeID = $text->getCiteID();
		// get the actual number out of Zitf
		preg_match('/\d+/', $text_citeID, $matches);
		$text_number = $matches[0];
		while(strlen($text_number) < 4 ) {
			$text_number = '0' . $text_number;
		}
		// compose filename with wildcard ending
		$potential_filename = 'c' . $corpus_number . $text_number . '_*.*';
		// look for images in $img_dir
		echo $potential_filename;
		foreach( glob($img_dir . DIRECTORY_SEPARATOR . $potential_filename) as $full_filepath) {
			// extract img name
			$img_name = end( explode( DIRECTORY_SEPARATOR, $full_filepath) );
			$img_name = str_replace("\n", '', $img_name);
			$img_descr = explode('_', $img_name);
			$img_descr = $img_descr[1];
			// compose filepath to be stored in the DB
			$img_filepath_for_db = PH2_FP_MEDIA . DIRECTORY_SEPARATOR . $img_name;
			// assign image to the text
			$img = new Image($img_filepath_for_db, $old_text_id . ' (' . $img_descr . ')');
			$img->linkToText($text->getID());
			$counter++;
		}
	}
}

echo "Successful. Linked $counter images to their corresponding text.";

//get all texts currently stored in the DB, alongside with their 

?>