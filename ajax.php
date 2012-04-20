<?php
   include_once("../../config.php");         
   require_once('lib.php');     
   global $DB,$USER;
   $sub = required_param('sub',PARAM_INT);
   $cmid = required_param('cmid',PARAM_INT);
   $action = optional_param('action','',PARAM_TEXT);
   $updates=array();
   if($action=='addlike'){
        // don't care about permissions!
        $DB->execute("replace into mdl_block_sharedropbox_likes (submission,sid) values ('$sub','{$USER->id}')");
        $updates= array('lk_'.$sub => likes($sub));                 
   }elseif($action=='deletelike'){
        // don't care about permissions!
        $DB->execute("delete from mdl_block_sharedropbox_likes where sid={$USER->id} and submission={$sub}");
        $updates= array('lk_'.$sub => likes($sub));                 
   }elseif($action=='viewcomments'){
        if($submission = $DB->get_record("assignment_submissions", array("id"=> $sub))){
            $editor = has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cmid));
            if(!$editor){
                $flags = explode(',',get_config('blocks/sharedropbox','drop'.$submission->assignment));
                $private=in_array('P',$flags);
            }else{
                $private=false;
            }
            $updates= array('panel_bd' => commentlist($submission,$private,$editor));                 
        }
   }elseif($action=='addcomment'){
        $comment = optional_param('comment','',PARAM_TEXT);
        if($comment!=''){
            $dt=date('YmdHis');
            if(permission($sub,$cmid,'mod/assignment:view')){
                $DB->execute("insert into mdl_block_sharedropbox_comments (datetimeno,submission,sid,comment) values ($dt,$sub,{$USER->id},'" . addslashes($comment) . "')");     
            }
        } 
        $updates= array('cm_'.$sub => comments($sub));                 
   }elseif($action=='deletecomment'){
        // $sub id the comment id number
        if($comment=$DB->get_record('block_sharedropbox_comments',array('id'=>$sub))){
            if(permission($comment->submission,$cmid,'mod/assignment:grade')){ // comment can be deleted by self or teacher (someone with grade permission)
                $DB->execute("delete from mdl_block_sharedropbox_comments where id=$sub");     
                $updates= array('cm_'.$comment->submission => comments($comment->submission));                 
            }
        }
   }
   echo json_encode( $updates );

/*
  return true if user is owner of submission or has specified permissions 
*/
function permission($sub,$cmid,$p='mod/assignment:view'){
    global $DB,$USER;
    if ($submission = $DB->get_record("assignment_submissions", array("id"=> $sub))) {   
       if($USER->id==$submission->userid){
           $editor=true;
       }else{
           $editor = has_capability($p, 
                    get_context_instance(CONTEXT_MODULE, $cmid)); 
       }
    }
    return $editor;   
}
           
?>
