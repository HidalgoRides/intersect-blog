<?php

namespace Tests\Services;

use Intersect\Blog\Models\Tag;
use Intersect\Blog\Models\Post;
use PHPUnit\Framework\TestCase;
use Intersect\Blog\Models\Category;
use Intersect\Blog\Services\BlogService;
use Intersect\Blog\Models\PostTagAssociation;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;
use Intersect\Database\Exception\ValidationException;

class BlogServiceTest extends TestCase {

    private static $PUBLISHED = 1;
    private static $DISABLED = 2;

    /** @var BlogService */
    private $blogService;

    public function setUp()
    {
        parent::setUp();

        $this->blogService = new BlogService();

        $connectionSettings = new ConnectionSettings('db', 'root', 'password', 3306, 'integration_tests');
        $connection = ConnectionFactory::get('mysql', $connectionSettings);

        $connection->query('truncate ib_post_tags');
        $connection->query('truncate ib_tags');
        $connection->query('truncate ib_posts');
        $connection->query('truncate ib_categories');
    }

    public function test_addTagsToPostId()
    {
        $tagName = 'test-tag';
        $createdPost = $this->blogService->createPost($this->getSamplePost());

        $existingTags = $this->blogService->getAllTagsForPostId($createdPost->id);
        $this->assertEmpty($existingTags);

        $this->blogService->addTagsToPostId($createdPost->getId(), [$tagName]);

        $existingTags = $this->blogService->getAllTagsForPostId($createdPost->getId());
        $this->assertEquals(1, count($existingTags));

        $existingTag = $existingTags[0];
        $this->assertEquals($tagName, $existingTag->getName());
    }

    public function test_addTagsToPostId_postNotFound()
    {
        $this->assertTrue($this->blogService->addTagsToPostId(9999, ['post-not-found']));
    }

    public function test_createCategory()
    {
        $category = $this->getSampleCategory();

        $createdCategory = $this->blogService->createCategory($category);

        $this->assertNotNull($createdCategory);
        $this->assertEquals($category->getName(), $createdCategory->getName());
        $this->assertNotNull($createdCategory->getSlug());
        $this->assertEquals(self::$PUBLISHED, $createdCategory->getStatus());
    }

    public function test_createPost()
    {
        $post = $this->getSamplePost();

        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($createdPost);
        $this->assertEquals($post->getTitle(), $createdPost->getTitle());
        $this->assertEquals($post->getBody(), $createdPost->getBody());
        $this->assertNotNull($createdPost->getSlug());
        $this->assertEquals(self::$PUBLISHED, $createdPost->getStatus());
    }

    public function test_createPost_withMetaData()
    {
        $post = $this->getSamplePost();
        $post->addMetaData('meta', 'data');

        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($createdPost);
        $this->assertEquals($post->getTitle(), $createdPost->getTitle());
        $this->assertEquals($post->getBody(), $createdPost->getBody());
        $this->assertNotNull($createdPost->getSlug());

        $metaData = $createdPost->getMetaData();

        $this->assertNotNull($metaData);
        $this->assertEquals('data', $createdPost->getMetaDataByKey('meta'));
    }

    public function test_createPostWithTags()
    {
        $post = $this->getSamplePost();
        $tagName = 'createpostwithtags';

        $createdPost = $this->blogService->createPostWithTags($post, [$tagName]);

        $this->assertNotNull($createdPost);
        $this->assertEquals($post->getTitle(), $createdPost->getTitle());
        $this->assertEquals($post->getBody(), $createdPost->getBody());
        $this->assertNotNull($createdPost->getSlug());

        $createdTags = $this->blogService->getAllTagsForPostId($createdPost->getId());
        $this->assertNotEmpty($createdTags);
        $this->assertEquals(1, count($createdTags));

        $tag = $createdTags[0];
        $this->assertEquals($tagName, $tag->getName());
    }

    public function test_createTag()
    {
        $tag = $this->getSampleTag();

        $createdTag = $this->blogService->createTag($tag);

        $this->assertNotNull($createdTag);
        $this->assertEquals($tag->getName(), $createdTag->getName());
    }

    public function test_deletePostById()
    {
        $post = $this->getSamplePost();
        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($this->blogService->getPostById($createdPost->getId()));

        $this->assertTrue($this->blogService->deletePostById($createdPost->getId()));
        $this->assertNull($this->blogService->getPostById($createdPost->getId()));
    }

    public function test_deletePostById_postNotFound()
    {
        $this->assertFalse($this->blogService->deletePostById(9999));
    }

    public function test_deletePostById_verifyDeletionOfPostTagAssociations()
    {
        $post = $this->getSamplePost();
        $createdPost = $this->blogService->createPostWithTags($post, ['delete-me']);

        $this->assertNotNull($this->blogService->getPostById($createdPost->getId()));

        $associations = PostTagAssociation::findAssociationsForColumnOne($createdPost->getId());
        $this->assertNotNull($associations);
        $this->assertEquals(1, count($associations));

        $this->assertTrue($this->blogService->deletePostById($createdPost->getId()));
        $this->assertNull($this->blogService->getPostById($createdPost->getId()));

        $associations = PostTagAssociation::findAssociationsForColumnOne($createdPost->getId());
        $this->assertNotNull($associations);
        $this->assertEquals(0, count($associations));
    }

    public function test_deleteCategoryById()
    {
        $category = $this->getSampleCategory();
        $createdCategory = $this->blogService->createCategory($category);

        $this->assertNotNull($this->blogService->getCategoryById($createdCategory->getId()));

        $this->assertTrue($this->blogService->deleteCategoryById($createdCategory->getId()));
        $this->assertNull($this->blogService->getCategoryById($createdCategory->getId()));
    }

    public function test_deleteCategoryById_categoryNotFound()
    {
        $this->assertFalse($this->blogService->deleteCategoryById(9999));
    }

    public function test_disableCategoryById()
    {
        $category = $this->getSampleCategory();
        $createdCategory = $this->blogService->createCategory($category);

        $this->assertNotNull($createdCategory);
        $this->assertEquals(self::$PUBLISHED, $createdCategory->getStatus());

        $this->assertNotFalse($this->blogService->disableCategoryById($createdCategory->getId()));

        $category = $this->blogService->getCategoryById($createdCategory->getId());
        $this->assertEquals(self::$DISABLED, $category->getStatus());
    }

    public function test_disableCategoryById_categoryNotFound()
    {
        $this->assertFalse($this->blogService->disableCategoryById(9999));
    }

    public function test_disablePostById()
    {
        $post = $this->getSamplePost();
        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($createdPost);
        $this->assertEquals(self::$PUBLISHED, $createdPost->getStatus());

        $this->assertNotFalse($this->blogService->disablePostById($createdPost->getId()));

        $post = $this->blogService->getPostById($createdPost->getId());
        $this->assertEquals(self::$DISABLED, $post->getStatus());
    }

    public function test_disablePostById_postNotFound()
    {
        $this->assertFalse($this->blogService->disablePostById(9999));
    }

    public function test_getAllCategories()
    {
        $this->blogService->createCategory($this->getSampleCategory());

        $disabledCategory = $this->getSampleCategory(self::$DISABLED);
        $this->blogService->createCategory($disabledCategory);

        $categories = $this->blogService->getAllCategories();

        $this->assertNotEmpty($categories);
        $this->assertEquals(1, count($categories));
    }

    public function test_getAllCategories_withLimit()
    {
        $this->blogService->createCategory($this->getSampleCategory());
        $this->blogService->createCategory($this->getSampleCategory());

        $categories = $this->blogService->getAllCategories(1);

        $this->assertNotEmpty($categories);
        $this->assertEquals(1, count($categories));
    }

    public function test_getAllCategories_allStatuses()
    {
        $this->blogService->createCategory($this->getSampleCategory());
        $this->blogService->createCategory($this->getSampleCategory());

        $disabledCategory = $this->getSampleCategory(self::$DISABLED);
        $this->blogService->createCategory($disabledCategory);

        $categories = $this->blogService->getAllCategories(null, false);

        $this->assertNotEmpty($categories);
        $this->assertEquals(3, count($categories));
    }

    public function test_getAllChildCategories()
    {
        $parentCategory = $this->blogService->createCategory($this->getSampleCategory());

        $childCategory = $this->getSampleCategory();
        $childCategory->setParentId($parentCategory->getId());

        $createdChildCategory = $this->blogService->createCategory($childCategory);

        $this->assertEquals(2, count(Category::find()));

        $childCategories = $this->blogService->getAllChildCategories($parentCategory->getId());
        $this->assertEquals(1, count($childCategories));

        $this->assertEquals($createdChildCategory->getId(), $childCategories[0]->getId());
    }

    public function test_getAllRootCategories()
    {
        $parentCategory = $this->blogService->createCategory($this->getSampleCategory());

        $childCategory = $this->getSampleCategory();
        $childCategory->setParentId($parentCategory->getId());

        $this->blogService->createCategory($childCategory);

        $this->assertEquals(2, count(Category::find()));

        $rootCategories = $this->blogService->getAllRootCategories();
        $this->assertEquals(1, count($rootCategories));

        $this->assertEquals($parentCategory->getId(), $rootCategories[0]->getId());
    }

    public function test_getAllActivePosts()
    {
        $this->blogService->createPost($this->getSamplePost());
        $this->blogService->createPost($this->getSamplePost(self::$DISABLED));

        $posts = $this->blogService->getAllActivePosts();

        $this->assertEquals(1, count($posts));
    }

    public function test_getAllActivePosts_withLimit()
    {
        $this->blogService->createPost($this->getSamplePost());
        $this->blogService->createPost($this->getSamplePost());

        $posts = $this->blogService->getAllActivePosts(1);

        $this->assertEquals(1, count($posts));
    }    

    public function test_getAllPosts()
    {
        $this->blogService->createPost($this->getSamplePost());
        $this->blogService->createPost($this->getSamplePost());

        $posts = $this->blogService->getAllPosts();

        $this->assertEquals(2, count($posts));
    }

    public function test_getAllPosts_withLimit()
    {
        $this->blogService->createPost($this->getSamplePost());

        $disabledPost = $this->getSamplePost(self::$DISABLED);
        $this->blogService->createPost($disabledPost);

        $posts = $this->blogService->getAllPosts(1);

        $this->assertEquals(1, count($posts));
    }

    public function test_getAllPostsWithTagName()
    {
        $tagName = 'test';
        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName]);
        $this->blogService->createPostWithTags($this->getSamplePost(), ['not-test']);

        $allPosts = $this->blogService->getAllPostsWithTagName($tagName);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(1, count($allPosts));
    }

    public function test_getAllPostsWithTagName_withLimit()
    {
        $tagName = 'test';
        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName]);
        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName]);

        $allPosts = $this->blogService->getAllPostsWithTagName($tagName, 1);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(1, count($allPosts));
    }

    public function test_getAllPostsWithTagName_tagNotFound()
    {
        $this->assertEmpty($this->blogService->getAllPostsWithTagName('not-found'));
    }

    public function test_getAllPostsWithTagName_noPostsAssociated()
    {
        $tag = $this->getSampleTag();

        $createdTag = $this->blogService->createTag($tag);
        $this->assertNotNull($createdTag);

        $this->assertEmpty($this->blogService->getAllPostsWithTagName($createdTag->getName()));
    }

    public function test_getAllPostsWithTagNames()
    {
        $tagName1 = 'tag-name-1';
        $tagName2 = 'tag-name-2';

        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName1]);
        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName2]);

        $allPosts = $this->blogService->getAllPostsWithTagNames([
            $tagName1,
            $tagName2
        ]);

        $this->assertNotEmpty($allPosts);
        $this->assertEquals(2, count($allPosts));
    }

    public function test_getAllPostsWithTagNames_withLimit()
    {
        $tagName1 = 'tag-name-1';
        $tagName2 = 'tag-name-2';

        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName1]);
        $this->blogService->createPostWithTags($this->getSamplePost(), [$tagName2]);

        $allPosts = $this->blogService->getAllPostsWithTagNames([
            $tagName1,
            $tagName2
        ], 1);

        $this->assertNotEmpty($allPosts);
        $this->assertEquals(1, count($allPosts));
    }

    public function test_getAllPostsInCategoryId()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());
        $categoryId = $category->getId();

        $post = $this->getSamplePost();
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $post = $this->getSamplePost();
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $allPosts = $this->blogService->getAllPostsInCategoryId($categoryId);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(2, count($allPosts));
    }

    public function test_getAllPostsInCategoryId_includesChildrenCategories()
    {
        $parentCategory = $this->blogService->createCategory($this->getSampleCategory());
        $parentCategoryId = $parentCategory->getId();

        $childCategory = $this->getSampleCategory();
        $childCategory->setParentId($parentCategoryId);
        $childCategory = $this->blogService->createCategory($childCategory);

        $post = $this->getSamplePost();
        $post->setCategoryId($parentCategoryId);
        $this->blogService->createPost($post);

        $post = $this->getSamplePost();
        $post->setCategoryId($childCategory->getId());
        $this->blogService->createPost($post);

        $allPosts = $this->blogService->getAllPostsInCategoryId($parentCategoryId);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(2, count($allPosts));
    }

    public function test_getAllPostsInCategoryId_categoryNotFound()
    {
        $this->assertEmpty($this->blogService->getAllPostsInCategoryId(9999));
    }

    public function test_getAllPostsInCategoryId_withLimit()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());
        $categoryId = $category->getId();

        $post = $this->getSamplePost();
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $post = $this->getSamplePost();
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $allPosts = $this->blogService->getAllPostsInCategoryId($categoryId, true, 1);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(1, count($allPosts));
    }

    public function test_getAllPostsInCategoryId_allStatuses()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());
        $categoryId = $category->getId();

        $post = $this->getSamplePost(self::$PUBLISHED);
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $post = $this->getSamplePost(self::$DISABLED);
        $post->setCategoryId($categoryId);
        $this->blogService->createPost($post);

        $allPosts = $this->blogService->getAllPostsInCategoryId($categoryId, true);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(1, count($allPosts));

        $allPosts = $this->blogService->getAllPostsInCategoryId($categoryId, false);
        $this->assertNotEmpty($allPosts);
        $this->assertEquals(2, count($allPosts));
    }

    public function test_getAllTags()
    {
        $this->blogService->createTag($this->getSampleTag());
        $this->blogService->createTag($this->getSampleTag());
        $this->blogService->createTag($this->getSampleTag());

        $allTags = $this->blogService->getAllTags();
        $this->assertNotEmpty($allTags);
        $this->assertEquals(3, count($allTags));
    }

    public function test_getAllTagsForPostId()
    {
        $post = $this->blogService->createPostWithTags($this->getSamplePost(), [
            'test-one',
            'test-two',
            'test-three'
        ]);

        $allTags = $this->blogService->getAllTagsForPostId($post->getId());
        $this->assertNotEmpty($allTags);
        $this->assertEquals(3, count($allTags));
    }

    public function test_getCategoryById()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());

        $this->assertNotNull($this->blogService->getCategoryById($category->getId()));
    }

    public function test_getCategoryById_notFound()
    {
        $this->assertNull($this->blogService->getCategoryById(9999));
    }

    public function test_getCategoryBySlug()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());

        $this->assertNotNull($this->blogService->getCategoryBySlug($category->getSlug()));
    }

    public function test_getCategoryBySlug_notFound()
    {
        $this->assertNull($this->blogService->getCategoryById('not-found'));
    }

    public function test_getLatestPosts_hasMetaData_asGetter()
    {
        $post = $this->getSamplePost();
        $post->addMetaData('meta', 'data');

        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($createdPost);

        $latestPosts = $this->blogService->getLatestPosts(1);

        $this->assertTrue(count($latestPosts) == 1);
        $metaData = $createdPost->getMetaData();
        $this->assertNotNull($metaData);
        $this->assertEquals('data', $createdPost->getMetaDataByKey('meta'));
    }

    public function test_getLatestPosts_hasMetaData_asAttribute()
    {
        $post = $this->getSamplePost();
        $post->addMetaData('meta', 'data');

        $createdPost = $this->blogService->createPost($post);

        $this->assertNotNull($createdPost);

        $latestPosts = $this->blogService->getLatestPosts(1);

        $this->assertTrue(count($latestPosts) == 1);
        $metaData = $createdPost->meta_data;
        $this->assertNotNull($metaData);
        $this->assertEquals('data', $createdPost->getMetaDataByKey('meta'));
    }

    public function test_getPostById()
    {
        $post = $this->blogService->createPost($this->getSamplePost());

        $this->assertNotNull($this->blogService->getPostById($post->getId()));
    }

    public function test_getPostById_notFound()
    {
        $this->assertNull($this->blogService->getPostById(9999));
    }

    public function test_getPostBySlug()
    {
        $post = $this->blogService->createPost($this->getSamplePost());

        $this->assertNotNull($this->blogService->getPostBySlug($post->getSlug()));
    }

    public function test_getPostBySlug_notFound()
    {
        $this->assertNull($this->blogService->getPostBySlug('not-found'));
    }

    public function test_getTagByName()
    {
        $tag = $this->getSampleTag();

        $this->blogService->createTag($tag);

        $this->assertNotNull($this->blogService->getTagByName($tag->getName()));
    }

    public function test_getTagByName_notFound()
    {
        $this->assertNull($this->blogService->getTagByName('not-found'));
    }

    public function test_getTagsByNames()
    {
        $tag1 = $this->getSampleTag();
        $this->blogService->createTag($tag1);

        $tag2 = $this->getSampleTag();
        $this->blogService->createTag($tag2);

        $tags = $this->blogService->getTagsByNames([$tag1->getName(), $tag2->getName()]);
        $this->assertNotEmpty($tags);
        $this->assertCount(2, $tags);
    }

    public function test_publishCategory()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory(self::$DISABLED));

        $this->assertNotNull($category->getId());
        $this->assertEquals(self::$DISABLED, $category->getStatus());

        $this->blogService->publishCategory($category);

        $this->assertEquals(self::$PUBLISHED, $category->getStatus());
    }

    public function test_publishPost()
    {
        $post = $this->blogService->createPost($this->getSamplePost(self::$DISABLED));

        $this->assertNotNull($post->getId());
        $this->assertEquals(self::$DISABLED, $post->getStatus());

        $this->blogService->publishPost($post);

        $this->assertEquals(self::$PUBLISHED, $post->getStatus());
    }

    public function test_removeTagsFromPostId()
    {
        $post = $this->blogService->createPostWithTags($this->getSamplePost(), [
            'test',
            'remove'
        ]);

        $tags = $this->blogService->getAllTagsForPostId($post->getId());
        $this->assertEquals(2, count($tags));

        $tagId = null;
        foreach ($tags as $tag)
        {
            if ($tag->getName() == 'remove')
            {
                $tagId = $tag->getId();
            }
        }

        if (is_null($tagId))
        {
            $this->fail('Tag not found by name');
        }

        $this->assertTrue($this->blogService->removeTagsFromPostId($post->getId(), [$tagId]));

        $tags = $this->blogService->getAllTagsForPostId($post->getId());
        $this->assertEquals(1, count($tags));
        $this->assertEquals('test', $tags[0]->getName());
    }

    public function test_removeTagsFromPostId_postNotFound()
    {
        $this->assertTrue($this->blogService->removeTagsFromPostId(9999, []));
    }

    public function test_removeTagNamesFromPostId()
    {
        $post = $this->blogService->createPostWithTags($this->getSamplePost(), [
            'test',
            'remove'
        ]);

        $this->assertEquals(2, count($this->blogService->getAllTagsForPostId($post->getId())));

        $this->assertTrue($this->blogService->removeTagNamesFromPostId($post->getId(), ['remove']));

        $tags = $this->blogService->getAllTagsForPostId($post->getId());
        $this->assertEquals(1, count($tags));
        $this->assertEquals('test', $tags[0]->getName());
    }

    public function test_removeTagNamesFromPostId_postNotFound()
    {
        $this->assertTrue($this->blogService->removeTagNamesFromPostId(9999, []));
    }

    public function test_updateCategory()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());

        $categoryName = 'Updated category';
        $category->name = $categoryName;

        $updatedCategory = $this->blogService->updateCategory($category, $category->getId());

        $this->assertNotNull($updatedCategory);
        $this->assertEquals($categoryName, $updatedCategory->getName());
    }

    public function test_updateCategory_categoryNameExists()
    {
        $category = $this->blogService->createCategory($this->getSampleCategory());
        $secondCategory = $this->blogService->createCategory($this->getSampleCategory());

        $secondCategory->name = $category->getName();

        $this->expectException(ValidationException::class);

        $this->blogService->updateCategory($secondCategory, $secondCategory->getId());
    }

    public function test_updatePost()
    {
        $post = $this->blogService->createPost($this->getSamplePost());

        $title = 'Updated title';
        $post->setTitle($title);

        $updatedPost = $this->blogService->updatePost($post, $post->getId());

        $this->assertNotNull($updatedPost);
        $this->assertEquals($title, $updatedPost->getTitle());
    }

    public function test_updatePost_titleExists()
    {
        $post = $this->blogService->createPost($this->getSamplePost());
        $secondPost = $this->blogService->createPost($this->getSamplePost());

        $secondPost->setTitle($post->title);

        $this->expectException(ValidationException::class);

        $this->blogService->updatePost($secondPost, $secondPost->getId());
    }

    private function getSampleCategory($status = 1)
    {
        $name = 'Test Category ' . uniqid();

        $category = new Category();
        $category->setName($name);
        $category->setStatus($status);

        return $category;
    }

    private function getSamplePost($status = 1)
    {
        $title = 'Test Title ' . uniqid();
        $body = 'Test Body';

        $post = new Post();
        $post->setTitle($title);
        $post->setBody($body);
        $post->setAuthorId(1);
        $post->setStatus($status);

        return $post;
    }

    private function getSampleTag()
    {
        $name = 'tag-' . uniqid();

        $tag = new Tag();
        $tag->setName($name);

        return $tag;
    }

}