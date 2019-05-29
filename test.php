<?php
$output = array();
//exec('/var/www/mark/myProgram 2>/tmp/test/err.txt >/tmp/test/out.txt &; i=$!; sleep 10;kill $i 2>/dev/null && echo "myProgram did not finish."', $output);
exec('/var/www/mark/test.sh', $output);
var_dump($output);

?>
