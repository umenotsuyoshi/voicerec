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
 * Defines the view event.
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name <your@email.adress>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_voicerec\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_voicerec instance list viewed event class
 *
 * If the view mode needs to be stored as well, you may need to
 * override methods get_url() and get_legacy_log_data(), too.
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name <your@email.adress>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_grade extends \core\event\base {

    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['crud'] = 'u';// Describes whether the event reflects creation (c), reading (r), updating (u) or deleting (d). This should be a single character string.
        $this->data['edulevel'] = self::LEVEL_TEACHING;//The level of educational value of the event. Can be LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['objecttable'] = 'voicerec';
    }
    public static function get_name() {
        return get_string('graderecdvoice', 'voicerec');
    }
	public function get_description() {
	    return "graded a user with id {$this->userid}'s recorded voice in the category with id {$this->objectid} in the voicerec with course module id {$this->contextinstanceid}.";
    }
 
	public function get_url() {
        return new \moodle_url("/mod/$this->objecttable/view.php", array('id' => $this->contextinstanceid));
	}
     
    
}
