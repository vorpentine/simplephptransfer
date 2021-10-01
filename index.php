<?php

date_default_timezone_set('Europe/London');
$expires=86400*7;

session_start();
if ($_SESSION['TS']) {
} else {
	$_SESSION['TS']=date("U");
}

$file=$_GET['d'];

if ($file) {
	$continue=0;
	$file=preg_replace('/[^a-zA-Z0-9]/',"",$file);
	if (file_exists("/var/www/attachments/$file.json")) {
		$details=file_get_contents("/var/www/attachments/$file.json");
		$details=json_decode($details,true);
		//print_r($details);
		//echo $file;
		if ($_SESSION['TS']==$_GET['ts']) {
			header("Content-disposition: attachment; filename=".$details['filename']);
			header("Content-type: ".$details['type']."/download");
			readfile("/var/www/attachments/$file");
		} else {
			$download=$file;
			$continue=1;
		}
	} else {
		die("Nothing here, transfers delete after 1 week");
	}
	
	//echo "Delete old files";
	$files=glob("/var/www/attachments/*");
	foreach($files as $file) {
		if (date("U")-filemtime($file) > $expires) {
			unlink($file);
		}
	}
	if (!$continue) die();
}

$filename=$_GET['n'];
$filename=preg_replace('/[^A-Za-z0-9\.\-_]/',"-",$filename);

function process_file($tmpfile,$filename) {
	global $res;
	$details=[];
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$type=finfo_file($finfo,"/tmp/$tmpfile");
	finfo_close($finfo);
	
	$details['type']=$type;
	$details['filename']=$filename;
	$details['ts']=date("U");
	
	$savename=md5($tmpfile.$filename);	
	mkdir("/var/www/attachments",755,true);
	$myfile="/var/www/attachments/$savename";
	rename("/tmp/$tmpfile",$myfile);
	file_put_contents("/var/www/attachments/$savename.json",json_encode($details));
	
	//print_r($_SERVER);
	
	echo (stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."?d=$savename<br><a target=_blank href=\"?d=$savename\">$filename</a>";
	echo "<button style=\"margin-left: 15px;\" class=\"btn btn-light\" onmouseup=\"
		copyTextToClipboard('".(stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."?d=$savename');
	\">&#10697; copy to clipboard</button>";
	echo "<br><br>";
}

if ($filename) {
	$pathinfo=pathinfo($filename);
	$ts=$pathinfo['filename']."_".date("YmdHi").".".$pathinfo['extension'];
	$inputHandler = fopen('php://input', "r");
	$fileHandler = fopen("/tmp/$ts", "w+");
	while(true) {
		$buffer = fgets($inputHandler, 4096);
		if (strlen($buffer) == 0) {
			fclose($inputHandler);
			fclose($fileHandler);
		       break;
		}
		fwrite($fileHandler, $buffer);
	}
	process_file($ts,$filename); 
	die();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>SPT</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

		<meta name="apple-mobile-web-app-capable" content="yes">
		<link rel="apple-touch-icon" sizes="114x114" href="iconified/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-icon" sizes="120x120" href="iconified/apple-touch-icon-120x120.png" />
		<link rel="apple-touch-icon" sizes="144x144" href="iconified/apple-touch-icon-144x144.png" />
		<link rel="apple-touch-icon" sizes="152x152" href="iconified/apple-touch-icon-152x152.png" />
		<link rel="apple-touch-icon" sizes="52x52" href="iconified/apple-touch-icon-52x52.png" />
		<link rel="apple-touch-icon" sizes="57x57" href="iconified/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon" sizes="72x72" href="iconified/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon" sizes="76x76" href="iconified/apple-touch-icon-76x76.png" />
		<link rel="shortcut icon" href="iconified/favicon.ico" type="image/x-icon">
		<link rel="icon" href="iconified/favicon.ico" type="image/x-icon">	
		
		<link rel="manifest" href="manifest.json">
		<meta id=myviewport name="viewport" content="width=350, user-scalable=no" /><!-- initial-scale=1.0, maximum-scale=1.0, -->
		<script>
			function fallbackCopyTextToClipboard(text) {
				var textArea = document.createElement("textarea");
				textArea.value = text;

				// Avoid scrolling to bottom
				textArea.style.top = "0";
				textArea.style.left = "0";
				textArea.style.position = "fixed";

				document.body.appendChild(textArea);
				textArea.focus();
				textArea.select();

				try {
					var successful = document.execCommand('copy');
					var msg = successful ? 'successful' : 'unsuccessful';
					console.log('Fallback: Copying text command was ' + msg);
				} catch (err) {
					console.error('Fallback: Oops, unable to copy', err);
				}

				document.body.removeChild(textArea);
			}
			
			function copyTextToClipboard(text) {
				if (!navigator.clipboard) {
					fallbackCopyTextToClipboard(text);
					return;
				}
				navigator.clipboard.writeText(text).then(function() {
						console.log('Async: Copying to clipboard was successful!');
					}, function(err) {
					console.error('Async: Could not copy text: ', err);
				});
			}
			
			var busy=0;

			function handleFileSelect(evt) {
				var files = evt.target.files; 
				for (var i = 0, f; f = files[i]; i++) {
					upload(i);
				}
			}

			function upload(fileIndex) {
				if (busy) {
					setTimeout("upload('"+fileIndex+"');",500);
				} else {
					file = document.getElementById('upload').files[fileIndex];
					var reader = new FileReader();
					reader.readAsBinaryString(file); // alternatively you can use readAsDataURL
					reader.onloadend  = function(evt) {
						xhr = new XMLHttpRequest();
						xhr.open("POST", '?n='+file.name, true);
						XMLHttpRequest.prototype.mySendAsBinary = function(text) {
							var data = new ArrayBuffer(text.length);
								var ui8a = new Uint8Array(data, 0);
							for (var i = 0; i < text.length; i++) ui8a[i] = (text.charCodeAt(i) & 0xff);
							if(typeof window.Blob == "function") {
								var blob = new Blob([data]);
							} else {
								var bb = new (window.MozBlobBuilder || window.WebKitBlobBuilder || window.BlobBuilder)();
								bb.append(data);
								var blob = bb.getBlob();
							}
							this.send(blob);
						}
				
						var eventSource = xhr.upload || xhr;
						eventSource.addEventListener("progress", function(e) {
							// get percentage of how much of the current file has been sent
							var position = e.position || e.loaded;
							var total = e.totalSize || e.total;
							var percentage = Math.round((position/total)*100);
							// here you should write your own code how you wish to proces this
							document.getElementById('progress').innerHTML=percentage+'%';
						});
				
						// state change observer - we need to know when and if the file was successfully uploaded
						xhr.onreadystatechange = function() {
							if(xhr.readyState == 4) {
								//alert(xhr.responseText);
								if(xhr.status == 200) {
									$('#attach').append(xhr.responseText);
									busy=0;
								} else {
									busy=0;
									// process error
								}
							}
						};
					// start sending
					xhr.mySendAsBinary(evt.target.result);
			};}} //WTF BRACE?

			var floatreturn='';		
		</script>
	</head>
	<body class="text-center">
		<div class="form-signin" style="width: 80%;margin: auto;margin-top: 150px;">
			<div style="
				background-color: #e36eff;
				color: white;
				font-weight: bold;
				font-size: 300%;
				width: fit-content;
				display: inline-block;
				padding: 15px;
				border-radius: 15px;
				margin-bottom: 15px;
			">SPT</div>
			<h1 class="h3 mb-3 font-weight-normal">Simple Transfer</h1>
			<div id=attach></div>
<?php
	if ($file) {
		echo "<button class=\"btn btn-success\" onmouseup=\"window.location='?d=$download&ts=".$_SESSION['TS']."';\">Download ".$details['filename']."</button><br><br>";
		echo "Expires in ".round((($details['ts']+$expires)-date("U"))/(60*60),2)." hours";
	} else {
		echo "<button class=\"btn btn-success\" onmouseup=\"$('#upload').click();\">Send File</button><br><br>Transfers expire in 1 week";
	}
?>			
			<br><span id=progress></span><br>
			<input style="display: none;" type="file" id="upload" onchange="handleFileSelect(event);">
			
		</div>
	</body>
</html>
