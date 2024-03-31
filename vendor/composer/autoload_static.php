<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitff70bcb61401d0f23cee81b7c39703e3
{
    public static $files = array (
        'e53cbdc1e03c5cc703d2d2b58cb8ffbd' => __DIR__ . '/../..' . '/includes/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'Licenser\\Traits\\' => 16,
            'Licenser\\Models\\' => 16,
            'Licenser\\Controllers\\' => 21,
            'Licenser\\Addons\\' => 16,
            'Licenser\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Licenser\\Traits\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes/traits',
        ),
        'Licenser\\Models\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes/models',
        ),
        'Licenser\\Controllers\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes/controllers',
        ),
        'Licenser\\Addons\\' => 
        array (
            0 => __DIR__ . '/../..' . '/addons',
        ),
        'Licenser\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitff70bcb61401d0f23cee81b7c39703e3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitff70bcb61401d0f23cee81b7c39703e3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitff70bcb61401d0f23cee81b7c39703e3::$classMap;

        }, null, ClassLoader::class);
    }
}
