<?php
require_once "lib/Parser.php";

$parser = new Parser();
$pdf    = $parser->parseFile($_FILES["fileToUpload"]["tmp_name"]);  
$text = $pdf->getText();
echo $text;//all text from mypdf.pdf
?>