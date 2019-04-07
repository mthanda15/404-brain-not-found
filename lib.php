?<?php
//read a json file
//$Json = file_get_contents('langConfig.json');

$langInfo = file_get_contents('langConfig.json');

$mysqlCommand =json_decode($langInfo)->compile->compile;
$output = shell_exec($mysqlCommand);
$myfile = fopen("output1.txt","w");
fwrite($myfile, $output);
var_dump($output);


 ?>
