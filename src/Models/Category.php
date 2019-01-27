<?php

namespace Intersect\Blog\Models;

use Intersect\Database\Model\TemporalModel;
use Intersect\Database\Model\Validation\Validation;

class Category extends TemporalModel implements Validation {

    protected $tableName = 'ib_categories';

    public function getValidatorMap()
    {
        return [
            'name' => 'required',
            'slug' => 'required',
            'status' => 'required'
        ];
    }

}