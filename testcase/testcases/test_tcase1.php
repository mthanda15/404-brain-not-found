
<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
class indexTest extends TestCase{
  
 public function test_case(){
   require_once('/home/travis/build/404-brain-not-found/testcase/tcase1.php');
   $test=case(4,5);
 $this->assertEquals(5,$test, "correct!"); 
 }
 
}