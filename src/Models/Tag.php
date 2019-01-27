<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\Model;
use Intersect\Database\Model\Validation\Validation;

class Tag extends Model implements Validation {

    protected $tableName = 'ib_tags';

    public function getValidatorMap()
    {
        return [
            'name' => 'required'
        ];
    }

}