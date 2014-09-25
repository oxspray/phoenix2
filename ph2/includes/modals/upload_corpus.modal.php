<?php
/* Phoenix2
** Modal Window
==
This dialogue enables a user to upload an xml file containing multiple texts that are to be parsed and migrated into the system.
*/
?>

<script type="text/javascript">
$(document).ready( function() {
	
	$('input[name=add_corpus]').click(function(e) {
		e.preventDefault();
		//upload form data / file
		var formData = new FormData($('form[name=upload_corpus]')[0]);
		$.ajax({
			url: 'actions/php/ajax.php?action=uploadCorpus',  //server script to process data
			type: 'POST',
			async: false,
			//Ajax events
			beforeSend: beforeSendHandler,
			success: function(d) {
				//check if file was uploaded
				var data = jQuery.parseJSON( d );
				if(data == 'error') {
					errorHandler('Error: File could not be uploaded.');
				} else {
					nextStep();
					//parse the temporary xml corpus
					$.ajax({
						type: "GET",
						url: "data/xml/temp/" + data.temp_file_name,
						dataType: "xml",
						async: false,
						success: function(xml) {
							//find out how many texts are to be parsed
							var total_texts = $(xml).find('gl').length;
							$('#total_texts').html( total_texts );
							//iterate over gl objects
							var serializer = new XMLSerializer();
							var counter = 1;
							$(xml).find('gl').each(function() {
								var xml_string = serializer.serializeToString(this);
								//var post_data = {};
								//post_data.xml = xml_string;
								var params = '&corpus_id=' + data.corpus_id;
								params += '&tokenize=' + data.tokenize;
								params += '&migrate=' + data.migrate;
								params += '&name=' + data.corpus_name + ' ' + counter;
								if (data.auto_comment) {
									params += '&comment=AUTO';
								}
								//import current text
								$.ajax({   
									type: "POST",
									async: false,
									contentType: "application/xml",
									processData: false,
									url: "actions/php/ajax.php?action=addTextFromXMLInputAJAX" + params,   
									data: xml_string,
									success: function(data) {/*alert(data);*/}
								});
								
								$('#current_text').html(counter);
								if (counter == total_texts) {
									nextStep(true); // invoke the final step (progress)
									$('#confirmation').slideDown();
									// delete the temp file
									$.get('actions/php/ajax.php?action=DeleteTempFile&filename=' + data.temp_file_name, function() {});
								}
								counter++;
							});
						},
						error: function(error) {alert(data.temp_file)}
					});
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
	
	function beforeSendHandler() {
		$('form[name=upload_corpus]').hide();
		$('#progress').show();
	}
	
	function errorHandler(error) {
		alert(error);
		$.facebox.close();
	}
	
	function nextStep( isFinalStep ) {
		$('.step').each( function() {
			if($(this).attr('src') == 'ressources/icons/processing.gif') {
				$(this).attr('src', 'ressources/icons/001_06.png');
				if ( isFinalStep ) {
					return false;
				} else {
					var next_id = parseInt($(this).attr('id')) + 1;
					$('.step#' + next_id).removeClass('hidden');
					return false; //breaks the loop
				}
			}
		});
	}
	
	
});
</script>

<h1>Import Corpus into active Project</h1>
<form method="post" name="upload_corpus" enctype="multipart/form-data"><!-- action="?action=UploadCorpus&next=<?php echo $_GET['next']; ?>" -->
    <fieldset>
    	<p>To add a new corpus to the current project, please fill in the details below:</p>
        <legend class="required">Name</legend>
        <input name="name" id="name" type="text" class="text w33" />
        <legend class="required">Corpus File (<a href="#" class="tooltipp" title="An XML file with a &lt;corpus&gt; root node, containing one or more texts (Phoenix2 XML schema).">XML</a>)</legend>
        <input type="file" name="corpusfile" id="corpusfile" /> 
    </fieldset>
    <fieldset>
        <legend>Options</legend>
        <input type="checkbox" name="tokenize" /> <a href="#" class="tooltipp" title="Check this option to automatically tokenize the &lt;txt&gt; section.">Tokenize text</a><br />
        <input type="checkbox" name="migrate" /> <a href="#" class="tooltipp" title="Check this option if the text is currently encoded in the old xml schema, i.e., words are separated by &lt;wn&gt; tags inside the &lt;txt&gt; section.">Convert old to new XML schema</a><br />
        <input type="checkbox" name="auto_comment" /> <a href="#" class="tooltipp" title="Check this option to automatically add the each texts's &lt;d0&gt; and &lt;rd0&gt; tags as a comment.">Automatically add comments</a>
    </fieldset>
    <fieldset>
        <p>Name and comments are only internal descriptions and will neither be written to the actual xml file nor exportet.</p>
        <legend>Comment</legend>
        <textarea name="comment" id="comment" class="w66 h100"></textarea>
    </fieldset>
    <input type="submit" class="button" value="Import corpus" name="add_corpus" />
</form>

<!-- progress -->

<div class="hidden w100" id="progress">
<p>Importing Corpus...</p>

<table class="w50 midalign">
<tbody>
	<tr>
    	<td style="text-align: center;"><img src="ressources/icons/processing.gif" class="step" id="1" align="" /></td><td>Transfering corpus file to server</td>
    </tr>
    <tr>
    	<td style="text-align: center;"><img src="ressources/icons/processing.gif" class="step hidden" id="2" /></td><td>Importing text <span id="current_text">0</span> / <span id="total_texts">?</span></td>
    </tr>
</tbody>
</table>

</div>

<!-- final confirmation -->
<div class="hidden w100" id="confirmation">
<p>The corpus has been imported successfully. Click OK to continue.</p>
<form method="post" name="quit" action="">
	<input type="submit" class="button" value="OK" name="quit" />
</form>

</div>