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
	$ps->setCurrentModule($get['module']);
}

function logout ( $get, $post ) { global $ps;
/* logs out the current user and redirects to the login page */
	
	//ROUTINE
	$ps->logout();
}


/*****************************
TEXT/CORPUS/PROJECT MANAGEMENT
*****************************/

function AddTextFromXMLInput ( $get, $post ) { global $ps;
/* takes an xml text submitted via form input and creates a corresponding entity on the system */
	
	// ASSERTIONS
	assert($_POST);
	
	// ROUTINE
	// check if no fields are empty
	if ($_POST['xml'] && $_POST['name']) {
		// Initialize parser
		$p = new XMLTextParser();
		$p->input_xml= $_POST['xml'];
		$p->text_name = $_POST['name'];
		$p->text_corpusID = $_POST['corpus_id'];
		$p->text_description = $_POST['comment'];
		// parse!
		if( $p->parse() ) {
			$ps->notifications->push( new Notification('The submitted text was successfully added and is now available on the system.', 'ok') );
		} else {
			$log = $p->getLog();
			$last_log_entry = array_pop($log);
			$ps->notifications->push(new Notification($last_log_entry[0], 'err'));
		}
	} else {
		if(!$_POST['xml']) $ps->notifications->push( new Notification('Cannot add text. XML-Text field is empty.', 'err') );
		if(!$_POST['name']) $ps->notifications->push( new Notification('Cannot add text. Please provide a name in the corresponding field.', 'err') );
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
		$corpus = new Corpus(4);
		$corpus_id = $corpus->getID();
		
		if ($_FILES['corpusfile']['error'] > 0)
		{
			$ps->notifications->push(new Notification($_FILES["file"]["error"], 'err'));
		} else {
			// parse the xml file #TODO: add file restrictions, error handling!
			$xmlObject = simplexml_load_file($_FILES['corpusfile']['tmp_name']);
			
			// create file (the whole migrated corpus will be stored as a corpus file which will not be handled by the PH2 System)
			$corpus_file = "data/xml/migrated_corpora/c5-part2.xml"; //must be emptied at first!
			$filehandler = fopen($corpus_file, 'w' /*empty file if it already exists*/) or die("Can't create file for whole corpus (see actions.php)");
			fclose($filehandler);
			$filehandler = fopen($corpus_file, 'a' /*append mode*/) or die("Can't open corpus file in append mode.");
			
			// write file header
			fwrite($filehandler, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
			fwrite($filehandler, '<corpus name="c5-part2">' . "\n");
			fwrite($filehandler, '<!-- Migration from old XML Scheme. Created by Phoenix2 on ' . now() . " -->\n\n");
			
			// iterate over texts
			$i = 285;
			foreach ($xmlObject->gl as $text) {
				
				// A) Convert text into new XML Schema (XMLSchemaMigration)
				$mp = new XMLMigrationParser();
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
?>