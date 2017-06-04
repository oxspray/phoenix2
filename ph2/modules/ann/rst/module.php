<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Search Restrictions
Module Signature: com.ph2.modules.ann.rst
Description:
Restrict the searches by metatext parameters.
---
/*/
//! MODULE BODY

// HTML GENERATION OF STD FILTER SKELETON

function html_filter_skeleton ( $id, $name, $checkboxes_html = '' ) {
	return '<div class="modulebox filter" id="' . $id . '">
            <div class="title">' . $name . ' &lt;' . $id . '&gt;</div>
            
            <div class="title_extension">
            	<a href="#" class="restrict_button" title=""><span class="loading hidden"><img src="ressources/icons/processing_small.gif" alt="loading" /></span><span class="text">Restrict</span></a>
            </div>

            <div class="body">
            	<div class="unrestricted">
                	<p>No restrictions.</p>
                </div>
                
                <div class="restricted hidden">
                	SEARCH-FIELD<br /><br />
                	<div class="values h150 scrollbox">
                		<form>
                    	<fieldset class="fieldset">'
						. $checkboxes_html . 
                    	'<table class="checkboxes">
						</table>
                        </fieldset>
                    	</form>
                    </div>
                </div>
                
            </div>
        </div>';
}

?>
<script type="text/javascript">
	$(document).ready( function() {
		
		// activate/unactivate restrictions
		$('.filter .restrict_button').click(function() {
			var id = $(this).parent().parent().attr('id');
			if ($(this).parent().parent().find('.restricted').hasClass('hidden')) {
				// restrictions are currently not activated. ACTIVATE
				activate_filter(id);
			} else {
				// restrictions are currently activated. DISABLE
				disactivate_filter(id);
			}
		});
		
		// bind checkboxes to actions
		$('.value_checkbox').live('click', function() {
			var value = null;
			var filter = null;
			// update values for d0
			if ($(this).attr('id') == 'checkbox_d0_from') {
				var filter = 'd0-from';
				var value = $('#input_d0_from').val();
			} else if ($(this).attr('id') == 'checkbox_d0_to') {
				var filter = 'd0-to';
				var value = $('#input_d0_to').val();
			}
			// general behaviour
			if (filter==null) {
				filter = $(this).closest('.modulebox.filter').attr('id');
			}
			if (value==null) {
				value = $(this).parent().parent().find('td.value').html();
			}
			if ($(this).attr('checked')) {
				$.getJSON('actions/php/ajax.php?action=AddFilter&filter=' + filter + '&value=' + value, function() {});
			} else {
				if (filter=='d0-from' || filter=='d0-to') {
					$.getJSON('actions/php/ajax.php?action=RemoveFilter&filter=' + filter, function() {});
				} else {
					$.getJSON('actions/php/ajax.php?action=RemoveFilter&filter=' + filter + '&value=' + value, function() {});
				}
			}
		});
		
		// bind changes to d0 textfields
		$('#input_d0_from').live('keyup click', function() {
			if ($('#checkbox_d0_from').is(':checked')) {
				$('#update_d0_from').show();
			}
		});
		$('#input_d0_to').live('keyup click', function() {
			if ($('#checkbox_d0_to').is(':checked')) {
				$('#update_d0_to').show();
			}
		});
		
		// bind update handles for d0 update buttons
		$('#update_d0_from').live('click', function() {
			var filter = 'd0-from';
			var value = $('#input_d0_from').val();
			$.getJSON('actions/php/ajax.php?action=AddFilter&filter=' + filter + '&value=' + value, function() {});
			$(this).val('saved');
			$(this).fadeOut('slow', function() {
				$(this).val('update');
			});
		});
		$('#update_d0_to').live('click', function() {
			var filter = 'd0-to';
			var value = $('#input_d0_to').val();
			$.getJSON('actions/php/ajax.php?action=AddFilter&filter=' + filter + '&value=' + value, function() {});
			$(this).val('saved');
			$(this).fadeOut('slow', function() {
				$(this).val('update');
			});
		});
		
		
		function activate_filter ( id ) {
			var container = $('#' + id);
			container.find('.restrict_button .loading').removeClass('hidden');
			container.find('.restrict_button .text').addClass('hidden');
			retrieve_checkboxes(id);
			//show_filter_content(id);
		}
		
		function disactivate_filter ( id ) {
			//todo: functions
			remove_checkboxes(id);
			hide_filter_content(id);
		}
		
		function show_filter_content ( id ) {
			var container = $('#' + id);
			container.find('.restrict_button .loading').addClass('hidden');
			container.find('.restrict_button .text').removeClass('hidden');
			container.find('.restrict_button .text').html('Remove Filter');
			container.find('.unrestricted').slideUp('slow').addClass('hidden');
			container.find('.restricted').slideDown().removeClass('hidden');
		}
		
		function hide_filter_content ( id ) {
			// remove all filters of the current filter_id if the filter is removed
			$.getJSON('actions/php/ajax.php?action=RemoveFilter&filter=' + id, function() {});
			var container = $('#' + id);
			container.find('.restrict_button .text').html('Restrict');
			container.find('.restricted').slideUp().addClass('hidden');
			container.find('.unrestricted').slideDown().removeClass('hidden');
		}
		
		function retrieve_checkboxes ( id ) {
			var fieldset = $('#' + id).find('.checkboxes');
			$.get('actions/php/ajax.php?action=GetFilterHTML&filter=' + id, function(checkboxes_html) {
				fieldset.html(checkboxes_html);
			})
			.success( function() {
				show_filter_content(id);
			});
		}
		
		function remove_checkboxes (id) {
			checkboxes = $('#' + id).find('.value_checkbox');
			$.each(checkboxes, function() {
				filter = $(this).attr('name');
				$.getJSON('actions/php/ajax.php?action=RemoveFilter&filter=' + filter, function() {});
			});
			$('#' + id).find('.checkboxes').html('');
		}
		
		/*****************/
		/// AT PAGELOAD ///
		
		// open all tabs for active filters
		$.getJSON('actions/php/ajax.php?action=GetActiveFilterIDs', function(active_filters) {
			$.each(active_filters, function() {
				if (this=='d0-from' || this=='d0-to') {
					activate_filter('d0');
				} else {
					activate_filter(this);
				}
			});
		});
		
	});
</script>
<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/ann/asg.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">

	<!-- LEFT -->
   	<div class="w33">
    	
        <!-- Corpora (SPECIAL) -->
       	<?php echo html_filter_skeleton('corpus', 'Corpus'); ?>
        
        <!-- Date (SPECIAL) --> 
        <?php echo html_filter_skeleton('d0', 'Date'); ?>
        
        <!-- type -->
        <?php echo html_filter_skeleton('type', 'Genre textuel'); ?>
        
    </div>
    
	<!-- MIDDLE -->
    <div class="w33">
        
        <!-- rd0 -->
        <?php echo html_filter_skeleton('rd0', 'Rédacteur'); ?>
        
        <!-- soc0 -->
        <?php echo html_filter_skeleton('soc0', 'Position sociale du rédacteur'); ?>
        
        <!-- aut -->
        <?php echo html_filter_skeleton('aut', 'Auteur'); ?>
        
        <!-- disp -->
        <?php echo html_filter_skeleton('disp', 'Disposant'); ?>
        
        <!-- b -->
        <?php echo html_filter_skeleton('b', 'Bénéficaire'); ?>
        
        <!-- act -->
        <?php echo html_filter_skeleton('act', 'Acteurs'); ?>
        
    </div>


	<!-- RIGHT -->
    <div class="w33">
	
    	<!-- loc0 -->
        <?php echo html_filter_skeleton('loc0', 'Lieu'); ?>
        
        <!-- s -->
        <?php echo html_filter_skeleton('s', 'Sceau'); ?>
        
        <!-- sc -->
        <?php echo html_filter_skeleton('sc', 'Scribe'); ?>
        
        <!-- l -->
        <?php echo html_filter_skeleton('l', 'Lieu de conservation'); ?>
        
	</div>

</div>