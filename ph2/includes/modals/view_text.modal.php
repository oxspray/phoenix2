<?php
/* Phoenix2
** Modal Window
==
This is the main view on Text entities.
*/

// ASSERTIONS
assert($_GET['textID']); // this window is called via modal('view_text') . '&textID=';

// Load Text
$text  = new Text( (int) $_GET['textID'] );
$corpus = new Corpus( $text->getCorpusID() );

?>
<script type="text/javascript">
// include syntax highlighting (gcp)
includeSyntaxHighlighter();

$(document).ready( function () {
	
	// toggle values
	var meta_toggle = 'ALL';
	var xml_toggle = true;
	var compact_toggle = false;
	var colors_toggle = false;
	
	// References
	var code_container = $("#text");
	var textID = <?php echo $_GET['textID']; ?>;
	
	// Button "Meta-section"
	$("#meta_toggle").click( function(e) {
		e.preventDefault();
		meta_toggle = (meta_toggle == 'ALL') ? 'txt' : 'ALL';
		reloadTextXML(code_container, textID, xml_toggle, compact_toggle, colors_toggle, meta_toggle);
		// toggle button style (active)
		$(this).toggleClass('active');
	});
	
	// Button "XML"
	$("#xml_toggle").click( function(e) {
		e.preventDefault();
		xml_toggle = (xml_toggle) ? false : true;
		reloadTextXML(code_container, textID, xml_toggle, compact_toggle, colors_toggle, meta_toggle);
		// toggle button style (active)
		$(this).toggleClass('active');
	});
	
	// Button "Compact"
	$("#compact_toggle").click( function(e) {
		e.preventDefault();
		compact_toggle = (compact_toggle) ? false : true;
		reloadTextXML(code_container, textID, xml_toggle, compact_toggle, colors_toggle, meta_toggle);
		// toggle button style (active)
		$(this).toggleClass('active');
	});
	
	// Button "Highlighting"
	$("#colors_toggle").click( function(e) {
		e.preventDefault();
		colors_toggle = (colors_toggle) ? false : true;
		reloadTextXML(code_container, textID, xml_toggle, compact_toggle, colors_toggle, meta_toggle);
		// toggle button style (active)
		$(this).toggleClass('active');
	});
	
});

</script>
<div class="modal-w800">
    <div class="modalheader">
        <h1><?php echo $text->getCiteID(); ?></h1>
        <h2>In: <?php echo $corpus->getName(); ?>. <?php echo $text->getNumberOfOccurrences(); ?> Occurrences, <?php echo $text->getNumberOfLemmata(); ?> Lemmas assigned.</h2>
    </div>
    <div class="modulebox tabs">
        <div class="title">
            <a rel="text" href="#">Text</a>
            <a rel="ann" href="#">Annotations</a>
            <a rel="img" href="#">Images</a>
            <a rel="stats" href="#">Statistics</a>
        </div>
        <div class="title_extension">
            <a href="#" id="meta_toggle" class="active">Meta-section</a>
            <a href="#" id="xml_toggle" class="active">Tags</a>
            <a href="#" id="compact_toggle">Compact</a>
            <a href="#" id="colors_toggle">Colors</a>
        </div>
        <div class="body h400 scrollbox">
            <div id="text">
				<?php printXML($text->getXML(), 'code', TRUE); ?>
            </div>
            <div id="ann">
            	<table>
                <tbody>
                	<?php // load xml<->text annotations from the dataabse
					
					$dao = new Table('DESCRIPTOR');
					$dao->from = "DESCRIPTOR natural join TEXT_DESCRIPTOR";
					$dao->where = "TextID=" . $text->getID();
					$annotations = $dao->get();
					foreach ($annotations as $ann) {
						// get key
						if ($ann['Descr'] != '') {
							$key = $ann['Descr'];
						} else {
							$key = $ann['XMLTagName'];
						}
						// print key/value-pair
						echo '<tr><td class="bold">' . $key . '</td><td>' . $ann['Value'] . '</td></tr>';
					}
					?>
                </tbody>
                </table>
            </div>
            <div id="img">
            	<!-- upload new image -->
                <h4>Upload a new image</h4>
                
				<script language="javascript">
				$(document).ready( function() {
					
					var imagesContainer = $('#tbody-images');
					var uploadForm = $('#form-img-upload');
					var uploadButton = $('input[name=upload]');
					var progressBar = $('#progress-img-upload');
					
					// handle file change
					$(':file').change(function(){
						var file = this.files[0];
						name = file.name;
						size = file.size;
						type = file.type;
						if (type == 'image/jpeg' || type == 'image/png' || type == 'image/gif' ) {
							uploadButton.fadeIn();
						} else {
							uploadButton.fadeOut();
							alert('Only images (png, jpeg, gif) are supported. Please select a suitable file.');
						}
					});
					
					// handle upload
					uploadButton.click(function(){
						var formData = new FormData(uploadForm[0]);
						$.ajax({
							url: 'actions/php/ajax.php?action=addImageToText',  //server script to process data
							type: 'POST',
							beforeSend: function() {
								uploadForm.hide();
								progressBar.fadeIn();
							},
							success: function(status) {
								if (status!='error') {
									var new_img_id = status;
									// reload body
									progressBar.hide();
									uploadForm.fadeIn();
									loadImages(new_img_id);
								} else {
									alert('Error: The image could not be uploaded');
									progressBar.hide();
									uploadForm.show();
								}
							},
							error: function() {
								alert('Error: The image could not be uploaded.');
								progressBar.hide();
								uploadForm.show();
							},
							// Form data
							data: formData,
							//Options to tell JQuery not to process data or worry about content-type
							cache: false,
							contentType: false,
							processData: false
						});
					});
					
					// load image details
					function loadImageDetails ( medium_id ) {
						$.getJSON('actions/php/ajax.php?action=loadImageDetails&mediumID=' + medium_id, function(data) {
							$('tr#medium-id-' + medium_id).find('input[name=title]').val(data.Title);
							$('tr#medium-id-' + medium_id).find('textarea[name=description]').val(data.Descr);
							$('tr#medium-id-' + medium_id).find('input[name=order]').val(data.Order);
						})
						.success(function() {
							$('tr#medium-id-' + medium_id).find('.buttons').hide();
						})
						.error(function() {
							alert('Error: Image details could not be restored');
						});
					}
					
					// save image details
					function saveImageDetails ( medium_id ) {
						// get values from form fields
						title = $('tr#medium-id-' + medium_id).find('input[name=title]').val();
						description = $('tr#medium-id-' + medium_id).find('textarea[name=description]').val();
						order_number = $('tr#medium-id-' + medium_id).find('input[name=order]').val();
						// write to db
						$.getJSON('actions/php/ajax.php?action=saveImageDetails&mediumID=' +  medium_id + '&title=' + title + '&description=' + description + '&order=' + order_number, function() {
							//pass
						})
						.success(function() {
							$('tr#medium-id-' + medium_id).find('.buttons').hide();
						})
						.error(function(error) {
							//alert(JSON.stringify(error));
							alert(JSON.stringify(error));
							//alert('Error: Image details could not be saved');
						});
					}
					
					function deleteImage ( medium_id ) {
						$.getJSON('actions/php/ajax.php?action=deleteImage&mediumID=' + medium_id, function() {
							//pass
						})
						.success(function() {
							loadImages();
						})
						.error(function(error) {
							alert(JSON.stringify(error));
							//alert('Error: Image details could not be saved');
							alert('Error: Image could not be deleted.');
						});
					}
					
					// (re)load images assigned to the current text
					function loadImages ( highlighted_img ) {
						// creates html code to replace the #tbody-images html content
						tbody = $('#tbody-images');
						thead = $('#thead-images');
						// clear all contents at first
						tbody.html('');
						// get data from server
						$.getJSON('actions/php/ajax.php?action=getImagesAssignedToText&textID=' + $('input[name=textID]').val(), function(data) {
							if (data=='') {
								thead.hide();
								tbody.html('<tr><td colspan="5">(no images assigned)</td></tr>');
							} else {
								thead.show();
								$.each(data, function () {
									var html = '<tr class="id" id="medium-id-' + this.MediumID + '">';
									html += '<td><a href="' + this.Filepath + '" title="click to enlarge" target="_blank"><img width="200" src="framework/php_unmanaged/PHPThumb/phpThumb.php?src=/' + this.Filepath + '&w=200" /></a></td>';
									html += '<td><input class="normal hybrid required" name="title" value="' + this.Title + '" /><br /><textarea class="normal hybrid" name="description">' + this.Descr + '</textarea></td>';
									html += '<td><input class="tiny hybrid required" name="order" value="' + this.Order + '" /></td>';
									html += '<td><span class="buttons hidden"><input type="button" name="save" value="Save" /><br /><input type="button" name="reset" value="Cancel" /><br /><br /></span><input type="button" name="delete" value="Delete" /></td>';
									html += '</tr>';
									tbody.html( tbody.html() + html );									
									// focus img if selected
									if (highlighted_img == this.MediumID) {
										$('.body').scrollTo( $('#medium-id-'+highlighted_img)[0], 500, {axis:'y'} );
										//$('#medium-id-'+highlighted_img).effect("highlight", {}, 3000); // requires jQuery.UI.Highlight-package
									}
								});
							}
						});
					}
				
				// bind entrance of buttons on field keyup
				$('input, textarea').live('keyup',function() {
				   	$(this).parent().parent().find('.buttons').show(); 
				});
				
				// restore button
				$('input[name=reset]').live('click', function() {
					var medium_id = $(this).closest('tr.id').attr('id').trim('medium-id-');
					loadImageDetails( medium_id );
				});

				// save button
				$('input[name=save]').live('click', function() {
					var medium_id = $(this).closest('tr.id').attr('id').trim('medium-id-');
					saveImageDetails( medium_id );
				});
				
				// delete button
				$('input[name=delete]').live('click', function() {
					var medium_id = $(this).closest('tr.id').attr('id').trim('medium-id-');
					deleteImage( medium_id );
				});
				
				// default routine
				loadImages();
					
				});</script>
                
                <p>                
                <form enctype="multipart/form-data" method="post" id="form-img-upload">
                <input name="file" type="file" />
                <input type="button" value="Upload" name="upload" class="hidden" />
                <input type="hidden" value="<?php echo $text->getID(); ?>" name="textID" />
                </form>
                <progress id="progress-img-upload" class="hidden"></progress>
                </p>
                <br />
                <h4>Existing Images</h4>
                <p>Click on an image thumbnail to enlarge it (opens a new browser window).</p>
                <!-- existing images -->
                <table class="topalign hoverable">
                <thead id="thead-images">
                	<tr>
                    	<td>Image</td>
                        <td>Title, Description</td>
                        <td>Order</td>
                        <td style="width:225px;"></td>
                  	</tr>
                </thead>
                <tbody id="tbody-images">
                	<!-- data ind. loaded here -->
                </tbody>
                </table>

            </div>
            <div id="stats">
				<script language="javascript">
					$(document).ready( function() {
						$('.stats_button').click(function() {
							var id = $(this).parent().parent().attr('id');
							if ($(this).parent().parent().find('.stats-details').hasClass('hidden')) {
								show_stats_content(id);
							} else {
								hide_stats_content(id);
							}
						});
						
						function show_stats_content ( id ) {
							var container = $('#' + id);
							container.find('.stats_button .loading').addClass('hidden');
							container.find('.stats_button .text').removeClass('hidden');
							container.find('.stats_button .text').html('Close');
							container.find('.stats-details').slideDown().removeClass('hidden');
							$.fancybox.center;
						}
						function hide_stats_content ( id ) {
							var container = $('#' + id);
							container.find('.stats_button .text').html('Open details');
							container.find('.stats-details').slideUp().addClass('hidden');
						}
					});
				</script>
				<style>
					.tablesorter-default .group td{
						border-collapse: collapse;
						border-top: #717171 1px solid !important;
						border-bottom: #717171 1px solid !important;
						background-color: #bfbfbf !important;
					}
					.tablesorter-default .subgroup td{
						border-collapse: collapse;
						border-top: #717171 1px solid !important;
						border-bottom: #717171 1px solid !important;
						background-color: #f0f0f0 !important;
					}
					.tablesorter-default .token td{
						border: none !important;
						background-color: #fff !important;
					}
				</style>
				<div class="modulebox" id="lemmata-stats">
					<div class="title">Lemmata</div>
					<div class="title_extension">
						<span class="stats_button" title="">Open details</span>
					</div>
					<div class="body">
						<div class="stats-overview">
							<?php
				
								$dao = new Table('LEMMA_OCCURRENCE');
								$sql = "SELECT l.LemmaIdentifier, l.MainLemmaIdentifier, tok.Surface FROM LEMMA_OCCURRENCE lo INNER JOIN LEMMA l ON l.LemmaID = lo.LemmaID "
										. "INNER JOIN OCCURRENCE o ON lo.OccurrenceID = o.OccurrenceID INNER JOIN TOKEN tok ON o.TokenID = tok.TokenID WHERE o.TextID=".$text->getID(). " ORDER BY l.LemmaIdentifier ASC";
								$rows = $dao->query( $sql );
								$count = count($rows);
								echo "<h5 style='margin-bottom: 10px;'>{$count} lemmatas assigned</h5>";
							?>
						</div>
						<div class="stats-details hidden">
							<div class="values h400 scrollbox">
								<?php
									if($count > 0){
										$aData = array();
										foreach($rows as $row){
											if(is_null($row["MainLemmaIdentifier"])){
												$row["MainLemmaIdentifier"] = "null";
											}
											if(!intval($aData[$row["LemmaIdentifier"]][$row["MainLemmaIdentifier"]][$row["Surface"]]) > 0){
												$aData[$row["LemmaIdentifier"]][$row["MainLemmaIdentifier"]][$row["Surface"]] = 0;
											}
											$aData[$row["LemmaIdentifier"]][$row["MainLemmaIdentifier"]][$row["Surface"]]++;
										}
										echo "<table class='tablesorter-default'>";
										echo "<tr><th>Lemmata</th><th>Sublemmata</th><th>Token</th></tr>";
										foreach ($aData as $lemmata => $tmp1) {
											echo "<tr class='group'><td colspan='3'><b>{$lemmata}</b> (".count($tmp1).")</td></tr>";
											foreach ($tmp1 as $mainlemmata => $aToken) {
												echo "<tr class='subgroup'><td>&nbsp;</td><td colspan='2'><b>{$mainlemmata}</b> (".count($aToken).")</td></tr>";
												foreach ($aToken as $token => $count) {
													echo "<tr class='token'><td>&nbsp;</td><td>&nbsp;</td><td>{$token} ({$count})</td></tr>";
												}
											}
										}
										echo "</table>";
									}else{
										echo "<h4>No lemmata assigned</h4>";
									}
								?>
							</div>
						</div>

					</div>
				</div>
				
				<div class="modulebox" id="graph-stats">
					<div class="title">Graphemes</div>
					<div class="title_extension">
						<span class="stats_button" title="">Open details</span>
					</div>
					<div class="body">
						<div class="stats-overview">
							<?php
				
								$dao = new Table('GRAPHGROUP_OCCURRENCE');
								$sql = "SELECT gg.Name as graphgroup_name, g.Name as graph_name, tok.Surface FROM GRAPHGROUP_OCCURRENCE go INNER JOIN GRAPHGROUP gg ON gg.GraphgroupID = go.GraphgroupID INNER JOIN GRAPH g ON gg.GraphID=g.GraphID "
										. "INNER JOIN OCCURRENCE o ON go.OccurrenceID = o.OccurrenceID INNER JOIN TOKEN tok ON o.TokenID = tok.TokenID WHERE o.TextID=".$text->getID() . " ORDER BY g.Name ASC";
								$rows = $dao->query( $sql );
								$count = count($rows);
								echo "<h5 style='margin-bottom: 10px;'>{$count} graphemes assigned</h5>";
							?>
						</div>
						<div class="stats-details hidden">
							<div class="values h400 scrollbox">
								<?php
									if($count > 0){
										$aData = array();
										foreach ($rows as $row) {
											if(!intval($aData[$row["graph_name"]][$row["graphgroup_name"]][$row["Surface"]]) > 0){
												$aData[$row["graph_name"]][$row["graphgroup_name"]][$row["Surface"]] = 0;
											}
											$aData[$row["graph_name"]][$row["graphgroup_name"]][$row["Surface"]]++;
											/*
											if(!intval($aData[$row["graphgroup_name"]]["count"] > 0)){
												$aData[$row["graphgroup_name"]]["count"] = 0;
												$aData[$row["graphgroup_name"]]["graph"] = $row["graph_name"];
												$aData[$row["graphgroup_name"]]["token"] = $row["Surface"];
											}
											$aData[$row["graphgroup_name"]]["count"]++;
											 * 
											 */
										}
			
										echo "<table class='tablesorter-default'>";
										echo "<tr><th>Group</th><th>Grapheme</th><th>Token</th></tr>";
										foreach ($aData as $graph => $tmp1) {
											echo "<tr class='group'><td colspan='3'><b>{$graph}</b> (".count($tmp1).")</td></tr>";
											foreach ($tmp1 as $grapheme => $aToken) {
												echo "<tr class='subgroup'><td>&nbsp;</td><td colspan='2'><b>{$grapheme}</b> (".count($aToken).")</td></tr>";
												foreach ($aToken as $token => $count) {
													echo "<tr class='token'><td>&nbsp;</td><td>&nbsp;</td><td>{$token} ({$count})</td></tr>";
												}
											}
										}
										echo "</table>";
									}else{
										echo "<h4>No grapheme assigned</h4>";
									}
								?>
							</div>
						</div>

					</div>
				</div>
				
				<div class="modulebox" id="morph-stats">
					<div class="title">Morphemes</div>
					<div class="title_extension">
						<span class="stats_button" title="">Open details</span>
					</div>
					<div class="body">
						<div class="stats-overview">
							<?php
				
								$dao = new Table('OCCURRENCE_MORPHVALUE');
								$sql = "SELECT m.Value, tok.Surface FROM OCCURRENCE_MORPHVALUE om INNER JOIN MORPHVALUE m ON m.MorphvalueID = om.MorphvalueID "
										. "INNER JOIN OCCURRENCE o ON om.OccurrenceID = o.OccurrenceID INNER JOIN TOKEN tok ON o.TokenID = tok.TokenID WHERE o.TextID=".$text->getID(). " ORDER BY o.Order ASC";
								$rows = $dao->query( $sql );
								$count = count($rows);
								echo "<h5 style='margin-bottom: 10px;'>{$count} lemmatas assigned</h5>";
							?>
						</div>
						<div class="stats-details hidden">
							<div class="values h400 scrollbox">
								<?php
									if($count > 0){
										echo "<table class='tablesorter-default'>";
										echo "<tr><th>Token</th><th>Morpheme</th></tr>";
										foreach ($rows as $row) {
											echo "<tr><td>{$row["Surface"]}</td><td>{$row["Value"]}</td></tr>";
										}
										echo "</table>";
									}else{
										echo "<h4>No morpheme assigned</h4>";
									}
								?>
							</div>
						</div>

					</div>
				</div>
            </div>
        </div>
    </div>
</div>