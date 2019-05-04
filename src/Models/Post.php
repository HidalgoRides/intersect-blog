<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\TemporalModel;
use Intersect\Database\Model\Validation\Validation;

class Post extends TemporalModel implements Validation {

    protected $tableName = 'ib_posts';

    public function getValidatorMap()
    {
        return [
            'title' => 'required',
            'slug' => 'required',
            'body' => 'required',
            'author_id' => 'required'
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getAuthorId()
    {
        return $this->author_id;
    }

    public function setAuthorId($authorId)
    {
        $this->author_id = $authorId;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getDateCreated()
    {
        return $this->date_created;
    }

    public function getDateUpdated()
    {
        return $this->date_updated;
    }

}