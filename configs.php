<?php
    require_once(dirname(__FILE__) . '/../../config.php');
    require_once('configs_form.php');    
    require_login();
    
    global $DB,$CFG;    
    
    $aid = required_param('aid',PARAM_INTEGER);
    
    $assignment = $DB->get_record('assignment', array('id'=>$aid));
    $context = get_context_instance(CONTEXT_COURSE, $assignment->course);   
    
    if(!has_capability('moodle/course:update', $context)){  
        print_error("no access allowed!");
    }

    $mform = new configs_form(Null,array('aid'=>$aid));
    if ($m=$mform->get_data())
    { 
         foreach($m as $k=>$v){
            if($v==='1'){
                if($k!='aid'){
                    $flags[]=$k;
                }
            } 
         }
         set_config('drop'.$aid,implode(',',$flags),'blocks/sharedropbox'); 
         redirect($CFG->wwwroot . "/course/view.php?id={$assignment->course}",'',0);
    }else{
        $PAGE->set_pagelayout('base');
        $PAGE->set_context($context);
        $PAGE->set_url('/blocks/sharedropbox.php');
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('configuration','block_sharedropbox'));
        echo get_string('subtitle','block_sharedropbox') . $assignment->name;
        $mform->display();
        echo '<br/><br/><br/><br/>';
        echo $OUTPUT->footer();   
    }                   

?>