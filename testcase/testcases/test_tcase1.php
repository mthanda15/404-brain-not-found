
<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
class tcase1Test extends TestCase{
  
 public function test_case(){
   require_once('/home/travis/build/mthanda15/404-brain-not-found/testcase/tcase1.php');
   $test=case(4,5);
 $this->assertEquals(5,$test, "correct!"); 
 }
 
}
