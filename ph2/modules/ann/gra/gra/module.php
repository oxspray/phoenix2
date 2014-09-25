<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Graphemes
Module Signature: com.ph2.modules.ann.gra.gra
Description:
Grapheme Head properties.
---
/*/
//! MODULE BODY

?>
<script type="text/javascript">
	$(document).ready( function() {
		
		var occurrences = PH2Component.OccContextBox('occbox1');
		var detailswindow = PH2Component.DetailsWindow('detailswindow', 'ann_gra_gra_details_load', 'ann_gra_gra_details_save', true);
		
		// get and load OccurrenceIDs of active Grapheme ($ps)
		occurrences.clear();
		var active_graph_id;
		
		$.getJSON('actions/php/ajax.php?action=getActiveGraphemeID', function(session_graph_id) {
			active_graph_id = session_graph_id;
			// load details of active grapheme
			detailswindow.load('&graphID=' + active_graph_id);
			$.getJSON('actions/php/ajax.php?action=getOccurrenceIDsByGrapheme&graphID=' + active_graph_id, function(occurrence_ids) {
				occurrences.addMultiple(occurrence_ids);
			});
		});
		
		var form1_save_button = $('#form1_save');
		var form1_name_field = $('#form1 input[name="name"]');
		
		form1_save_button.click( function() {
			if (form1_save_button.hasClass('disabled') == true) {
				// disabled
				form1_name_field.focus();
				return false;
			} else {
				// enabled
				var form_data = $('#form1').serializeArray();
				active_grapheme_id = $('#select_graph').val();
				form_data.push({ name: "graph_id", value: active_grapheme_id });
				$.post( 'actions/php/ajax_forms.php?action=ann_gra_gra_form1', form_data, function(data) {
					if (data) {
						pushNotification(1, 'Changes saved successfully.');
						refreshGraphSelectionDropdown();
					} else {
						alert ('Error: Cannot save data.\nInvalid form data'); //#TODO: propper form data validation
					}
				});
			}
		});
		
		$('#form1_reset').click( function() {
			restoreForm('form1');
			checkNameField();
		});
		
		
		$('#occ_action').submit( function(e) {
			e.preventDefault();
			// switch action
			var action = $('#select_action').val();
			if (action == 'remove_occurrences') {
				var action_url = 'actions/php/ajax.php?action=removeOccurrencesFromGraph&graphID=' + active_graph_id + '&occurrenceIDs=' + occurrences.getSelected();
				// remove items from occurrence box
				occurrences.removeSelected();
				// remove assignment on the database
				$.get( action_url );
				// confirm to user
				pushNotification(1, 'The selected Occurrences have been removed from this Grapheme.');
			} else if (action == 'export_occurrences') {
				$('#export_button').trigger('click');
			}
		});
		
		
		
		// FIELD VALIDATION
		function nameAllreadyExists () {
			// check if name allready exists
			if (form1_name_field.val() == '') {
				return true;
			} else {
				var invalid = false;
				$('#select_graph option').each( function() {
					if ($(this).html() == form1_name_field.val() && $(this).attr('selected') != 'selected') {
						invalid = true;
					}
				});
				if (invalid) {
					return true
				}
			}
			return false;
		}
		
		function checkNameField () {
			if (nameAllreadyExists()) {
				form1_name_field.addClass('invalid');
				form1_save_button.addClass('disabled');
			} else {
				form1_name_field.removeClass('invalid');
				form1_save_button.removeClass('disabled');
			}
		}
		
		form1_name_field.keyup( function() {
			checkNameField();
		});
		
	});
</script>

<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/ann/gra.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">

	<div class="w100">
        <div class="modulebox OccContextBox" id="occbox1">
            <div class="title">Occurrences</div>
            
            <div class="title_extension">
                <form id="occ_action" action="" method="post">
                    <select id="select_action" name="select_action">
                        <option value="remove_occurrences">Remove Selected</option>
                        <option value="export_occurrences">Export Selected</option>
                        <!--<option value="2">Reassign Selected</option>-->
                    </select>
                    <input type="submit" class="button" value="OK" />
                </form>
                <!-- triggered via select above -->
                <a href="#" class="tablink invisible" rel="tab1" id="export_button" title="Export selected Occurrences">Export</a>
            </div>

            <div class="body">
            	<!-- tabs -->
                <div id="tab1" class="tab hidden">
                	<?php include('includes/components/tabs/export_search_results.tab.php'); ?>
                </div>
                <!-- end tabs -->
                
                <div id="occ_progress" class="hidden">loading <span id="current"></span>/<span id="total"></span></div>
                <table>
                    <thead>
                      <tr>
                        <td><input type="checkbox" class="select_all" rel="occ_selection" name=""/></td>
                        <th><a href="#" class="tooltipp" title="Corpus ID. Hover to display the name of the corpus.">Crp</a></th>
                        <th><a href="#" class="sort" id="sort_by_text" title="Click to sort results by Text ID.">Txt</a></th>
                        <th class="wider"><a href="#" class="tooltipp" title="Text Section. Hover to display the corresponding description.">Sct</a></th>
                        <th><a href="#" class="tooltipp" title="Involved text division.">Div</a></th>
                        <th class="widest"><a href="#" class="tooltipp" title="Order number.">Num</a></th>
                        <th class="padded">Context</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            
            	<div id="occ_matches_meta" class="h300">
                	<table>
                    	<!-- occ meta lines -->
                    </table>
                </div>
                
            	<div id="occ_matches" class="scrollbox h300">
                	<!-- occ context lines -->
              	</div>
                
            </div>
        </div>
    </div>
    
    <div class="w66">
        <div class="modulebox" id="detailswindow">
            <div class="title">Grapheme Head: Details</div>

            <div class="title_extension">
                <a href="#" class="save_button" title="Save changes to grapheme head">Save</a>
                <a href="#" class="restore_button" title="Discard changes and restore original values">Restore</a>
            </div>
            <div class="body">
            
                <form class="mainform" id="form1" action="" method="post">
                    <fieldset>	
                    
                        <label for="Name">Bezeichnung</label>
                        <input type="text" class="text normal required" name="Name" value="" />
                        
                        <label for="Description">Beschreibung</label>
                        <input type="text" class="text w75" name="Description" value="" />
                        
                        <label for="Comment">Kommentar</label>
                        <textarea name="Comment" class="w98"></textarea>
                        
                   	</fieldset>
                </form>
            </div>
       </div>
   	</div>
    
    
</div>