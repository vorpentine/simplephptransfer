<?php

function process_file($tmpfile,$filename) {
	global $_SERVER;
	$details=[];
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$type=finfo_file($finfo,$tmpfile);
	finfo_close($finfo);
	
	$details['type']=$type;
	$details['filename']=$filename;
	$details['ts']=date("U");
	
	$savename=md5($tmpfile.$filename);	
	mkdir("/var/www/attachments",755,true);
	$myfile="/var/www/attachments/$savename";
	rename($tmpfile,$myfile);
	file_put_contents("/var/www/attachments/$savename.json",json_encode($details));
	
	//print_r($_SERVER);
	
	$html=(stripos($_SERVER['HTTP_ORIGIN'],'https') === 0 ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."?d=$savename<br><a target=_blank href=\"?d=$savename\">$filename</a>";
	$html.="<button style=\"margin-left: 15px;\" class=\"btn btn-light\" onmouseup=\"
		copyTextToClipboard('".(stripos($_SERVER['HTTP_ORIGIN'],'https') === 0 ? 'https://' : 'http://').$_SERVER['HTTP_HOST']."?d=$savename');
	\">&#10697; copy to clipboard</button>";
	$html.="<br><br>";
	return $html;
}

// (A) FUNCTION TO FORMULATE SERVER RESPONSE
function verbose($ok=1,$info=""){
  // THROW A 400 ERROR ON FAILURE
  if ($ok==0) { http_response_code(400); }
  die(json_encode(["ok"=>$ok, "info"=>$info]));
}

// (B) INVALID UPLOAD
if (empty($_FILES) || $_FILES['file']['error']) {
  verbose(0, "Failed to move uploaded file.");
}

// (C) UPLOAD DESTINATION
// ! CHANGE FOLDER IF REQUIRED !
$filePath = "/tmp";
if (!file_exists($filePath)) { 
  if (!mkdir($filePath, 0777, true)) {
    verbose(0, "Failed to create $filePath");
  }
}

$realname=isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
//$fileName = microtime(true);//isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];
$fileName=preg_replace('/[^A-Za-z0-9\.]/',"-",$fileName);
$filePath = "/tmp/$fileName";//$filePath . DIRECTORY_SEPARATOR . $fileName;

// (D) DEAL WITH CHUNKS
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
$out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
if ($out) {
  $in = @fopen($_FILES['file']['tmp_name'], "rb");
  if ($in) {
    while ($buff = fread($in, 4096)) { fwrite($out, $buff); }
  } else {
    verbose(0, "Failed to open input stream");
  }
  @fclose($in);
  @fclose($out);
  @unlink($_FILES['file']['tmp_name']);
} else {
  verbose(0, "Failed to open output stream");
}

// (E) CHECK IF FILE HAS BEEN UPLOADED
if (!$chunks || $chunk == $chunks - 1) {
  rename("{$filePath}.part", $filePath);
} 

$html=process_file($filePath,$realname);
echo $html;
//verbose(1, "Upload OK");
