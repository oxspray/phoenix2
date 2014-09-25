<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Morphology
Module Signature: com.ph2.modules.ann.lex.mrp
Description:
Define morphological categories and assign occurrences.
---
/*/
//! MODULE BODY

?>
<script type="text/javascript">
	$(document).ready( function() {
		var matchingOccurrences = PH2Component.OccContextBox('occbox1');
		var morphGroupSelector = PH2Component.GroupSelectorMorphology('groupselector1', matchingOccurrences, '');
	});
</script>

<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/ann/lex.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">

	<!-- Occurrence Context Box -->
    <div class="w100">
        <div class="modulebox OccContextBox" id="occbox1">
            <div class="title">Assigned Occurrences</div>
            
            <div class="title_extension">
            	<form action="" method="post">
                    <select name="select_action">
                        <option value="1">Remove Selected</option>
                        <option value="2">Reassign Selected</option>
                    </select>
                    <input type="button" class="button" value="OK" />
                </form>
            </div>

            <div class="body">
            	<!-- tabs -->
                <!-- end tabs -->
                
                <div id="occ_progress" class="hidden">loading <span id="current"></span>/<span id="total"></span></div>
                <table>
                    <thead>
                      <tr>
                        <td><input type="checkbox" class="select_all" rel="occ_selection" name=""/></td>
                        <th><a href="#" class="tooltipp" title="Corpus ID. Hover to display the name of the corpus.">Crp</a></th>
                        <th><a href="#" class="tooltipp" title="Text ID. Hover to display the name of the text.">Txt</a></th>
                        <th class="wider"><a href="#" class="tooltipp" title="Text Section. Hover to display the corresponding description.">Sct</a></th>
                        <th><a href="#" class="tooltipp" title="Involved text division.">Div</a></th>
                        <th class="padded">Context</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            
            	<div id="occ_matches_meta" class="h200">
                	<table>
                    	<!-- occ meta lines -->
                    </table>
                </div>
                
            	<div id="occ_matches" class="scrollbox h200">
                	<!-- occ context lines -->
              	</div>
                
            </div>
        </div>
    </div>
                
    <div class="w33">
        <div class="modulebox GroupSelector Morphology" id="groupselector1">
            <div class="title">Morphology</div>
            <div class="title_extension">
            	<a href="#" class="tablink" rel="tab1" title="Change citation form">
                    <span id="cit_form" class="bold">n masc</span>&nbsp;
                </a>
            </div>
            <div class="body">
            
            <!-- tabs -->
            	<div id="tab1" class="tab hidden">
                	<p>Citation Form: [tagged input field] [OK-Button]</p>
                </div>
            <!-- end tabs -->
            
                <table class="selectable" id="groups">
                    <thead>
                        <tr>
                            <td></td>
                            <td><a class="tooltipp" title="Morphological category" href="#">Group</a></td>
                            <td>Occ.</td>
                            <td>Texts</td>
                            <td>Corp.</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="special" id="all">
                            <td></td>
                            <td>» show all</td>
                            <td>31</td>
                            <td>2</td>
                            <td>1</td>
                        </tr>
                        <tr class="last special" id="unassigned">
                            <td></td>
                            <td>» unassigned occurrences</td>
                            <td>6</td>
                            <td>1</td>
                            <td>1</td>
                        </tr>
                        <tr id="group-x">
                            <td><a class="icon ok" title="Add selected occurrences (above) to this group" href="#"></a></td>
                            <td>n m sg reg</td>
                            <td>23</td>
                            <td>2</td>
                            <td>1</td>
                        </tr>
                        <tr id="group-y">
                            <td><a class="icon ok" title="Add selected occurrences (above) to this group" href="#"></a></td>
                            <td>n m pl reg [1]</td>
                            <td>2</td>
                            <td>1</td>
                            <td>1</td>
                        </tr>
                        <tr id="group-y" class="selected">
                            <td><a class="icon ok" title="Add selected occurrences (above) to this group" href="#"></a></td>
                            <td>n m pl reg [2]</td>
                            <td>2</td>
                            <td>1</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="w66">
        <div class="modulebox">
            <div class="title">Morphological Group</div>

            <div class="title_extension">
                <a href="#" title="Save changes to morph group">Save</a>
                <a href="#" title="Discard changes and restore original values">Restore</a>
            </div>
            <div class="body">
                <form action="" method="post">
                    <fieldset>
                        <label for="f1">Wortart</label>
                        <input type="text" class="text small" name="f1" value="n m pl reg" />
                        <label class="inline" for="f2">Untergruppe:</label>
                        <input type="text" class="text tiny" name="f2" value="2" />
                    </fieldset>
                    <fieldset>
                    <legend>Varianten</legend>
                    	<p>Formen in den Korpora: (autom. Liste aus allen der Gruppe zugeordneten Okkurrenzen)</p>
                        <label for="f1">Typische Form</label>
                        <input type="text" class="text small" name="f1" value="imouture" />
                        <label class="inline" for="f1">Varianten:</label>
                        <input type="text" class="text big" name="f1" value="imolture" />
                    </fieldset>
                    <fieldset>
                    <legend>Chronologie</legend>
                    <p>Erstes Vorkommen: (autom.)</p>
                    <p>Letztes Vorkommen: (autom.)</p>
                    <label for="f4" >Periode</label>
                    <input type="text" class="text normal" name="f1" value="12es. -frm" />
                    <label for="f4" >Comment</label>
                    <textarea class="w80">In Migrationsset keine Daten vorhanden?</textarea>
                    </fieldset>
                </form>
            </div>
        </div>

    </div>
    
</div>

</div>