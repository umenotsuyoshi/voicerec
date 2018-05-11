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
 * Prints a particular instance of voicerec
 *
 * JavaScriptによる録音プログラム試作
 * 
 * 
 * @package    mod_voicerec
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');
$PAGE->requires->css('/mod/voicerec/style.css');
$action = optional_param('action', 0, PARAM_TEXT); //録音や音声削除などユーザの操作種別
$id = optional_param('id', 0, PARAM_INT); // course_modulesテーブルのID
$v  = optional_param('v', 0, PARAM_INT);  // 

// course_modules のレコードを取得する。
// 取得したcourse_modulesにコースのIDが含まれているので、コーステーブルのレコードを取得
// voicerecモジュールテーブル中でインスタンスの情報取得
if ($id) {
    $cm         = get_coursemodule_from_id('voicerec', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $voicerec  = $DB->get_record('voicerec', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($v) {
    $voicerec  = $DB->get_record('voicerec', array('id' => $v), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $voicerec->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('voicerec', $voicerec->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
//mdl_contextテーブルからレコードを取得
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
// 「コースモジュールが閲覧されました。」のログを残す。
$event = \mod_voicerec\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $voicerec);
$event->trigger();

$PAGE->set_url('/mod/voicerec/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($voicerec->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->jquery();
$PAGE->requires->js_init_call('M.mod_voicerec.init',array($voicerec->maxduration));
$PAGE->requires->strings_for_js(
        array('cannotsumit','changeserver','changebrowser','inputrectitle','timeoutmessage','notavailable','submissionlocked','reachedupperlimit'), 
        'voicerec');// Javascriptで使用する言語パック準備

// ここまでテンプレートどおり
/*********************************************************************************************************************************/
/*
 * voicerec_messagesテーブルにメッセージは記録される。
*/
/*********************************************************************************************************************************/
$cansubmit = has_capability('mod/voicerec:submit', $context);
$isavailable = true;
if($cansubmit){
    $time = time();
    if ($voicerec->timeavailable > $time) $isavailable = false;
    if ($voicerec->timedue && $time > $voicerec->timedue && $voicerec->preventlate != 0) $isavailable = false;
}
$PAGE->requires->jquery();
/*********************************************************************************************************************************/
/* HTMLページの出力開始  */
/*********************************************************************************************************************************/
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulename', 'voicerec'));
// Recorded voice list
/*********************************************************************************************************************************/
// 教員のブロック開始
/*********************************************************************************************************************************/
if (has_capability('mod/voicerec:grade', $context)) {
    if ($action === 0) {
        voicerec_print_audiotags($context, $voicerec); // 録音済みユーザの音声一覧のHTMLタグ出力
        voicerec_print_intro($cm, $voicerec);// 説明
        voicerec_print_rec_form($cm, $voicerec); // 録音フォーム
        voicerec_print_students_submit_list($context, $voicerec); // 
    // 評定用のフォームを表示する。個別の学生に対して評定を行う。
    }else if ($action === 'showgradeform') {
        voicerec_print_grade_form($context, $cm, $course, $action, $voicerec);
    }
/*********************************************************************************************************************************/
// 学生のブロック開始
/*********************************************************************************************************************************/
}else{
    voicerec_print_audiotags($context, $voicerec); // 録音済みユーザの音声一覧のHTMLタグ出力
    voicerec_print_intro($cm, $voicerec);
    voicerec_print_rec_form($cm, $voicerec);
    $submission = $DB->get_record('voicerec_messages', array('voicerecid'=>$voicerec->id, 'userid'=>$USER->id));
    voicerec_print_teacher_commet($context, $submission);
}
    echo $OUTPUT->footer();
