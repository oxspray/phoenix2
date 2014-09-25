<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Lemma
Module Signature: com.ph2.modules.ann.lex.lem
Description:
Lexical Head properties.
---
/*/
//! MODULE BODY

?>

<script type="text/javascript">
	$(document).ready( function() {
		var matchingOccurrences = PH2Component.OccContextBox('occbox1');
		var lexRefBox = PH2Component.LexRefBox('lexrefbox1');
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
    
    <div class="w66">
        <div class="modulebox">
            <div class="title">Basic</div>

            <div class="title_extension">
                <a href="#" title="Save changes to lemma head">Save</a>
                <a href="#" title="Discard changes and restore original values">Restore</a>
            </div>
            <div class="body">
                <form action="" method="post">
                    
                    <fieldset>	
                    
                        <label for="f3">Bezeichnung</label>
                        <input type="text" class="text normal" name="f3" value="mouture" />

                        <label class="inline" for="f2">Type:</label>
                        <select>
                        	<option selected="selected">Concept [c] </option>
                            <option>Name of Person [n]</option>
                            <option>Name of Location [l]</option>
                        </select>

                        <label class="inline" for="f1">Wortart:</label>
                        <input type="text" class="text small" name="f1" value="n f [tagged]" />

                        <label class="inline">Diasystematik:</label>
                        <select>
                        	<option selected="selected">lorr.</option>
                            <option>latinisme</option>
                        </select>

            		</fieldset>
                    
                    <fieldset>
                        <legend>Etymon</legend>

                        <label for="f1">Form</label>
                        <input type="text" class="text small" name="f1" value="mouture" />

                        <label  class="inline"for="f1">Wortart:</label>
                        <input type="text" class="text small" name="f1" value="n.f. [tagged]" />

                        <label  class="inline"for="f1">Sprache:</label>
                        <select>
                        	<option>Latein</option>
                            <option selected="selected">Mittellatein</option>
                            <option>Fränkisch</option>
                        </select>

                        <label  class="inline"for="f1">Quelle:</label>
                        <select>
                        	<option>XYZ</option>
                            <option selected="selected">FEW</option>
                            <option>ZZZ</option>
                        </select>
                        <input type="text" class="text tiny" name="f1" value="6/3, 42b" />
                        
                        <label for="f1">Bedeutung</label>
                        <input type="text" class="text w75" name="f1" value="action de moudre" />
                        
                        <label for="f1">Datierung &amp; Herkunftsweg</label>
                        <input type="text" class="text w75" name="f1" value="mot héréditaire, avec cont..." />
                        
                    </fieldset>
                    
                    <fieldset>
                    	<legend>Derivation</legend>
                        
                        <div class="toggle_details_container">
                        	<div class="toggle_details">
                        		<input type="radio" name="deriv" value="0" checked="checked"> Etymon ist direkte Vorform<span class="details">: &nbsp;
                                	<label class="inline above" for="f1">Derivation:</label>
                                    <select>
                                        <option>(none)</option>
                                        <option selected="selected">N → V</option>
                                        <option>V → N</option>
                                    </select>
                               	</span>
                        		<br />
                          	</div>
                        <div class="toggle_details">
                        	<input type="radio" name="deriv" value="1"> Andere direkte Vorform<span class="details indent">: &nbsp;
                            	<div class="indent">
                                    <label for="f1">Grundform</label>
                                    <input type="text" class="text small" name="f1" value="mouture" />
            
                                    <label for="f1" class="inline">Affix:</label>
                                    <input type="text" class="text tiny" name="f1" value="-nt" />
                                    
                                    <label  class="inline"for="f1">Wortart:</label>
                                    <input type="text" class="text small" name="f1" value="n.f. [tagged]" />
            						
                                    <label class="inline" for="f1">Derivation:</label>
                                    <select>
                                        <option>(none)</option>
                                        <option selected="selected">N → V</option>
                                        <option>V → N</option>
                                    </select>
                                    
                                    <label  class="inline"for="f1">Sprache:</label>
                                    <select>
                                        <option>Latein</option>
                                        <option selected="selected">Mittellatein</option>
                                        <option>Fränkisch</option>
                                    </select>
                                    
                                    <label for="f1">Bedeutung</label>
                        			<input type="text" class="text w50" name="f1" value="ANM.: Wollen wir das Feld «Derivation» nicht autom. ableiten lassen?" />
                               </div>
                            </span>
                        </div>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                    	<legend>Basislexem</legend>
                        
                       	<div class="toggle_details_container">
                        
                        	<div class="toggle_details">
                        		<input type="radio" name="baselex" value="0" checked="checked"> Als Basislexem markieren
                            </div>
                            
                            <div class="toggle_details">
                                <input type="radio" name="baselex" value="1"> Anderes Basislexem<span class="details">:&nbsp; <?php echo htmlLemmaSelectionDropdown($ps->getActiveProject(), 'baselex_lemma_id', array('modulefield', 'text', 'small', 'combobox'), 'select_baselex'); ?></span>
                            </div>    
                       	
                        </div>                   
                        
                    </fieldset>
                    
                </form>
            </div>
        </div>

    </div>
    
    <div class="w33">
        <div class="modulebox LexRefBox" id="lexrefbox1">
            <div class="title">Lexikogr. Basis</div>

            <div class="title_extension">
                <a href="#" id="add_lexref" title="Add reference">Add</a>
            </div>
            <div class="body">
            <!-- tabs -->
            <!-- end tabs -->
            <form>
            	<fieldset>
                <table class="selectable" id="references">
                    <thead>
                        <tr>
                            <td></td>
                            <td>Form</td>
                            <td>Quelle</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <tr id="ref-1">
                            <td><a class="icon delete" title="delete this reference" href="#"></a></td>
                            <td><input type="text" class="text w80" name="form-cur-1" id="form-cur-1" value="immouture n.f." /></td>
                            <td>	<select name="source_book-cur-1" id="source_book-cur-1">
                            		<!-- implementation note: post field-value-ids for integers in id, name -x -->
                                        <option>FEW</option>
                                        <option selected="selected">TL</option>
                                        <option>Gdf</option>
                                    </select>
                        			<input type="text" class="text tiny" name="source_page-cur-1" id="source_page-cur-1" value="6,374" />
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr id="ref-2">
                            <td><a class="icon delete" title="delete this reference" href="#"></a></td>
                            <td><input type="text" class="text w80" name="form-cur-2" id="form-cur-2" value="immolture n.f." /></td>
                            <td>	<select name="source_book-cur-2" id="source_book-cur-2">
                                        <option>FEW</option>
                                        <option>TL</option>
                                        <option selected="selected">Gdf</option>
                                    </select>
                        			<input type="text" class="text tiny" name="source_page-cur-2" id="source_page-cur-2" value="10,167a" />
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr id="ref-3">
                            <td><a class="icon delete" title="delete this reference" href="#"></a></td>
                            <td><input type="text" class="text w80" name="form-cur-3" id="form-cur-3" value="" /></td>
                            <td>	<select name="source_book-cur-3" id="source_book-cur-3">
                                        <option selected="selected">…</option>
                                        <option>TL</option>
                                        <option>Gdf</option>
                                        <option>FEW</option>
                                    </select>
                        			<input type="text" class="text tiny" name="source_page-cur-3" id="source_page-cur-3" value="" />
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                </fieldset>
          	</form>
            </div>
        </div>

    </div>
    
    <div class="w33">
        <div class="modulebox">
            <div class="title">Comments</div>

            <div class="title_extension">
            </div>
            <div class="body">
                <form action="" method="post">
                    <fieldset>
                    
                        <label for="f4" >Semantik</label>
                        <textarea class="w98"></textarea>
                        
                        <label for="f5" >Derivation</label>
                        <textarea class="w98"></textarea>
                        
                        <label for="f6" >Metalexikographie</label>
                        <textarea class="w98"></textarea>
                        
                    </fieldset>
                </form>
            </div>
        </div>

    </div>
    
</div>

</div>