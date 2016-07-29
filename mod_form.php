<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main voicerec configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_voicerec_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $USER, $PAGE, $CFG;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('voicerecname', 'voicerec'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'voicerecname', 'voicerec');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }
        //-------------------------------------------------------------------------------
		// ここから独自の実装
        // Adding the rest of voicerec settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
		// $mform->addElement('static', 'label1', 'voicerecsetting1', 'Your voicerec fields go here. Replace me!')
        //-------------------------------------------------------------------------------
        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'voicerec'), array('optional'=>true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'voicerec'), array('optional'=>true));
        $mform->setDefault('timedue', time()+7*24*3600);

        // 録音最大時間
        $mform->addElement('text', 'maxduration', get_string('maxduration', 'voicerec'), array('size'=>'16'));
        $mform->setType('maxduration', PARAM_INT);
        $mform->addHelpButton('maxduration', 'maxduration', 'voicerec');
        $mform->setDefault('maxduration', get_config('voicerec' , 'maxduration'));
        
        // 録音ファイルの数
        $options = array();
        for ($i = 1; $i <= get_config('voicerec' , 'maxnumber'); $i++) {
        	$options[$i] = $i;
        }
        $name = get_string('maxnumber', 'voicerec');
        $mform->addElement('select', 'maxnumber', $name, $options);
        $mform->addHelpButton('maxnumber', 'maxnumber', 'voicerec');
        
        //提出日以降の送信を阻止する
        $mform->addElement('selectyesno', 'preventlate', get_string('preventlate', 'voicerec'));

//        $mform->addElement('header', 'voicerecfieldset', get_string('voicerecfieldset', 'voicerec'));
//        $mform->addElement('static', 'label2', 'voicerecsetting2', 'Your voicerec fields go here. Replace me!');

        //-------------------------------------------------------------------------------
		// 以下テンプレのまま
        //-------------------------------------------------------------------------------
        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
    function validation($data, $files) {
    	$errors = parent::validation($data, $files);
    	$system_maxduration = 1200; // in case of admin failuer.
    	$site_maxduration = get_config('voicerec' , 'maxduration');
    	$maxduration = ($system_maxduration>$site_maxduration)?$site_maxduration:$system_maxduration;
    	if(0<= $data['maxduration'] && $data['maxduration'] <= $maxduration){
    		return $errors;
    	}else{
    		$error['maxduration'] = get_string('maxdurationerror', 'voicerec',$site_maxduration);
    		return $error;
    	}
    }
    
}
