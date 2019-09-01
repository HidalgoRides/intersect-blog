<?php

use Intersect\Database\Migrations\AbstractMigration;
use Intersect\Database\Schema\Blueprint;

class BlogSchemaV11534999063 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTableIfNotExists('ib_categories', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('name', 50);
            $blueprint->string('slug', 50);
            $blueprint->integer('parent_id')->nullable();
            $blueprint->tinyInteger('status')->default('1');
            $blueprint->datetime('date_created');
            $blueprint->datetime('date_updated')->nullable();
            $blueprint->text('meta_data')->nullable();
            $blueprint->unique('slug', 'uq_category_slug');
            $blueprint->foreign('parent_id', 'id', 'ib_categories', 'category_parent_id_fidx');
            $blueprint->index('parent_id');
        });

        $this->schema->createTableIfNotExists('ib_post_tags', function(Blueprint $blueprint) {
            $blueprint->integer('post_id');
            $blueprint->integer('tag_id');
            $blueprint->primary(['post_id', 'tag_id']);
        });

        $this->schema->createTableIfNotExists('ib_posts', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->integer('category_id')->nullable();
            $blueprint->string('title', 255);
            $blueprint->string('slug', 255);
            $blueprint->text('body');
            $blueprint->integer('author_id');
            $blueprint->tinyInteger('status')->default('1');
            $blueprint->datetime('date_created');
            $blueprint->datetime('date_updated')->nullable();
            $blueprint->text('meta_data')->nullable();
            $blueprint->unique('slug', 'uq_post_slug');
        });

        $this->schema->createTableIfNotExists('ib_tags', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('name', 25);
            $blueprint->text('meta_data')->nullable();
            $blueprint->unique('name', 'uq_tag_name');
        });
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->schema->dropTableIfExists('ib_tags');
        $this->schema->dropTableIfExists('ib_posts');
        $this->schema->dropTableIfExists('ib_post_tags');
        $this->schema->dropTableIfExists('ib_categories');
    }

}