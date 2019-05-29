<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * ideone.com judge engine
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . "/../../../../config.php");
require_once($CFG->dirroot . "/local/onlinejudge/judgelib.php");

class judge_wits extends judge_base {
    protected static $marker_url = "http://aiml.cs.wits.ac.za/mark/";
    protected static $marker_name = "wits";

    //TODO: update latest language list through ideone API
    //protected static $supported_languages = array(
    //    1 => 'Jar (wits)',
    //    2 => 'JavaZip (wits)',
    //    3 => 'Jython (wits)',
    //    4 => 'Python (wits)',
    //    5 => 'C (wits)',
    //    6 => 'C++ (wits)'
    //);

    static function get_languages() {
	global $CFG;
        $langs = array();
        if (!self::is_available()) {
            return $langs;
        }

	$languages_json = file_get_contents($CFG->dirroot . "/local/onlinejudge/judge/wits/languages.json");
	$languages_json = json_decode($languages_json, true);

	foreach ($languages_json as $key => $value){
            $langs[$key . '_' . self::$marker_name] = $value['name'] . " (" . self::$marker_name . ')';
	}
        return $langs;
    }
    /**
     * Judge the current task
     *
     * @return updated task
     */
    function judge() {
        global $CFG, $DB;
        $task = &$this->task;
        // create client.
        $url = self::$marker_url."mark.php";
        $language = $this->language;
        $input = $task->input;
        $output = $task->output;

        // Get source code
        $fs = get_file_storage();
        $files = $fs->get_area_files(get_context_instance(CONTEXT_SYSTEM)->id, 'local_onlinejudge', 'tasks', $task->id, 'sortorder, timemodified', false);
        $source = '';
        foreach ($files as $file) {
            $source = $file->get_content();
            break;
        }

        $source = base64_encode($source);

        // Begin soap
        /**
         * function createSubmission create a paste.
         * @param user is the user name.
         * @param pass is the user's password.
         * @param source is the source code of the paste.
         * @param language is language identifier. these identifiers can be
         *     retrieved by using the getLanguages methods.
         * @param input is the data that will be given to the program on the stdin
         * @param run is the determines whether the source code should be executed.
         * @param private is the determines whether the paste should be private.
         *     Private pastes do not appear on the recent pastes page on ideone.com.
         *     Notice: you can only set submission's visibility to public or private through
         *     the API (you cannot set the user's visibility).
         * @return array(
         *         error => string
         *         link  => string
         *     )
         */
        $data = array("language" => "$language",
            "source" => "$source",
            "input" => "$input",
            "output" => "$output",
            "timelimit" => "$task->cpulimit");
        $data_string = json_encode($data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data_string,
                'header' => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);
        $task->status = $response->result;
        $task->infostudent = $response->stderr;
        $task->infoteacher = $response->stdout;

//        $task->stdout = $details['output'];
//        $task->stderr = $details['stderr'];
//        $task->compileroutput = $details['cmpinfo'];
//        $task->memusage = $details['memory'] * 1024;
//        $task->cpuusage = $details['time'];

        return $task;
    }

    /**
     * Whether the judge is avaliable
     *
     * @return true for yes, false for no
     */
    static function is_available() {
        return true;
    }

}

