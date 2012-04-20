<?php  

    require_once("../../config.php");
    require_once("lib.php");
    require_once("../../mod/assignment/lib.php");
     //dbg();
     
    $id = optional_param('id', 0, PARAM_INT);  // Course Module ID
    $a  = optional_param('a', 0, PARAM_INT);   // Assignment ID
    $sid  = optional_param('sid', 0, PARAM_INT);   // Assignment ID
    
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
        require_once($CFG->libdir .'/filelib.php');
        $out = IconStrip($sid,$a,$editor,in_array('U',$flags));
        $submission = $DB->get_record('assignment_submissions',array('assignment'=>$assignment->id,'userid'=>$sid));
        $submit=true;
           
        $fs = get_file_storage();                    
        if ($files = $fs->get_area_files($context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $found = true;
                $mimetype = $file->get_mimetype();
                $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/block_sharedropbox/submission/'.$submission->id.'/'.$filename);
                $out .= embedlink($path,"{$colw}px","{$colh}px");
            }
            $out = ($count==1)?'1 File':"{$count} Files" . $out;    
        }
        if(!empty($submission->data1)){
            // an online assignment with something submitted
            $out .= embedlink($submission->data1,"{$colw}px","{$colh}px");
        }
        $out .= renderAllLikes($flags,$submission,$stud->id,$editor,in_array('P',$flags));
        if($submit){
            echo $out;
        }
    }else{
        print_error('unavailable','block_sharedropbox'); 
    }
?>