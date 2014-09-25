<?php
/*/
Phoenix2
Version 0.7 alpha, Build 12
===
Project Lead: Prof. Martin-Dietrich Glessgen, University of Zurich
Code by: Samuel Laeubli, University of Zurich
Contact: samuel.laeubli@uzh.ch
===
Module Name: Projects
Module Signature: com.ph2.modules.prj.prj
Description:
Create, edit and delete projects and edit their assignments.
---
/*/
//! MODULE BODY

?>
<script type="text/javascript">
// functions
function showProjectDetailsBox (rowReference, projectID, fadeIn) {
	// show corpus details window
	if (fadeIn == true) {
		$("#project_properties-active").fadeIn();
	} else {
		$("#project_properties-active").show();
	}
	$("#project_properties-inactive").hide();
	// update corpus details in form (NAIVE!)
	var name  = $("td:eq(2)", rowReference).text();
	var descr = $("td:eq(3)", rowReference).text();
	$("#name").val(name);
	$("#comment").val(descr);
	// assign projectID to hidden form field
	$("#project_id").val(projectID);
}

function showAssignedBox (projectID, fadeIn) {
	// show assigned texts window
	if (fadeIn == true) {
		$("#assigned").fadeIn();
	} else {
		$("#assigned").show();
	}
	$("#corpora > *").hide();
	$("#project-corpora-" + projectID).show();
	$("#users > *").hide();
	$("#project-users-" + projectID).show();
}

function reopenAfterPageRefresh (projectID) {
	// reopens all settings after a page refresh
	var row = findTableRowByInputValue ('projects', projectID);
	showProjectDetailsBox( row, projectID, false );
	showAssignedBox( projectID, false );
	$(row).addClass('selected');
}

// routine
$(document).ready( function() {
	// show corpus details / assigned texts
	$("table#projects tbody tr").click( function() {
		// select corpusID from checkbox in same tr
		var projectID = $("td input", this).attr('value');
		// show boxes and update their content
		showProjectDetailsBox(this, projectID, true);
		showAssignedBox(projectID, true);
	});
	// active project selection
	// toggle Active Project
	var pSel = $("#project-selection")
	var currentItem = pSel.parent().children('a.current');
	var button= $('input#change_active_project');
	var curProjName = currentItem.html();
	var curProjID = currentItem.attr('id');
	function toggleActiveProject() {
		if (pSel.hasClass('hidden')) {
			pSel.slideDown();
			pSel.removeClass('hidden');
			currentItem.addClass('active');
			currentItem.html('Please select:');
			button.val('cancel');
		} else {
			pSel.slideUp();
			pSel.addClass('hidden');
			currentItem.removeClass('active');
			currentItem.html(curProjName);
			button.val('change');
		}
	}
	// event listeners for ToggleActiveProject()
	$("#change_active_project").click( function() {
		toggleActiveProject();
	});
	$(currentItem).click( function() {
		toggleActiveProject();
	});
	// hover for whole item while hovering the change-button
	button.mouseenter( function() {
		if (pSel.hasClass('hidden')) {
			currentItem.addClass('hover');
		}
	});
	button.mouseleave( function() {
		if (currentItem.hasClass('hover')) {
			currentItem.removeClass('hover');
		}
	});
	// make current project bold in project overview table
	var row = findTableRowByInputValue ('projects', curProjID);
	row.children('td').addClass('bold');
	// session - reopen modules after page refresh
	<?php if($_POST['project_details'] || $_POST['add_project']) {
		$project_id = $_POST['project_id'];
		//die($corpus_id);
		echo "reopenAfterPageRefresh($project_id);";
	}	
	?>
});
</script>
<div id="mod_top">
    <?php include PH2_WP_INC . '/modules/menus/prj/prj.modulemenu.php'; ?>
</div>
<div id="mod_status"><?php htmlModuleStatusBarMessages($ps); ?></div>
<div id="mod_body">
    <div class="w66">
        <div class="modulebox">
            <div class="title">Projects</div>
            <div class="title_extension">
            	<form action="?action=UpdateCorpora" method="post">
                    <select name="corpus_selection">
                        <option value="" selected="selected">(select action)</option>
                        <option value="delete">delete selected</option>
                    </select>
                    <input type="submit" class="button" value="OK" />
            	</form>
            </div>
            <div class="body">
            	<?php
				/* For the gathering of projects, a Class like ProjectContainer holding all projects could be implemented but is left out at the moment as it would (?) only be used within this module. */
				// select all Projects from the database
				$dao = new Table('PROJECT');
				$dao->orderby = 'Created, ProjectID ASC';
				$projects = $dao->get();
				// store project IDs in seperate array
				$project_list = array();
				foreach ($projects as $project) {
					$project_list[(int) $project['ProjectID']] = $project['Name'];
				}
				// transform project overview to html table
				$transformer = new ResultSetTransformer($projects);
				echo $transformer->toSelectableHTMLTable( array( 'ProjectID' => 'ID', 'Name' => 'Name', 'ProjectDescr' => 'Description', 'Created' => 'Created' ), 'ProjectID', 'project_id', 'projects');
				?>
            </div>
        </div>
    </div>
    
    <div class="w33 right">
        <div class="modulebox" id="active-project">
            <div class="title"><a class="tooltipp" href="#" title="This project is currently loaded and thus active for editing. Click «change» to select another project to work on.">Active Project</a></div>
            <div class="body">
            	<input id="change_active_project" name="change_active_project" type="button" class="button" value="change" />
                <?php
				// prepare active project selection
				$inactive_projects_html = '';
				foreach ($project_list as $project_id => $project_name) {
					if ($project_id == $ps->getActiveProject()) {
						echo '<a href="#" class="active-project-selection current" id="' . $project_id . '">' . $project_name . '</a>';
					} else {
						$inactive_projects_html .= '<a href="?action=ChangeActiveProject&projectID=' . $project_id . '" class="active-project-selection" id="' . $project_id . '">' . $project_name . '</a>' . "\n";
					}
				}
				?>
            	<div id="project-selection" class="hidden">
                    <?php echo $inactive_projects_html; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w33 right">
        <div class="modulebox">
            <div class="title">Project Properties</div>
            <div class="body hidden" id="project_properties-active">
                <form action="?action=UpdateProjectDetails" method="post">
                    <fieldset>
                        <legend class="required">Name</legend>
                        <input name="name" id="name" type="text" class="text w33" />
                        <p>Name and comments are only internal descriptions and will neither be written to the actual xml file nor exportet.</p>
                        <legend>Comment</legend>
                        <textarea name="comment" id="comment" class="w66 h100"></textarea>
                    </fieldset>
                    <input id="project_id" type="hidden" name="project_id" value="" />
                    <input name="project_details" type="submit" class="button" value="Save" />
            	</form>
            </div>
            <div class="body" id="project_properties-inactive">
            <p>To edit details, please select a project from the list.</p>
            </div>
        </div>
    </div>
    
    <div class="w66 hidden" id="assigned">
        <div class="modulebox tabs">
        	<div class="title">
            	<a rel="corpora" href="#">Assigned Texts</a>
                <a rel="users" href="#">User Privileges</a>
            </div>
            <div class="body">
				<?php
                /* create Corpus Overview and User Privileges Overview for each project */
                foreach ($project_list as $project_id => $project_name) {
					// Corpus Overview
					echo '<div id="corpora">';
					$project = new Project($project_id);
					$tr = new ResultSetTransformer($project->getAssignedCorpora($as_resultset=TRUE));
					echo $tr->toHTMLTable( 'all', NULL, NULL, 'project-corpora-' . $project_id, array('hidden') );
					echo ('</div>');
					// User Privlieges
					echo '<div id="users">';
					echo '<p id="project-users-' . $project_id . '" class="hidden">User privileges management is not implemented yet.</p>'; // TODO
					echo ('</div>');
				}
                ?>
        	</div>
        </div>
    </div>
    
</div>