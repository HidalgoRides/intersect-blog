<?php

namespace Intersect\Blog\Commands;

class UpgradeBlogCommand extends InstallBlogCommand {

    public function getDescription()
    {
        return 'Updates the blog schema to the most recent version';
    }

}