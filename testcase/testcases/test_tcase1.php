
<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
class tcase1Test extends TestCase{
  
 public function test_nazo(){
   require_once('/home/travis/build/mthanda15/404-brain-not-found/testcase/tcase1.php');
   $test=nazo(4,5);
 $this->assertEquals(5,$test, "correct!"); 
 }
 
}
