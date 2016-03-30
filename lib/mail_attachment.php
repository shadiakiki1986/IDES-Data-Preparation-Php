<?php
// From http://stackoverflow.com/a/13459244

function mail_attachment($files, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
  if(!is_array($files)) throw new Exception("Please pass an array for files");
  $uid = md5(uniqid(time()));
  
  $header = "From: ".$from_name." <".$from_mail.">".PHP_EOL;
  $header .= "Reply-To: ".$replyto.PHP_EOL;
  $header .= "MIME-Version: 1.0".PHP_EOL;
  $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"".PHP_EOL.PHP_EOL;
  // split header from message
  // as in http://stackoverflow.com/a/30897790/4126114
  $msg = "This is a multi-part message in MIME format.".PHP_EOL;
  $msg .= "--".$uid.PHP_EOL;
  $msg .= "Content-type:text/html; charset=iso-8859-1".PHP_EOL;
  $msg .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;
  $msg .= $message.PHP_EOL.PHP_EOL;

  foreach ($files as $filename) { 

      $file = $filename; //$path.$filename;
      //$name = basename($file);
      $file_size = filesize($file);

      $handle = fopen($file, "r");
	if(!$handle) die("Failed to open file $file");
      $content = fread($handle, $file_size);
      fclose($handle);
      $content = chunk_split(base64_encode($content));

      $msg .= "--".$uid.PHP_EOL;
      $msg .= "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; name=\"".basename($filename)."\"".PHP_EOL; // use different content types here
      $msg .= "Content-Transfer-Encoding: base64".PHP_EOL;
      $msg .= "Content-Disposition: attachment; filename=\"".basename($filename)."\"".PHP_EOL.PHP_EOL;
      $msg .= $content.PHP_EOL.PHP_EOL;
  }

  $msg .= "--".$uid."--";
  return mail($mailto, $subject, $msg, $header);
}
