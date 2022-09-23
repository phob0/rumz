<?php

if (! function_exists('link_image_path')) {
    function link_image_path($path)
    {
        return implode('/', array_replace(explode('/', $path), [0 => 'storage']));
    }
}
