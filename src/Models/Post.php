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

}