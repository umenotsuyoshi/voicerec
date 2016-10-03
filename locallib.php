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
 * Internal library of functions for module voicerec
 *
 * All the voicerec specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_voicerec
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');
/**
 * 標準設定の説明into表示
 * 
 * @param stdClass $voicerec
 * @param stdClass $cm
 */
function voicerec_print_intro($cm, $voicerec){
    global $OUTPUT;
    if ($voicerec->intro) { //
        echo $OUTPUT->box(format_module_intro('voicerec', $voicerec, $cm->id), 'generalbox mod_introbox', 'voicerecintro');
    }
}
/**
 * 録音用の独自フォームを表示
 * 録音した音声は、JavaScriptのBlobデータとして保持するが、それをformのinput type="file"要素に設定する
 * と、formをsubmitした際にファイルシステム上のファイルを見に行くような挙動をする。
 * 上記のブラウザの動作の回避方法が不明のため、音声のアップロードはJavaScriptで行う。
 * Moodleの提供するformオブジェクトも使えない。
 * 
 * JavaAppletがセキュリティ上の理由で、JavaApplet起点で動作しなければならないのとは別理由。
 *  
 * @param stdClass $cm
 * @param stdClass $voicerec
 */
function voicerec_print_rec_form($cm, $voicerec){
    global $DB, $OUTPUT, $USER;
    
    if($err_type = check_can_recording($voicerec)){
        echo "<h4 id='$err_type'>" . get_string($err_type, 'voicerec') . "</h4>";
        return;
    }
    // 言語パックのメッセージ準備
    //$langmsgstr = get_strings(array('rectitle','usemodernbrowser','checkmikvolume','startpermitbrowserrec',
    //        'youcanuploadfromhere','inputrectitle','uploadmanualy','submissionlabel',
    //        'permitbrowserrec','changebrowser','remainingtime','remainingtimeunit'),'voicerec');
    $rectitle = get_string('rectitle', 'voicerec');
    $usemodernbrowser = get_string('usemodernbrowser', 'voicerec');
    $checkmikvolume = get_string('checkmikvolume', 'voicerec');
    $startpermitbrowserrec = get_string('startpermitbrowserrec', 'voicerec');
    $inputrectitle = get_string('inputrectitle', 'voicerec');
    $submissionlabel = get_string('submissionlabel', 'voicerec');
    $permitbrowserrec = get_string('permitbrowserrec', 'voicerec');
    $changebrowser =  get_string('changebrowser', 'voicerec');
    $remainingtime =  get_string('remainingtime', 'voicerec');
    $remainingtimeunit =  get_string('remainingtimeunit', 'voicerec');
    $checkrecording = get_string('checkrecording','voicerec');
    $reclaber= get_string('reclabel','voicerec');
    $stoplaber= get_string('stoplabel','voicerec');
    $sesskey = sesskey();
    echo $OUTPUT->box_start('generalbox', 'recform');
    
    $voice_form = <<<EOD
    <form action="./saveaudio.php" method="POST" enctype="multipart/form-data" id="voice_send">
    <div>
    <h3>$rectitle</h3>
    <input id='voicerec_rec_comment' size=30 type="text" name="title" value="$inputrectitle" class='not_changed'/>
    </div>
    <ol>
    <li>$usemodernbrowser</li>
    <li>$checkmikvolume</li>
    <li>$startpermitbrowserrec</li>
    </ol>
    <canvas id="rec_level_meter" width="10" height="29"></canvas>
    <input type="button" id="voicerec_rec" value="$reclaber" />
    <input type="button" id="voicerec_stop" value="$stoplaber"  disabled='disabled'/>
    <input type="button" id="voicerec_check" value="$checkrecording" disabled='disabled'/>
    <audio src="" id="voicerec_recording_audio" controls><p>$usemodernbrowser</p></audio>
    <input type="button" id="voicerec_upload" value="$submissionlabel" disabled='disabled'/>
    <div>
        <div id="rectimer_block"><span>$remainingtime</span><span id="rectime_timer">{$voicerec->maxduration}</span><span>$remainingtimeunit</span></div>
    </div>
    <input type="hidden" name="id" value="$cm->id"/>
    <input type="hidden" name="sesskey" value="$sesskey" />
    </form>
EOD;
    echo $voice_form;
    echo $OUTPUT->box_end();
}
/**
 * 録音可否を判定
 * 対応するエラーメッセージのランゲッジパックの添字を返却
 * 
 * 
 * @param stdClass $voicerec
 * @param stdClass $submission
 * @return String  if no error, return null
 */
function check_can_recording($voicerec, $submission=null){
    global $DB, $OUTPUT, $USER;
    // before available time. :利用可能以前
    $time = time();
    if ($time < $voicerec->timeavailable){
        return 'notavailableyet';
    }
    // 提出日以降の送信を阻止する
    if ($voicerec->timedue && $time > $voicerec->timedue && $voicerec->preventlate != 0){
        return 'pastduedate';
    }
    // 教師に提出をロックされている場合
    if($submission==null){
        $submission = $DB->get_record('voicerec_messages', array('voicerecid'=>$voicerec->id, 'userid'=>$USER->id));
    }
    if($submission && $submission->locked){
        //echo "<h4 id='submittedvoicelocked'>" . get_string('submittedvoicelocked', 'voicerec') . "</h4>";
        return 'submissionlocked';
    }
    //録音数上限のチェック
    $audiocount = $DB->count_records('voicerec_audios', array('voicerecid'=>$voicerec->id, 'userid'=>$USER->id));
    if ($voicerec->maxnumber && $audiocount >= $voicerec->maxnumber){
        return 'reachedupperlimit';
    }
    return null;   
}
/**
 * 学生数分、提出のリストを表示する。
 * 教員のcapabilityで利用
 * 
 * @param stdClass $context
 * @param stdClass $voicerec
 */
function voicerec_print_students_submit_list($context, $voicerec) {
    global $DB, $OUTPUT, $PAGE;
    $students = get_users_by_capability($context, 'mod/voicerec:submit');
    echo $OUTPUT->box_start('generalbox', 'studentlist');
    foreach ($students as $student) {
        voicerec_print_student_submissions($context, $voicerec, $student->id);
	}
	echo $OUTPUT->box_end();
}
/**
 * 学生の録音音声、評点、教員のコメントを表示 
 *
 * @param stdClass $context
 * @param stdClass $voicerec
 * @param int $studentid
 */
function voicerec_print_student_submissions($context, $voicerec, $studentid) {
    global $DB, $OUTPUT;
    $submission = $DB->get_record('voicerec_messages', array('voicerecid'=>$voicerec->id, 'userid'=>$studentid));
    echo '<div class="student_submissions">';
    echo '<div class="student_name">';
    voicerec_print_student_link($studentid, $voicerec->course);
    if ($submission && $submission->locked) echo ' <img src="pix/lock.gif" style="vertical-align: middle" alt="" title="" />';
    echo '</div>';// student_name
    echo '<div class="submission_pane">';// submissions  
    voicerec_print_audiotags($context, $voicerec, $studentid);
    voicerec_print_commet_tool($context, $studentid, $submission);
    echo '</div>';// submissions    
    echo '</div>';//student_submissions
}
/**
 * 教員の付けた点数、コメントを表示する。
 * 
 * @param stdClass $context
 * @param int $studentid
 * @param stdClass $submission
 */
function voicerec_print_commet_tool($context, $studentid, $submission){
    global $PAGE;
    if(! voicerec_print_teacher_commet($context, $submission)){
        return;
    }
    // grede button 
    $url = new moodle_url($PAGE->url, array('student'=>$studentid, 'action'=>'showgradeform'));
    $edit = get_string('edit', 'voicerec');
    echo '<input type="button" value="' . $edit . '" class="voicerec_editgrade_button" action="' . $url . '" />';
    if ($submission->audio || $submission->comments || $submission->grade >= 0) {
        echo get_string('tablemodified', 'voicerec') . ' ' . userdate($submission->timestamp) ;
    }
}
/**
 * 教員のコメントを表示
 * 提出がない場合はfalseを返却
 * 
 * @param stdClass $context
 * @param stdClass $submission
 * @return boolean False if no submission
 */
function voicerec_print_teacher_commet($context, $submission){
    if (!$submission){
        echo '<div>' . get_string('nosubmission', 'voicerec') . '</div>';
        return false;
    }
    $gradestr = get_string('grade', 'voicerec');
    $comment = get_string('comment', 'voicerec');
    $grade = ($submission->grade >= 0)?$submission->grade: '-';
    echo "<div class='teacher_comment'>";
    echo "<div class='student_grade'><h4>$gradestr</h4><p>$grade</p></div>";
    echo "<div class='comment_text'><h4>$comment</h4><p>";
    if ($submission->comments) {
        $text = file_rewrite_pluginfile_urls($submission->comments, 'pluginfile.php', $context->id, 'mod_voicerec', 'message', $submission->id);
        echo format_text($text, $submission->commentsformat);
    }
    else {
        echo '-';
    }
    echo '</p></div>';// comment_text
    echo '</div>';// teachers_comment
    return true;
}

/**
 * ユーザ名とユーザのプロファイルへのリンクを表示する
 * 
 * @param int $studentid
 * @param int $courseid
 */
function voicerec_print_student_link($studentid, $courseid) {
    global $CFG, $DB;
    $student = $DB->get_record('user', array('id'=>$studentid));
    $fullname = fullname($student);
    if ($fullname == '') $fullname = '<Unnamed student>';
    echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$studentid.'&amp;course='.$courseid.'">'.$fullname.'</a>';
}
/**
 * ユーザの録音音声をテーブル表示
 * ユーザの指定がない場合はログインユーザ
 * print a student's voice audio tag table.
 * if $userid==0, print login user voice.
 * 
 * @param stdClass $context
 * @param stdClass $voicerec
 * @param int $userid 
 */
function voicerec_print_audiotags($context, $voicerec, $userid=0){
    global $DB, $CFG, $USER, $OUTPUT;
    if($userid==0){
        $userid = $USER->id;
    }
    if(!$voicerecaudios = $DB->get_records('voicerec_audios', array('voicerecid'=>$voicerec->id,'userid'=>$userid))){
        return;
    }
    echo $OUTPUT->box_start('generalbox', 'userrecedlist');
    $rectime = get_string('rectime', 'voicerec');
    $rectitle = get_string('rectitle', 'voicerec');
    $submittedvoice = get_string('submittedvoice', 'voicerec');
    $audiotagsupportneeded = get_string('audiotagsupportneeded','voicerec');
    echo "<table class='voicerec_submitted_voice'><tr><th>$rectitle</th><th>$rectime</th><th>$submittedvoice</th></tr>";
    foreach ($voicerecaudios as $voicerecaudio) {
        $filename = $voicerecaudio->name;
        $latesubmit='';
        if($voicerec->timedue && $voicerecaudio->timecreated > $voicerec->timedue){
            $latesubmit=' class="late_submit" ';
        }
        $time = userdate($voicerecaudio->timecreated);
        $relativepath = "/$context->id/mod_voicerec/audio/$voicerec->id/$filename";
        $url = $CFG->wwwroot . '/pluginfile.php?file=' . $relativepath;
        echo "<tr><td>{$voicerecaudio->title}</td><td {$latesubmit}>$time</td><td>
        <audio src='{$url}' controls><p>{audiotagsupportneeded}</p></audio></td></tr>";
    }
    echo "</table>";
    echo $OUTPUT->box_end();
}
/**
 * 
 * 
 */
 function voicerec_print_backto_list(){
    global $PAGE;
    $backtolist = get_string('backtolist', 'voicerec');
    echo '<input class="backto_list" type="button" onclick="location.href=\''.$PAGE->url.'\'" value="'.$backtolist.'">';
}

/**
 * print grade form
 * 
 * @param stdClass $context
 * @param stdClass $cm
 * @param stdClass $course
 * @param string $action
 * @param stdClass $voicerec
 */
function voicerec_print_grade_form($context, $cm, $course, $action, $voicerec){
    global $DB, $CFG, $PAGE, $USER, $OUTPUT;
    $studentid = optional_param('student', 0, PARAM_INT);
    $submission = $DB->get_record('voicerec_messages', array('voicerecid'=>$voicerec->id, 'userid'=>$studentid));
    if (!$submission) {
        print_error('The student submission does not exist!', 'voicerec', $PAGE->url);
    }
    // Prepare the grade form
    $editoroptions = array(
            'noclean'   => false,
            'maxfiles'  => EDITOR_UNLIMITED_FILES,
            'maxbytes'  => $course->maxbytes,
            'context'   => $context
    );
    
    $data = new stdClass();
    $data->id             = $cm->id;
    $data->action         = $action;
    $data->student        = $studentid;
    $data->maxduration    = $voicerec->maxduration;
    $data->sid            = $submission->id;
    $data->grade          = ($submission->grade < 0)? '' : $submission->grade;
    $data->url            = '';
    $data->comments       = $submission->comments;
    $data->commentsformat = $submission->commentsformat;
    $data->locked         = $submission->locked;
    $data = file_prepare_standard_editor($data, 'comments', $editoroptions, $context, 'mod_voicerec', 'message', $data->sid);
    // 評定フォームの生成
    $gradeform = new mod_voicerec_grade_form(null, array($context, $course, $voicerec, $submission, $data, $editoroptions, $voicerec->grade));
    if ($gradeform->is_cancelled()) {
        voicerec_print_teacher_commet($context, $submission); 
        voicerec_print_backto_list();
    }else if ($gradeform->is_submitted() && $gradeform->is_validated($data)) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        $data = $gradeform->get_data();
        $data = file_postupdate_standard_editor($data, 'comments', $editoroptions, $context, 'mod_voicerec', 'message', $submission->id);
        $submission->comments = $data->comments;
        $submission->commentsformat = $data->commentsformat;
        $grade = trim($data->grade);
        if ($grade && (int) $grade <= $voicerec->grade && (int) $grade >= 0) {
            $submission->grade = $data->grade;
        }
        else {
            $submission->grade = -1;
        }
        $submission->commentedby    = $USER->id;
        $submission->locked         = empty($data->locked)? 0 : 1;
        $submission->timestamp      = time();
    
        $DB->update_record('voicerec_messages', $submission);
        $event = \mod_voicerec\event\update_grade::create(array(
                'objectid' => $cm->instance,
                'context' => $context,
        ));
        $event->trigger();//        
        voicerec_update_grades($voicerec, $submission->userid);
        voicerec_print_teacher_commet($context, $submission);
        voicerec_print_backto_list();
    }else{
        // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
        // or on the first display of the form.        
        $PAGE->requires->strings_for_js(array('emptymessage', 'notavailable', 'submissionlocked', 'servererror', 'voicetitle'), 'voicerec');
        $gradeform->focus();
        $gradeform->display();
    }
}
/**
 * greade fome
 * 
 *
 */
class mod_voicerec_grade_form extends moodleform {
    public function definition() {
        global $PAGE;

        $mform = $this->_form;// moodleformの定形作法
        list($context, $course, $voicerec, $submission, $data, $editoroptions, $maxgrade) = $this->_customdata; //生成時のパラメータ引継ぎ
        // 評定する音声表示
        echo '<table align="center" cellspacing="0" cellpadding="0"><tr><td><b>' . get_string('gradingstudentrec', 'voicerec');
        voicerec_print_student_link($submission->userid, $course->id);
        echo '</b></td></tr></table>';
        voicerec_print_audiotags($context, $voicerec, $submission->userid);
        
        // Main content
        $gradetitle = get_string('grade', 'voicerec') . get_string('outof', 'voicerec') . $maxgrade;
        $mform->addElement('text', 'grade', $gradetitle);
        $mform->setType('grade', PARAM_INT);
        $grademsg = get_string('wronggrade', 'voicerec') . $maxgrade;
        $mform->addRule('grade', $grademsg, 'numeric', null, 'client');
        $mform->addElement('editor', 'comments_editor', get_string('yourmessage', 'voicerec'), null, $editoroptions);
        $mform->setType('comments_editor', PARAM_RAW);
        $mform->addElement('checkbox', 'locked', get_string('lockstudent', 'voicerec'));

        // Hidden params
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'student');
        $mform->setType('student', PARAM_INT);
        $mform->addElement('hidden', 'maxduration');
        $mform->setType('maxduration', PARAM_INT);

        // Buttons
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'voicerec_savegrade_button', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $this->set_data($data);
    }
    /**
     * エラーになったフォーム部品のname属性を$errorに設定すると、form->focus()を呼び出したときに自動で
     * エラー表示される。
     * 
     * {@inheritDoc}
     * @see moodleform::validation()
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if(0<= $data['grade'] && $data['grade'] <= 100){
        	return $errors;
        }else{
            $error['grade'] = get_string('gradeerror', 'voicerec');
            return $error;
        }
    }
}

