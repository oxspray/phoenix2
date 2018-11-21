<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/

# MOCKUP! This is a mockup prototype.

?>
<script type="text/javascript">

// ADD EXISTING LEMMA
var LemmaTab = {

	LemmaAssigner : function (occ_selection_box, search_controller) {
		
		// define targets
		var existing_lemma_ok_button = $('#ok_button_existing');
		var existing_lemma_cancel_button = $('#cancel_button_existing');
		var existing_lemma_clean_button = $('#clean_button_empty_lemmata');
		var existing_lemma_selector = $('#lemma_id');
		existing_lemma_selector.combobox(); // convert to combobox
		var existing_lemma_selector_input = $('#lemma_id-input');
		var new_lemma_ok_button = $('#ok_button_new');
		var new_lemma_cancel_button = $('#cancel_button_new');
		var new_lemma_identifier = $('#lemma_identifier');
		var new_lemma_mainidentifier = $('#lemma_mainidentifier');
		var new_lemma_type = $('#lemma_type');
		var new_lemma_pos = $('#lemma_pos');
		var new_lemma_gen = $('#lemma_gen');
		var new_lemma_gen_group = $('#lemma_gen_group');
		
		existing_lemma_ok_button.click( function(e) {
			e.preventDefault();
			// check if selection is valid
			if (existingLemmaSelectionIsValid()) {
				// check if at least one occurrence has been selected
				var selected_occurrences = occ_selection_box.getSelected();
				if (selected_occurrences != '[]') {
					var countAssignedOccurrences = countExistingLemmaAssignments(selected_occurrences);
					if ( countAssignedOccurrences > 0) {
						// display warning if at least one occurrence is already assigned to a Lemma
						if (confirm( countAssignedOccurrences + " of the selected Occurrences are already assigned to a Lemma. Press OK to overwrite their assignments with the selected Lemma.")) {
							addOccurrencesToExistingLemma( existing_lemma_selector.val(), selected_occurrences);
						}
					} else {
						addOccurrencesToExistingLemma( existing_lemma_selector.val(), selected_occurrences);
					}
				} else {
					alert('Please select at least one occurrence.');
				}				
			} else {
				alert('The lemma you selected does not exist. Please select an existing lemma from the list or create a new lemma by using the «New Lemma» form.');
				existing_lemma_selector_input.focus();
			}
		});
		
		existing_lemma_clean_button.click( function(e) {
			e.preventDefault();
			// clean DB from 'empty' Lemmata i.e. Lemmata, which have no Occurrence assigned to them.
			if (confirm("You're about to delete all Lemmata, which have no assigned Occurrences. This action is irreversible. All 'empty' Lemmata will be deleted.")) {
				// clean the DB from empty Lemmata
				cleanEmptyLemmata();
			}
		});

		new_lemma_ok_button.click( function(e) {
			e.preventDefault();
			// check if at least one occurrence has been selected
			var selected_occurrences = occ_selection_box.getSelected();
			if (selected_occurrences != '[]') {
				// check if the lemma already exists
				var new_lemma_identifier_value = new_lemma_identifier.val();
				var new_lemma_mainidentifier_value = new_lemma_mainidentifier.val();
				var new_lemma_type_value = new_lemma_type.children('option:selected').text();
				if (lemmaExists(new_lemma_identifier_value, new_lemma_mainidentifier_value, new_lemma_type_value)) {
					alert('A lemma with the given identifier and type allready exists. Please select another identifier and/or type.');
				} else {
					var new_lemma_pos_value = new_lemma_pos.children('option:selected').text();
					if (new_lemma_pos_value == '(none)') new_lemma_pos_value = null;
					var new_lemma_gen_value = new_lemma_gen.children('option:selected').text();
					if (new_lemma_gen_value == '(none)') new_lemma_gen_value = null;
					var countAssignedOccurrences = countExistingLemmaAssignments(selected_occurrences)
					if ( countAssignedOccurrences > 0) {
						// display warning if at least one occurrence is already assigned to a Lemma
						if (confirm( countAssignedOccurrences + " of the selected Occurrences are already assigned to a Lemma. Press OK to overwrite their assignments with the selected Lemma.")) {
							// create new lemma and add selected occurrences
							addOccurrencesToNewLemma(new_lemma_identifier_value, new_lemma_mainidentifier_value, new_lemma_type_value, new_lemma_pos_value, new_lemma_gen_value, selected_occurrences);
							resetNewLemmaForm();
						}
					} else {
						addOccurrencesToNewLemma(new_lemma_identifier_value, new_lemma_mainidentifier_value, new_lemma_type_value, new_lemma_pos_value, new_lemma_gen_value, selected_occurrences);
						resetNewLemmaForm();
					}
				}
			} else {
				alert('Please select at least one occurrence.');
			}				
		});
		
		// show GENUS selector only if PoS==Nom (noun)
		new_lemma_gen_group.hide();
		new_lemma_pos.change( function() {
			selected_tag = $(this).children('option:selected').text();
			if (selected_tag == 'NOM') {
				new_lemma_gen_group.fadeIn();
			} else {
				new_lemma_gen.val('none');
				new_lemma_gen_group.fadeOut();
			}
		});
		
		// activate create new lemma button when identifier is not empty
		new_lemma_identifier.bind( 'input', function() {
			if ($(this).val() != '') {
				new_lemma_ok_button.removeAttr('disabled');
			} else {
				new_lemma_ok_button.attr('disabled','disabled');
			}
		});
		
		function lemmaExists ( identifier, mainidentifier, concept ) {
			var exists = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=lemmaExists&identifier=' + identifier + '&mainidentifier=' + mainidentifier + '&concept=' + concept,
				type: 'GET',
				dataType: 'json',
				success: function(data) {
					exists = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});

			return exists;			
		}
		
		function addOccurrencesToExistingLemma ( lemma_id, occurrence_ids ) {
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToLemma',
				type: 'POST',
				dataType: 'json',
				data: {lemmaID: lemma_id, occurrenceIDs: occurrence_ids},
				success: function(data) {
					lemma_identifier = existing_lemma_selector_input.val();
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Lemma «' + lemma_identifier + '»');
					occ_selection_box.markSelectedAsLemmatized(lemma_identifier);
					search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: true
			});
		}
		
		function addOccurrencesToNewLemma ( identifier, mainidentifier, concept_short, pos, genus, occurrence_ids) {
			var morphvalues = null;
			if (pos) {
				morphvalues = new Object();
				morphvalues['lemma_pos'] = pos;
				if (genus) {
					morphvalues['lemma_gen'] = genus;
				}
			}
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToLemma',
				type: 'POST',
				dataType: 'json',
				data: {lemmaIdentifier: identifier, lemmaMainIdentifier: mainidentifier, conceptShort: concept_short, morphvalues: morphvalues, occurrenceIDs: occurrence_ids},
				success: function(data) {
					lemma_identifier = new_lemma_identifier.val();
					lemma_mainidentifier = new_lemma_mainidentifier.val();
					if(lemma_mainidentifier == ""){
						lemma_mainidentifier = "null";
					}
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Lemma «' + lemma_mainidentifier + ", " + lemma_identifier + ' [' + new_lemma_type.children('option:selected').text() + ']»');
					occ_selection_box.markSelectedAsLemmatized(lemma_mainidentifier + ", " + lemma_identifier);
					search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
		}
		
		function countExistingLemmaAssignments ( occurrence_ids ) {
			var count;
			$.ajax({
				url: 'actions/php/ajax.php?action=countLemmaAssignments',
				type: 'POST',
				dataType: 'json',
				data: {occurrenceIDs: occurrence_ids},
				success: function(data) {
					count = data;
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
			return count;
		}
		
		function cleanEmptyLemmata () {
			$.ajax({
				url: 'actions/php/ajax.php?action=cleanEmptyLemmata',
				type: 'POST',
				dataType: 'json',
				data: {},
				success: function(data) {
					pushNotification(1, 'Lemma cleanup successful: All empty Lemmata were removed from the database.');
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
		}

		function existingLemmaSelectionIsValid () {
			// checks if the text-content of the dropdown box for existing lemmata is properly synchronized with the underlying set of options
			// and is not empty
			return existing_lemma_selector_input.val() == existing_lemma_selector.children('option:selected').text();
		}
		
		function resetNewLemmaForm () {
			new_lemma_identifier.val('');
			new_lemma_mainidentifier.val('');
			new_lemma_identifier.trigger('input');
			new_lemma_identifier.focus();
		}
		
	}
	
}

</script>
<div class="h150">
    <form id="assign_lemma_form" method="post" action="#">
            
        <div id="columns">
        
            <div id="left_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend class="required">Existing Lemma</legend>
                    <?php echo htmlLemmaSelectionDropdown($ps->getActiveProject(), 'lemma_id', NULL, 'lemma_id'); ?>
                    <br/><br/>
                    <input type="button" id="ok_button_existing" class="button" value="Assign Existing Lemma" name="assign" />
                	<input type="button" id="cancel_button_existing" class="button" value="Cancel" name="cancel" />
					<br/><br/>
					<input type="button" id="clean_button_empty_lemmata" class="button" value="Clean Empty Lemmata" name="clean" />
                </fieldset>
            
              </div>
            </div>
            
            <div id="right_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend>New Lemma (<a href="http://www.rose.uzh.ch/phoenix/workspace/static/lemma_deaf_tl.html" target="blank">see reference</a>)</legend>
                    <br/>
                    
                    <label class="inline above">Lemma (Identifier)</label>
                    <input name="lemma_identifier" id="lemma_identifier" type="text" class="text small-normal inline" title="Lemma (Identifier)"/>
					
					<label class="inline above">Main Identifier</label>
                    <input name="lemma_mainidentifier" id="lemma_mainidentifier" type="text" class="text small-normal inline" title="Main Identifier"/>					
                    
                    <label class="inline above">Type</label>
                    <?php echo htmlLemmaTypeSelectionDropdown('lemma_type', NULL, 'lemma_type'); ?>
                    
                    <label class="inline above">PoS</label>
                    <?php echo htmlMorphSelectionDropdown('lemma_pos','lemma_pos', NULL, 'lemma_pos', FALSE, TRUE); ?>
                    
                    <span id="lemma_gen_group">
                    <label class="inline above">Genus</label>
                    <?php echo htmlMorphSelectionDropdown('lemma_gen','lemma_gen', NULL, 'lemma_gen', FALSE, TRUE); ?>
                    </span>
                    
                    <br/><br/>
                    <input type="button" id="ok_button_new" class="button" value="Assign New Lemma" name="assign" disabled="disabled" title="Enter an identifier to create a new lemma." />
                	<input type="button" id="cancel_button_new" class="button" value="Cancel" name="cancel" />
                </fieldset>
              
              </div>
            </div>
            
       </div>
    </form>
</div>