<?php

/**
 * @file mark.php
 * Web service interface.
 */
require_once("config.php"); // Include Global Configuration
require_once("lib.php");    // Include Library Functions

$inputJSON = file_get_contents('php://input');  // Get input from the client
$input = json_decode($inputJSON, TRUE);        // Decode the JSON object

error_log($inputJSON);
die("MARKER DEBUG");


$source = base64_decode($input['source']);     // Decode the Base64



// Mark the submission
// This returns the stderr, stdout and result
$input['input'] = str_replace("\r\n","\n",$input['input']);
$input['output'] = str_replace("\r\n","\n",$input['output']);
$val = mark($input['language'], $source,
        $input['input'], $input['output'], $input["timelimit"]);

// Return the resulting json object to the client
echo json_encode($val);
?>
