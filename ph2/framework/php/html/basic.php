<?php
/*/
Phoenix2
Version 0.7 alpha, Build 10
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Framework File Name: Basic HTML Output Generators
Framework File Signature: com.ph2.framework.php.html.basic
Description:
Basic functions returning various HTML Code.
---
/*/

//+
function htmlUserTopBar ( $session )
/*/
The Content visible at the very top of the
GUI, showing information on the currently logged-in user etc.
---
@param session: the current unserialized session
object
@type  session: Session
/*/
{
	if ($session->getNickname() != 'guest') {
		echo 'Logged in as ';
	}

	echo '<a href="#' . $session->getUserID() .'">' . $session->getFullname() .'</a>';

	if ($session->getNickname() != 'guest') {
		echo ' [<a href="?action=logout" title="logout">x</a>]';
	}

} //htmlUserTopBar

//+
function htmlTopCorpusSelection ( )
/*/
The Selector visible at the very top of the GUI, showing information on the currently
active Project and Corpus in the session.
/*/
{
	global $ps;

	// display active project / corpus selection
	$active_project = new Project($ps->getActiveProject());
	$active_project_html = '<a class="item project" href="?action=redirect&module=prj.prj" title="active project">' . $active_project->getName() . '</a>';
	$active_corpus_html = '<div class="corpus_selection">' . "\n";
	$active_corpus_item = '';
	$inactive_corpus_items = array();
	foreach ($active_project->getAssignedCorpora() as $corpus) {
		if ($corpus->getID() == $ps->getActiveCorpus()) {
			$active_corpus_item = '<a class="item current" href="?action=redirect&module=prj.crp">' . $corpus->getName() . '</a>';
		} else {
			$inactive_corpus_items[] = '<a class="item" href="?action=ChangeActiveCorpus&corpusID='. $corpus->getID() .'">' . $corpus->getName() . '</a>';
		}
	}
	$active_corpus_html .= $active_corpus_item . "\n";
	foreach ($inactive_corpus_items as $item) {
		$active_corpus_html .= $item . "\n";
	}
	$active_corpus_html .= '</div>' . "\n";

	echo $active_project_html . $active_corpus_html;
} //htmlTopCorpusSelection

//+
function htmlModuleStatusBarMessages ( $session )
/*/
The Messages with global scope
within the submitted session, formated with their status color
---
@param session: the current unserialized session
object
@type  session: Session
-
@return: the html code
@rtype:  string
/*/
{
	$html = '';
	foreach ($session->notifications->popScope('all') as $note) {
		$html .= '<span class="status' . $note->getType() .'">' . $note->getText() . "</span>\n";
	}
	echo $html;
} //htmlModuleStatusBarMessages

//+
function htmlCorpusSelectionDropdown ( $project_id , $name='corpus_id' , $class='' , $id='' )
/*/
Returns a select-box (dropdown
selection) listing all corpora of a given project.
---
@param project_id: the project from which to select the
corpora
@type  project_id: int
@param name: the name of the
form element
@type  name: string
@param class: the class of the form
element
@type  class: string
@param id: the id of the form
element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{
	$tb_CORPORA = new Table('CORPUS');
	$tb_CORPORA->where = array('ProjectID' => $project_id);
	$resultset = new ResultSetTransformer($tb_CORPORA->get());

	if ($class!='') $class = ' class="' . $class . '"';
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('Name', 'CorpusID');
	$html .= "</select>";

	return $html;

} //htmlCorpusSelectionDropdown

//+
function htmlLemmaSelectionDropdown ( $project_id , $name='lemma_id' , $class='' , $id='' )
/*/
Returns a select-box (dropdown
selection) listing all lemmata of a given project.
---
@param project_id: the project from which to select the
lemmata
@type  project_id: int
@param name: the name of the
form element
@type  name: string
@param class: the class of the form
element
@type  class: string
@param id: the id of the form
element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{
	$tb_LEMMA = new Table('LEMMA');
	$tb_LEMMA->select = "LemmaID, LemmaIdentifier, Short as ConceptShort";
	$tb_LEMMA->from =  'LEMMA join CONCEPT on LEMMA.ConceptID=CONCEPT.ConceptID';
	$tb_LEMMA->where = array('ProjectID' => $project_id);
	$tb_LEMMA = $tb_LEMMA->get();
	// modify resultset: token surface is enriched with concept type of lemma
	foreach ($tb_LEMMA as $key => $value) {
		$tb_LEMMA[$key]['LemmaIdentifier'] = $tb_LEMMA[$key]['LemmaIdentifier'] . ' [' . $tb_LEMMA[$key]['ConceptShort'] . ']';
	}
	$resultset = new ResultSetTransformer($tb_LEMMA);

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('LemmaIdentifier', 'LemmaID');
	$html .= "</select>";

	return $html;
} //htmlLemmaSelectionDropdown

//+
function htmlLemmaTypeSelectionDropdown ( $name='lemma_type' , $class='' , $id='' )
/*/
Returns a select-box (dropdown selection) listing all lemma types (concepts).
---
@param name: the name of the form element
@type  name: string
@param class: the class of the form element
@type  class: string
@param id: the id of the form element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{

	$dao = new Table('CONCEPT');
	$concepts = $dao->get();
	$resultset = new ResultSetTransformer($concepts);

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('Short', 'ConceptID', 3);
	$html .= "</select>";

	return $html;

} //htmlLemmaTypeSelectionDropdown

function htmlGraphSelectionDropdown ( $project_id , $name='graph_id' , $class='' , $id='' )
/*/
Returns a select-box (dropdown
selection) listing all graphs of a given project.
---
@param project_id: the project from which to select the graphs
@type  project_id: int
@param name: the name of the
form element
@type  name: string
@param class: the class of the form
element
@type  class: string
@param id: the id of the form
element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{
	$tb_GRAPH = new Table('GRAPH');
	$tb_GRAPH->select = "GraphID, Name";
	$tb_GRAPH->from =  'GRAPH';
	$tb_GRAPH->where = array('ProjectID' => $project_id);
	$tb_GRAPH = $tb_GRAPH->get();


	$resultset = new ResultSetTransformer($tb_GRAPH);

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('Name', 'GraphID');
	$html .= "</select>";

	return $html;
} //htmlGraphSelectionDropdown

function htmlGraphgroupSelectionDropdown ( $project_id, $graphgroup_id, $name='graphgroup_id', $class='' , $id='' )
/*/
Returns a select-box (dropdown
selection) listing all graphgroups of a given project
EV TODO: not project but graph?. >> yes
---
@param project_id: the project from which to select the graphgroups
@type  project_id: int
@param name: the name of the
form element
@type  name: string
@param class: the class of the form
element
@type  class: string
@param id: the id of the form
element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{
	$tb_GRAPHGROUP = new Table('GRAPHGROUP');
	$tb_GRAPHGROUP->select = "GraphgroupID, Name";
	$tb_GRAPHGROUP->from =  'GRAPHGROUP';
	// $tb_GRAPHGROUP->where = array('ProjectID' => $project_id);
	$tb_GRAPHGROUP = $tb_GRAPHGROUP->get();


	$resultset = new ResultSetTransformer($tb_GRAPHGROUP);

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('Name', 'GraphgroupID');
	$html .= "</select>";

	return $html;
} //htmlGraphSelectionDropdown

//+
// function htmlGraphSelectionDropdown ( $project_id , $name='graph_id' , $class='' , $id='' , $initial_selection=NULL )
// /*/
// Returns a select-box (dropdown
// selection) listing all graphs of a given project.
// ---
// @param project_id: the project from which to select the graphs
// @type  project_id: int
// @param name: the name of the form element
// @type  name: string
// @param class: the class of the form element
// @type  class: string
// @param id: the id of the form element
// @type  id: string
// @param initial_selection: the ID of the Grapheme to be selected by default/on-load
// @type  initial_selection: int
// -
// @return: the html code
// @rtype:  string
// /*/
// {
// 	$tb_GRAPH = new Table('GRAPH');
// 	$tb_GRAPH->where = array('ProjectID' => $project_id);
// 	$resultset = new ResultSetTransformer($tb_GRAPH->get());
//
// 	$class = toHtmlClass($class);
// 	$id = toHtmlId($id);
//
// 	$html  = "<select name=\"$name\"$class$id>\n";
//     $html .= $resultset->toDropdownSelection('Name', 'GraphID', $initial_selection);
// 	$html .= "</select>";
//
// 	return $html;
// } //htmlGraphSelectionDropdown

//+
function htmlTypeSelectionDropdown ( $project_id , $name='token_id' , $class='' , $id='' )
/*/
Returns a select-box (dropdown selection) listing all types of a given project.
---
@param project_id: the project from which to select the types
@type  project_id: int
@param name: the name of the form element
@type  name: string
@param class: the class of the form element
@type  class: string
@param id: the id of the form element
@type  id: string
-
@return: the html code
@rtype:  string
/*/
{
	$dao = new Table('OCCURRENCE');
	$dao->select="TokenID, Surface, count(*) as Count";
	$dao->from = "TOKEN natural join OCCURRENCE natural join TOKENTYPE";
	$dao->where = "Name='occ'";
	$dao->groupby = "Surface COLLATE utf8_unicode_ci";
	$resultset = new ResultSetTransformer($dao->get());

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
    $html .= $resultset->toDropdownSelection('Surface', 'TokenID');
	$html .= "</select>";

	return $html;
} //htmlTypeSelectionDropdown

//+
function htmlMorphSelectionDropdown ( $morphcategory_XMLTagName , $name , $class='' , $id='' , $preselection=FALSE , $none_option=FALSE )
/*/
Returns a select-box (dropdown selection) listing all Morphvalues of a given
Morphcategory.
---
@param morphcategory_XMLTagName: the XMLTagName (see Table MORPHCATEGORY) to select the
morphological values from
@type  morphcategory_XMLTagName: string
@param name: the name of the form element
@type  name: string
@param class: the class of the form element
@type  class: string
@param id: the id of the form element
@type  id: string
@param preselection: the ID of the MORPHCATEGORY item that should be selected by default
@type  preselection: mixed
@param none_option: whether or not to include a none-option
@type  none_option: bool
-
@return: the html code
@rtype:  string
/*/
{
	$dao = new Table('MORPHVALUE');
	$dao->select = "MorphvalueID, Value as Morphvalue";
	$dao->from = "MORPHVALUE join MORPHCATEGORY on MORPHVALUE.MorphcategoryID=MORPHCATEGORY.MorphcategoryID";
	$resultset = new ResultSetTransformer( $dao->get( array( 'XMLTagName' => $morphcategory_XMLTagName ) ) );

	$class = toHtmlClass($class);
	$id = toHtmlId($id);

	$html  = "<select name=\"$name\"$class$id>\n";
	//$html .= "<option value=\"-\">(select)</option>\n"; // add a default option
    $html .= $resultset->toDropdownSelection('Morphvalue', 'MorphvalueID', $preselection, $none_option);
	$html .= "</select>";

	return $html;
} //htmlMorphSelectionDropdown

//+
function modal ( $modal_name , $redirect=NULL )
/*/
prints the path (href) to a modal window with all needed parameters. Note that
'rel="modal"' still has to be added to links pointing to a modal window!
---
@param modal_name: the name of the modal to be loaded (without suffixes .modal.php)
@type  modal_name: string
@param redirect: the module signature of the module to be loaded after the modal action
has been fired
@type  redirect: string
/*/
{
	echo getModal($modal_name, $redirect);
} //modal

//+
function getModal ( $modal_name , $redirect=NULL )
/*/
returns the path (href) to a modal window with all needed parameters. Note that
'rel="modal"' still has to be added to links pointing to a modal window!
---
@param modal_name: the name of the modal to be loaded (without suffixes .modal.php)
@type  modal_name: string
@param redirect: the module signature of the module to be loaded after the modal action
has been fired
@type  redirect: string
/*/
{
	$href = "modal.php?modal=$modal_name";
	if ($redirect) {
		$href .= "&next=$redirect";
	}
	return $href;
} //getModal

//+
function htmlCheckboxCorporaSelection ( $classes , $include_select_all=FALSE , $project=NULL , $check_current_corpus=TRUE )
/*/
Returns a checkbox for each curpus in a specified project, optionally accompanied by a
'select all'-checkbox on top.
---
@param classes: the class(es) to append to each checkbox except for the 'select all' box
@type  classes: array(string)
@param include_select_all: whether to include a 'select all'-checkbox on top
@type  include_select_all: bool
@param project: the project id to select the corpora from. If NULL, the active project of
the current user session is taken.
@type  project: int
@param check_current_corpus: if TRUE, the corpus that is active in the current session
will be ticked by default.
@type  check_current_corpus: bool
-
@return: the html code
@rtype:  string
/*/
{
	// prepare vars
	$html = '';
	$class = toHtmlClass($classes);
	if (empty($project)) {
		global $ps;
		$project = $ps->getActiveProject();
	}

	// include select all input if applicable
	if ($include_select_all) {
		$html .= '<input type="checkbox" class="select_all" rel="' . $classes[0] . '" name="select_all"/> ';
		$html .= "select all<br />\n";
	}

	// add the corpora checkboxes
	$project = new Project( (int) $project );
	foreach ($project->getAssignedCorpora() as $corpus) {
		$html .= '<input type="checkbox"' . $class . ' name="corpora[]" value="' . $corpus->getID() . '"';
		if ($check_current_corpus) {
			global $ps;
			if ($corpus->getID() == $ps->getActiveCorpus()) {
				$html .= ' checked="checked"';
			}
		}
		$html .= '/>';
		$html .= '<a href="#" class="description" title="' . $corpus->getDescription() . '"> ';
		$html .= $corpus->getName();
		$html .= "</a><br />\n";
	}

	echo $html;

} //htmlCheckboxCorporaSelection

//+
function toHtmlClass ( $classes )
/*/
transforms an array to a string that can be placed inside a html object, e.g. ' class="foo
bar"'.
---
@param classes: the array of classes
@type  classes: array(string)
-
@return: the class string to be placed inside a html object
@rtype:  string
/*/
{
	if (!empty($classes)) {
		return ' class="' . expandArray($classes, ' ') . '"';
	}
} //toHtmlClass

//+
function toHtmlId ( $id )
/*/
transforms a simple string to a string that can be placed inside a html object, e.g. '
id="foo"'.
---
@param id: the id
@type  id: string
-
@return: the id string to be placed inside a html object
@rtype:  string
/*/
{
	if ($id != '' && $id != NULL) {
		return ' id="' . $id . '"';
	}
} //toHtmlId

?>
