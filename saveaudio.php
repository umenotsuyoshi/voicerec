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
 * Save audio file
 *
 * @author     
 * @author     
 * @package    mod
 * @subpackage voicerec
 * @copyright  
 * @license    
 * @version    
 */
require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');


require_login();
$id = required_param('id', PARAM_INT);
$title = required_param('title', PARAM_TEXT);
if (!confirm_sesskey()){
    error('Bad Session Key');
    exit;
}

if ($id) {
    $cm = get_coursemodule_from_id('voicerec', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);   
    $voicerec = $DB->get_record('voicerec', array('id' => $cm->instance), '*', MUST_EXIST);
}
else {
    error('Invalid Parameters!');
}
$PAGE->set_url('/course/view.php', array('id' => $id));
$cansubmit = has_capability('mod/voicerec:submit', $context);
if (!$cansubmit) {
    print 'notavailable';
    die;
}
$cangrade = false;
// 開始日、終了日のチェック
// timeavailable:開始日時
// timedue:提出日
$submission = $DB->get_record('voicerec_messages', array('voicerecid'=>$voicerec->id, 'userid'=>$USER->id));
if($err_type = check_can_recording($voicerec, $submission)){
    $event = \mod_voicerec\event\voice_save_error::create_voice_save_error($cm, $context, $err_type);
    $event->trigger();
    print $err_type; // defined in lang pack. and conv by javascript and alert user. 
    die;
}

$elname = "voicerec_upload_file";
// Use data/time as the file name
if (isset($_FILES[$elname]) && isset($_FILES[$elname]['name'])) {
    $oldname = $_FILES[$elname]['name'];//blobが
    $ext = preg_replace("/.*(\.[^\.]*)$/", "$1", $oldname);
    $newname = date("Ymd") . date("His") . $ext;
    $_FILES[$elname]['name'] = $newname;
}
else {
    print '[servererror]';
    die;
}
// Store the audio file
$fs = get_file_storage();
$file = array('contextid'=>$context->id, 'component'=>'mod_voicerec', 'filearea'=>'audio',
              'itemid'=>$voicerec->id, 'filepath'=>'/', 'filename'=>$_FILES[$elname]['name'],
              'timecreated'=>time(), 'timemodified'=>time(),
              'mimetype'=>'audio/ogg', 'userid'=>$USER->id, 'author'=>fullname($USER),
              'license'=>$CFG->sitedefaultlicense);
$retfs = $fs->create_file_from_pathname($file, $_FILES[$elname]['tmp_name']);
$url = $_FILES[$elname]['name'];

if (!$submission) {
    $submission = new stdClass();
    $submission->voicerecid       = $voicerec->id;
    $submission->userid           = $USER->id;
    $submission->message          = '';
    $submission->supplement       = '';
    $submission->supplementformat = FORMAT_HTML;
    $submission->audio            = '';
    $submission->comments         = '';
    $submission->commentsformat   = FORMAT_HTML;
    $submission->commentedby      = 0;
    $submission->grade            = -1;
    $submission->timestamp        = time();
    $submission->locked           = false;
    $submission->id = $DB->insert_record("voicerec_messages", $submission);
}
$DB->update_record('voicerec_messages', $submission);
// add_to_logの置き換え 
$event = \mod_voicerec\event\voice_save::create(array(
        'objectid' => $cm->instance,
        'context' => $context,
));
$event->trigger();// replace add_to_log($course->id, 'nanogong', 'update', 'view.php?n='.$nanogong->id, $nanogong->id, $cm->id);

$voicerecaudio = new stdClass();
$voicerecaudio->voicerecid   = $voicerec->id;
$voicerecaudio->userid       = $USER->id;
$voicerecaudio->type         = 1;
$voicerecaudio->title        = $title;
$voicerecaudio->name         = $url;
$voicerecaudio->timecreated  = time();
$voicerecaudio->id = $DB->insert_record("voicerec_audios", $voicerecaudio);
$DB->update_record('voicerec_audios', $voicerecaudio);
redirect(new moodle_url('view.php', array('id'=>$id, 'action'=>'savemessage')));



