<?php
/* Phoenix2
** Project Lead: Martin-Dietrich Glessgen, University of Zurich
** Code by: Samuel Läubli, University of Zurich
** Contact: samuel.laeubli@uzh.ch
** ===
** These are handler functions communicating between the data models and views. A handler function is
** called by actionhandler.php (see that file for details).
** ---
** NOTE: Action handler functions must always take exactly two parameters ($get, $post) and assert
** essential $_GET-/$_POST-parameters!
*/

/**************
GENERAL ACTIONS
**************/

function redirect ( $get, $post ) { global $ps;
/* redirects to a module (tbe) */
	
	// ASSERTIONS
	assert(!empty($get['module']));
	
	// ROUTINE
	if ($ps->getNickname() == 'guest') {
		if ($get['module'] == 'ann.rst') {
			$ps->setCurrentModule('ann.rst');
		} else {
			$ps->setCurrentModule('ann.fnd'); // always redirect guests to ANNOTATION module
		}
	} else {
		$ps->setCurrentModule($get['module']);
	}
}

function logout ( $get, $post ) { global $ps;
/* logs out the current user and redirects to the login page */
	
	//ROUTINE
	$ps->logout();
}


/**************
USER MANAGEMENT
**************/

function CreateUser ( $get, $post ) { global $ps;
/* creates a User on the database */
	
	$password = encodePassword( $post['password']);
	$user_db_fields = array( 'Nickname' => $post['nickname'], 	'Password' => $password[0], 'PasswordSalt' => $password[1], 'Privilege' => 1, 'Fullname' => $post['fullname'], 'Mail' => $post['mail'] );
	$user = new User( $user_db_fields );
	$ps->notifications->push(new Notification('New user «'.$post['nickname'].'» created successfully.', 'ok'));
}

function UpdateUserDetails ( $get, $post ) { global $ps;
/* updates nickname, fullname, and e-mail for a given user */
	
	$user = new User( (int)$post['user_id'] );
	$user->change( array( 'Nickname' => $post['nickname'], 'Fullname' => $post['fullname'], 'Mail' => $post['mail']) );
	$ps->updateUser(); // load new user information into open session
	$ps->notifications->push(new Notification('All changes for user «'.$post['nickname'].'» have been saved successfully.', 'ok'));
}

function UpdateUserPassword ( $get, $post ) { global $ps;
/* updates nickname, fullname, and e-mail for a given user */
	
	$user = new User( (int)$post['user_id'] );
	
	if ($user->checkPassword( $post['current_password'] )) {
		$user->setPassword($post['new_password']);
		$ps->notifications->push(new Notification('The password has been updated.', 'ok'));
	} else {
		$ps->notifications->push(new Notification('Wrong current password. The password could not be updated.', 'err'));
	}
}

function UpdateUsers ( $get, $post ) { global $ps;
/* applies changes to a number of selected users */
	
	switch ($post['users_action']) {
		case 'delete':
			$counter_deleted_users = 0;
			$attempted_to_delete_current_user = FALSE;
			$current_user_id = $ps->getUserID();
			foreach ($post['user_id'] as $user_id) {
				if ($user_id != $current_user_id) {
					$counter_deleted_users++;
					$u = new User( (int)$user_id );
					$u->delete();
				} else {
					$attempted_to_delete_current_user = TRUE;
				}
			}
			$message = "$counter_deleted_users user(s) have been deleted.";
			if ($attempted_to_delete_current_user) {
				$ps->notifications->push(new Notification($message . ' The currently logged-in user has not been deleted.', 'note'));
			} else {
				$ps->notifications->push(new Notification($message, 'ok'));
			}
		break;
	}
	
}


/*****************************
TEXT/CORPUS/PROJECT MANAGEMENT
*****************************/

function AddTextFromXMLInputPOST ( $get, $post ) { global $ps;
/* takes an xml text submitted via form input and creates a corresponding entity on the system */
	
	$status = addTextFromXMLInput ( $_POST['xml'] , $_POST['name'] , $_POST['comment'] , $_POST['corpus_id'] , $_POST['migrate'] , $_POST['tokenize'] );
	if ($status == TRUE) {
		$ps->notifications->push(new Notification('The provided text was successfully imported and is now available on the system.', 'ok'));
	} else {
		$ps->notifications->push(new Notification($status, 'err'));
	}
}

function UploadCorpus ( $get, $post ) { global $ps;
/* takes a corpus file via form input and creates a new corpus on the system, importing all the texts stored in the file and assigning it to the new corpus */
	
	// ASSERTIONS
	assert($_POST);
	assert($_FILES);
	
	// ROUTINE
	// check if no fields are empty
	if ($_POST['name'] && $_FILES['corpusfile']) {
		
		// create new corpus
		$corpus = new Corpus($post['name'], $ps->getActiveProject(), $post['comment']);
		$corpus_id = $corpus->getID();
		
		if ($_FILES['corpusfile']['error'] > 0)
		{
			$ps->notifications->push(new Notification($_FILES["file"]["error"], 'err'));
		} else {
			// parse the xml file #TODO: add file restrictions, error handling!
			$xmlObject = simplexml_load_file($_FILES['corpusfile']['tmp_name']);
			
			// create file (the whole migrated corpus will be stored as a corpus file which will not be handled by the PH2 System)
			$corpus_file = "data/xml/migrated_corpora/" . $post['name'] . ".xml"; //must be emptied at first!
			$filehandler = fopen($corpus_file, 'w' /*empty file if it already exists*/) or die("Can't create file for whole corpus (see actions.php)");
			fclose($filehandler);
			$filehandler = fopen($corpus_file, 'a' /*append mode*/) or die("Can't open corpus file in append mode.");
			
			// write file header
			fwrite($filehandler, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
			fwrite($filehandler, '<corpus name="' . $post['name'] . '">' . "\n");
			fwrite($filehandler, '<!-- Migration from old XML Scheme. Created by Phoenix2 on ' . now() . " -->\n\n");
			
			// iterate over texts
			$i = 1;
			foreach ($xmlObject->gl as $text) {
				
				// A) Convert text into new XML Schema (XMLSchemaMigration)
				$mp = new XMLMigrationParser(NULL,NULL,FALSE,$_POST['tokenize']);
				$mp->input_xml= $text->asXML();
				$mp->parse();
				$migrated_text_xml = $mp->getOutputXML();
				unset($mp);
				
				// B) Add converted Text to the Database
				$p = new XMLTextParser();
				$p->input_xml= $migrated_text_xml;
				$p->text_name = $_POST['name'] . ' ' . $i;
				$p->text_corpusID = $corpus_id;
				$p->auto_description = TRUE;
				$p->parse();
				
				// additionally write migrated xml to corpus file
				fwrite($filehandler, xmlpp($p->getOutputXML()) . "\n");
				unset($p);
				
				$i++;
			}
			
			// close file
			fwrite($filehandler, "\n</corpus>");
			fclose($filehandler);
		}
		$i -= 1;
		$ps->notifications->push( new Notification("Corpus «".$post['name']."» successfully created. Its $i texts were parsed and are now available on the system. Click <a href=\"$corpus_file\">here</a> to download the contracted xml corpus file.", 'ok') );
	}
}

function AddCorpus ( $get, $post ) { global $ps;
/* adds a corpus to the system */
	
	// ASSERTIONS
	assert($_POST);
	
	// create new corpus
	$corpus = new Corpus($post['name'], $ps->getActiveProject(), $post['comment']);
	if ($corpus) {
		$ps->notifications->push( new Notification("Corpus «".$post['name']."» successfully created. It is now available on the system.", 'ok') );
	} else {
		$ps->notifications->push( new Notification("Error: New corpus could not be added.", 'err') );
	}
	
	// redirect if applicable
	$_POST['corpus_id'] = $corpus->getID();
	if ($get['next']) {
		$ps->setCurrentModule($get['next']);
	}
	
}

function UpdateCorpusDetails ( $get, $post ) { global $ps;
/* updates the details of a corpus */
	
	// ASSERTIONS
	assert($_POST);
	assert($post['corpus_id']);
	
	// update corpus
	$corpus = new Corpus( (int) $post['corpus_id'] );
	$corpus->setName($post['name']);
	$corpus->setDescription($post['comment']);
	savedSuccessfullyMsg();
	
}

function UpdateCorpora ( $get, $post ) { global $ps;
/* performs an action on a selection of corpora */
	
	// TEMP
	print_r($post);
	
}

function ChangeActiveCorpus ( $get, $post ) { global $ps;
/* changes the active corpus, meaning the corpus to work on in the session */
	
	// ASSERTIONS
	assert($get['corpusID']);
	
	// update session
	$corpus_id = (int) $get['corpusID'];
	$ps->setActiveCorpus( $corpus_id );
	
	// release notification
	$corpus = new Corpus( $corpus_id );
	$ps->notifications->push( new Notification("Corpus «".$corpus->getName()."» is now acitve in your working session.", 'ok') );
	
}

function AddProject ( $get, $post ) { global $ps;
/* adds a project to the system */

	// ASSERTIONS
	assert($_POST);
	
	// create new corpus
	$project = new Project($post['name'], $post['comment']);
	if ($project) {
		$ps->notifications->push( new Notification("Project «".$post['name']."» successfully created. It is now available on the system.", 'ok') );
	} else {
		$ps->notifications->push( new Notification("Error: New project could not be added.", 'err') );
	}
	
	// redirect if applicable
	$_POST['project_id'] = $project->getID();
	if ($get['next']) {
		$ps->setCurrentModule($get['next']);
	}

}

function UpdateProjectDetails ( $get, $post ) { global $ps;
/* updates the details of a project */

	// ASSERTIONS
	assert($_POST);
	assert($post['project_id']);
	
	// update corpus
	$project = new Project( (int) $post['project_id'] );
	$project->setName($post['name']);
	$project->setDescription($post['comment']);
	savedSuccessfullyMsg();

}

function UpdateProjects ( $get, $post ) { global $ps;
/* performs an action on a selection of projects */
	
	// TEMP
	print_r($post);

}

function ChangeActiveProject ( $get, $post ) { global $ps;
/* changes the active project, meaning the project to work on in the session */
	
	// ASSERTIONS
	assert($get['projectID']);
	
	// update session
	$project_id = (int) $get['projectID'];
	$ps->setActiveProject( $project_id );
	
	// release notification
	$project = new Project( $project_id );
	$ps->notifications->push( new Notification("Project «".$project->getName()."» is now acitve in your working session.", 'ok') );
	
}

function UpdateExportsTexts ( $get, $post ) { global $ps;
/* applies changes to a number of checked-out texts */
	
	switch ($post['exports_texts_action']) {
		case 'reset':
			$i = 0;
			$dao = new Table('CHECKOUT');
			foreach ($post['checkout_identifier_text'] as $identifier) {
				$dao->where = array('Identifier' => $identifier);
				$dao->update( array('IsInvalid' => 1) );
				$i++;
			}
			$ps->notifications->push(new Notification("$i texts have been reset.", 'ok'));
		break;
	}
	
}

function UpdateExportsCorpora ( $get, $post ) { global $ps;
/* applies changes to a number of checked-out texts */
	
	switch ($post['exports_corpora_action']) {
		case 'reset':
			$i = 0;
			$dao = new Table('CHECKOUT');
			foreach ($post['checkout_identifier_corpus'] as $identifier) {
				// get the CorpusID of the corpus in scope
				$result = $dao->get( array('Identifier' => $identifier) );
				$corpus_id = $result[0]['CorpusID'];
				// reset all texts that are assigned to that corpus, valid, and not yet checked-in
				$dao->where = "TextID in (select TextID from TEXT where CorpusID=$corpus_id) and Checkin is Null and IsInvalid = 0";
				$dao->update( array('IsInvalid' => 1) );
				// reset the corpus itself
				$dao->where = array('Identifier' => $identifier);
				$dao->update( array('IsInvalid' => 1) );
				$i++;
			}
			$ps->notifications->push(new Notification("$i corpora have been reset.", 'ok'));
		break;
	}
	
}


function SearchOccurrences ( $get, $post ) { global $ps;
/* searches for occurrences as specified via search_occurrences.modal.php */
	
	// ASSERTIONS
	assert(count($post['corpora']) > 0); // the involved corpora
	// $post['query'] the query on the occurrence surface (REGEX) (may be empty)
	// $post['has_lemma'] the lemma that MUST be assigned to matching occurrences
	// $post['not_lemma'] the lemma that MUST NOT be assigned to matching occurrences
	// $post['has_graph'] the graph that MUST be assigned to matching occurrences
	// $post['not_graph'] the graph that MUST NOT be assigned to matching occurrences
	
	if ($post['has_lemma']) {
		$has_lemma = removeEmptyArrayFields($post['has_lemma']);
	} else {
		$has_lemma = NULL;
	}
	if ($post['not_lemma']) {
		$not_lemma = removeEmptyArrayFields($post['not_lemma']);
	} else {
		$not_lemma = NULL;
	}
	if ($post['has_graph']) {
		$has_graph = removeEmptyArrayFields($post['has_graph']);
	} else {
		$has_graph = NULL;
	}
	if ($post['not_graph']) {
		$not_graph = removeEmptyArrayFields($post['not_graph']);
	} else {
		$not_graph = NULL;
	}
	if ($post['has_type']) {
		$has_type = removeEmptyArrayFields($post['has_type']);
	} else {
		$has_type = NULL;
	}
	if ($post['not_type']) {
		$not_type = removeEmptyArrayFields($post['not_type']);
	} else {
		$not_type = NULL;
	}
	
	// search occurrences
	$_POST['matching_occurrences'] = findOccurrences($post['corpora'], $post['query'], $has_lemma, $not_lemma, $has_graph, $not_graph, $has_type, $not_type);
	
}

function ListOccurrencesByType ( $get, $post ) { global $ps;
/* takes a TokenID and returns all associated Occurrences within $_POST['matching_occurrences'] */
	
	// ASSERTIONS
	assert($_GET['tokenID'] || $_POST['tokenID']);
	
	// determine TokenID
	if ($_GET['tokenID']) {
		$token_id = $_GET['tokenID'];
	} else if ($_POST['tokenID']) {
		$token_id = $_POST['tokenID'];
	}
	
	// get all acitve Corpora
	$active_project = new Project($ps->getActiveProject());
	$corpora = $active_project->getAssignedCorporaIDs();
	
	$_POST['matching_occurrences'] = findOccurrences($corpora, NULL, NULL, NULL, NULL, NULL, array($token_id));
	
}

function ExportCSV ($get, $post ) { global $ps;
/* 	takes a list of OccurrenceIDs and returns a CSV file with the Occurrences' contexts and meta data
	caution: this function should be loaded within a new target (_blank window) */
	
	$csv = '';
	
	// CSV Headline
	$csv .= '"CiteF", "Date <d0>", "Editor <rd0>", "Divisio", "Word Number", "Left Context", "Match", "Lemma", "Right Context"';
	
	// get Occurrence IDs (json array)
	$ids = explode(',', $post['occurrenceIDs']);
	
	foreach ($ids as $id) {
		$context = getOccurrenceContext($id);
		$meta = $context['meta'][0];
		$match = $context['match'][0];
		
		// new csv line
		$csv .= "\n";
		// meta section
		$csv .= "\"" . $meta['zitfFull'] .  "\",";
		$csv .= "\"" . $meta['d0Full'] .  "\",";
		$csv .= "\"" . $meta['rd0Full'] .  "\",";
		$csv .= "\"" . $meta['divID'] .  "\",";
		$csv .= "\"" . $meta['order'] .  "\",";
		
		// context section
		$csv .= "\"" . $match['leftContext'] .  "\",";
		$csv .= "\"" . $match['surface'] .  "\",";
		$csv .= "\"" . $meta['lemma'] .  "\",";
		$csv .= "\"" . $match['rightContext'] .  "\""; // no comma after last field in line!
		
	}
	
	$csv .= "\n"; # a CSV file is always terminated with a line break
	
	//Output
	header('Content-type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="ph2.results.csv"');
	echo $csv;
	exit();
	
}

function ExportXLS ($get, $post) { global $ps;
/* 	takes a list of OccurrenceIDs and returns an Excel spreadsheet file with the Occurrences' contexts and meta data
	caution: this function should be loaded within a new target (_blank window) */
	
	/** PHPExcel */
	require_once 'framework/php_unmanaged/PHPExcel/Classes/PHPExcel.php';
	
	
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();
	
	// Set properties
	$objPHPExcel->getProperties()->setCreator("Phoenix 2")
								 ->setLastModifiedBy("Phoenix 2")
								 ->setTitle("PH2 Export")
								 ->setDescription("Exportet occurrences with their meta data and context.")
								 ->setKeywords("ph2")
								 ->setCategory("exportet occurrences");
	
	$csv .= '"CorpusID", "TextID", "Section", "Divisio", "Order", "Left Context", "Match", "Right Context"';
	// Set Header
	$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A1', 'CiteF')
				->setCellValue('B1', 'Date <d0>')
				->setCellValue('C1', 'Editor <rd0>')
				->setCellValue('D1', 'Divisio')
				->setCellValue('E1', 'Word Number')
				->setCellValue('F1', 'Left Context')
				->setCellValue('G1', 'Match')
				->setCellValue('H1', 'Lemma')
				->setCellValue('I1', 'Right Context');
	
	// Include Data
	// get Occurrence IDs (json array)
	$ids = explode(',', $post['occurrenceIDs']);
	$i = 2;
	foreach ($ids as $id) {
		
		$context = getOccurrenceContext($id);
		$meta = $context['meta'][0];
		$match = $context['match'][0];
		
		// Data
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A'.$i, $meta['zitfFull'])
					->setCellValue('B'.$i, $meta['d0Full'])
					->setCellValue('C'.$i, $meta['rd0Full'])
					->setCellValue('D'.$i, $meta['divID'])
					->setCellValue('E'.$i, $meta['order'])
					->setCellValue('F'.$i, $match['leftContext'])
					->setCellValue('G'.$i, $match['surface'])
					->setCellValue('H'.$i, $meta['lemma'])
					->setCellValue('I'.$i, $match['rightContext']);				
		// Format
		$objPHPExcel->getActiveSheet()->getStyle('F'.$i)->getFont()->setName('Courier');
		$objPHPExcel->getActiveSheet()->getStyle('G'.$i)->getFont()->setName('Courier');
		$objPHPExcel->getActiveSheet()->getStyle('I'.$i)->getFont()->setName('Courier');
		$objPHPExcel->getActiveSheet()->getStyle('F'.$i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$objPHPExcel->getActiveSheet()->getStyle('G'.$i)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_BLUE);
		$objPHPExcel->getActiveSheet()->getStyle('H'.$i)->getFont()->getColor()->applyFromArray( array('rgb' => 'CC6633') ); 
		
		$i++;
	}
	
	// Adjust column widths
	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);
	$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
	$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(50);
	
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('PH2 Export');
	
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);
	
	// Redirect output to a client's web browser (Excel5)
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="ph2.results.xls"');
	header('Cache-Control: max-age=0');
	
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit;
		
}

function ExportGraphemeTextDistributionXLS ($get, $post) { global $ps;
/* 	takes a number of graphIDs and returns an XML spreadsheet that lists the number of
	all grapheme occurrences for each involved text. #TODO: Specify more precisely */
	
	$graph_ids = $_POST['graphIDs'];
	assert($graph_ids);
	
	$result = array(); // $result: 'textID' => array('graph_name' => count, 'graph_name' => count, 'graph_name' => count, ...), ...
	$dao = new Table('GRAPH');
	$dao->select = "TextID, count(*) as Count";
	$dao->from = "(GRAPHGROUP_OCCURRENCE as A natural join GRAPHGROUP) join OCCURRENCE as B on A.OccurrenceID=B.OccurrenceID";
	$dao->groupby = "TextID";
	$dao->orderby = "TextID ASC";
	foreach ($graph_ids as $graph_id) {
		// load the statistics for a graph
		$dao->where = "GraphID=" . $graph_id;
		foreach ($dao->get() as $row) {
			// put the count for a given text in the results array
			$result[$row['TextID']][$graph_id] = $row['Count'];
		}
	}
	
	// sort results array by key, i.e. by TextID ASC
	function cmp($a, $b)
	{
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
	uksort($result, "cmp");
	
	// FORMAT XLS DATA
	/** PHPExcel */
	require_once 'framework/php_unmanaged/PHPExcel/Classes/PHPExcel.php';
	
	
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();
	
	// Set properties
	$objPHPExcel->getProperties()->setCreator("Phoenix 2")
								 ->setLastModifiedBy("Phoenix 2")
								 ->setTitle("PH2 Export")
								 ->setDescription("Exportet Grapheme/Text-Distribution")
								 ->setKeywords("ph2")
								 ->setCategory("exportet grapheme/text-distribution");
	
	// Set Header
	$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A1', 'TextID:');
	$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
	
	// Header: TextID
	function num2alpha($n) {
		// converts a column id (integer) to the corresponding excel column name
		for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
			$r = chr($n%26 + 0x41) . $r;
		return $r;
	}

	$current_column = 1;
	foreach ($result as $key => $value) {
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($current_column, 1, $key);
		// adjust width
		$objPHPExcel->getActiveSheet()->getColumnDimension(num2alpha($current_column))->setWidth(5);
		$current_column++;
	}
	
	// DATA
	
	// get involved graph names
	$graph = array();
	$dao = new Table('GRAPH');
	$dao->where = "GraphID in (" . expandArray($graph_ids, ',') . ")";
	foreach ($dao->get() as $row) {
		$graph[$row['GraphID']] = $row['Name'];
	}
	
	// set grapheme names (A-Column)
	$row = 2;
	foreach($graph_ids as $graph_id) {
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$row, $graph[$graph_id]);
		$row++;
	}
	
	// iterate over each TextID (results)
	$column = 1;
	foreach ($result as $result_portion) {
		$row = 2;
		foreach ($graph_ids as $graph_id) {
			if (array_key_exists($graph_id, $result_portion)) {
				$val = $result_portion[$graph_id];
			} else {
				$val = 0;
			}
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue(num2alpha($column).$row, $val);
			$row++;
		}
		$column++;
	}
	
	
	
	
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Grapheme-Text Distribution');
	
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);
	
	// Redirect output to a client's web browser (Excel5)
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="ph2.grapheme-text-distribution.xls"');
	header('Cache-Control: max-age=0');
	
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit;
}

function SetActiveGrapheme ( $get, $post ) { global $ps;
/* sets the active Grapheme in the current User Session ($ps) */

	assert($post['graph_id']);
	$ps->setActiveGrapheme($post['graph_id']);	
	
}

function DownloadXML ( $get, $post ) { global $ps;
/* Let's the user download the subimtted @param xml_string as a file with @param filename */

	assert( !empty( $get['xml_string'] ) );
	assert( !empty( $get['filename'] ) );
	
	header('Content-type: text/xml');
	header('Content-Disposition: attachment; filename="' . $get['filename'] . '"');

	echo $get['xml_string'];;
	exit();
}

function DownloadXMLText ( $get, $post ) { global $ps;
/* Lets the user download the subimtted text @param text_id as xml file */

	assert( !empty( $get['text_id'] ) );
	
	$text = new Text( (int)$get['text_id'] );
	
	$get['xml_string'] = $text->getXML();
	$get['filename'] = $text->getCiteID() . '.xml';
	
	DownloadXML($get, $post);

}

function CheckoutXMLText ( $get, $post ) { global $ps;
/* Lets the user export a Text @param text_id as xml file in EDIT format for external editing (and later re-importing) */

	assert( !empty( $get['text_id'] ) );
	
	$text = new Text( (int)$get['text_id'] );
	if(isset($get["annotations_included"]) && $get["annotations_included"] == "1"){
		$get['xml_string'] = $text->checkout( $ps->getUserID(), true )->saveXML();
		$get['filename'] = $text->getCiteID() . '_annotations.xml';
	}else{
		$get['xml_string'] = $text->checkout( $ps->getUserID() )->saveXML();
		$get['filename'] = $text->getCiteID() . '.xml';
	}
	
	DownloadXML($get, $post);
}

function DownloadXMLCorpus ( $get, $post ) { global $ps;
/* Lets the user download the subimtted Corpus @param corpus_id as xml file */

	assert( !empty( $get['corpus_id'] ) );
	
	$corpus = new Corpus( (int)$get['corpus_id'] );
	
	$get['xml_string'] = $corpus->getXML();
	$get['filename'] = $corpus->getName() . '.xml';
	
	DownloadXML($get, $post);
}

function CheckoutXMLCorpus ( $get, $post ) { global $ps;
/* Lets the user export a Text @param text_id as xml file in EDIT format for external editing (and later re-importing) */

	assert( !empty( $get['corpus_id'] ) );
	
	$corpus = new Corpus( (int)$get['corpus_id'] );
	
	$get['xml_string'] = $corpus->checkout( $ps->getUserID() )->saveXML();
	$get['filename'] = $corpus->getName() . '.xml';
	
	DownloadXML($get, $post);
}

function UpdateTexts ( $get, $post ) { global $ps;
/* Lets the user export a Text @param text_id as xml file in EDIT format for external editing (and later re-importing) */

	switch ($post['texts_action']) {
		case 'delete':
			foreach ($post['text_id'] as $text_id) {
				$text = new Text( (int)$text_id );
				$text->delete();
			}
			$notification = "The selected texts have been deleted.";
		break;
	}

	// release notification
	$ps->notifications->push( new Notification("$notification", 'ok') );
}

?>