<?php
session_start();
$corrInfo = Array();
$flName = "correctionInfoTemp.txt";

if (! 0 == filesize($flName)) {
  $file = fopen($flName,"r");
  while(! feof($file))
    {
    array_push($corrInfo, fgetcsv($file));
  };
  //$curInfo = $corrInfo[0];
  $curInfo = array_splice($corrInfo, 0, 1)[0];
  //$curInfo = array_pop($corrInfo);
  //print_r($curInfo);
  //print_r($corrInfo);
  fclose($file);

  $fp = fopen($flName, "w+");
  foreach($corrInfo as $out) {
    if ($out[2] != "") {
      $str = $out[0].','.$out[1].',"'.$out[2].'"'."\n";
      fwrite($fp,$str);
    }
  }
  fclose($fp);

  $_SESSION['pid'] = $curInfo[0];
  $_SESSION['seed'] = $curInfo[1];
  $_SESSION['condition'] = $curInfo[2];
  header('Location: http://localhost/~chrismellinger/ModelingS4/WIT/reconstructTask.php');
}
?>
