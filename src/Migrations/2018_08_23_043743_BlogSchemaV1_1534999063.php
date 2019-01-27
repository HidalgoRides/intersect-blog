<?php

use Intersect\Database\Migrations\AbstractMigration;

class BlogSchemaV11534999063 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->getConnection()->query("
            create table ib_categories (
                id int auto_increment primary key,
                name varchar(50) not null,
                slug varchar(50) not null,
                parent_id int null,
                status tinyint default '1' not null,
                date_created datetime not null,
                date_updated datetime null,
                meta_data text null,
                constraint uq_category_slug unique (slug),
                constraint category_parent_id_fidx foreign key (parent_id) references ib_categories (id)
            ) engine=InnoDB default charset=utf8;
        ");

        $this->getConnection()->query("create index category_parent_id_fidx on ib_categories (parent_id)");

        $this->getConnection()->query("
            create table ib_post_tags (
                post_id int not null,
                tag_id int not null,
                primary key (post_id, tag_id)
            ) engine=InnoDB default charset=utf8;
        ");

        $this->getConnection()->query("
            create table ib_posts (
                id int auto_increment primary key,
                category_id int null,
                title varchar(255) not null,
                slug varchar(255) not null,
                body text not null,
                author_id int not null,
                status tinyint default '1' not null,
                date_created datetime not null,
                date_updated datetime null,
                meta_data text null,
                constraint uq_post_slug unique (slug)
            ) engine=InnoDB default charset=utf8;
        ");

        $this->getConnection()->query("
            create table ib_tags (
                id int auto_increment primary key,
                name varchar(25) not null,
                meta_data text null,
                constraint uq_tag_name unique (name)
            ) engine=InnoDB default charset=utf8;
        ");
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        //
    }

}