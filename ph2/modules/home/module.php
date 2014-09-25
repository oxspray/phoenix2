<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Home Screen
Module Signature: com.ph2.modules.home
Description:
The Phoenix2 dashboard which is loaded immediately after a user has logged
in.
---
/*/
//! MODULE BODY

?>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">

    <div class="w100">
    	<div class="modulebox dashboard">
        	<h3>Dashboard</h3>
            <p>Please select a module from the menu below. Note that the system status is Alpha; there are many non-functional links and stubs.</p>
        </div>
    </div>
    
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="?action=redirect&module=prj.crp">Project &amp; Corpus Management</a></div>
            <div class="body">
            	<p>A Phoenix2 project gathers one or more corpora consisting of xml texts.</p>
            </div>
        </div>
    </div>
    
    <!--
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="?action=redirect&module=chg.new">Text Management</a></div>
            <div class="body">
            	<p>A new text is added by submitting an XML document that validates against the Phoenix2 schema definitions. Once fed into the system, a text can be assigned to a corpus, and its word occurrences may be tagged and annotated.</p>
            </div>
        </div>
    </div>
    -->
    
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="?action=redirect&module=ann.fnd">Assignment</a></div>
            <div class="body">
            	<p>Word occurrences can be assigned to lemmata or graphs. The assignment proccess is called «tagging» within Phoenix2 and is based on Regular Expressions and existing assignment queries.</p>
            </div>
        </div>
    </div>
    
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="?action=redirect&module=ann.gra.gra">Annotation</a></div>
            <div class="body">
            	<p>Edit details of entities such as lemmata, morphology or semantic groups or graphematics.</p>
                <!-- quicklinks can be insertedd as:
                <a href="#">Add text from xml</a>
                -->
            </div>
        </div>
    </div>
    
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="#">Export</a></div>
            <div class="body">
            	
            </div>
        </div>
    </div>
    
    <div class="w20">
        <div class="modulebox dashboard">
            <div class="title"><a href="?action=redirect&module=sys.prf">System &amp; User Management</a></div>
            <div class="body">
            	
            </div>
        </div>
    </div>
    
</div>