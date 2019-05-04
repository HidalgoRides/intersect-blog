<?php

namespace Intersect\Blog\Services;

use Intersect\Blog\Models\Category;
use Intersect\Blog\Models\Post;
use Intersect\Blog\Models\PostTagAssociation;
use Intersect\Blog\Models\Tag;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Query\QueryParameters;

class BlogService {

    /**
     * @param $postId
     * @param array $tags
     * @return bool
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function addTagsToPostId($postId, array $tags)
    {
        $post = $this->getPostById($postId);

        if (is_null($post))
        {
            return true;
        }

        foreach ($tags as $tag)
        {
            $newTag = new Tag();
            $newTag->setName($tag);

            $createdTag = $this->createTag($newTag);

            if (!is_null($createdTag))
            {
                $this->createPostTagAssociation($postId, $createdTag->getPrimaryKeyValue());
            }
        }

        return true;
    }

    /**
     * @param Category $category
     * @return Category|null
     * @throws ValidationException
     * @throws DatabaseException
     */
    public function createCategory(Category $category)
    {
        $category->setId(null);
        return $this->saveCategory($category);
    }

    /**
     * @param Post $post
     * @return Post|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function createPost(Post $post)
    {
        return $this->createPostWithTags($post, []);
    }

    /**
     * @param Post $post
     * @param array $tags
     * @return Post|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function createPostWithTags(Post $post, array $tags)
    {
        $post->setId(null);
        $createdPost = $this->savePost($post);

        if (!is_null($createdPost) && count($tags) > 0)
        {
            $this->addTagsToPostId($createdPost->getPrimaryKeyValue(), $tags);
        }

        return $createdPost;
    }

    /**
     * @param Tag $tag
     * @return Tag|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function createTag(Tag $tag)
    {
        return $this->saveTag($tag);
    }

    /**
     * @param $postId
     * @return bool
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function deletePostById($postId)
    {
        $postParameters = new QueryParameters();
        $postParameters->equals('id', $postId);

        $post = $this->findPost($postParameters);

        if (is_null($post))
        {
            return false;
        }

        $postDeleted = $post->delete();

        if ($postDeleted)
        {
            $this->deletePostTagAssociationsForPostId($postId);
        }

        return $postDeleted;
    }

    /**
     * @param $categoryId
     * @return bool
     * @throws DatabaseException
     */
    public function deleteCategoryById($categoryId)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->equals('id', $categoryId);

        $category = $this->findCategory($categoryParameters);

        if (is_null($category))
        {
            return false;
        }

        return $category->delete();
    }

    /**
     * @param $categoryId
     * @return bool|Post|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function disableCategoryById($categoryId)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->equals('id', $categoryId);

        $category = $this->findCategory($categoryParameters);

        if (is_null($category))
        {
            return false;
        }

        $category->setStatus(2);

        return $category->save();
    }

    /**
     * @param $postId
     * @return bool|Post|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function disablePostById($postId)
    {
        $postParameters = new QueryParameters();
        $postParameters->equals('id', $postId);

        $post = $this->findPost($postParameters);

        if (is_null($post))
        {
            return false;
        }

        $post->setStatus(2);

        return $post->save();
    }

    /**
     * @param null $limit
     * @param bool $onlyActive
     * @return Category[]
     * @throws DatabaseException
     */
    public function getAllCategories($limit = null, $onlyActive = true)
    {
        $categoryParameters = new QueryParameters();

        if ($onlyActive)
        {
            $categoryParameters->equals('status', 1);
        }

        if (!is_null($limit) && ((int) $limit) > 0)
        {
            $categoryParameters->setLimit($limit);
        }

        return Category::find($categoryParameters);
    }

    /**
     * @param $parentId
     * @param bool $onlyActive
     * @return Category[]
     * @throws DatabaseException
     */
    public function getAllChildCategories($parentId, $onlyActive = true)
    {
        $allChildCategories = [];

        $this->getAllChildCategoriesRecursive($parentId, $allChildCategories, $onlyActive);

        return $allChildCategories;
    }

    /**
     * @param bool $onlyActive
     * @return Category[]
     * @throws DatabaseException
     */
    public function getAllRootCategories($onlyActive = true)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->isNull('parent_id');

        if ($onlyActive)
        {
            $categoryParameters->equals('status', 1);
        }

        return Category::find($categoryParameters);
    }

    /**
     * @param null $limit
     * @return Post[]
     * @throws DatabaseException
     */
    public function getAllPosts($limit = null)
    {
        $postParameters = new QueryParameters();
        $postParameters->setOrder('date_created DESC');

        if (!is_null($limit) && ((int) $limit) > 0)
        {
            $postParameters->setLimit($limit);
        }

        return Post::find($postParameters);
    }

    /**
     * @param $name
     * @param null $limit
     * @return Post[]
     * @throws DatabaseException
     */
    public function getAllPostsWithTagName($name, $limit = null)
    {
        return $this->getAllPostsWithTagNames([$name], $limit);
    }

    /**
     * @param $name
     * @param null $limit
     * @return Post[]
     * @throws DatabaseException
     */
    public function getAllPostsWithTagNames(array $tagNames, $limit = null)
    {
        $tags = $this->getTagsByNames($tagNames);

        if (count($tags) == 0)
        {
            return [];
        }
        
        $allPostIds = [];

        foreach ($tags as $tag)
        {
            $postTagAssociations = PostTagAssociation::findAssociationsForColumnTwo($tag->getPrimaryKeyValue());

            $postIds = array_column($postTagAssociations, 'post_id');

            foreach ($postIds as $postId)
            {
                if (in_array($postId, $allPostIds))
                {
                    continue;
                }

                $allPostIds[] = $postId;
            }
        }

        if (count($allPostIds) == 0)
        {
            return [];
        }

        $postParameters = new QueryParameters();
        $postParameters->in('id', $allPostIds);
        $postParameters->setOrder('date_created DESC');

        if (!is_null($limit) && ((int) $limit) > 0)
        {
            $postParameters->setLimit($limit);
        }

        return Post::find($postParameters);
    }

    /**
     * @param $categoryId
     * @param bool $onlyActive
     * @param null $limit
     * @return Post[]
     * @throws DatabaseException
     */
    public function getAllPostsInCategoryId($categoryId, $onlyActive = true, $limit = null)
    {
        $category = $this->getCategoryById($categoryId);

        if (is_null($category))
        {
            return [];
        }

        $allCategoryIds = [$categoryId];
        $allChildCategories = [];

        $this->getAllChildCategoriesRecursive($categoryId, $allChildCategories, true);

        /** @var Category $childCategory */
        foreach ($allChildCategories as $childCategory)
        {
            $allCategoryIds[] = intval($childCategory->getPrimaryKeyValue());
        }

        $postParameters = new QueryParameters();
        $postParameters->setOrder('date_created DESC');
        $postParameters->in('category_id', $allCategoryIds);

        if ($onlyActive)
        {
            $postParameters->equals('status', 1);
        }

        if (!is_null($limit) && ((int) $limit) > 0)
        {
            $postParameters->setLimit($limit);
        }

        return Post::find($postParameters);
    }

    /**
     * @return Tag[]
     * @throws DatabaseException
     */
    public function getAllTags()
    {
        $tagParameters = new QueryParameters();
        $tagParameters->setOrder('name ASC');

        return Tag::find($tagParameters);
    }

    /**
     * @param $postId
     * @return Tag[]
     * @throws DatabaseException
     */
    public function getAllTagsForPostId($postId)
    {
        $postTagAssociations = PostTagAssociation::findAssociationsForColumnOne($postId);

        $tagIds = array_column($postTagAssociations, 'tag_id');

        if (count($tagIds) == 0)
        {
            return [];
        }

        $tagParameters = new QueryParameters();
        $tagParameters->setOrder('name ASC');
        $tagParameters->in('id', $tagIds);

        return Tag::find($tagParameters);
    }

    /**
     * @param $categoryId
     * @return Category|null
     * @throws DatabaseException
     */
    public function getCategoryById($categoryId)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->equals('id', $categoryId);

        return $this->findCategory($categoryParameters);
    }

    /**
     * @param $slug
     * @return Category|null
     * @throws DatabaseException
     */
    public function getCategoryBySlug($slug)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->equals('slug', $slug);

        return $this->findCategory($categoryParameters);
    }

    /**
     * @param null $limit
     * @return Post[]
     * @throws DatabaseException
     */
    public function getLatestPosts($limit = null)
    {
        $postParameters = new QueryParameters();
        $postParameters->setOrder('date_created DESC');
        $postParameters->equals('status', 1);

        if (!is_null($limit) && ((int) $limit) > 0)
        {
            $postParameters->setLimit($limit);
        }

        return Post::find($postParameters);
    }

    /**
     * @param $postId
     * @return Post|null
     * @throws DatabaseException
     */
    public function getPostById($postId)
    {
        $postParameters = new QueryParameters();
        $postParameters->equals('id', $postId);

        return $this->findPost($postParameters);
    }

    /**
     * @param $slug
     * @return Post|null
     * @throws DatabaseException
     */
    public function getPostBySlug($slug)
    {
        $postParameters = new QueryParameters();
        $postParameters->equals('slug', $slug);

        return $this->findPost($postParameters);
    }

    /**
     * @param $name
     * @return Tag|null
     * @throws DatabaseException
     */
    public function getTagByName($name)
    {
        $name = $this->createSlug($name);

        $tagParameters = new QueryParameters();
        $tagParameters->equals('name', $name);

        return Tag::findOne($tagParameters);
    }

    /**
     * @param $names
     * @return Tag[]
     * @throws DatabaseException
     */
    public function getTagsByNames(array $names)
    {
        foreach ($names as &$name)
        {
            $name = $this->createSlug($name);
        }

        $tagParameters = new QueryParameters();
        $tagParameters->in('name', $names, true);

        return Tag::find($tagParameters);
    }

    /**
     * @param $postId
     * @param array $tagIds
     * @return bool
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function removeTagsFromPostId($postId, array $tagIds)
    {
        $post = $this->getPostById($postId);

        if (is_null($post))
        {
            return true;
        }

        $tagQueryParameters = new QueryParameters();
        $tagQueryParameters->in('id', $tagIds);

        $tags = Tag::find($tagQueryParameters);

        /** @var Tag $tag */
        foreach ($tags as $tag)
        {
            $this->removePostTagAssociation($postId, $tag->getPrimaryKeyValue());
        }

        return true;
    }

    /**
     * @param $postId
     * @param array $tagNames
     * @return bool
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function removeTagNamesFromPostId($postId, array $tagNames)
    {
        $post = $this->getPostById($postId);

        if (is_null($post))
        {
            return true;
        }

        $tagQueryParameters = new QueryParameters();
        $tagQueryParameters->in('name', $tagNames, true);

        $tags = Tag::find($tagQueryParameters);

        /** @var Tag $tag */
        foreach ($tags as $tag)
        {
            $this->removePostTagAssociation($postId, $tag->getPrimaryKeyValue());
        }

        return true;
    }

    /**
     * @param Category $category
     * @param $categoryId
     * @return Category|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function updateCategory(Category $category, $categoryId)
    {
        $category->setId((int) $categoryId);

        return $this->saveCategory($category);
    }

    /**
     * @param Post $post
     * @param $postId
     * @return Post|null
     * @throws DatabaseException
     * @throws ValidationException
     */
    public function updatePost(Post $post, $postId)
    {
        $post->setId((int) $postId);

        return $this->savePost($post);
    }

    /**
     * @param $postId
     * @param $tagId
     * @throws DatabaseException
     * @throws ValidationException
     */
    private function createPostTagAssociation($postId, $tagId)
    {
        $postTagAssociation = new PostTagAssociation($postId, $tagId);
        $postTagAssociation->save();
    }

    /**
     * @param $postId
     * @param $tagId
     * @throws DatabaseException
     * @throws ValidationException
     */
    private function removePostTagAssociation($postId, $tagId)
    {
        /** @var PostTagAssociation $postTagAssociation */
        $postTagAssociation = PostTagAssociation::findAssociation($postId, $tagId);
        if (!is_null($postTagAssociation))
        {
            $postTagAssociation->delete();
        }
    }

    /**
     * @param $s
     * @return string
     */
    private function createSlug($s)
    {
        $s = str_replace('-', '_', trim($s));
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $s));
    }

    /**
     * @param $postId
     * @throws DatabaseException
     * @throws ValidationException
     */
    private function deletePostTagAssociationsForPostId($postId)
    {
        $postTagAssociations = PostTagAssociation::findAssociationsForColumnOne($postId);

        /** @var PostTagAssociation $postTagAssociation */
        foreach ($postTagAssociations as $postTagAssociation)
        {
            $postTagAssociation->delete();
        }
    }

    /**
     * @param $parentId
     * @param $childCategories
     * @param bool $onlyActive
     * @throws DatabaseException
     */
    private function getAllChildCategoriesRecursive($parentId, &$childCategories, bool $onlyActive)
    {
        $categoryParameters = new QueryParameters();
        $categoryParameters->equals('parent_id', $parentId);

        if ($onlyActive)
        {
            $categoryParameters->equals('status', 1);
        }

        /** @var Category $childCategory */
        foreach (Category::find($categoryParameters) as $childCategory)
        {
            $childCategories[] = $childCategory;

            $this->getAllChildCategoriesRecursive($childCategory->getPrimaryKeyValue(), $childCategories, $onlyActive);
        }
    }

    /**
     * @param QueryParameters $queryParameters
     * @return Category|null
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    private function findCategory(QueryParameters $queryParameters)
    {
        return Category::findOne($queryParameters);
    }

    /**
     * @param QueryParameters $queryParameters
     * @return Post|null
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    private function findPost(QueryParameters $queryParameters)
    {
        return Post::findOne($queryParameters);
    }

    /**
     * @param Category $category
     * @return Category|null
     * @throws ValidationException
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    private function saveCategory(Category $category)
    {
        $category->setName(trim($category->getName()));
        $newSlug = $this->createSlug($category->getName());
        $category->slug = $newSlug;

        if (is_null($category->getStatus()))
        {
            $category->setStatus(1);
        }

        $previousCategory = Category::findById($category->getPrimaryKeyValue());
        if (is_null($previousCategory) || $previousCategory->getSlug() != $newSlug)
        {
            $categoryParameters = new QueryParameters();
            $categoryParameters->equals('slug', $newSlug);

            $existingCategoryWithNewSlug = Category::findOne($categoryParameters);

            if (!is_null($existingCategoryWithNewSlug))
            {
                throw new ValidationException($category, ['Category already exists with name: ' . $category->getName()]);
            }
        }

        return $category->save();
    }

    /**
     * @param Post $post
     * @return Post|null
     * @throws ValidationException
     * @throws DatabaseException
     */
    private function savePost(Post $post)
    {
        $post->setTitle(trim($post->getTitle()));
        $newSlug = $this->createSlug($post->getTitle());
        $post->slug = $newSlug;

        $previousPost = Post::findById($post->getPrimaryKeyValue());
        if (is_null($previousPost) || $previousPost->getSlug() != $newSlug)
        {
            $postParameters = new QueryParameters();
            $postParameters->equals('slug', $newSlug);

            $existingPostWithNewSlug = Post::findOne($postParameters);

            if (!is_null($existingPostWithNewSlug))
            {
                throw new ValidationException($post, ['Post already exists with title: ' . $post->getTitle()]);
            }
        }

        return $post->save();
    }

    /**
     * @param Tag $tag
     * @return Tag|null
     * @throws ValidationException
     * @throws DatabaseException
     */
    private function saveTag(Tag $tag)
    {
        $tag->setName($this->createSlug($tag->getName()));

        $tagParameters = new QueryParameters();
        $tagParameters->equals('name', $tag->getName());

        $createdTag = Tag::findOne($tagParameters);

        if (is_null($createdTag))
        {
            $createdTag = $tag->save();
        }

        return $createdTag;
    }

}