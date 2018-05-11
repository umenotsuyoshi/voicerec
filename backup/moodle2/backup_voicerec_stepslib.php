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
 * Define all the backup steps that will be used by the backup_voicerec_activity_task
 *
 * @package   mod_voicerec
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete voicerec structure for backup, with file and id annotations
 *
 * @package   mod_voicerec
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_voicerec_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // https://docs.moodle.org/dev/Backup_2.0_for_developers
        // Define the root element describing the voicerec instance.
        $voicerec = new backup_nested_element('voicerec', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timeavailable',
            'timedue', 'grade', 'maxduration', 'maxnumber', 'preventlate',
            'permission', 'timecreated', 'timemodified'));

        $messages = new backup_nested_element('messages');

        $message = new backup_nested_element('message', array('id'), array(
            'userid', 'message', 'supplement', 'supplementformat',
            'audio', 'comments', 'commentsformat',
            'commentedby', 'grade', 'timestamp', 'locked'));

        $audios = new backup_nested_element('audios');

        $audio = new backup_nested_element('audio', array('id'), array(
            'userid', 'type', 'title', 'name', 'timecreated'));

        // Build the tree
        $voicerec->add_child($messages);
        $messages->add_child($message);
        $voicerec->add_child($audios);
        $audios->add_child($audio);

        // Define data sources.
        $voicerec->set_source_table('voicerec', array('id' => backup::VAR_ACTIVITYID));
		if ($userinfo) {
		    $message->set_source_sql('
                SELECT *
                  FROM {voicerec_messages}
                  WHERE voicerecid = ?',
		        array(backup::VAR_PARENTID));
		    $audio->set_source_table('voicerec_audios', array('voicerecid' => backup::VAR_PARENTID));

		}
        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

		// Define id annotations
		$voicerec->annotate_ids('scale', 'grade');
		$message->annotate_ids('user', 'userid');
		$message->annotate_ids('user', 'commentedby');
		$audio->annotate_ids('user', 'userid');


        // Define file annotations (we do not use itemid in this example).
		$voicerec->annotate_files('mod_voicerec', 'intro', null);
		$message->annotate_files('mod_voicerec', 'message', 'id');
        $voicerec->annotate_files('mod_voicerec', 'audio', 'id');



        // Return the root element (voicerec), wrapped into standard activity structure.
        return $this->prepare_activity_structure($voicerec);
    }
}
