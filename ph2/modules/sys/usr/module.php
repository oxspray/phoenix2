<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Users and Permissions
Module Signature: com.ph2.modules.sys.usr
Description:
Manage user accounts and set read/write/remove permissions.
---
TODO: define exact permission set
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
    <?php include PH2_WP_INC . '/modules/menus/sys/sys.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>

<div id="mod_body">
	<?php $project = new Project($ps->getActiveProject()); ?>
    <div class="w66">
        <div class="modulebox">
            <div class="title">Registered Users</div>
            <div class="title_extension">
            	<form id="change_user" action="?action=UpdateUsers" method="post">
                    <select id="users_action" name="users_action">
                        <option value="" selected="selected">(select action)</option>
                        <option value="delete">delete selected</option>
                    </select>
                    <input type="submit" class="button" value="OK" />
            </div>
            <div class="body">
            	<?php
				$users = new Table('sys_USER');
				$users->select = 'UserID, Nickname, Fullname, Mail';
				$users->where = "Nickname != 'guest'";
				$tr = new ResultSetTransformer( $users->get() );
				echo $tr->toSelectableHTMLTable('all', 'UserID', 'user_id', 'users');
				?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="w33 right">
        <div class="modulebox">
            <div class="title">Information</div>
            <div class="body hidden" id="user_properties-active">
                <form action="?action=UpdateUserDetails" method="post">
                    <fieldset>
                        <legend class="required">Nickname</legend>
                        <input name="nickname" id="nickname" type="text" class="text w33" />
                        <legend class="required">Password</legend>
                        <input name="change_password" id="change_password" type="button" class="button" value="Change password" />
                        <br /><br />
                        <legend class="required">Full Name</legend>
                        <input name="fullname" id="fullname" type="text" class="text w50" />
                        <legend class="required">Mail</legend>
                        <input name="mail" id="mail" type="text" class="text w50" />
                    </fieldset>
                    <input id="user_id" type="hidden" name="user_id" value="" />
                    <input name="corpus_details" type="submit" class="button" value="Save" />
            	</form>
            </div>
            <div class="body" id="user_properties-inactive">
            <p>To edit details, please select a user from the list.</p>
            </div>
            </div>
        </div>
    </div>
</div>