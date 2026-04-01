<?php
// test_gd.php
var_dump(extension_loaded('gd'));
$info = @getimagesize('/var/www/html/app/Ml/Dataset/fauna/capivara/capivara_01.jpg');
var_dump($info);