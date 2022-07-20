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
			$details['filename']=preg_replace('/[^A-Za-z0-9\.\-]/',"_",$details['filename']);
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
		echo "Transfers expire in 1 week";
?>
		<!-- CHECK https://cdnjs.com/libraries/plupload FOR LATEST VERSION -->
		<!-- OFFICIAL WEBSITE: https://www.plupload.com/ -->
		<script src="plupload.full.min.js"></script>
		<script>
			window.addEventListener("load", function () {
				var uploader = new plupload.Uploader({
					runtimes: 'html5,html4',
					browse_button: 'pickfiles',
					url: '2b-chunk.php',
					chunk_size: '50mb',
					/* OPTIONAL
					filters: {
					max_file_size: '150mb',
					mime_types: [{title: "Image files", extensions: "jpg,gif,png"}]
					},
					*/
					init: {
						PostInit: function () {
							document.getElementById('filelist').innerHTML = '';
						},
						FilesAdded: function (up, files) {
							plupload.each(files, function (file) {
								document.getElementById('filelist').innerHTML += `<div id="${file.id}">${file.name} (${plupload.formatSize(file.size)}) <strong></strong></div>`;
							});
							uploader.start();
						},
						FileUploaded: function (up, file, reponse) {
							//reponse=JSON.parse(reponse);
							//console.log(reponse);
							$('#attach').append(reponse['response']);
						},
						UploadProgress: function (up, file) {
							document.querySelector(`#${file.id} strong`).innerHTML = `<span>${file.percent}%</span>`;
						},
						Error: function (up, err) {
							console.log(err);
						}
					}
				});
				uploader.init();
			});
		</script>

		<!-- UPLOAD FORM -->
		<div id="container">
		<button class="btn btn-success" id="pickfiles">Send files</button>
		</div>

		<!-- UPLOAD FILE LIST -->
		<div id="filelist">Your browser doesn't support HTML5 upload.</div>

<?php		
	}	
?>			
		</div>
	</body>
</html>
