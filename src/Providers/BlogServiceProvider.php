<?php

namespace Intersect\Blog\Providers;

use Intersect\Core\Providers\ServiceProvider;

class BlogServiceProvider extends ServiceProvider {

    public function init()
    {
        $this->container->migrationPath(dirname(dirname(__FILE__)) . '/Migrations');
    }

}