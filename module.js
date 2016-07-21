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
	    			alert(err.name ? err.name : err);
	    		});
	    	}catch(e){
				console.log(e);
				alert(M.str.voicerec.changebrowser);
	    	}
	    }else{
			$('#voicerec_rec').attr('disabled','disabled');
			$('#voicerec_stop').attr('disabled','disabled');
			$('#voicerec_upload').attr('disabled','disabled');
	    } 
	});
	/**
	 * 録音開始
	 * 
	 * chromeはondataavailableが小刻みに発生する。音声データはe.dataを結合したもの。（実験から）
	 * FirefoxはmediaRecorder.stop()呼出し後、一度だけ発生。
	 * また、chromeはtypeが空。バイナリエディタで見るとメディア・タイプはaudio/webm
	 * Firefoxはaudio/ogg
	 * 
	 */
	$('#voicerec_rec').click(function(){
		try{
			mediaRecorder = new MediaRecorder(mediaStream);
			mediaRecorder.ondataavailable = function(e) {
				buffArray .push(e.data);
				if('' != e.data.type){
					dataType = e.data.type;
					console.log("e.data.type is null" );
				}
				//var extension = e.data.type.substr(e.data.type.indexOf('/') + 1); // "audio/ogg"->"ogg"
				console.log("e.data.size = " + e.data.size);
				console.log('buffArray.length :'+ buffArray .length);
			}
			mediaRecorder.start();
			limitTimerID = limit_timer();
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
	 * 停止ボタン　2016.07.20時点での注意事項
	 * 
	 * FirefoxではmediaRecorder.stop()呼出し後にondataavailableイベントが発生する。
	 * そのため、停止ボタン処理の中でBlobの生成は不可。
	 * 停止ボタン処理の中はまだデータを受信していないので注意。
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
	 * Edge,IEはOggの再生未サポート。
	 * BlobからFileに変換し、File.name属性に拡張子付きでアップロードしたいがBlobからFileへの変換方法不明。
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
				//alert(text); debug時に開ける。
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


