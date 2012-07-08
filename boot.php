<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/src');

spl_autoload_register(
    function ($className)
    {
        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';

        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);

            $fileName = str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                $namespace
            );

            $fileName .= DIRECTORY_SEPARATOR;
        }

        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (stream_resolve_include_path($fileName)) {
            include $fileName;

            return true;
        }
    },
    true
);