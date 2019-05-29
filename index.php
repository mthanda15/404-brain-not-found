<?php
// Show errors/warnings
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    
// var_dump($_FILES);
if (count($_FILES) != 0) {

// The test program
    $source = file_get_contents($_FILES['uploadedfile']['tmp_name']);
// Always base64_encode the source
    $source = base64_encode($source);
// languageid, source, input, output and timelimit
    $data = array("language" => $_POST['language'], "source" => $source,
        "input" => $_POST['stdin'], "output" => $_POST['stdout'], "timelimit" => 10);
// json_encode the object to send to the marker
    $data_string = json_encode($data);

// Post the data to the marker
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => $data_string,
            'header' => "Content-Type: application/json\r\n" .
            "Accept: application/json\r\n"
        )
    );
    $url = "http://marker.ms.wits.ac.za/marker/mark.php";

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);

// Show the response
    var_dump($response);
   
}

    function generateOptions(){
            $string = file_get_contents("languages.json");
            $languages = json_decode($string, true);
            $txt = '';
            foreach($languages as $key => $value){
                $name = $value['name'];
                $txt .= "<option value='$key'>$name</option>\n";                    
            }
            return $txt;
        
    }
    
    function lang(){
            $string = file_get_contents("languages.json");
            $languages = json_decode($string, true);
            var_dump($languages);
    }
?>
<form enctype="multipart/form-data" action="index.php" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
    Choose a file to upload: <input name="uploadedfile" type="file" /><br />
    <textarea id="stdin" name="stdin"></textarea>
    <textarea id="stdout" name="stdout"></textarea>
    <select id="language" name="language">
        <?php echo generateOptions(); ?>
    </select>
    <input type="submit" value="Upload File" />
</form>
