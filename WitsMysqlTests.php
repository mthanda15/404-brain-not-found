<?php

class HelloWorldTest extends \PHPUnit\framework\TestCase{
  public function testGreeting() {

  		$greeting = "Hello World";
  		$requiredGreeting = "Hello World";

  		$this->assertEquals($greeting, $requiredGreeting);
      $this->assertEquals(2,4);
  	}
}

?>
