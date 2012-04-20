<?php

//------------------------------------------------------------------------------
// Main class
class block_sharedropbox extends block_list {

    //--------------------------------------------------------------------------
    function init() {
        $this->title = get_string('sharedropbox','blocks/sharedropbox');
        $this->content_type = BLOCK_TYPE_LIST;
    }

    //--------------------------------------------------------------------------
    function instance_allow_config() {
        return true;
    }
    
    //--------------------------------------------------------------------------
    function hide_header() {
        return isset($this->config->hide_header) && $this->config->hide_header=='on';
    }
    
    //--------------------------------------------------------------------------
    function preferred_width() {
        // The preferred value is in pixels
        return 190;
    }
                            
    function get_content() {
        // Access to settings needed
        global $USER, $COURSE, $CFG, $DB, $PAGE;
        // If content has already been generated, just update the time
        if ($this->content !== NULL) {
		
			// Try to get the most up-to-date time
            $this->content->header =get_string('sharedropbox','blocks_sharedropbox');
			return $this->content;
        }        
     
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        $editor=has_capability('moodle/course:update', $context);
        
        $this->content = new stdClass;
        $this->content->items = array(); 
        $drops = $DB->get_records('assignment',array('course'=>$COURSE->id));
        if($drops){
            $PAGE->requires->js('/blocks/sharedropbox/sharedropbox.js'); 
            
            foreach($drops as $d){
                $drp = "drop{$d->id}";
                $cnfg = get_config('blocks/sharedropbox',$drp);
                // deal with legacy encodings [ESF only]
                if($cnfg>0 && $cnfg<6){
                    switch($cnfg){
                        case 1: $cnfg='S';break;    
                        case 2: $cnfg='S,T';break;    
                        case 3: $cnfg='S,T,L';break;    
                        case 4: $cnfg='S,T,C,L';break;    
                        case 5: $cnfg='S,T,P,C,L';break;
                    }
                    set_config($drp,$cnfg,'blocks/sharedropbox');
                }
                $flags = explode(',',$cnfg);
                $sharing = in_array('T',$flags) && in_array('S',$flags); 
                if($cm = get_coursemodule_from_instance("assignment", $d->id, $COURSE->id)){
                    list($icons,$summary) = $this->flagsToSummary($flags);
                    $edit=$editor?"<a href='{$CFG->wwwroot}/blocks/sharedropbox/configs.php?aid={$d->id}'><img src=\"{$CFG->wwwroot}/blocks/sharedropbox/pix/edit.gif\" alt=\"Edit\" /></a>":'';
                    $link = "<a href='{$CFG->wwwroot}/blocks/sharedropbox/view.php?a={$d->id}' title='{$summary}' alt='{$summary}'>[$icons]</a> $edit";
                    if($editor||$sharing){
                        $PAGE->requires->js_init_call('addsharedropbox', array($cm->id,$link));
                    }
                    $this->content->items[] = $d->name . ': ' . $link;
                }
            }
        }
        
        return $this->content;
    }
    
    function flagsToSummary($f){
        global $CFG;
        $student=in_array('S',$f);
        $teacher=in_array('T',$f);
        if($teacher){
            $summary=$student?'all':'teacher';    
        }else{
            $summary=$student?'student':'none';    
        }
        if($summary=='none'){
            return array('','');    
        } else{
            $text=get_string($summary,'blocks/sharedropbox');
            $icons="<img src='{$CFG->wwwroot}/blocks/sharedropbox/pix/share{$summary}.gif' alt='$text'>";
            if(in_array('L',$f)){
                $t=get_string('likes','blocks/sharedropbox');    
                $icons.="<img src='{$CFG->wwwroot}/blocks/sharedropbox/pix/yes.gif' alt='$t'>";    
                $text .= ' '.$t;
            }
            if(in_array('C',$f)){
                if(in_array('P',$f)){
                    $t=get_string('private','blocks/sharedropbox');    
                    $icons.="<img src='{$CFG->wwwroot}/blocks/sharedropbox/pix/private.gif' alt='{$t}'>";    
                    $text .= ' '.$t;
                }else{ 
                    $t= get_string('comments','blocks/sharedropbox');    
                    $icons.="<img src='{$CFG->wwwroot}/blocks/sharedropbox/pix/comments.gif' alt='{$t}'>";
                    $text .= ' '.$t;
                }
            }
            if(in_array('U',$f)){
                $t=get_string('blind','blocks/sharedropbox');    
                $icons.="<img src='{$CFG->wwwroot}/blocks/sharedropbox/pix/blind.gif' alt='{$t}'>";
                $text .= ' '.$t;
            }
            return array($icons,$text);    
        }
    }  

}
?>
