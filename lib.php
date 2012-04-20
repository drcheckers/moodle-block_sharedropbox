<?php

/* provides sharing authenticated to access to any uploaded files
   through hook from pluginfile.php
*/
function block_sharedropbox_pluginfile($course, $birecord, $context, $filearea, $args, $forcedownload){
    global $CFG, $DB, $USER;
    require_once($CFG->libdir.'/filelib.php');
    $aid=$args[0];
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if (!$submission = $DB->get_record('assignment_submissions', array('id'=>$aid))) {
        return false;
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $submission->assignment, $course->id)) {
        return false;
    }    
    require_login($course, false, $cm);

    if ($filearea !== 'submission') {
        return false;
    }
    // what sharing do we have - 
    $flags = explode(',',get_config('blocks/sharedropbox','drop'.$aid));
    $editor = has_capability('mod/assignment:grade', $context); 
    $student = !$editor && has_capability('mod/assignment:view', $context); 
    if($editor){
        if(!in_array('T',$flags)){return false;}
    }elseif($student){
        if(!in_array('S',$flags)){return false;}
    }else{
        return false;    
    }
    $relativepath = implode('/', $args);
    $fullpath = '/'.$context->id.'/mod_assignment/'.$filearea.'/'.$relativepath;

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}

function renderLikes($flags,$sub,$sid){
    return 
    (in_array('L',$flags)
        ?("<div id='lk_{$sub}'>" . likes($sub) . '</div>')
        :'') .
    ((in_array('C',$flags)||in_array('P',$flags))
        ?("<div id='cm_{$sub}'>" . comments($sub) . '</div>')
        :'');     
}

function renderAllLikes($com,$sub,$sid,$editor=0,$private=0){
    global $DB;
    if($lks = $DB->get_records('block_sharedropbox_likes',array('submission'=>$sub->id))){
        $sep='';
        foreach($lks as $lk){
            $other=$DB->get_record('user',array('id'=>$lk->sid));
            $nm=fullname($other);
            $likes .= "{$sep}{$nm}";
            $sep =',';
        }
    }
    $coms = $DB->get_records_sql("select t1.id, comment, datetimeno, firstname, lastname, t0.id as sid from mdl_block_sharedropbox_comments t1, mdl_user t0 where t1.sid=t0.id and submission={$sub->id} order by datetimeno");
    if($coms){
        $comments ='';
        $private = get_string('currentlyprivate','blocks_sharedropbox');
        foreach($coms as $c){       
            if($private){
                if($sub->userid==$USER->id || $editor || $USER->id==$c->sid){ 
                    $thecomment = stripslashes($c->comment);
                }else{
                    $thecomment = "<i>{$private}</i>";
                }
            }else{
                $thecomment = stripslashes($c->comment);
            }
            $comments .= '<b>' . fullname($c) . "</b> <small>" . formatsince($c->datetimeno). "</small><br />" . ($thecomment) . "<br />"   ;
        }
    }         
    return ($likes?("<hr/>".get_string('likes','block_sharedropbox').":<br/>$likes"):'') . ($comments?"<hr/>".get_string('comments','block_sharedropbox')."<br/>$comments":'');
}

function IconStrip($r,$a,$editor=false,$blind=false){
    global $DB;
    $expand = "<a target='_blank' href='singleview.php?a=$a&sid={$r}'><img alt='Full Screen' src='pix/fullscreen.gif' border='0' /></a>";
            
    if($row=$DB->get_record('user',array('id'=>$r))){   
        $email = $editor?"<a href='mailto:{$row->email}'><img src='pix/email.gif'/></a>":"";
        $name = !$blind;
        return ($name?fullname($row):get_string('anonymous','blocks/sharedropbox')) . '&nbsp;' . $email . '&nbsp;' . $expand;
    }
}  

function likes($sub){
    global $DB,$USER;
    $self=0;
    if($lks = $DB->get_records('block_sharedropbox_likes',array('submission'=>$sub))){
        $cmtcount = $lkcount = 0;
        foreach($lks as $lk){
            if($USER->id==$lk->sid){
                $self=$lk->id;
            }else{
                $other=$DB->get_record('user',array('id'=>$lk->sid));
                $nm=fullname($other);
                $out .= "<img width=20 src='pix/yes.gif' title='{$nm}' alt='{$nm}' />";
            }
        }
        if($self){
            if($readonly){
                $out .= "<img width=20 src='pix/youyes.gif' alt='You' title='You' />";      
            }else{
                $out .= "<a class='deleteLike' ><img  id='dl_{$sub}' width=20 src='pix/youyes.gif' alt='You' title='You' /></a>";  
            }
        }
    }
    if(!$self && !$readonly){
        $ll = "<div style='float:left;clear:both'>$out</div>";
        $ll .= "<div style='float:right;clear:right'><a class='addLike'><img id='al_{$sub}' width=20 src='pix/addyes.gif' /></a></div>";
    }else{
        $ll="<div style='float:left;clear:both'>$out</div>";
    }
    return $ll;
}
function comments($sub){
    global $DB;
    if($cc=$DB->get_records("block_sharedropbox_comments",array('submission'=>$sub))){
        $cs = count($cc);
        $cc = ($cs==1)?'1 comment':"$cs comments";
    }else{
        $cc = 'comment';    
    }
    return "<hr style='clear:both' /><a id='sc_{$sub}' class='showComment'>$cc</a><div class='modal' id='modal_{$sub}'></div>";
}
function commentlist($sub,$private=0,$editor=0){
    global $DB;
    global $USER;
    if($coms = $DB->get_records_sql("select t1.id, comment, datetimeno, firstname, lastname, t0.id as sid from mdl_block_sharedropbox_comments t1, mdl_user t0 where t1.sid=t0.id and submission={$sub->id} order by datetimeno")){
        foreach($coms as $c){                
            if($USER->id==$c->sid || $editor){
                $delete = "<a class='deleteComment'><img id='del_{$c->id}' src='pix/delete.gif' /></a>";
            }
            if($private){
                if($sub->userid==$USER->id || $editor || $USER->id==$c->sid){ 
                    $thecomment = $c->comment;
                }else{
                    $thecomment = '<i>currently private</i>';      
                }
            }else{
                $thecomment = $c->comment;
            }    
            $out .= '<b>' . fullname($c) . "</b> <small>" . formatsince($c->datetimeno). "</small>$delete<br />" . ($thecomment) . " <br />";
        }
    } 
    $out .= "<br />Add comment:<br />
                        <input type='text' size='50' id='addcomment' onblur='javascript:document.getElementById(\"addbutton_{$sub->id}\").click()' value=''/><br />
                        <input type='submit' class='addButton' id='addbutton_{$sub->id}' value='Submit' />";
    return $out;
}
  
function getextension($file){
    $ps = explode('/',$file);
    $ps = explode('.',$ps[count($ps)-1]);
    if (count($ps)==1)
    {
        return '';
    }
    else
    {
        $qs = explode('?',$ps[count($ps)-1]);
        return strtolower($qs[0]);
    }
}

function embedLink($url,$x='100%',$y='100%',$optimistic=false){
    global $CFG;
    $url = trim($url);
    $ext = getextension($url);
    
    if($ext=='xxx'){
        $frame = "<iframe width='$x' src='http://docs.google.com/gview?url=$url'></iframe>";    
    }elseif($ext=='pdf'||$ext=='doc'||$ext=='docx'||$ext=='xls'||$ext=='xlsx'||$ext=='ppt'||$ext=='pptx'){
        $ps = explode('/',urldecode($url)); 
        $frame="<div width='$x'><a href='{$url}' target='_blank'>Click to visit<br />" . getfilename($url) . "</a></div>";
        //$frame = "<iframe id='iframe' security='restricted' padding='10px' border='2' width='100%' height='$y' src='{$url}'></iframe>";
    }elseif($ext=='gif'||$ext=='jpg'||$ext=='png'||$ext=='jpeg'||$ext=='bmp'){
        $frame = "<div width='$x'><a target='new' href='$url'><img width='100%' src='{$url}' /></a></div>";    
    }elseif($ext=='mp3'){
        $fname = getfilename($url);
        $fid = md5($url);
        $frame = "<br /><a href='$url'>$fname</a><br /><span class='mediaplugin mediaplugin_mp3' id='filter_mp3_{$fid}'>(MP3 audio)</span>
                    <script type='text/javascript'>
                    //<![CDATA[
                      var FO = { movie:'{$CFG->wwwroot}/filter/mediaplugin/mp3player.swf?src=$url',
                        width:'90', height:'15', majorversion:'6', build:'40', flashvars:'bgColour=000000&amp;btnColour=ffffff&amp;btnBorderColour=cccccc&amp;iconColour=000000&amp;iconOverColour=00cc00&amp;trackColour=cccccc&amp;handleColour=ffffff&amp;loaderColour=ffffff&amp;waitForPlay=yes', quality: 'high' };
                      UFO.create(FO, 'filter_mp3_{$fid}');
                    //]]>
                    </script><script defer='defer' src='{$CFG->wwwroot}/filter/mediaplugin/eolas_fix.js' type='text/javascript'>
                    // <![CDATA[ ]]>
                    </script>";        
    }elseif($ext=='wmv'){                                                     
            $f = '<!--[if !IE]> <-->
                <div width="' . $x .'"><a href="' . $url . '" target="_blank">Click to view<br />' . getfilename($url) . '</a></div> 
                <i>Tip: use IE to show wmv files embedded</i>
                <!--> <![endif]-->';
            $f.='<!--[if IE]>
                <object id="MediaPlayer" width="320" height="280" classid="CLSID:22D6f312-B0F6-11D0-94AB-0080C74C7E95" standby="Loading Windows Media Player components..." type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,7,1112">
                <param name="filename" value="' . $url . '">
                <param name="Showcontrols" value="1">
                <param name="autoStart" value="0">
                <embed type="application/x-mplayer2" src="' . $url . '" name="MediaPlayer" width=320 height=240></embed>
                </object>
        <![endif]-->'; 
            $frame="<div width='$x'>$f</div>";         
    }elseif(substr($url,1,3)=='div'){
        $frame = $url; 
    }elseif(substr($url,0,4)=='http'){ 
        if(stripos($url,'sites.google')>0 || 
        stripos($url,'google.com')>0 || 
        stripos($url,'imdb.com')>0 || 
        stripos($url,'scratch.mit.edu')>0 ||  
        
        stripos($url,'bbc.co.uk')>0 ||  
        stripos($url,'kgv.hk')>0 ||  
        stripos($url,'kgv.edu.hk')>0 ||  
        $optimistic ||
            empty($url)){
            $frame="<div width='$x'><iframe id='iframe' padding='10px' border='2' width='$x' height='$y' src='{$url}'></iframe></div>"; 
        }elseif(stripos($url,'vimeo.com')>0){
             $ps = explode('vimeo.com/',$this->url);
             $vid = $ps[1];
             $frame = "<div width=\"$x\" ><object width=\"$x\" height=\"$y\"><param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowscriptaccess\" value=\"always\" /><param name=\"movie\" value=\"http://vimeo.com/moogaloop.swf?clip_id={$vid}&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=00ADEF&amp;fullscreen=1\" /><embed src=\"http://vimeo.com/moogaloop.swf?clip_id={$vid}&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=00ADEF&amp;fullscreen=1\" type=\"application/x-shockwave-flash\" allowfullscreen=\"true\" allowscriptaccess=\"always\" width=\"$x\" height=\"$y\"></embed></object></div>";
        }elseif(stripos($url,'youtube.com')>0){
            $ps = explode('v=',$url);
            $ps1 = explode('&',$ps[1]);
            $url=$ps1[0];
            $frame= '<object width="'. $x .'"><param name="movie" value="http://www.youtube.com/v/' . $url . '&hl=en_GB&fs=1&border=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/' . $url . '&hl=en_GB&fs=1&border=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $x . '" height="' . $y . '"></embed></object>';
        }else{                                                     
            //$frame="<div width='$x'><a href='{$url}' target='_blank'>Click to visit<br />" . getfilename($url) . "</a></div>";         
            $frame="<div width='$x'>" . make_clickable($url) . "</div>";         
            //$frame="HERE";//<div width='$x'>" . make_clickable($url) . "</div>";         
            
        }
    }else{
        $frame="<div width='$x'>" . make_clickable($url) . '</div>';    
    }
    return $frame;
}

function _make_url_clickable_cb($matches) {
    $ret = '';
    $url = $matches[2];
 
    if ( empty($url) )
        return $matches[0];
    
    $tmpkey="&nbsp;";
    $cnt=substr_count($url, $tmpkey);
    $url=str_replace($tmpkey, "", $url);
    
    // removed trailing [.,;:] from URL
    if ( in_array(substr($url, -1), array('.', ',', ';', ':')) === true ) {
        $ret = substr($url, -1);
        $url = substr($url, 0, strlen($url)-1);
    }
    return $matches[1] . "<a href=\"$url\" rel=\"nofollow\">$url</a>" . $ret.str_repeat($tmpkey, $cnt);
}
 
function _make_web_ftp_clickable_cb($matches) {
    $ret = '';
    $dest = $matches[2];
    $dest = 'http://' . $dest;
 
    if ( empty($dest) )
        return $matches[0];
    // removed trailing [,;:] from URL
    if ( in_array(substr($dest, -1), array('.', ',', ';', ':')) === true ) {
        $ret = substr($dest, -1);
        $dest = substr($dest, 0, strlen($dest)-1);
    }
    return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\">$dest</a>" . $ret;
}
 
function _make_email_clickable_cb($matches) {
    $email = $matches[2] . '@' . $matches[3];
    return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
}
 
function make_clickable($ret) {
    $ret = ' ' . $ret ;
    // in testing, using arrays here was found to be faster
    $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\x80-\xff\#$%~/.\-;:,@\[\]]*(\?[\w\x80-\xff\#$%&~/.\-;:=,@\[\]+]+|))#is', '_make_url_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\x80-\xff\#$%~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);
 
    // this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
    $ret = trim($ret);
    return $ret;
}
  
function formatsince($time){
    $t = str_split($time,2);
    $timethen = ("{$t[0]}{$t[1]}-{$t[2]}-{$t[3]} {$t[4]}:{$t[5]}:{$t[6]}") ;
    $timenow= (date("Y-m-d H:i:s"));
    $timeCalc = strtotime($timenow) - strtotime($timethen);
    if ($timeCalc > (60*60*24)) {$timeCalc = round($timeCalc/60/60/24) . " days ago";}
    else if ($timeCalc > (60*60)) {$timeCalc = round($timeCalc/60/60) . " hours ago";}
    else if ($timeCalc > 60) {$timeCalc = round($timeCalc/60) . " minutes ago";}
    else if ($timeCalc > 0) {$timeCalc .= " seconds ago";}

    return $timeCalc;
}                              


?>
