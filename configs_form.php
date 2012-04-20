<?php
require_once($CFG->libdir.'/formslib.php');
class configs_form extends moodleform
{
   function definition()
   {
        global $CFG,$DB,$OUTPUT,$PAGE;        
        $aid     = $this->_customdata['aid'];  
        $mform =& $this->_form;
        
        $drp = "drop{$aid}";
        $init = get_config('blocks/sharedropbox',$drp);
        $flags = explode(',',$init);    
        $mform->addElement('html', '<hr/>'.$d->name);
        
        $options = array(0=>get_string('no','block_sharedropbox'),1=>get_string('yes','block_sharedropbox')); 
        $mform->addElement('select', 'T', get_string('teacher','block_sharedropbox'), $options); 
        $mform->addHelpButton('T', 'teacher', 'block_sharedropbox');
        $mform->addElement('select', 'S', get_string('student','block_sharedropbox'), $options); 
        $mform->addHelpButton('S', 'student', 'block_sharedropbox');
        $mform->addElement('select', 'L', get_string('likes','block_sharedropbox'), $options); 
        $mform->addHelpButton('L', 'likes', 'block_sharedropbox');
        $mform->addElement('select', 'C', get_string('comments','block_sharedropbox'), $options); 
        $mform->addHelpButton('C', 'comments', 'block_sharedropbox');
        $mform->addElement('select', 'P', get_string('private','block_sharedropbox'), $options); 
        $mform->addHelpButton('P', 'private', 'block_sharedropbox');
        $mform->addElement('select', 'U', get_string('blind','block_sharedropbox'), $options); 
        $mform->addHelpButton('U', 'blind', 'block_sharedropbox');
        $mform->addElement('select', 'A', get_string('ascending','block_sharedropbox'), $options); 
        $mform->addHelpButton('A', 'ascending', 'block_sharedropbox');
        
        foreach($flags as $f){
           $mform->setDefault($f,1); 
        }
        
        $mform->addElement('hidden', 'aid', $aid);
    
        $mform->addElement('submit', 'submitbutton', get_string('savechanges','block_sharedropbox'));
      
   }
}
?>