<?php

require 'vendor/silverstripe/framework/tests/bootstrap.php';

function include_dir_r( $dir_path ) {
    $path = realpath( $dir_path );
    $objects = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $path ), \RecursiveIteratorIterator::SELF_FIRST );
    foreach( $objects as $name => $object ) {
        if( $object->getFilename() !== "." && $object->getFilename() !== ".." ) {
            if( !is_dir( $name ) ){
                include_once $name;
            }
        }
    }
}

include_dir_r('/Users/matthijs/PhpStormProjects/surf-sharekit-cms/app/src'); //must a hard path to test, otherwise phpstorm can't find it