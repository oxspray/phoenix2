<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Create Text
Module Signature: com.ph2.modules.chg.new
Description:
Create a new text and assign it to a corpus.
---
COMMENT: editor implementation (grade) tbd
/*/
//! MODULE BODY
?>
<!-- module js -->
<script type="text/javascript">

// automatically find name of new text in xml
$(document).ready( function() {
	
	$("#xml").keyup( function() {
		if($("#name").val()=='') {
		// act only if name field is empty
			try {
				var xmlDoc = $.parseXML($("#xml").val());
				$xml = $(xmlDoc);
				$name = $xml.find("nom");
				$("#name").val($name.text());
				// #ev-todo: a highlight event for the name field could be placed here
			} catch (err) {
				return;
			}
		}			
	});
	
});

// enable syntax highlighting for this module
<!-- includeSyntaxHighlighter(); -->

</script>
<!-- end module js -->
<div id="mod_top">
	<?php include PH2_WP_INC . '/modules/menus/chg/chg.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">
<!-- <pre id="test" class="prettyprint"><code>class Voila {
public:
// Voila
static const string VOILA = "Voila";
// will not interfere with embedded tags.
}</code></pre> -->
<form action="?action=AddTextFromXMLInputPOST" method="post">
    <div class="w66">
        <div class="modulebox">
            <div class="title">XML-Text</div>
            <div class="title_extension">
                <a href="#">Reset</a>
            </div>
            <div class="body min-h400">
                <textarea name="xml" id="xml" class="w100 h400"></textarea>
            </div>
        </div>
    </div>
    
    <div class="w33 right">
        <div class="modulebox">
            <div class="title">Properties</div>
            <div class="body">
                <fieldset>
                    <legend class="required">Name</legend>
                    <input name="name" id="name" type="text" class="text w33" />
                    <!--<p>Note: Name and comments are only internal descriptions and will neither be written to the actual xml file nor exportet.</p>-->
                    <legend>Comment</legend>
                    <textarea name="comment" class="w66 h100"></textarea><br />
                </fieldset>
                <fieldset>
                	<legend>Options</legend>
                	<input type="checkbox" name="tokenize" /> <a href="#" class="tooltipp" title="Check this option to automatically tokenize the &lt;txt&gt; section.">Tokenize text</a><br />
                    <input type="checkbox" name="migrate" /> <a href="#" class="tooltipp" title="Check this option if the text is currently encoded in the old xml schema, i.e., words are separated by &lt;wn&gt; tags inside the &lt;txt&gt; section.">Convert old to new XML schema</a>
                </fieldset>
                <fieldset>
                    <legend class="required">Corpus Assignment</legend>
                    <?php echo htmlCorpusSelectionDropdown($ps->getActiveProject()); ?>
                </fieldset>
                <input type="submit" class="button" value="Add text" />
            </div>
        </div>
    </div>
</form>
</div>