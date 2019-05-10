<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\TemporalModel;
use Intersect\Database\Model\Validation\Validation;

class Category extends TemporalModel implements Validation {

    protected $connectionKey = 'ib_conn';
    protected $tableName = 'ib_categories';

    public function getValidatorMap()
    {
        return [
            'name' => 'required',
            'slug' => 'required',
            'status' => 'required'
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

    public function getName() 
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSlug() 
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getParentId() 
    {
        return $this->parent_id;
    }

    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;
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