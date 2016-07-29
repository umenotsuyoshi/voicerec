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
M.mod_voicerec = {};

M.mod_voicerec.init = function(yui, maxduration) {
	navigator.getUserMedia = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
	var mediaStream = null; // http://www.w3.org/TR/mediacapture-streams/#mediastream
	var mediaRecorder = null;// http://www.w3.org/TR/mediastream-recording/
	buffArray  = new Array();
	var limitTimerID = 0;
	var dataType = 'audio/webm';
	/**
	 * ユーザの録音許可
	 */
	$('#voicerec_allow').bind('click', function () {
	    if ($(this).prop('checked')) {
	    	try{
	    		navigator.getUserMedia({
	    			video : false,
	    			audio : true
	    		}, function(stream) {
	    			$('#voicerec_rec').removeAttr('disabled');
	    			mediaStream = stream;
	    		}, function(err) {
					console.log(e);
	    			alert(err.name ? err.name : err);
	    		});
	    	}catch(e){
				console.log(e);
				browser_error(e);
	    	}
	    }else{
			$('#voicerec_rec').attr('disabled','disabled');
			$('#voicerec_stop').attr('disabled','disabled');
			$('#voicerec_upload').attr('disabled','disabled');
	    } 
	});
	/**
	 * getUserMediaはChorm, Operaの場合は、httpsでなければ動作しない。
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
	 * MediaRecorderの第２引数でmimeType指定できるが、audioはChromeはaudio/webmのみ、
	 * Firefoxはaudio/oggのみ。
	 * ChromeはコンストラクタでmimeType指定してもondataavailableのe.data.typeが空。
	 * 現状、指定しても意味なし。
	 * 
	 * ビデオの場合だがコーデック指定も可能
	 * ex. options = {mimeType: 'video/webm, codecs=vp9'};
	 */
	$('#voicerec_rec').click(function(){
		try{
			mediaRecorder = new MediaRecorder(mediaStream);
			/**
			 * chromeはondataavailableが小刻みに発生する。音声データはe.dataを結合したもの。
			 * timesliceを指定しない場合、FirefoxはmediaRecorder.stop()呼出し後一度だけ発生。
			 * chromeはtypeが空。バイナリエディタで見るとメディア・タイプはaudio/webm
			 * Firefoxはaudio/ogg
			 */
			mediaRecorder.ondataavailable = function(e) {
				buffArray .push(e.data);
				if('' != e.data.type){
					dataType = e.data.type;
				}
				//var extension = e.data.type.substr(e.data.type.indexOf('/') + 1); // "audio/ogg"->"ogg"
				console.log("e.data.size = " + e.data.size);
				console.log('buffArray.length :'+ buffArray .length);
			}
			var timeslice = 1000; // The number of milliseconds of data to return in a single Blob.
			mediaRecorder.start(timeslice);
			limitTimerID = limit_timer(timeslice);
			/* */
			$('#voicerec_rec').attr('disabled','disabled');
			$('#voicerec_stop').removeAttr('disabled');
			$('#voicerec_upload').attr('disabled','disabled');
		}catch(e){
			console.log(e);
			alert(M.str.voicerec.changebrowser);
		}
	});
	/**
	 * 停止ボタン　
	 * start時にtimeslice指定してUTの挙動揃える。
	 * mediaRecorder.start(timeslice);
	 * 
	 */
	$('#voicerec_stop').click(function(){
		stop_recording();
	});
	stop_recording = function(){
		console.log("stop mediaRecorder");
		mediaRecorder.stop();
		clearTimeout(limitTimerID);
		$('#voicerec_rec').attr('disabled','disabled');
		$('#voicerec_stop').attr('disabled','disabled');
		$('#voicerec_upload').removeAttr('disabled');
	}
	/**
	 * アップロードボタン
	 * 
	 * Blobのままアップロードするのでname属性がない。typeにはaudio/oggがブラウザにより設定されている。
	 * FirefoxではOggフォーマットで録音され、OggはFirefox、Chrome、Operaでは再生可能。
	 * Edge,IEはOgg、webmの再生未サポート。Edgeでは録音だけでなく、再生も不可。
	 * （Blobはtype、size属性を持つ。FileはBlobを継承。name属性が追加）
	 */
	$("#voicerec_upload").bind("click", function(){
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
				alert(M.str.voicerec[text]);
			}
			console.log( text );
			location.reload();
			$('#voicerec_allow').prop('checked',false);
		});
		$('#voicerec_rec').attr('disabled','disabled');
		$('#voicerec_stop').attr('disabled','disabled');
		$('#voicerec_upload').attr('disabled','disabled');
	});
	/*
	 * ユーザがファイルを選択したらユーザアップロードボタンを有効にする。
	 * 
	 */
	$('#voicerecfile').on("change", function() {
        var file = this.files[0];
        if(file != null) {
            console.log(file.name); 
            $('#voicerec_user_upload').removeAttr('disabled');
        }
    });
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

} 


