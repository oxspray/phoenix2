<?php
/* Phoenix2
** Modal Window
==
This is the user interface to formulate searches on occurrences.
*/

# MOCKUP! This is a mockup prototype.

?>
<script type="text/javascript">

// ADD EXISTING LANG
var LangTab = {

	LangAssigner : function (occ_selection_box, search_controller) {
		
		// define targets
		var existing_lang_ok_button = $('#ok_button_existing_lang');
		var existing_lang_cancel_button = $('#cancel_button_existing_lang');
		var existing_lang_selector = $('#lang_id');
		var new_lang_ok_button = $('#ok_button_new_lang');
		var new_lang_cancel_button = $('#cancel_button_new_lang');
		var new_lang_code = $('#lang_code');
		var new_lang_name = $('#lang_name');
		var new_lang_desc = $('#lang_desc');
		
		existing_lang_ok_button.click( function(e) {
			e.preventDefault();
			// check if at least one occurrence has been selected
			var selected_occurrences = occ_selection_box.getSelected();
			if (selected_occurrences != '[]') {
				var countAssignedOccurrences = countExistingLangAssignments(selected_occurrences);
				if ( countAssignedOccurrences > 0) {
					// display warning if at least one occurrence is already assigned to a Lang
					if (confirm( countAssignedOccurrences + " of the selected Occurrences are already assigned to a Lang. Press OK to overwrite their assignments with the selected Lang.")) {
						addOccurrencesToExistingLang( existing_lang_selector.val(), selected_occurrences);
					}
				} else {
					addOccurrencesToExistingLang( existing_lang_selector.val(), selected_occurrences);
				}
			} else {
				alert('Please select at least one occurrence.');
			}				
			
		});

		new_lang_ok_button.click( function(e) {
			e.preventDefault();
			// check if at least one occurrence has been selected
			var selected_occurrences = occ_selection_box.getSelected();
			if (selected_occurrences != '[]') {
				// check if the lang already exists
				var new_lang_code_value = new_lang_code.val();
				var new_lang_name_value = new_lang_name.val();
				var new_lang_desc_value = new_lang_desc.val();
				if (langExists(new_lang_code_value)) {
					alert('A Lang with the given code allready exists. Please select another code.');
				} else {
					var countAssignedOccurrences = countExistingLangAssignments(selected_occurrences)
					if ( countAssignedOccurrences > 0) {
						// display warning if at least one occurrence is already assigned to a Lang
						if (confirm( countAssignedOccurrences + " of the selected Occurrences are already assigned to a Lang. Press OK to overwrite their assignments with the selected Lang.")) {
							// create new lang and add selected occurrences
							addOccurrencesToNewLang(new_lang_code_value, new_lang_name_value, new_lang_desc_value, selected_occurrences);
							resetNewLangForm();
						}
					} else {
						addOccurrencesToNewLang(new_lang_code_value, new_lang_name_value, new_lang_desc_value, selected_occurrences);
						resetNewLangForm();
					}
				}
			} else {
				alert('Please select at least one occurrence.');
			}				
		});
				
		// activate create new lang button when code and name are not empty
		$('#lang_code, #lang_name').bind('input', function() {
			if ($('#lang_code').val() != '' && $('#lang_name').val() != '') {
				new_lang_ok_button.removeAttr('disabled');
			} else {
				new_lang_ok_button.attr('disabled','disabled');
			}
		});
		
		function langExists ( code ) {
			var exists = null;
			$.ajax({
				url: 'actions/php/ajax.php?action=langExists&code=' + code,
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
		
		function addOccurrencesToExistingLang ( lang_id, occurrence_ids ) {
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToLang',
				type: 'POST',
				dataType: 'json',
				data: {langID: lang_id, occurrenceIDs: occurrence_ids},
				success: function(data) {
					lang_code = $("#lang_id option:selected").text().replace(/ *\([^)]*\) */g, "");
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Lang «' + lang_code + '»');
					occ_selection_box.markSelectedAsLangified(lang_code);
					search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: true
			});
		}
		
		function addOccurrencesToNewLang ( code, name, desc, occurrence_ids) {
			$.ajax({
				url: 'actions/php/ajax.php?action=assignOccurrencesToLang',
				type: 'POST',
				dataType: 'json',
				data: {langCode: code, langName: name, langDesc: desc, occurrenceIDs: occurrence_ids},
				success: function(lang_id) {
					lang_code = new_lang_code.val();
					var o = new Option(lang_code + " ("+new_lang_name.val()+")", lang_id);
					existing_lang_selector.append(o);
					pushNotification(1, 'Assignment successful: ' + $.parseJSON(occurrence_ids).length + ' Occurrences assigned to Lang «' + lang_code + '»');
					occ_selection_box.markSelectedAsLangified(lang_code);
					search_controller.refresh_lemmata();
				},
				error: function(data) {
					alert('error: ' + JSON.stringify(data));
				},
				async: false
			});
		}
		
		function countExistingLangAssignments ( occurrence_ids ) {
			var count;
			$.ajax({
				url: 'actions/php/ajax.php?action=countLangAssignments',
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
		
		function resetNewLangForm () {
			new_lang_code.val('');
			new_lang_name.val('');
			new_lang_desc.val('');
			new_lang_ok_button.attr('disabled','disabled');
			new_lang_code.focus();
		}
		
	}
	
}

</script>
<div class="h150">
    <form id="assign_lang_form" method="post" action="#">
            
        <div id="columns">
        
            <div id="left_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend class="required">Existing Lang</legend>
                    <?php echo htmlLangSelectionDropdown('lang_id', NULL, 'lang_id'); ?>
                    <br/><br/>
                    <input type="button" id="ok_button_existing_lang" class="button" value="Assign Existing Lang" name="assign" />
                	<input type="button" id="cancel_button_existing_lang" class="button" value="Cancel" name="cancel" />
                </fieldset>
            
              </div>
            </div>
            
            <div id="right_column" class="w50">
              <div class="inner10">
            
                <fieldset>
                    <legend>New Lang</legend>
                    <br/>
                    
                    <label class="inline above">Code</label>
                    <input name="lang_code" id="lang_code" type="text" class="text small inline" title="Code (Identifier)"/>
                    
                    <label class="inline above">Name</label>
                    <input name="lang_name" id="lang_name" type="text" class="text small-normal inline" title="Name"/>
                    <label class="inline above">Description</label>
                    <input name="lang_desc" id="lang_desc" type="text" class="text big inline" title="Description"/>
                   
                    
                    <br/>
                    <input type="button" id="ok_button_new_lang" class="button" value="Assign New Lang" name="assign" disabled="disabled" title="Enter a code and a name to create a new lang." />
                	<input type="button" id="cancel_button_new_lang" class="button" value="Cancel" name="cancel" />
                </fieldset>
              
              </div>
            </div>
            
       </div>
    </form>
</div>