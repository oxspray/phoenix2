<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Exports
Module Signature: com.ph2.modules.prj.exp
Description:
See Texts and Corpora that are currently checked out for external editing and delete 
checkouts.
---
/*/
//! MODULE BODY

?>

<script type="text/javascript">
// functions
function showUserDetailsBox (rowReference, userID, fadeIn) {
	// show corpus details window
	if (fadeIn == true) {
		$("#user_properties-active").fadeIn();
	} else {
		$("#user_properties-active").show();
	}
	$("#user_properties-inactive").hide();
	// update user details in form (NAIVE!)
	var nickname  = $("td:eq(2)", rowReference).text();
	var fullname = $("td:eq(3)", rowReference).text();
	var mail = $("td:eq(4)", rowReference).text();
	$("#nickname").val(nickname);
	$("#fullname").val(fullname);
	$("#mail").val(mail);
	// assign corpusID to hidden form field
	$("#user_id").val(userID);
}

// routine
$(document).ready( function() {
	
	// show user details
	$("table#users tbody tr").click( function() {
		// select corpusID from checkbox in same tr
		var userID = $("td input", this).attr('value');
		// show boxes and update their content
		showUserDetailsBox(this, userID, true);
	});
	
	$('#change_password').click ( function() {
		var nickname = $('#nickname').val();
		var fullname = $('#fullname').val();
		var user_id = $('#user_id').val();
		$.fancybox( { 'href':'modal.php?modal=user_change_password&nickname=' + nickname + '&fullname=' +  fullname + '&id=' + user_id } );
	});
	
});
</script>


<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/prj/prj.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>

<div id="mod_body">
    
    <div class="w66">
    
        <div class="w100">
            <div class="modulebox">
                <div class="title">Corpora</div>
                <div class="title_extension">
                    <form id="change_exports_corpora" action="?action=UpdateExportsCorpora" method="post">
                        <select id="exports_corpora_action" name="exports_corpora_action">
                            <option value="" selected="selected">(select action)</option>
                            <option value="reset">reset selected</option>
                        </select>
                        <input type="submit" class="button" value="OK" />
                </div>
                <div class="body">
                    <?php
                    $dao_CORPUS = new Table('CHECKOUT');
					$dao_CORPUS->select = 'Identifier, Checkout as `Checked-Out on`, CORPUS.Name as `Corpus Name`, Fullname as `Checked-Out by`';
					$dao_CORPUS->from = 'CHECKOUT join CORPUS on CHECKOUT.CorpusID=CORPUS.CorpusID join sys_USER on CHECKOUT.UserID=sys_USER.UserID';
					$dao_CORPUS->where = 'CHECKOUT.CorpusID and Checkin is NULL and IsInvalid = 0';
					$dao_CORPUS->orderby = 'Checkout DESC';
					$tr = new ResultSetTransformer( $dao_CORPUS->get() );
                    echo $tr->toHTMLTable('all', 'Identifier', 'checkout_identifier_corpus', 'corpora', array('hoverable2'), array('Identifier'), TRUE);
                    ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="w100">
            <div class="modulebox">
                <div class="title">Individual Texts</div>
                <div class="title_extension">
                    <form id="change_exports_texts" action="?action=UpdateExportsTexts" method="post">
                        <select id="exports_texts_action" name="exports_texts_action">
                            <option value="" selected="selected">(select action)</option>
                            <option value="reset">reset selected</option>
                        </select>
                        <input type="submit" class="button" value="OK" />
                </div>
                <div class="body">
                    <?php
                    $dao_TEXT = new Table('CHECKOUT');
                    $dao_TEXT->select = 'Identifier, Checkout as `Checked-Out on`, CiteID as `Text CiteID`, `Order`, CORPUS.Name as `Corpus Name`, Fullname as `Checked-Out by`';
					$dao_TEXT->from = 'CHECKOUT join TEXT on CHECKOUT.TextID=TEXT.TextID join CORPUS on TEXT.CorpusID=CORPUS.CorpusID join sys_USER on CHECKOUT.UserID=sys_USER.UserID';
                    $dao_TEXT->where = 'TEXT.CorpusID not in (select CorpusID from CHECKOUT where CorpusID and Checkin is NULL and IsInvalid = 0)
											and Checkin is null
											and IsInvalid = 0';
					$dao_TEXT->orderby = 'Checkout DESC';					
                    $tr = new ResultSetTransformer( $dao_TEXT->get() );
                    echo $tr->toHTMLTable('all', 'Identifier', 'checkout_identifier_text', 'texts', array('hoverable2'), array('Identifier'), TRUE);
                    ?>
                    </form>
                </div>
            </div>
        </div>
    
    </div>
    
    <div class="w33 right">
        <div class="modulebox">
            <div class="title">Information</div>
            <div class="body">
            <p>All Corpora and Texts listed here have been checked-out for external editing. Both corpora and texts can be resetted by selecting the relevant items and then commiting "reset" in the (select action) menu. However, note that this will invalidate all exported XML files, i.e., they cannot be checked-in anymore.</p>
            </div>
        </div>
    </div>
    
</div>