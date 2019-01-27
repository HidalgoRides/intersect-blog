<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\AssociativeModel;

class PostTagAssociation extends AssociativeModel {

    protected $primaryKey = null;
    protected $tableName = 'ib_post_tags';

    public function __construct($postId = null, $tagId = null)
    {
        parent::__construct();

        $this->setAttribute('post_id', $postId);
        $this->setAttribute('tag_id', $tagId);
    }

    protected function getColumnOneName()
    {
        return 'post_id';
    }

    protected function getColumnTwoName()
    {
        return 'tag_id';
    }

}