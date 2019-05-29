<?php

/**
 * @file mark.php
 * Web service interface.
 */
require_once("config.php"); // Include Global Configuration
require_once("lib.php");    // Include Library Functions

/*
$inputJSON =<<<JSN
{"userid":"2","language":"12","cpu_limit":"0","mem_limit":"0","pe_ratio":"0","callback":"https:\\/\\/wits2.rklein.me\\/test\\/mod\\/assign\\/feedback\\/witsoj\\/insert_grade.php?id=93","testcase":{"url":"https:\\/\\/wits2.rklein.me\\/test\\/pluginfile.php\\/2803\\/assignfeedback_witsoj\\/oj_testcases\\/0\\/Lab1.zip","contenthash":"e19a18ceb11cb3fe28fbb8a230a90a4452d7b572","pathnamehash":"6c40566f5163045b60a9d3480da09973501bc53a"},"source":{"content":"VGVzdGluZzEyMwo=","ext":"txt"},"witsoj_token":"1e6947ac7fb3a9529a9726eb692c8cc5","markerid":"1"}
JSN;
*/
$inputJSON = file_get_contents('php://input');  // Get input from the client
$input = json_decode($inputJSON, TRUE);        // Decode the JSON object

$markerid = $input["markerid"];
$auth = $input["witsoj_token"];
$userid = $input["userid"];
$firstname = $input["firstname"];
$lastname = $input["lastname"];
$language = $input["language"];
$cpu_limit = $input["cpu_limit"];
$mem_limit = $input["mem_limit"];
$pe_ratio = $input["pe_ratio"];
error_log("PE_RATIO: " . $pe_ratio);
$callback = $input["callback"]; // Post back to this address after marking
$testcase = $input["testcase"]; // url, contenthash, pathnamehash
$source = base64_decode($input["source"]["content"]);     // Decode the Base64
settings::$temp .= "/$markerid";
// Start buffering output
ob_start();

if($auth != settings::$auth_token['witsoj_token']){
	error_log('{"status" : "Bad auth"}');
	die('{"status" : "Bad auth"}');
}

// Get the test case
//   If they are already cached locally, use that
//   If not, download and extract the file from moodle
//       This will die("{status : feedback}") on error.
$tests = testcases($testcase);

//print(json_encode($tests));

$tests = testcases($testcase);
$test_count = count($tests["yml"]["test_cases"]);
$output = array("status" => "0", "test_count" => $test_count);

print(json_encode($output));

// Send all the output back to moodle
$size = ob_get_length();
header("Content-Encoding: none");
header("Content-Length: {$size}");
header("Connection: close");
ob_end_flush();
ob_flush();
flush();

// TODO
// Now continue with the marking work.
error_log("Closed moodle connection. Starting to mark....");

/*$feedback = array();
foreach($tests["yml"]["test_cases"] as $tc){
	if(isset($tc["feedback"])){
		$feedback[] = $tc["feedback"];	
	}else{
		$feedback[] = "";
	}
}
$oj_feedback = json_encode($feedback);*/

$marker_data = mark($source, $tests, $language, $userid, $firstname, $lastname, $markerid, $cpu_limit, $mem_limit, floatval($pe_ratio));
$status = $marker_data["status"];
$oj_feedback = $marker_data["oj_feedback"];
$grade = $marker_data["grade"];
$outputs = $marker_data["outputs"];
//die();
error_log("Finished Marking... Sending grade to moodle..." . $grade);
//sleep(10);//+intval($markerid));
return_grade($callback, $markerid, $userid, $grade, $status, json_encode($outputs), $oj_feedback);
error_log("Grade sent.");

?>
