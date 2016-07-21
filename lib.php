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
 * Library of interface functions and constants for module voicerec
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the voicerec specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('voicerec_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function voicerec_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the voicerec into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $voicerec Submitted data from the form in mod_form.php
 * @param mod_voicerec_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted voicerec record
 */
function voicerec_add_instance(stdClass $voicerec, mod_voicerec_mod_form $mform = null) {
    global $DB;

    $voicerec->timecreated = time();

    // You may have to add extra stuff in here.

    $voicerec->id = $DB->insert_record('voicerec', $voicerec);

    voicerec_grade_item_update($voicerec);

    return $voicerec->id;
}

/**
 * Updates an instance of the voicerec in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $voicerec An object from the form in mod_form.php
 * @param mod_voicerec_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function voicerec_update_instance(stdClass $voicerec, mod_voicerec_mod_form $mform = null) {
    global $DB;

    $voicerec->timemodified = time();
    $voicerec->id = $voicerec->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('voicerec', $voicerec);

    voicerec_grade_item_update($voicerec);

    return $result;
}

/**
 * Removes an instance of the voicerec from the database
 * 活動削除時に呼ばれて活動に関するレコード、ファイルを削除する。
 * 
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function voicerec_delete_instance($id) {
    global  $CFG, $DB;
    if (! $voicerec = $DB->get_record('voicerec', array('id'=>$id))) {
        return false;
    }
    $result = true;
    $fs = get_file_storage();
    if ($cm = get_coursemodule_from_instance('voicerec', $voicerec->id)) {
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id);
    }
    
    if (! $DB->delete_records('voicerec_messages', array('voicerecid'=>$voicerec->id))) {
        $result = false;
    }
    
    if (! $DB->delete_records('voicerec_audios', array('voicerecid'=>$voicerec->id))) {
        $result = false;
    }
    
    if (! $DB->delete_records('event', array('modulename'=>'voicerec', 'instance'=>$voicerec->id))) {
        $result = false;
    }
    
    if (! $DB->delete_records('voicerec', array('id'=>$voicerec->id))) {
        $result = false;
    }
    $mod = $DB->get_field('modules','id',array('name'=>'voicerec'));
    
    voicerec_grade_item_delete($voicerec);
    
    return $result;
    
}
/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $voicerec The voicerec instance record
 * @return stdClass|null
 */
function voicerec_user_outline($course, $user, $mod, $voicerec) {
    global $CFG;
    
    require_once("$CFG->libdir/gradelib.php");
    
    $grade = voicerec_get_user_grades($voicerec, $user->id);
    if ($grade > -1) {
        $result = new stdClass();
        $result->info = get_string('grade').': '.$grade;
        $result->time = '';
        return $result;
    }
    else {
        return null;
    }}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $voicerec the module instance record
 */
function voicerec_user_complete($course, $user, $mod, $voicerec) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in voicerec activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function voicerec_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link voicerec_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function voicerec_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link voicerec_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function voicerec_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function voicerec_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function voicerec_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of voicerec?
 *
 * This function returns if a scale is being used by one voicerec
 * if it has support for grading and scales.
 *
 * @param int $voicerecid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given voicerec instance
 */
function voicerec_scale_used($voicerecid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('voicerec', array('id' => $voicerecid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of voicerec.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any voicerec instance
 */
function voicerec_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('voicerec', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given voicerec instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $voicerec instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function voicerec_grade_item_update(stdClass $voicerec, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    /*テンプレートの処理
    $item['itemname'] = clean_param($voicerec->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($voicerec->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $voicerec->grade;
        $item['grademin']  = 0;
    } else if ($voicerec->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$voicerec->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    */

    $item = array();
    $item['itemname'] = clean_param($voicerec->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $voicerec->grade;
    $item['grademin']  = 0;
    
    if ($reset) {
        $item['reset'] = true;
    }
    //61 function grade_update($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, $grades=NULL, $itemdetails=NULL) {
    $update_ret = grade_update('mod/voicerec', $voicerec->course, 'mod', 'voicerec', $voicerec->id, 0, null, $item);
}

/**
 * Delete grade item for given voicerec instance
 * 活動削除時に呼ばれて活動に関する評定関連のデータを削除する。
 * voicerec_delete_instanceから呼ばれる
 * 
 * @param stdClass $voicerec instance object
 * @return grade_item
 */
function voicerec_grade_item_delete($voicerec) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/voicerec', $voicerec->course, 'mod', 'voicerec',
            $voicerec->id, 0, null, array('deleted' => 1));
}
/**
 * Return grade for given user or all users.
 * テンプレートにはない。
 * voicerec_update_gradesの処理をnanogongからコピーした際に、同時にコピー。
 * 
 * 
 * 
 * @param int $voicerecid id of voicerec
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function voicerec_get_user_grades($voicerec, $userid=0) {
    global $CFG, $DB;

    if ($userid) {
        $user = "AND u.id = :userid";
        $params = array('userid'=>$userid);
    } else {
        $user = "";
    }
    $params['nid'] = $voicerec->id;

    $sql = "SELECT u.id, u.id AS userid, m.grade AS rawgrade, m.comments AS feedback, m.commentsformat AS feedbackformat, m.commentedby AS usermodified, m.timestamp AS dategraded
    FROM {user} u, {voicerec_messages} m
    WHERE u.id = m.userid AND m.voicerecid = :nid
    $user";

    return $DB->get_records_sql($sql, $params);
}
/**
 * Update voicerec grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 * テンプレートどおりの内容だったが評定が反映されないので、voicerecのソースをコピーしてみる。
 * 
 * @param stdClass $voicerec instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function voicerec_update_grades(stdClass $voicerec, $userid = 0) {
/* テンプレートの処理
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('mod/voicerec', $voicerec->course, 'mod', 'voicerec', $voicerec->id, 0, $grades);
*/
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    if ($voicerec->grade == 0) {
        voicerec_grade_item_update($voicerec);
    }
    else if ($grades = voicerec_get_user_grades($voicerec, $userid)) {
        foreach($grades as $k=>$v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        voicerec_grade_item_update($voicerec, $grades);
    }
    else {
        voicerec_grade_item_update($voicerec);
    }
    
    
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function voicerec_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for voicerec file areas
 *
 * @package mod_voicerec
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function voicerec_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the voicerec file areas
 *
 * @package mod_voicerec
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the voicerec's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function voicerec_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }
    require_login($course, true, $cm);
   
    if (!$voicerec = $DB->get_record('voicerec', array('id'=>$cm->instance))) {
        send_file_not_found();
    }
    require_capability('mod/voicerec:view', $context);
    
    $fullpath = "/{$context->id}/mod_voicerec/$filearea/".implode('/', $args);
    
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }
    
    //session_get_instance()->write_close(); // unlock session during fileserving
    \core\session\manager::write_close(); // Unlock session during file serving. umeno
    
    send_stored_file($file, 60*60, 0, true);
    
    
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding voicerec nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the voicerec module instance
 * @param stdClass $course current course record
 * @param stdClass $module current voicerec instance record
 * @param cm_info $cm course module information
 */
function voicerec_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the voicerec settings
 *
 * This function is called when the context for the page is a voicerec module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $voicerecnode voicerec administration node
 */
function voicerec_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $voicerecnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}

