<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\AssociativeModel;

class PostTagAssociation extends AssociativeModel {

    protected $primaryKey = null;
    protected $tableName = 'ib_post_tags';

    public function __construct($postId = null, $tagId = null)
    {
        parent::__construct();

        $this->post_id = $postId;
        $this->tag_id = $tagId;
    }

    public function getPostId()
    {
        return $this->post_id;
    }

    public function setPostId($postId)
    {
        $this->post_id = $postId;
    }

    public function getTagId()
    {
        return $this->tag_id;
    }

    public function setTagId($tagId)
    {
        $this->tag_id = $tagId;
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