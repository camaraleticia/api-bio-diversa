<?php
define('ROOT_DIR', __DIR__);
require ROOT_DIR.'/vendor/autoload.php';
require ROOT_DIR.'/app/Core/Autoloader.php';
$al = new \App\Core\Autoloader();
$al->register();
new \App\Services\ImageUploadService();
$dir = 'public/uploads/images';
printf("Dir: %s Exists:%s Writable:%s Perm:%o\n", $dir, file_exists($dir)?'yes':'no', is_writable($dir)?'yes':'no', fileperms($dir)&0777);