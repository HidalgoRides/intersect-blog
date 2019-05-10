<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\Model;
use Intersect\Database\Model\Validation\Validation;

class Tag extends Model implements Validation {

    protected $connectionKey = 'ib_conn';
    protected $tableName = 'ib_tags';

    public function getValidatorMap()
    {
        return [
            'name' => 'required'
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

}