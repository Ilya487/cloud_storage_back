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
use App\Tools\DbConnect;
use App\Tools\Session;

class Container
{
    private static ?DiContainerContainer $container = null;

    public static function getInstance()
    {
        if (is_null(self::$container)) {
            $containerBuilder = new ContainerBuilder;
            $containerBuilder->bind(AuthenticationInterface::class, SessionAuthentication::class);
            $containerBuilder->share(DbConnect::class);
            $containerBuilder->share(Session::class);
            $containerBuilder->setParam(new ContainerParam(DiskStorage::class, 'storagePath', '/var/lib/cloud-storage'));
            $containerBuilder->setParam(new ContainerParam(UploadsStorage::class, 'storagePath', '/var/lib/cloud-storage'));
            $containerBuilder->setParam(new ContainerParam(DownloadStorage::class, 'storagePath', '/var/lib/cloud-storage'));
            self::$container = $containerBuilder->build();
        }

        return self::$container;
    }
}
