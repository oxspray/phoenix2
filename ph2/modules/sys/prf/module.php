<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: System Preferences
Module Signature: com.ph2.modules.sys.prf
Description:
Various system settings and preferences, e.g. e-mail
notifications.
---
/*/
//! MODULE BODY

?>

<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/sys/sys.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
