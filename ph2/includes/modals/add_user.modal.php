<?php
/* Phoenix2
** Modal Window
==
This dialogue adds a new corpus to the currently active project (according to the $ps session) and redirects to a given site.
*/
?>
<h1>Create User</h1>
<p>To Create a new user, please fill in the details below:</p>
<form method="post" action="?action=CreateUser">
    <fieldset>
        <legend class="required">Nickname</legend>
        <input name="nickname" type="text" class="text w33" />
        <legend class="required">Password</legend>
        <input name="password" type="password" class="text w33" />
        <legend class="required">Full Name</legend>
        <input name="fullname" type="text" class="text w50" />
        <legend class="required">Mail</legend>
        <input name="mail" type="text" class="text w50" />
    </fieldset>
    <input type="submit" class="button" value="Create User" name="create_user" />
</form>