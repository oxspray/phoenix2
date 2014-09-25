<?php
/* Phoenix2
** Modal Window
==
This dialogue enables users to upload a text or a collection of texts (=corpus) serialized in the PH2 ENTRY, STORRAGE, or EDIT format.
*/
?>

<script type="text/javascript">
$(document).ready( function() {
	
	var is_corpus = false; // false = file, true = corpus
	var xsd_type = '';
	var temp_file_name = '';
	
	var serializer = new XMLSerializer();
	
	// STEP 1 main
	
	$('input[name=upload]').click(function(e) {
		e.preventDefault();
		//upload form data / file
		var formData = new FormData($('form[name=upload_file]')[0]);
		$.ajax({
			url: 'actions/php/ajax.php?action=uploadFile',  //server script to process data
			type: 'POST',
			async: false,
			//Ajax events
			beforeSend: beforeSendHandler,
			success: function(d) {
				//check if file was uploaded
				var data = jQuery.parseJSON( d );
				if(data.success) {
					is_corpus = data.is_corpus;
					xsd_type = data.xsd_type;
					temp_file_name = data.temp_file_name;
					var typestring = '';
					if (is_corpus) {
						typestring = 'Corpus';
						$('#entity_type').html('corpus');
					} else {
						typestring = 'Single Text';
						$('#entity_type').html('text');
					}
					typestring += ', ' + xsd_type.toUpperCase() + ' format';
					$('.subtitle').html(typestring)
					// get additional information if an EDIT entity is re-imported
					if (xsd_type == 'edit') {
						$('#checkout_date').html(data.timestamp_checkout);
						$('#entity_name').html(data.entity_name);
						// check if the file to be checked-in matches the file that a user wants to check-in
						if ( $('#import_text_id').val() != '' && $('#import_text_id').val() != '0' && $('#import_text_id').val() != data.entity_id ) {
							$('#import_id_mismatch_warning').show();
						}
						if ( $('#import_corpus_id').val() != '' && $('#import_corpus_id').val() != '0' && $('#import_corpus_id').val() != data.entity_id ) {
							$('#import_id_mismatch_warning').show();
						}
						fillInEditCorpusName(is_corpus, data.entity_id);
					}
					showRelevantOptions(is_corpus, xsd_type);
					showStep('step2');
					// continue with step 2					
				} else {
					showStep('error');
					$('#error_msg').html(data.error);
				}
			},
			error: errorHandler,
			// Form data
			data: formData,
			//Options to tell JQuery not to process data or worry about content-type
			cache: false,
			contentType: false,
			processData: false
		});

	});
	
	// STEP 2 main
	$('input[name=start]').click(function(e) {
		e.preventDefault();
		
		// create a new empty corpus if necessary
		if (is_corpus && xsd_type != 'edit') {
			createCorpus($('input[name=new_corpus_name]').val()); // #TODO: ensure that a corpus name is entered
		}
		
		// collect additional parameters (from checkbox options)
		var params = '';
		if ( $('input[name=option_tokenize]').is(':checked') ) {
			params += '&tokenize=1';
		}
		if ( $('input[name=option_overwrite]').is(':checked') ) {
			params += '&overwrite=1';
		}
		if (xsd_type == 'entry') {
			params += '&migrate=1';
		}
		
		// get the XML file
		$.ajax({
			type: "GET",
			url: "data/xml/temp/" + temp_file_name,
			dataType: "xml",
			async: false,
			success: function(xml) {
				
				// get corpus id (existing or newly created)
				if ($('#import_corpus_id').val() != '') {
					var corpus_id = $('#import_corpus_id').val();
				} else if ( $('input[name=actual_corpus_id]').val() == '' ) {
					var corpus_id = $('select[name=corpus_id]').val();
				} else {
					var corpus_id = $('input[name=actual_corpus_id]').val();
				}
				
				// import text
				// parse the temporary xml corpus
				if (is_corpus) {
					
					// CORPUS
					// check if the corpus can be importet (all expected texts need to be contained, ...)
					if (xsd_type == 'edit') {
						var xml_string = (new XMLSerializer()).serializeToString(xml);
						var corpus_check = checkinCorpus( corpus_id, xml_string );
						if (corpus_check != 1) {
							errorHandler(corpus_check);
							throw "Corpus cannot be imported.";
						}
					}
					
					// find out how many texts are to be parsed
					var total_texts = $(xml).find('gl').length;
					$('#total_texts').html( total_texts );
					//iterate over gl objects
					var serializer = new XMLSerializer();
					var counter = 1;
					showStep('progress');
					
					$(xml).find('gl').each(function() {
						var xml_string = serializer.serializeToString(this);
						// import current text
						importText( xml_string, xsd_type, corpus_id, params );
						// update counter
						$('#current_text').html(counter+1);
						if (counter == total_texts) {
							showStep('confirmation');
							$.get('actions/php/ajax.php?action=DeleteTempFile&filename=' + temp_file_name, function() {});
						}
						counter++;
					});
					
				} else {
					// SINGLE TEXT
					$('#total_texts').html( '1' );
					$('#current_text').html('1' );
					showStep('progress');
					// get xml string
					var xml_string = (new XMLSerializer()).serializeToString(xml);
					// import text
					params += '&temp_filename=' + temp_file_name;
					importText( xml_string, xsd_type, corpus_id, params );
					// show confirmation
					showStep('confirmation');
					// delete temp file
					$.get('actions/php/ajax.php?action=DeleteTempFile&filename=' + temp_file_name, function() {});
				}
			}
		});
	
	});
	
	
	function beforeSendHandler() {
		/*$('form[name=upload_corpus]').hide();
		$('#progress').show();*/
	}
	
	function errorHandler(error) {
		alert(error);
		parent.$.fancybox.close();
	}
	
	function showStep( step ) {
		$('.step').each( function() {
			$(this).hide();
			$('#' + step).show();
		});
	}
	
	function showRelevantOptions ( is_corpus, xsd_type ) {
		if (is_corpus) {
			var corpus = 'corpus';
		} else {
			var corpus = 'text';
		}
		$('.'+corpus+'.'+xsd_type).show();
	}
	
	function importText ( xml_string, xsd_type, corpus_id, params ) {
		/* imports a text, i.e., it passes it to the respective XMLTextparser
		** @param xml: the well-formed and valid (according to xsd_type) xml representation of the text
		** @param xsd_type: the type of the text: {entry, storage, edit}
		** @param corpus_id: the ID of the corpus the text should be assigned to
		** @param params: string of optional parameters, e.g. '&tokenize=1&key2=val2'
		*/
		$.ajax({   
			type: "POST",
			async: false,
			contentType: "application/xml",
			processData: false,
			url: "actions/php/ajax.php?action=importTextFromXMLInputAJAX&xsd_type=" + xsd_type + "&corpus_id=" + corpus_id + params,   
			data: xml_string,
			success: function(data) {/*alert(data);*/}
		});
	}
	
	function createCorpus ( name ) {
		// creates a Corpus and saves the new corpus ID in a hidden field
		$.ajax({
			type: 'GET',
			async: false,
			url: 'actions/php/ajax.php?action=createCorpus&name=' + name,
			success: function(data) {
				$('input[name=actual_corpus_id]').val( data );
			}
		});
	}
	
	function checkinCorpus ( corpus_id, xml_string ) {
		// marks a corpus as checked in if all expected texts are present
		var check;
		$.ajax({
			type: "POST",
			async: false,
			contentType: "application/xml",
			url: 'actions/php/ajax.php?action=checkinCorpus&corpus_id=' + corpus_id,
			data: xml_string,
			success: function(data) {
				check = data;
			}
		});
		return check;
	}
	
	function fillInEditCorpusName ( is_corpus, entity_id ) {
		// gets the name of the corpus itself (corpus) or to which the text is assigned to (text)
		if (is_corpus) {
			$.get('actions/php/ajax.php?action=getCorpusName&id=' + entity_id, function(name) {
				$('#edit_corpus_name').val(name);
			});
		} else {
			$.get('actions/php/ajax.php?action=getCorpusNameByTextID&id=' + entity_id, function(name) {
				$('#edit_corpus_name').val(name);
			});
		}
		
	}
	
	// bindings
	$('input[name=corpus_name]').change( function() {
		$('input[name=new_corpus_name]').val( $(this).val() );
	});
	$('input[name=abort]').click( function() {
		parent.$.fancybox.close();
	});
	
	
});
</script>

<?php
/* This modal window can be called with $_GET-parameters import_text_id and import_corpus_id.
** If any of these is provided, a warning message is generated when (a) an entity in ENTRY or
** STORAGE format is imported or (b) the EDIT entity to be imported does not match the ID
** provided with the $_GET-parameters.
*/

echo('<input type="hidden" name="import_text_id" id="import_text_id" value="' . $_GET['import_text_id'] . '" />');
echo('<input type="hidden" name="import_corpus_id" id="import_corpus_id" value="' . $_GET['import_corpus_id'] . '" />');

?>

<h1>Import</h1>

<!-- step 1 -->

<div class="step modal-w400" id="step1">
<p>Please select a file:</p>
<form method="post" name="upload_file" enctype="multipart/form-data">
    <fieldset>
        <legend class="required">Text or Corpus File (<a href="#" class="tooltipp" title="A single text or a collection of texts (corpus) in PH2 XML format, i.e., ENTRY, STORRAGE, or EDIT.">XML</a>)</legend>
        <input type="file" name="uploadfile" id="file" /> 
    </fieldset>
    <input type="submit" class="button" value="Continue" name="upload" />
</form>
</div>

<!-- step 2 -->

<div class="hidden step modal-w400" id="error">
<p style="background:#f5bace">The provided file cannot be imported.</p>
<p id="error_msg"></p>
<br />
<br />
<form method="post" name="abort" enctype="multipart/form-data">
	<input type="button" class="button" value="Close" name="abort" />
</form>
</div>

<div class="hidden step modal-w400" id="step2">
<p class="subtitle"></p>
<p class="hidden text corpus edit">Checked out on <span id="checkout_date">UNKNOWN</span></p>
<br />

<div class="hidden" id="import_id_mismatch_warning">
    <table class="midalign">
    <tbody>
        <tr>
            <td style="text-align: center; width:50px;"><img src="ressources/icons/001_11.png" /></td><td>The provided file does not correspond to the text you selected to check-in. If you proceed, you will check-in <span id="entity_name" class="bold"></span> instead.</td>
        </tr>
    </tbody>
    </table>
</div>
<br />

<form method="post" name="upload_details">
	<fieldset>
    
    	<!-- actual values -->
        <input type="hidden" name="new_corpus_name" value="" />
        <input type="hidden" name="actual_corpus_id" value="" />
    
    	<span class="hidden corpus entry storage">
        	<legend class="required" style="margin-bottom:6px;"><a href="#" class="tooltipp" title="Provide a name for the new corpus to be used within Phoenix2.">Corpus Name</a>:</legend>
        	<input name="corpus_name" id="name" type="text" class="text w50 corpus" />
        </span>
        
        <span class="hidden text entry storage">
        	<legend class="required inline" style="margin-bottom:8px;"><a href="#" class="tooltipp" title="Select an existing project that the new text should be assigned to.">Corpus Assignment</a>:</legend>
			<?php echo htmlCorpusSelectionDropdown($ps->getActiveProject(), 'corpus_id'); ?>
        </span>
        
        <span class="hidden corpus edit">
        	<legend class="required" style="margin-bottom:6px;"><a href="#" class="tooltipp" title="The name of a checked-out corpus cannot be changed before the check-in.">Corpus Name</a>:</legend>
        </span>
        
        <span class="hidden text edit">
        	<legend class="required" style="margin-bottom:6px;"><a href="#" class="tooltipp" title="The corpus assignment of a checked-out text cannot be changed before the check-in.">Corpus Assignment</a>:</legend>
        </span>
        
        <span class="hidden text corpus edit">
        	<input name="" id="edit_corpus_name" type="text" class="text w50 corpus" value="#TODO" disabled="disabled" />
        </span>
          
    </fieldset>
    <fieldset>
    	<span class="hidden text corpus entry edit">
        	<legend>Options:</legend>
        </span>
        <span class="hidden text corpus entry">
        	<input type="checkbox" name="option_tokenize" /> <a href="#" class="tooltipp" title="Check this option to automatically tokenize the &lt;txt&gt; section.">Tokenize text</a><br />
        </span>
    	<span class="hidden text corpus edit">
        	<input type="checkbox" name="option_overwrite" /> <a href="#" class="tooltipp" title="If this option is selected, all existing annotations are overwritten for tokens who were edited externally.">Overwrite existing annotations</a>
        </span>
    </fieldset>
    <br />
    <input type="submit" class="button" value="Continue" name="start" />
</form>
</div>

<!-- progress -->

<div class="hidden w100 step" id="progress">
<p>Importing <span id="entity_type"></span>...</p>
<p>Do neither close this window nor start any other processes meanwhile.</p>

<table class="w50 midalign">
<tbody>
    <tr>
    	<td style="text-align: center;"><img src="ressources/icons/processing.gif" /></td><td>Importing text <span id="current_text">0</span> / <span id="total_texts">?</span></td>
    </tr>
</tbody>
</table>

</div>

<!-- final confirmation -->
<div class="hidden step w100" id="confirmation">
<br />
<table class="w100 midalign">
<tbody>
    <tr>
    	<td style="text-align: center;"><img src="ressources/icons/001_06.png" /></td><td>The <span id="entity_type"></span> has been imported successfully.<br />Click OK to continue.</td>
    </tr>
</tbody>
</table>
<br />
<form method="post" name="quit" action="">
	<input type="submit" class="button" value="OK" name="quit" />
</form>

</div>