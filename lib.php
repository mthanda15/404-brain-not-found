<?php

/**
 * @file lib.php
 * General library routines.
 */
// Include global configurations
require_once("config.php");



const output_max_length = 20000;
const result_correct = ASSIGNFEEDBACK_WITSOJ_STATUS_ACCEPTED;        		///< Correct Submission
const result_incorrect = ASSIGNFEEDBACK_WITSOJ_STATUS_INCORRECT;     		///< Incorrect Submission
const result_compile_error = ASSIGNFEEDBACK_WITSOJ_STATUS_COMPILEERROR;     	///< Compile Error
const result_presentation_error = ASSIGNFEEDBACK_WITSOJ_STATUS_PRESENTATIONERROR; ///< Presentation Error
const result_time_limit = ASSIGNFEEDBACK_WITSOJ_STATUS_TIMELIMIT;       	///< Exceeded Time Limit
const result_marker_error = ASSIGNFEEDBACK_WITSOJ_STATUS_MARKERERROR;	    	///< Marker Error
const result_mixed = ASSIGNFEEDBACK_WITSOJ_STATUS_MIXED;	    		///< Submission has been graded


/**
 * Recursively delete a directory
 * @param string $dir Directory to Delete
 * @return boolean Success/Failure
 */
function deleteDirectory($dir) {
	// If the folder/file doesn't exist return
	if (!file_exists($dir))
		return true;
	// If it isn't a directory, remove and return
	if (!is_dir($dir) || is_link($dir))
		return unlink($dir);
	// For each item in the directory
	foreach (scandir($dir) as $item) {
		// Ignore special folders
		if ($item == '.' || $item == '..')
			continue;
		// Recursively delete items in the folder
		if (!deleteDirectory($dir . "/" . $item)) {
			//chmod($dir . "/" . $item, 0777);
			if (!deleteDirectory($dir . "/" . $item))
				return false;
		};
	}
	return rmdir($dir);
}

//https://hotexamples.com/examples/-/-/recurse_copy/php-recurse_copy-function-examples.html
function recurse_copy($source, $dest)
{
	// Check for symlinks
	if (is_link($source)) {
		return symlink(readlink($source), $dest);
	}
	// Simple copy for a file
	if (is_file($source)) {
		return copy($source, $dest);
	}
	// Make destination directory
	if (!is_dir($dest)) {
		mkdir($dest);
	}
	// Loop through the folder
	$dir = dir($source);
	while (false !== ($entry = $dir->read())) {
		// Skip pointers
		if ($entry == '.' || $entry == '..') {
			continue;
		}
		// Deep copy directories
		recurse_copy("{$source}/{$entry}", "{$dest}/{$entry}");
	}
	// Clean up
	$dir->close();
	return true;
}

/**
 * Object representing a source code file.
 */
class program_file {

	public $path;       ///< Folder containing the file
	public $filename;   ///< Filename without extension
	public $extension;  ///< Extension, based on the language
	public $fullname;   ///< Absolute Path and Filename
	public $sourcefile; ///< Replaces sourcefile in commands
	public $id;         ///< Submission ID
	public $commands;   ///< Commands with Keywords Replaced
	public $tests;       ///< Tests with Keywords Replaced 
	public $timelimit;   ///< Timelimit. Currently only used by the matlab marker   
	public $firstname;
	public $lastname;
	public $userid;

	/**
	 * Constructor
	 * @param array $lang Array containing information about the Language
	 * @param string $sourcecode All the sourcecode to be written to the file
	 * @param string $input Optional Input data to be written to file
	 */

	function program_file($lang, $sourcecode, $markerid, $timelimit, $sourcepath = "", $extra_path = "", $firstname = "", $lastname = "", $userid = "") {
		// Get filename extension from $lang
		$this->extension = $lang['extension']; //TODO allow override from student file
		// All files are called source
		$this->filename = "source";
		$this->sourcefile = "$this->filename.$this->extension";
		$this->timelimit=$timelimit;
		$this->markerid = $markerid;
		$this->firstname = $firstname;
		$this->lastname = $lastname;
		$this->userid = $userid;

		// Get the Submission ID
		$this->id = date("Ymd-His-") . uniqid("", $more_entropy = true);

		// Construct the path
		$this->path = settings::$temp;
		if(trim($extra_path) != "" and substr($extra_path, -1) != "/"){
			$extra_path .= "/";
		}
		$this->path = "$this->path/$this->extension/$extra_path$this->id";
		// Construct the full path/file
		$this->fullname = "$this->path/$this->filename.$this->extension";

		// Create the folder
		mkdir($this->path, 0777, $recursive = true);
		// Save the code
		file_put_contents($this->fullname, $sourcecode);

		error_log("cp -r \"$sourcepath/*\" \"" . $this->path . "\"");
		$success = recurse_copy($sourcepath, $this->path);
		//system("cp -r \"$sourcepath/\" \"" . $this->path . "\"", $success);
		if(!$success){
			$exception = new Exception("Marker Error" . $success);
			$exception->details = array(result_marker_error, -1, array("Marker Error: Unable to copy testcases."));
			throw $exception;
		}
		// setup commands
		$this->compile_commands = $lang['compile'];
		$this->compile_tests = $lang['compile_tests'];
		$this->commands = $lang['commands'];
	}

	/**
	 * Iterates through commands from the language description and replaces 
	 * keywords with the relevant paths
	 * @param array $comm Array of commands
	 * @return Array of commands with keywords replaced
	 */
	function setup_commands($comm, $inputfile, $outputfile, $args = '') {
		$temp = $comm;
                $inputfilenoex = substr($inputfile, 0, strpos($inputfile, '.'));
		//if($args != ''){
		//	$args = '"'.$args.'"'; // Add quotes around args if it exists
		//}
		foreach ($temp as $key => $value) {
			$value = str_replace("~sourcefile~", $this->sourcefile, $value);
			$value = str_replace("~sourcefile_noex~", $this->filename, $value);
			$value = str_replace("~input~", $inputfile, $value);
			$value = str_replace("~input_noex~", $inputfilenoex, $value);
			$value = str_replace("~output~", $outputfile, $value);
			$value = str_replace("~markers~", getcwd(), $value);
			$value = str_replace("~path~", $this->path, $value);
			$value = str_replace("~args~", $args, $value);
			$value = str_replace("~timeout~", $this->timelimit, $value);
			$value = str_replace("~markerid~", $this->markerid, $value);
$firstname = preg_replace('/\s+/', '', $this->firstname);
$lastname = preg_replace('/\s+/', '', $this->lastname);
			$value = str_replace("~firstname~", $firstname, $value);
			$value = str_replace("~lastname~", $lastname, $value);
			$value = str_replace("~userid~", $this->userid, $value);
			$temp[$key] = $value;
		}
		return $temp;
	}

	/**
	 * Destructor deletes the relevant directory unless settings::$keep_files is
	 * set to true.
	 */
	function __destruct() {
		if (!settings::$keep_files) {
			deleteDirectory($this->path);
		}
	}

}

/**
 * Kill a process and all of its children. 
 * TODO: This function needs some testing with regards to programs
 * with threads and/or forks.
 * Is this code necessary if the bash script killer runs?
 * @param int $process PID of the process to kill
 * @return int exit code of the process
 */
function killprocess($process) {
	$status = proc_get_status($process);
	if ($status['running'] == true) { //process ran too long, kill it
		//close all pipes that are still open
		fclose($pipes[1]); //stdout
		fclose($pipes[2]); //stderr
		//get the parent pid of the process we want to kill
		$ppid = $status['pid'];
		//use ps to get all the children of this process, and kill them
		$pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
		foreach ($pids as $pid) {
			if (is_numeric($pid)) {
				posix_kill($pid, 9); //9 is the SIGKILL signal
			}
		}

		return proc_close($process);
	}else{
		return $status['exitcode'];
	}
}

function mark_log($text){
	file_put_contents(settings::$temp."/log.txt", $text);
}

/**
 * Runs a program with a timelimit and input.
 * @param string $path  Working directory of the program. 
 *      The system cd's to this path before running the program.
 * @param type $program The program within $path that should execute
 * @param type $input   Input to the program on stdin
 * @param type $limit   Optional Time limit in seconds
 * @return Array containing stdout, stderr and exit code (result).
 * @throws Exception if the program cannot be started.
 */
function run($path, $program, $input, $limit = -1) {
	$descriptorspec = array(
		0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
		1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
		2 => array('pipe', 'w')  // stderr is a pipe the child will write to
	);

	if ($limit == -1) {
		$execString = "cd $path; $program";
	} else {
		$execString = getcwd() . "/timeout_runner.sh '$path' '$program' $limit";

	}
	$process = proc_open($execString, $descriptorspec, $pipes);
	if (!is_resource($process)) {
		throw new Exception('bad_program could not be started.');
	}
	//pass some input to the program
	fwrite($pipes[0], $input);
	//close stdin. By closing stdin, the program should exit
	//after it finishes processing the input
	fclose($pipes[0]);

	//do some other stuff ... the process will probably still be running
	//if we check on it right away
	$output = '';
	if (is_resource($process)) {
		while (!feof($pipes[1])) {
			$return_message = fgets($pipes[1], 1024);
			if (strlen($return_message) == 0)
				break;

			$output .= $return_message;
			ob_flush();
			flush();
		}
	}
	$len = strlen($output);
	if ($len>output_max_length)
		$output = substr($output, 0, output_max_length);
	$stderr = '';
	if (is_resource($process)) {
		while (!feof($pipes[2])) {
			$return_message = fgets($pipes[2], 1024);
			if (strlen($return_message) == 0)
				break;

			$stderr .= $return_message;
			ob_flush();
			flush();
		}
	}
	$len = strlen($stderr);
	if ($len>output_max_length)
		$stderr = substr($stderr, 0, output_max_length);

	$res = killprocess($process);

	return array('stdout' => $output, 'stderr' => $stderr, "result" => $res, "exec" => $execString);
}

function update_status($curr, $update){
	if($curr == null){
		return $update;
	}
	if($curr === $update){
		return $curr;
	}
	return ASSIGNFEEDBACK_WITSOJ_STATUS_MIXED;
}

/**
 * The main marking function. This is called from the webservice, saves the
 * source code, runs the commands and checks the output.
 * @param int $language    Language ID found in the languages.json file.
 * @param string $sourcecode  The source code/binary the program.
 * @param string $input   Input to the program on STDIN and the input file.
 * @param string $output  The expected output of the program on STDOUT.
 * @param int $timelimit   Time limit for the "run" command.
 * @return string array containing STDERR, STDOUT and the result.
 */
function mark($sourcecode, $tests, $language, $userid, $firstname, $lastname, $markerid, $cpu_limit, $mem_limit, $pe_ratio) {
	$string = file_get_contents("languages.json");
	$languages = json_decode($string, true); // THIS IS NOT PARSING PROPERLY AT THE MOMENT?!
	foreach ($languages as $k => $v){
		error_log("Comparing: " . $language . " and " . $v["name"] . " ($k)");
		if($v["name"] == $language){
			$language = $k;
			error_log("Using: " . $language);
			break;
		}
	}

	if(!isset($languages[$language])){
		// TODO: Invalid Language Selection
		error_log("Invalid Language");
		$outputs = array("result" => result_marker_error, "oj_feedback" => "Marker Error: Invalid Language");
		return array("status" => result_marker_error, "oj_feedback" => "Marker Error: Invalid Language", "grade" => -1.0, "outputs" => array($outputs) );
	}
	$lang = $languages[$language];

	$prefix = $userid . "/";
	$code = new program_file($lang, $sourcecode, $markerid, $cpu_limit, $tests["path"], $prefix, $firstname, $lastname, $userid);

	$compile_commands = $code->setup_commands($code->compile_commands, "input", "output");
	$compile_tests    = $code->setup_commands($code->compile_tests   , "input", "output");
	foreach ($compile_commands as $key => $command) {
		$runner = (($key=="run")||(strpos($key, "time")===0));
		if ($runner) {
			$outputs = run($code->path, $command, "", $cpu_limit);
			if(strpos($outputs["stderr"], 'Time limit exceeded') !== FALSE){
				$outputs["result"] = result_compile_error;
				$outputs["oj_feedback"] = "Compile Time Exceeded";
				return array("status" => result_compile_error, "oj_feedback" => "Compile Time Limit Exceeded", "grade" => 0.0, "outputs" => array($outputs));
			}
		} else {
			$outputs = run($code->path, $command, "");
		}
		if (array_key_exists($key, $compile_tests)) {
			$filename = $code->path . "/" . $compile_tests[$key];
			if (!file_exists($filename)) {
				$outputs["oj_feedback"] = "Compile Error";
				$outputs["result"] = result_compile_error;
				return array("status" => result_compile_error, "oj_feedback" => "Compile Error", "grade" => 0.0, "outputs" => array($outputs));
			}
		}
	}

	$all_outputs = array();
	$total_grade = 0.0;
	$max_grade = 0.0;
	$status = null;
	// Run each test case
	foreach ($tests["yml"]["test_cases"] as $tc){
		$outputs = null;
		$timeout_problem = false;
		$result = array();
		$commands = $code->setup_commands($code->commands, $tc["in"], $tc["out"], $tc["args"]);
		// Run each command
		foreach ($commands as $key => $command) {
			$runner = (($key=="run")||(strpos($key, "time")===0));
			$input = file_get_contents($code->path . "/" . $tc["in"]);
			$input = str_replace("\r", "", $input);
			if ($key == "display"){
				$displayout = run($code->path, $command, $input);
			} elseif ($runner) {
				$outputs = run($code->path, $command, $input, $cpu_limit);
				if(strpos($outputs["stderr"], 'Time limit exceeded') !== FALSE or strpos($outputs["stdout"], 'Time limit exceeded') !== FALSE){
					$timeout_problem = true;
					break;
				}
			} else {
				$outputs = run($code->path, $command, $input);
			}
		}
		if(!isset($tc["feedback"])){
			$tc["feedback"] = "";
		}
		if($timeout_problem){
			// Check if we had a timeout
			$outputs["result"] = result_time_limit;
			$outputs["oj_feedback"] = $tc["feedback"];
			$outputs["path"] = $code->path;
			$outputs["grade"] = 0.0;
			$outputs["max_grade"] = $tc["points"];
			$max_grade += floatval($tc["points"]);
			$status = update_status($status, result_time_limit);
		}else{
			// No timeout, check for correctness
			$model_output = file_get_contents($code->path . "/" . $tc["out"]); 	// Fetch the output
			$model_output = str_replace("\r", "", $model_output);			// Remove \r just in case

			$outputs['stdout'] = trim(str_replace("\r", "", $outputs['stdout']));		// Remove \r from student output
			$outputs["result"] = test_output($model_output, $outputs['stdout']);	// Check the output
			$outputs["progout"] = trim($outputs['stdout']);				// Actual output
			if(isset($displayout)){
				$outputs["stdout"] = trim($displayout['stdout']);				// Actual output
			}
			$outputs["modelout"] = trim($model_output);				// Model output
			//$outputs["progout"] = trim($outputs['stdout']);				// Actual output
			$outputs["path"] =  $code->path;					// Path on marker
			$outputs["max_grade"] = $tc["points"];
			$max_grade += floatval($tc["points"]);
			
			if($outputs["result"] === result_presentation_error){			// Presentation Error
				$total_grade += $pe_ratio * floatval($tc["points"]);			//	Scale by pe_ratio
				$outputs["grade"] = $pe_ratio * floatval($tc["points"]);
				$outputs["oj_feedback"] = $tc["feedback"];
				$status = update_status($status, result_presentation_error);
			}else if($outputs["result"] === result_correct){			// Correct
				$total_grade += $tc["points"];					//	Add full points
				$outputs["grade"] = $tc["points"];
				$outputs["oj_feedback"] = $tc["feedback"];	
				$status = update_status($status, result_correct);		
			}else if($outputs["result"] === result_incorrect){			// Incorrect (or something else)
				$outputs["grade"] = 0.0;					// 	0 Marks
				$outputs["oj_feedback"] = $tc["feedback"];	
				$status = update_status($status, result_incorrect);	
			}else{
				$outputs["grade"] = 0.0;					// 	0 Marks
				$outputs["oj_feedback"] = "Unknown Error, Check Marker";				
			}
		}
		$all_outputs[] = $outputs;
	}

	error_log("TOTALGRADE: " . $total_grade);
	return array("status" => $status, "oj_feedback" => "Graded", "grade" => $total_grade*100.0/$max_grade, "outputs" => $all_outputs);
}

/**
 * Compare ideal and program outputs. Checks for an exact match 
 * then for presentation errors.
 * @param string $correct Ideal output.
 * @param string $progoutput Program output.
 * @return int result code.
 */
function test_output($correct, $progoutput) {
	$correct = trim($correct);
	$progoutput = trim($progoutput);
	if ($correct == $progoutput) {
		return result_correct;
	}

	$correct = strtolower($correct);
	$correct = str_replace(" ", "", $correct);
	$correct = str_replace("\t", "", $correct);
	$correct = str_replace("\n", "", $correct);

	$progoutput = strtolower($progoutput);
	$progoutput = str_replace(" ", "", $progoutput);
	$progoutput = str_replace("\t", "", $progoutput);
	$progoutput = str_replace("\n", "", $progoutput);
	error_log($correct);
	error_log($progoutput);
	if ($correct == $progoutput) {
		error_log("Presentation Error");
		return result_presentation_error;
	} else {
		error_log("Incorrect Result");
		return result_incorrect;
	}
}

function test_filename($testcases){
	return settings::$testcases . "/tests/" . $testcases["contenthash"];
}
function fetch_tests($testcases, $base_path){
	// Paths
	$contenthash = $testcases["contenthash"];
	$path_folder = $base_path . $contenthash;
	$path_zip = $path_folder . ".zip";
	// Check if the base directory exists
	if(!file_exists($base_path)){
		mkdir($base_path, 0777, $recursive = true);
	}
	// Check if the zip file exists. If not, download it.
	if(!file_exists($path_zip)){
		// Setup cURL
		$fileHandle = fopen($path_zip, 'w+');
		$ch = curl_init($testcases["url"]);
		curl_setopt_array($ch, array(
			CURLOPT_POST => count(settings::$auth_token),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_BINARYTRANSFER => TRUE,
			CURLOPT_POSTFIELDS => http_build_query(settings::$auth_token),
			CURLOPT_FILE => $fileHandle,
			CURLOPT_TIMEOUT => 30
		));

		// Send the request
		curl_exec($ch);

		fclose($fileHandle);
		// Check that we actually downloaded the file now
		if(!file_exists($path_zip)){
			fatal("Unable to download test cases.");
			return false;
		}
	}

	// Check the file downloaded correctly
	$sha1file = sha1_file($path_zip);
	if($sha1file != $contenthash){
		unlink($path_zip);
		fatal("Invalid testcase SHA1.");
		return false;
	}
	// Unzip the file
	$zip = new ZipArchive;
	$res = $zip->open($path_zip);
	if ($res === TRUE) {
		$zip->extractTo($path_folder);
		$zip->close();
	} else {
		fatal('Unable to exctract test cases.');
		return false;
	}
	return true;
}

function fatal($str){
	die('{ "fatalstatus" : "' . $str . '"}');
}

/**
 * Check if test cases have been downloaded already, if not download them.
 * @param string $correct Ideal output.
 * @param string $progoutput Program output.
 * @return int result code.
 */
function testcases($testcases) {
	// Get the paths of the test cases
	$path = settings::$testcases . "/";
	$path_extracted = $path . $testcases["contenthash"];

	// Check for the extracted folder
	if(!file_exists($path_extracted)){
		if(!fetch_tests($testcases, $path)){
			fatal("Failed to fetch tests.");
		}
	}

	$manifest = $path_extracted . "/init.yml";
	$yml = yaml_parse_file($manifest);
	if(!$yml){
		fatal("Unable to parse init.yml file to load test cases.");
	}
	$defaults = array("args" => "");
	foreach($yml["test_cases"] as $k=>$v){
		foreach($defaults as $key => $def){
			if(!isset($yml["test_cases"][$k][$key])){
				$yml["test_cases"][$k][$key] = $def;
			}
		}
        }

	return array("path" => $path_extracted, "yml" => $yml);
}

function return_grade($callback, $markerid, $userid, $grade, $status, $oj_testcases, $oj_feedback){
	// Setup cURL
	$data['witsoj_token'] = settings::$auth_token['witsoj_token'];
	$data['markerid'] = $markerid;
	$data['userid'] = $userid;
	$data['grade'] = $grade;
	$data['status'] = $status;
	$data['oj_testcases'] = $oj_testcases;
	$data['oj_feedback'] = $oj_feedback;
	$data['witsoj_name'] = settings::$auth_token['witsoj_name'];

	$ch = curl_init($callback);
	curl_setopt_array($ch, array(
		CURLOPT_POST => count($data),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
			//'Content-Type: application/json'
			'Content-Type: text/plain'
		),
		CURLOPT_POSTFIELDS => json_encode($data)
	));
	// Send the request
	$response = curl_exec($ch);
	var_dump($response);

	// Check for errors
	if($response === FALSE){
		die("Curl Error: " . curl_error($ch));
	}

	if($response != '{"status" : "0"}'){
		error_log($response);
		return false;
	}
	return true;
}
?>
