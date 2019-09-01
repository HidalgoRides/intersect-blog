<?php

namespace Intersect\Blog\Providers;

use Intersect\Blog\Services\BlogService;
use Intersect\Core\Providers\ServiceProvider;

class BlogServiceProvider extends ServiceProvider {

    public function init()
    {
        $this->container->bind(BlogService::class);
        
        $this->container->migrationPath(dirname(dirname(__FILE__)) . '/Migrations');
    }

}