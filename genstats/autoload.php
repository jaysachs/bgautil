<?php

spl_autoload_register(function ($class) {
    $file = str_replace('Bga\\Games\\testgame\\','', $class) . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});
