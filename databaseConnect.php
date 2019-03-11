<?php
class databaseConnect{
  var $servername;
  var $username;
  var $password;
  function __construct($servername,$username,$password){
    $this->servername = $servername;
    $this->username = $username;
    $this->password = $password;
  }
  functoin connect(){
    if(isset($servername,$username, $password)){
    $conn = mysqli_connect($this->servername,$this->username, $this->password);
    if(!$conn){
      die("connection failed: ".mysql_connect_error());
    }
    echo "Connected successfully";
  }else{
    echo "connection variables not set, check the constructor";
  }

  }
}
?>
