<?php

if (! function_exists('link_image_path')) {
    function link_image_path($path)
    {
        return !is_null($path) ? implode('/', array_replace(explode('/', $path), [0 => 'storage'])) : null;
    }
}

if (! function_exists('public_image_path')) {
    function public_image_path($path)
    {
        return !is_null($path) ? implode('/', array_replace(explode('/', $path), [0 => 'public'])) : null;
    }
}

if (! function_exists('get_public_image_path')) {
    function get_public_image_path($folder = '')
    {
        return implode('/', [
            'public',
            'images',
            $folder,
        ]);
    }
}

if (! function_exists('get_image_name')) {
    function get_image_name($path)
    {
        $folders = explode('/', $path);
        return !is_null($path) ? $folders[(count($folders) - 1)] : null;
    }
}
