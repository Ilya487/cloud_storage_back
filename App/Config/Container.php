<?php

namespace App\Config;

use App\Authentication\AuthenticationInterface;
use App\Authentication\SessionAuthentication;
use App\Core\DiContainer\Container as DiContainerContainer;
use App\Core\DiContainer\ContainerBuilder;
use App\Core\DiContainer\ContainerParam;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;
use App\Storage\UploadsStorage;

class Container
{
    private static ?DiContainerContainer $container = null;

    public static function getInstance()
    {
        if (is_null(self::$container)) {
            $containerBuilder = new ContainerBuilder;
            $containerBuilder->bind(AuthenticationInterface::class, SessionAuthentication::class);
            $containerBuilder->set('storagePath', '/var/lib/cloud-storage');
            $containerBuilder->setParam(new ContainerParam(DiskStorage::class, 'storagePath', $containerBuilder->get('storagePath')));
            $containerBuilder->setParam(new ContainerParam(UploadsStorage::class, 'storagePath', $containerBuilder->get('storagePath')));
            $containerBuilder->setParam(new ContainerParam(DownloadStorage::class, 'storagePath', $containerBuilder->get('storagePath')));
            self::$container = $containerBuilder->build();
        }

        return self::$container;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public static function resolve(string $className)
    {
        return self::getInstance()->resolve($className);
    }
}
