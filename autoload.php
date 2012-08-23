<?php
require_once 'src/autoloader.php';

$splClassLoader = new SplClassLoader("Abraham", "src");
$splClassLoader->register();