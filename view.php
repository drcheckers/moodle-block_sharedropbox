<?php 

    require_once("../../config.php");
    global $CFG;
    require_once("lib.php");
    require_once("../../mod/assignment/lib.php");
    
    global $DB, $COURSE, $OUTPUT;
    
    $id = optional_param('id', 0, PARAM_INT);  // Course Module ID
    $a  = optional_param('a', 0, PARAM_INT);   // Assignment ID
    $group  = optional_param('group', 0, PARAM_INT);   // group ID
    $width  = optional_param('width', 3, PARAM_INT);   
     
    $widths = array(1,2,3,4,5,6,7,8,10);
    $colw = 100*$width;
    $colh = 80*$width;
             
    
    if ($id) {
        if (! $cm = get_coursemodule_from_id('assignment', $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $assignment = $DB->get_record("assignment", array("id"=> $cm->instance))) {
            error("assignment ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
            error("Course is misconfigured");
        }
    } else {
        if (!$assignment = $DB->get_record("assignment", array("id"=> $a))) {
            error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id"=> $assignment->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course, true, $cm);

    require ("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
    $assignmentclass = "assignment_$assignment->assignmenttype";
    $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);
    
    $PAGE->set_pagelayout('base');
    $PAGE->set_context($context);
    $url = new moodle_url('/blocks/sharedropbox/view.php');
    $PAGE->set_url($url);
    $PAGE->requires->js('/blocks/sharedropbox/sharedropbox.js');
    //$PAGE->requires->css('/blocks/sharedropbox/sharedropbox.css');
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('sharedropbox','block_sharedropbox'));
       
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);   
    $editor = has_capability('mod/assignment:grade', $context);
    
    $drp = "drop{$assignment->id}";
    $flags = explode(',',get_config('blocks/sharedropbox',$drp));
    
    echo "<input type='hidden' id='cmid' value='{$cm->id}'/>";
                            
    
    if((in_array('S',$flags) && !$editor) || (in_array('T',$flags) && $editor)){        
        $url->params($_GET);
        if(!isset($params['width'])){
            $url->params(array('width'=>$width));
        }
        echo "<br />Width: ";
        foreach($widths as $w){
            $url->params(array('width'=>$w));
            echo "<a href='" . $url . "'>" . $w*100 . " </a>";
        }
        $url->params(array('width'=>$width));
         
        echo '<br />';
        if($editor || $cm->groupmode==2){
           // editor or all groups mode allows user to choose a group
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping (all if groupings not used)
        }else{
           // no groups - no sharing
           // seperate groups - share only with the students course
           // visible groups - allow group selection to students 
           if($cm->groupmode==0){
                print_error("errNoGroups",'sharedropbox');
                //Must select seperate or visible groups mode for share dropbox");    
           }else{
                $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);                     
           }
        }
        
        // create a combo group selector populated with allowed groups
        $groupsmenu = array();
        $groupsmenu[0] = 'Select group ...';
        if ($allowedgroups) {
            $cnt=0;
            $url->params(array('a'=>$assignment->id));
            foreach ($allowedgroups as $grp) {
                $url->params(array('group'=>$grp->id));
                $index = $url->out(false);
                if($grp->id==$group){
                    $selected=$index;
                }
                $options[$index]=format_string($grp->name);
                $cnt++;
            }
            if($cnt==1){
                $selected=$index;  
                $group=$grp->id;
            }
        }
        $select = new url_select($options, $selected, array(0=>'Select group ...'), 'choosegroup');
        $select->label='Group:';
        echo $OUTPUT->render($select);            
        echo "<br /><br />";
                
        if($group){
            require_once($CFG->libdir .'/filelib.php');
            echo "<br /><h2>" . get_string('submissions','block_sharedropbox') . " {$options[$index]}</h2>";    
            $studs=groups_get_members($group);
            $rout='';
            foreach($studs as $stud){
                $out = IconStrip($stud->id,$a,$editor,in_array('U',$flags));
                $submission = $DB->get_record('assignment_submissions',array('assignment'=>$assignment->id,'userid'=>$stud->id));
                $submit=true;
                // looks for any upload files
                $fs = get_file_storage();                    
                $count=0;
                if ($files = $fs->get_area_files($context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
                    $fout='';
                    foreach ($files as $file) {
                        $filename = $file->get_filename();
                        $found = true;
                        $mimetype = $file->get_mimetype();
                        $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/block_sharedropbox/submission/'.$submission->id.'/'.$filename);
                        $fout .= embedlink($path,"{$colw}px","{$colh}px");
                        $count++;
                    }
                    $out .= ($count==1)?'1 File':"{$count} Files" . $fout;    
                }
                if(!empty($submission->data1)){
                    // its an online assignment with something submitted
                    $out .= embedlink($submission->data1,"{$colw}px","{$colh}px");
                    $count++;    
                }    
                $out .= renderLikes($flags,$submission->id,$stud->id);
                if($count==0){
                    $unsubmits[] = $stud->id;     
                    $submit=false;
                }else{
                    $ordered[$submission->grade][]= "<li style='vertical-align:top;display:inline-block;border:2px solid #444;width:{$colw}px;height:100%;overflow:hidden;'>{$out}</li>";
                }
            }
        }
        // output the assignments in chosen teacher grade order [unmarked last] 
        // then sub sorted by fullname alphabetical order 
        if($ordered){
            if(in_array('A',$flags)){
                ksort($ordered);
            }else{
                krsort($ordered);
            }
            foreach($ordered as $k=>$gs){
                if($k==-1){
                    $nout = implode('',$gs);
                }else{
                    $rout .= implode('',$gs);
                }
            }
            echo "<ul style='width:100%;margin-bottom:200px'>$rout $nout</ul>";
        }
        // include a handy list of unsubmitted students - for teachers only
        if($unsubmits && $editor){
            echo "<hr style='clear:both'/><h2>" . get_string('unsubmitted','block_sharedropbox') . "</h2><ul>";
            foreach ($unsubmits as $r){ 
                echo "<li>" . IconStrip($r,$a,$editor) . "</li>";    
            }
            echo '</ul>';
        }
    }else{
       print_error('unavailable','block_sharedropbox');     
    }   
    

 ?>    
    <div id="panel">
    <div class="hd"><?php print_string("addview","block_sharedropbox"); ?></div>
    <div id='panel_bd' class="bd"></div>
    </div>    
<?php     
    print_footer();
?>
