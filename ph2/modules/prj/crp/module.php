<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Corpora
Module Signature: com.ph2.modules.prj.crp
Description:
Create, edit and delete corpora and edit their assignments.
---
/*/
//! MODULE BODY
?>
<script type="text/javascript">
// functions
function showCorpusDetailsBox (rowReference, corpusID, fadeIn) {
	// show corpus details window
	if (fadeIn == true) {
		$("#corpus_properties-active").fadeIn();
	} else {
		$("#corpus_properties-active").show();
	}
	$("#corpus_properties-inactive").hide();
	// update corpus details in form (NAIVE!)
	var name  = $("td:eq(3)", rowReference).text();
	var descr = $("td:eq(4)", rowReference).text();
	$("#name").val(name);
	$("#comment").val(descr);
	// assign corpusID to hidden form field
	$("#corpus_id").val(corpusID);
}

function showAssignedTextBox (corpusID, fadeIn) {
	// show assigned texts window
	if (fadeIn == true) {
		$("#assigned_texts").fadeIn();
	} else {
		$("#assigned_texts").show();
	}
	$("#assigned_texts table").hide();
	$("#corpus-texts-" + corpusID).show();
}

function reopenAfterPageRefresh (corpusID) {
	// reopens all settings after a page refresh
	var row = findTableRowByInputValue ('corpora', corpusID);
	showCorpusDetailsBox( row, corpusID, false );
	showAssignedTextBox( corpusID, false );
	$(row).addClass('selected');
}

// routine
$(document).ready( function() {
	
	// show corpus details / assigned texts
	$("table#corpora tbody tr").click( function() {
		// select corpusID from checkbox in same tr
		var corpusID = $("td input", this).attr('value');
		// show boxes and update their content
		showCorpusDetailsBox(this, corpusID, true);
		showAssignedTextBox(corpusID, true);
	});
	
	// switch checked-in to checked-out when the check-out symbol is clicked
	$("a.checkout").live('click', function() {
		$(this).toggleClass('invisible');
		$(this).parent().find('a.checkin').toggleClass('invisible');
	});
	
	// handle corpus update actions (form)
	$('#update_corpora').submit( function(e) {
		if ($('#corpora_action').val() == 'delete' ) {
			e.preventDefault();
			// delete corpora procedure (redirect to fancybox)
			$.fancybox({
				'href' : 'modal.php?modal=delete_corpus&' + $('#update_corpora').serialize()
        	});
		}
	});
	
	// handle text order number input fields
	var wto;
	$("input.hybrid.textordernumber").live('keypress', function(e) {
		var keyCode = e.keyCode || e.which;
		var input_object = $(this);
		if (keyCode == 9) {
			// a tab keypress fires the save event immediately
			updateTextOrderNumber(input_object);
		} else {
			clearTimeout(wto);
			wto = setTimeout(function() {
				// do stuff when user has been idle for 1 second
				updateTextOrderNumber(input_object);
			}, 1000);
		}
	});
	
	$("input.hybrid.textordernumber").live('focusout', function() {
		updateTextOrderNumber($(this));
	});
	
	function updateTextOrderNumber (input_object) {
	// updates the order number of a text, given a JQuery object of the corresponding <input> element
		var text_id = input_object.attr('name').split('-').slice(-1)[0];
		var input_value = input_object.val();
		if ( /^\d*$/.test(input_value) ) {
			// valid input: only numbers or empty
			input_object.removeClass('invalid');
			$.get('actions/php/ajax.php?action=updateTextOrderNumber&id=' + text_id + '&order=' + input_value, function() {
				var saved_icon = input_object.parent().parent().find('.icon');
				saved_icon.show().delay(1100).fadeOut('slow');
			})
		} else {
			// invalid input
			input_object.addClass('invalid');
		}
	}
	
	// session - reopen modules after page refresh
	<?php if($_POST['corpus_details'] || $_POST['add_corpus']) {
		$corpus_id = $_POST['corpus_id'];
		//die($corpus_id);
		echo "reopenAfterPageRefresh($corpus_id);";
	}
	?>
	
	// REMOVE LOADING PAGE INDICATOR
	$('#loading').fadeOut();
});
</script>

<div id="loading">
	<!-- #EV. TODO: GENERALIZE -->
	<img src="ressources/icons/processing_small.gif" /> &nbsp;Loading
</div>

<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/prj/prj.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">
	<?php $project = new Project($ps->getActiveProject()); ?>
    <div class="w66">
        <div class="modulebox">
            <div class="title"><?php echo $project->getName(); ?>: Corpora</div>
            <div class="title_extension">
            	<form id="update_corpora" action="?action=UpdateCorpora" method="post">
                    <select id="corpora_action" name="corpora_action">
                        <option value="" selected="selected">(select action)</option>
                        <option value="delete">delete selected</option>
                    </select>
                    <input type="submit" class="button" value="OK" />
            </div>
            <div class="body">
            	<?php
				$resultset_corpora = $project->getAssignedCorpora($as_resultset=TRUE);
				// actions for each corpus (export XML, checkout)
				
				// get the IDs of all Corpora which are currently checked out
				$dao = new Table ('CHECKOUT');
				$checked_out_corpora = $dao->get( "CorpusID is not NULL and Checkin is NULL and IsInvalid=0" );
				$checked_out_corpus_ids = array();
				foreach($checked_out_corpora as $checked_out_corpus) {
					$checked_out_corpus_ids[] = $checked_out_corpus['CorpusID'];
				}			
				
				$resultset_corpora_with_actions = array();
				foreach ($resultset_corpora as $r_corpus) {
					$corpus_id = $r_corpus['ID'];
					$corpus_name = $r_corpus['Name'];
					$actions = '<a class="icon inline download" title="Download ' . $corpus_name . ' as XML file (STORRAGE format)." href="?action=DownloadXMLCorpus&corpus_id=' . $corpus_id . '"></a>';
					//check whether this corpus is checked-out
					
					$hidden_checkin = '';
					$hidden_checkout = '';
					if (in_array($corpus_id, $checked_out_corpus_ids)) {
						$hidden_checkout = ' invisible';
					} else {
						$hidden_checkin = ' invisible';
					}
					
					$actions .= '<a class="icon inline def checkin' . $hidden_checkin . '" title="' . $corpus_name . ' has been exported for external editing. Click here to check-in the edited XML file (EDIT format)." href="modal.php?modal=import&import_corpus_id=' . $corpus_id . '" rel="facebox"></a>';
					$actions .= '<a id="checkout-corpus-' . $corpus_id . '"class="icon inline abc checkout' . $hidden_checkout . '" title="Export ' . $corpus_name . ' as XML file for external editing (EDIT format)." href="modal.php?modal=export&type=corpus&id=' . $corpus_id . '&name=' . $corpus_name . '" rel="facebox"></a>';
					//add the new entry to the resultset
					$resultset_corpora_with_actions[] = array( 'ID' => $corpus_id, 'Actions' => $actions, 'Name' => $corpus_name, 'Description' => $r_corpus['Description'], '# Texts' => $r_corpus['# Texts'] );
				}
				
				$tr = new ResultSetTransformer($resultset_corpora_with_actions);
				echo $tr->toSelectableHTMLTable('all', 'ID', 'corpus_id', 'corpora');
				?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="w33 right">
        <div class="modulebox">
            <div class="title">Corpus Properties</div>
            <div class="body hidden" id="corpus_properties-active">
                <form action="?action=UpdateCorpusDetails" method="post">
                    <fieldset>
                        <legend class="required">Name</legend>
                        <input name="name" id="name" type="text" class="text w33" />
                        <p>Name and comments are only internal descriptions and will neither be written to the actual xml file nor exportet.</p>
                        <legend>Comment</legend>
                        <textarea name="comment" id="comment" class="w66 h100"></textarea>
                    </fieldset>
                    <input id="corpus_id" type="hidden" name="corpus_id" value="" />
                    <input name="corpus_details" type="submit" class="button" value="Save" />
            	</form>
            </div>
            <div class="body" id="corpus_properties-inactive">
            <p>To edit details, please select a corpus from the list.</p>
            </div>
        </div>
    </div>
    
    <div class="w66 hidden" id="assigned_texts">
        <div class="modulebox">
            <div class="title">Assigned Texts</div>
            <div class="title_extension">
            	<form action="?action=UpdateTexts" method="post">
                    <select name="texts_action">
                        <option value="" selected="selected">(select action)</option>
                        <option value="delete">delete selected</option>
                    </select>
                    <input type="submit" class="button" value="OK" />
            </div>
            <div class="body">
                <?php
				/* old routine via entities; slow
				foreach ($project->getAssignedCorpora() as $corpus) {
					$tr = new ResultSetTransformer($corpus->getAssignedTexts($as_resultset=TRUE, $include_links=TRUE));
					echo $tr->toHTMLTable('all', NULL, NULL, 'corpus-texts-' . $corpus->getID(), array('hidden'));
				}*/
				$tabindex=1;
				foreach ($project->getAssignedCorpora() as $corpus) {
					// get all texts assigned to a corpus
					$dao = new Table('TEXT');
					/* SQL query:
					select t.TextID, CiteID, d.d0, r.rd0 from TEXT as t 
					join (select TextID, Value as d0 from DESCRIPTOR natural join TEXT_DESCRIPTOR where XMLTagName='d0') as d on t.TextID=d.TextID
					join (select TextID, Value as rd0 from DESCRIPTOR natural join TEXT_DESCRIPTOR where XMLTagName='rd0') as r on t.TextID=r.TextID
					where CorpusID=30
					*/
					
					// get Texts from DB
					$dao->select = "t.TextID, t.Order, CiteID, d.d0, r.rd0";
					$dao->from = "TEXT as t 
					join (select TextID, Value as d0 from DESCRIPTOR natural join TEXT_DESCRIPTOR where XMLTagName='d0') as d on t.TextID=d.TextID
					join (select TextID, Value as rd0 from DESCRIPTOR natural join TEXT_DESCRIPTOR where XMLTagName='rd0') as r on t.TextID=r.TextID";
					// $dao->orderby = "t.Order, CAST(SUBSTRING(t.CiteID,LOCATE(' ',t.CiteID)+1) AS SIGNED) ASC";
					// depending on the order, choose the ordering. (if 'o' is given)
					// 1 = CiteID; 2 = Date; 3 = Editor
					if (isset($_REQUEST['o'])) {
						 $o = $_REQUEST['o'];
						 if ($o == 2) {
							 $dao->orderby = "d.d0 ASC";
						 } elseif ($o == 3) {
							 $dao->orderby = "r.rd0 ASC";
						 } else {
							 $dao->orderby = "CiteID ASC";
						 }
					 // in case no order is set
					 } else {
						 $dao->orderby = "CiteID ASC";
					 }
					$results = $dao->get( array('CorpusID' => $corpus->getID()) );
					
					// get the IDs of all Texts which are currently checked out
					/* SQL QUERY
					select * from CHECKOUT where TextID is not NULL and Checkin is NULL and IsInvalid=0
					*/
					$dao = new Table ('CHECKOUT');
					$checked_out_texts = $dao->get( "TextID is not NULL and Checkin is NULL and IsInvalid=0" );
					$checked_out_text_ids = array();
					foreach($checked_out_texts as $checked_out_text) {
						$checked_out_text_ids[] = $checked_out_text['TextID'];
					}
					
					// insert links for facebox-popup (text display)
					$results_with_links = array();
					foreach ($results as $row) {
						// actions
						$actions = '';
						$actions .= '<a class="icon inline download" title="Download ' . $row['CiteID'] . ' as XML file (STORRAGE format)." href="?action=DownloadXMLText&text_id=' . $row['TextID'] . '"></a>';
						
						$hidden_checkin = '';
						$hidden_checkout = '';
						if (in_array($row['TextID'], $checked_out_text_ids)) {
							$hidden_checkout = ' invisible';
						} else {
							$hidden_checkin = ' invisible';
						}
						
						$actions .= '<a class="icon inline def checkin' . $hidden_checkin . '" title="' . $row['CiteID'] . ' has been exported for external editing. Click here to check-in the edited XML file (EDIT format)." href="modal.php?modal=import&import_text_id=' . $row['TextID'] . '" rel="facebox"></a>';
						$actions .= '<a id="checkout-text-' . $row['TextID'] . '" class="icon inline abc checkout' . $hidden_checkout . '" title="Export ' . $row['CiteID'] . ' as XML file for external editing (EDIT format)." href="modal.php?modal=export&type=text&id=' . $row['TextID'] . '&name=' . $row['CiteID'] . '" rel="facebox"></a>';
						// information
						$a_start = '<a href="' . getModal('view_text') . '&textID=' . $row['TextID'] . '" rel="facebox" class="viewtext" title="view this text">';
						$a_end = '</a>';
						$order_nr = $row['Order'];
						$id = $a_start . $row['CiteID'] . $a_end;
						$d0 = $a_start . $row['d0'] . $a_end;
						$rd0 = $a_start . $row['rd0'] . $a_end;
						$results_with_links[] = array('ID' => $row['TextID'], 'Actions' => $actions, 'CiteID' => $id, 'Date' => $d0, 'Editor' => $rd0);
						$tabindex++;
					}
					// print the html
					$transformer = new ResultSetTransformer($results_with_links);
					echo $transformer->toSelectableHTMLTable( 'all', 'ID', 'text_id', 'corpus-texts-' . $corpus->getID(), array('hidden'), array(0) );
					unset($results, $transformer);
				}
				?>
                </form>
            </div>
        </div>
    </div>
</div>