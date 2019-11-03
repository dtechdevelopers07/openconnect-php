<?php 
header('Content-Type: text/event-stream');
header('Cache-Control:no-cache' ) ;
header("Access-Control-Allow-Origin: *");
echo exec("users");
?>
