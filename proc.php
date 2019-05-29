<?php
/**
 * @file proc.php
 * User front end to the marking routines.
 */
require_once("config.php"); // Include Global Configuration
require_once("lib.php");    // Include Library Functions

$val = mark(5, '#include <stdio.h>
    int main(){printf("hellofdthdhfgt");}', 'test', 'Hello', 3);

var_dump($val);

?>
