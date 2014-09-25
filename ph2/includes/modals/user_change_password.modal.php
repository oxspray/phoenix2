<?php
/* Phoenix2
** Modal Window
==
This dialogue adds a new corpus to the currently active project (according to the $ps session) and redirects to a given site.
*/
?>

<script type="text/javascript">
$(document).ready( function() {
	
	// check valid input, then submit
	$("input[name='update_password']").click( function(e) {
		
		e.preventDefault();
		
		// check if password is entered twice the same
		if ( $("input[name='new_password']").val() == $("input[name='new_password_verify']").val() ) {
			$('p.invalid').hide();
			$('input').removeClass('invalid');
			$("#form_update_password").submit();
		} else {
			$('p.invalid').html('Please repeat your password identically.').show();
			$("input[name='new_password']").addClass('invalid');
			$("input[name='new_password_verify']").addClass('invalid');
		}
		
		
		
	});
	
});
</script>


<h1>Change Password</h1>
<p>User: <?php echo $_GET['fullname'] . ' (' . $_GET['nickname'] . ')'; ?></p>
<p class="invalid hidden"></p>
<form id="form_update_password" method="post" action="?action=UpdateUserPassword">
    <fieldset>
        <legend class="required">Current Password</legend>
        <input name="current_password" type="password" class="text w80" />
        <legend class="required">New Password</legend>
        <input name="new_password" type="password" class="text w80" />
        <legend class="required">New Password (repeat)</legend>
        <input name="new_password_verify" type="password" class="text w80" />
    </fieldset>
    <input id="user_id" type="hidden" name="user_id" value="<?php echo $_GET['id']; ?>" />
    <input type="submit" class="button" value="Save" name="update_password" />
</form>