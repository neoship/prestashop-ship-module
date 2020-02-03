<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit170c5c5b819bc3b5fdd9ebc26dcc30cc
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Neoship\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Neoship\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit170c5c5b819bc3b5fdd9ebc26dcc30cc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit170c5c5b819bc3b5fdd9ebc26dcc30cc::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}