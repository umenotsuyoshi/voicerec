// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

M.mod_voicerec = {};

/**
 * The JavaScript used in the voicerec activity module
 *
 * @author     
 * @author     
 * @package    mod
 * @subpackage voicerec
 * @copyright  
 * @license    
 * @version    
 */
M.mod_voicerec.init = function(yui, maxduration) {
	navigator.getUserMedia = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
	var mediaStream = null; // http://www.w3.org/TR/mediacapture-streams/#mediastream
	var mediaRecorder = null;// http://www.w3.org/TR/mediastream-recording/
	var buffArray  = new Array();
	var limitTimerID = 0;
	var restart_maxduration = maxduration;
	/*
	 * 現在（2016.08）Chromeは、mediaRecorder.ondataavailable(e)のe.data.typeを空で呼び出す。
	 * デフォルト値をChrome(audio/webm)にしておいて、Firefox（audio/ogg)で上書きで回避。
	 */
	var dataType = 'audio/webm';
	var voicerec_recording_audio = document.getElementById('voicerec_recording_audio');
	var voicerec_rec = document.getElementById('voicerec_rec');
	var voicerec_stop = document.getElementById('voicerec_stop');
	var voicerec_check = document.getElementById('voicerec_check');
	var voicerec_recording_audio = document.getElementById('voicerec_recording_audio');
	var voicerec_upload = document.getElementById('voicerec_upload');
	var rectime_timer = document.getElementById('rectime_timer');
	/*
	 * 
	 */

	/**
	 * ユーザの録音許可
	 * ユーザの許可を得る必要があるのは、Firefoxだけ。
	 * Chromeでユーザの許可を得るためのダイアログが表示されなくなり、すぐに録音が開始されるようになっている。
	 * 許可を得ると録音開始する。
	 * 
	 */
	voicerec_rec.addEventListener('click', function () {
    	try{
    		navigator.getUserMedia({
    			video : false,
    			audio : true
    		}, function(stream) {
    			mediaStream = stream;
    			rec_start();
    		}, function(err) {
				console.log(e);
    			alert(err.name ? err.name : err);
    		});
    	}catch(e){
			console.log(e);
			browser_error(e);
    	}
	});
	/**
	 * getUserMediaはChrome, Operaの場合は、httpsでなければ動作しなくなった。
	 * Ver.0．X時は警告を出しておく。
	 * OperaのレンダリングエンジンChromeと同じ。
	 * UAにOperaの文字ないので注意。（2016.07.27）
	 * ex."mozilla/5.0 (windows nt 10.0; wow64) applewebkit/537.36 (khtml, like gecko) chrome/51.0.2704.106 safari/537.36 opr/38.0.2220.41" 
	 */
	browser_error = function(e){
		var href = window.location.href ;
		var ua = window.navigator.userAgent.toLowerCase();
		if(href.indexOf('https://')<0 && ua.indexOf('chrome')>0){
			alert(M.str.voicerec.changeserver);
		}else{
			alert(M.str.voicerec.changebrowser);
		}
	}
		
	/**
	 * 録音開始
	 * 
	 * MediaRecorder(stream	MediaStream,options	MediaRecorderOptions)
	 * MediaRecorderの第２引数でmimeType指定できる。
	 * しかし、audioについては、Chromeはaudio/webmのみ、Firefoxはaudio/oggのみサポートの様。
	 * MediaRecorder.isTypeSupported()
	 * ChromeはコンストラクタでmimeType指定してもondataavailableのe.data.typeが空。
	 * サポートするmimeTypeを返すAPIはなし。（W3C Working Draft 20 June 2016）
	 * 現在は、第2パラメータを指定しても意味なし。
	 * ビデオの場合だがコーデック指定も可能
	 * ex. options = {mimeType: 'video/webm, codecs=vp9'};
	 */
	function rec_start() {
		maxduration = restart_maxduration;
		buffArray  = new Array();
		try{
			mediaRecorder = new MediaRecorder(mediaStream);
			/**
			 * chromeはondataavailableが小刻みに発生する。音声データはe.dataを結合したもの。
			 * timesliceを指定しない場合、FirefoxはmediaRecorder.stop()呼出し後一度だけ発生。
			 * chromeはtypeが空。バイナリエディタで見るとメディア・タイプはaudio/webm
			 * Firefoxはaudio/ogg。
			 * 
			 */
			mediaRecorder.ondataavailable = function(e) {
				buffArray.push(e.data);
				if('' != e.data.type){
					dataType = e.data.type;　// Firefoxだけ指定してくる
				}
				//var extension = e.data.type.substr(e.data.type.indexOf('/') + 1); // "audio/ogg"->"ogg"
				console.log("e.data.size = " + e.data.size);
				console.log('buffArray.length :'+ buffArray .length);
			}
			/**
			 * mediaRecorder.startの直後停止ボタンを有効にすると録音、停止と連続してボタンされ
			 * startする前に停止処理が走行する。タイマーが止まらない。
			 */
			mediaRecorder.onstart = function(e) {
				voicerec_stop.removeAttribute('disabled');
			}
			var timeslice = 1000; // The number of milliseconds of data to return in a single Blob.
			mediaRecorder.start(timeslice);
			limitTimerID = limit_timer();
			voicerec_rec.setAttribute('disabled','disabled');
			voicerec_check.setAttribute('disabled','disabled');
			voicerec_recording_audio.src='';
			voicerec_upload.setAttribute('disabled','disabled');

			/* */
		}catch(e){
			console.log(e);
			clearTimeout(limitTimerID);
			alert(M.str.voicerec.changebrowser);
		}
	}
	/**
	 * 停止ボタン　
	 * start時にtimeslice指定してUAの挙動揃える。
	 * mediaRecorder.start(timeslice);
	 * 
	 */
	voicerec_stop.addEventListener('click', function () {
		stop_recording();
	});
	/**
	 * 録音停止
	 */
	stop_recording = function(){
		clearTimeout(limitTimerID);
		mediaRecorder.stop();
		voicerec_rec.removeAttribute('disabled');
		voicerec_stop.setAttribute('disabled','disabled');
		voicerec_check.removeAttribute('disabled');
		voicerec_upload.removeAttribute('disabled');
		console.log("stop mediaRecorder");
	}
	/**
	 * 確認ボタン
	 */
	voicerec_check.addEventListener('click', function () {
		blob = new Blob(buffArray , { type : dataType }); // blobオブジェクトは.typeと.sizeのみ
		if(blob.size==0){
			alert(M.str.voicerec.changebrowser);
			return false;
		}
		var blobUrl = window.URL.createObjectURL(blob);
		voicerec_recording_audio.src= blobUrl;
		voicerec_recording_audio.play();
	});
	/**
	 * アップロードボタン
	 * 
	 * Blobのままアップロードするのでname属性がない。typeにはaudio/ogg（Firefoxの場合）
	 * がブラウザにより設定されている。
	 * FirefoxではOggフォーマットで録音され、OggはFirefox、Chrome、Operaでは再生可能。
	 * Edge,IEはOgg、webmの再生未サポート。Edgeでは録音だけでなく、再生も不可。
	 * （Blobはtype、size属性を持つ。FileはBlobを継承。name属性が追加）
	 * 
	 * https://developer.mozilla.org/ja/docs/Web/API/FormData
	 * のブラウザ実装状況の部分要確認。元は空文字が送信されていた。
	 * 今はFormDataにblobをappendした場合、"blob"がファイル名として送信される。
	 * 
	 *  Formをsubmitするとファイルシステムにアクセスしエラーとなる。
	 *  実際にファイルシステム上からファイルをアップロードしようとしている模様。
	 *  BlobからFileをnewしようとするとTypeError: Value can't be converted to a dictionary.が発生
	 *  
	 */
	voicerec_upload.addEventListener('click', function () {
		if( $('#voicerec_rec_comment').hasClass("not_changed")== true)$('#voicerec_rec_comment').val("");
		blob = new Blob(buffArray , { type : dataType }); // blobオブジェクトは.typeと.sizeのみ
		if(blob.size==0){
			alert(M.str.voicerec.changebrowser);
			return false;
		}
		var formdata = new FormData($('#voice_send').get(0));
        formdata.append( "status", $("#status").val() );
        formdata.append( "voicerec_upload_file", blob );
		var postData = {
				type : "POST",
				dataType : "text",
				data : formdata,
				processData : false,
				contentType : false
		};
		$.ajax( "./saveaudio.php", postData ).done(function( text ){
			if(!M.str.voicerec[text]){
				//alert(text); //debug時に開ける。
			}else{
				//alert(text); //debug時に開ける。
				alert(M.str.voicerec[text]);
			}
			console.log( text );
			location.reload();
		});
		voicerec_rec.removeAttribute('disabled');
		voicerec_stop.setAttribute('disabled','disabled');
		voicerec_check.setAttribute('disabled','disabled');
		voicerec_recording_audio.src='';
		voicerec_upload.setAttribute('disabled','disabled');
	});
	/**
	 * 録音制限時間タイマー
     */
	limit_timer = function(){
		maxduration--;
		$('#rectime_timer').text(maxduration);
		if(maxduration <= 0){
			stop_recording();
			alert(M.str.voicerec.timeoutmessage);
			return;
		}
		if(maxduration <= 10){
			$('#rectime_timer').css('color','red!important');
		}
		console.log(maxduration);
		limitTimerID = setTimeout(limit_timer, 1000);
	}

	
	
	/* 以下mod voicerec固有部分 */
	
	/*
	 * 録音コメント
	 */
	$('#voicerec_rec_comment')
	.focusin(function() {
		$('#voicerec_rec_comment').val("");
		$('#voicerec_rec_comment').removeClass("not_changed");
	})
	.focusout(function(){
		if(M.str.voicerec.inputrectitle == $('#voicerec_rec_comment').val() ||
			$('#voicerec_rec_comment').val() == ''){
			$('#voicerec_rec_comment').addClass("not_changed");
			$('#voicerec_rec_comment').val(M.str.voicerec.inputrectitle);
		}
	})
	
	/*
	 * 編集画面へ遷移
	 */
	$('.voicerec_editgrade_button').on("click",function(){
		location = $(this).attr('action');
	});
} 


