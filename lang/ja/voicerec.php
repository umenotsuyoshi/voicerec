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
 *
 * @package    mod
 * @subpackage rectool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['allstudents'] = 'すべての受講生';
$string['availabledate'] = '開始日時';
$string['backtolist'] = '受講生リストに戻る';
$string['cannnotplayonyourbrowser'] = '音声を再生するには、audioタグをサポートしたブラウザが必要です。';
$string['cannotsumit'] = '提出できません。学生権限で試してみてください。';
$string['changebrowser'] = 'このブラウザでは録音できませんでした。Chrome、Firefox、Operaなどの最新バージョンで試してみてください。';
$string['changeserver'] = 'Chrome、Operaはセキュアなサーバ（https）からしか録音できません。管理者に相談してください。';
$string['checkmikvolume'] = 'マイクの音量を確認してください。';
$string['checkrecording'] = '確認';
$string['comment'] = '受講生へのメッセージ';
$string['deletealertmessage'] = '録音を選択してください';
$string['deleterecordings'] = '選択した複数の録音を消去する';
$string['duedate'] = '提出日';
$string['edit'] = '評定する';
$string['emptymessage'] = '録音はまだありません';
$string['eventvoicesave'] = '録音音声が提出されました。';
$string['eventvoicesaveerror'] = '録音音声保存エラー。';
$string['feedbackfor'] = 'コメントを送る相手';
$string['grade'] = '得点';
$string['gradeerror'] = '0点から100点で採点してください。';
$string['graderecdvoice'] = '録音音声を採点しました。';
$string['gradedstudents'] = '録音が採点された受講生';
$string['gradingstudentrec'] = '評定中の学生';
$string['inonepage'] = '受講生を1ページに表示する';
$string['inputrectitle'] = 'ここに録音タイトルを入力してください。';
$string['lockstudent'] = 'この受講生をロックする';
$string['maxduration'] = '録音時間(秒)';
$string['maxdurationdefaultsetinfo'] = 'ここに設定した値がこのサイトでの録音時間(秒)の上限のデフォルトになります。';
$string['maxdurationerror'] = '録音時間(秒)異常。サイト上限は {$a} 秒に設定されています。';
$string['maxduration_help'] = '録音の最長時間(秒)(1-1200)';
$string['maxnumber'] = '録音ファイルの数の上限';
$string['maxnumberdefaultsetinfo'] = 'ここに設定した値がこのサイトでの選択できる録音ファイルの数の上限になります。';
$string['maxnumber_help'] = '各受講生による、異なる録音の最大数';
$string['modulename'] = '録音課題';
$string['modulenameplural'] = '録音課題';
$string['nosubmission'] = '提出された音声はありません';
$string['notavailable'] = '現在使用できません';
$string['notavailableyet'] = 'まだ使用できません';
$string['outof'] = '中';
$string['page'] = 'ページ';
$string['pastduedate'] = '提出日を過ぎています。';
$string['permission'] = '受講生は他の受講生の録音を聞くことができますか？';
$string['permitbrowserrec'] = 'このブラウザでの録音を許可する';
$string['pluginadministration'] = 'voicerec の管理';
$string['pluginname'] = '録音ツール';
$string['preventlate'] = '提出日以降の送信を阻止する';
$string['reachedupperlimit'] = '録音数の上限に達しました。これ以上の録音はできません。';
$string['reclabel'] = '録音';
$string['rectime'] = '録音日時';
$string['rectitle'] = '録音タイトル';
$string['remainingtime'] = '残り時間';
$string['remainingtimeunit'] = '秒';
$string['servererror'] = 'サーバーへの録音送信に問題があります。時間をおいてまた送信してください。';
$string['show'] = '表示する';
$string['showdeleteboxes'] = 'ここをクリックして消去したい録音を表示する';
$string['startpermitbrowserrec'] = '録音を許可して始めてください。';
$string['stoplabel'] = '停止';
$string['studentsnosubmissions'] = '録音を送信していない受講生';
$string['submittedrecordings'] = '提出済み録音リスト';
$string['submissionlabel'] = '提出';
$string['submissionlocked'] = 'ロックされているので、変更することができません';
$string['submittedcategory'] = '1つの録音を送信したすべての受講生';
$string['submittedcategoryone'] = '1つの録音を送信した受講生';
$string['submittedvoice'] = '提出音声';
$string['submittedvoicelocked'] = '提出済み音声はロックされています';
$string['tablemodified'] = '最終変更';
$string['timelimit'] = '制限時間';
$string['timeoutmessage'] = '時間切れです。提出してください。';
$string['thereare'] = '受講生の録音';
$string['thereareno'] = '録音はありません';
$string['ungradedstudents'] = '録音送信後、まだ採点が行われていません';
$string['uploadmanualy'] = 'ファイルをアップロードする';
$string['usemodernbrowser'] = 'Chrome、Firefox、Operaなどの最新版のブラウザで開いてください。';
$string['voicerecfieldset'] = '録音と内容を表示する';
$string['voicerecname'] = 'この練習問題の名称';
$string['voicerecname_help'] = 'このモジュールは受講生に対して録音問題を表示することができます。';
$string['voicerecording'] = 'あなたの録音';
$string['voicetitle'] = '録音の題名は何ですか？';
$string['with'] = '表示数';
$string['wronggrade'] = '数字を入力してください：0から';
$string['youcanuploadfromhere'] = '録音がうまくいかない場合は、以下から音声ファイルをアップロードしてください。';
$string['yourmessage'] = 'はい';
