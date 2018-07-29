<?php

declare(strict_types=1);

namespace bizley\podium\tests\base;

use bizley\podium\tests\DbTestCase;
use yii\data\ActiveDataFilter;

/**
 * Class CategoryTest
 * @package bizley\podium\tests\base
 */
class CategoryTest extends DbTestCase
{
    /**
     * @var array
     */
    public $fixtures = [
        'podium_category' => [
            [
                'id' => 1,
                'author_id' => 1,
                'name' => 'category1',
                'slug' => 'category1',
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'id' => 2,
                'author_id' => 1,
                'name' => 'category2',
                'slug' => 'category2',
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
    ];

    /**
     * @throws \yii\db\Exception
     */
    protected function setUp(): void
    {
        $this->fixturesUp();
    }

    /**
     * @throws \yii\db\Exception
     */
    protected function tearDown(): void
    {
        $this->fixturesDown();
    }

    public function testGetCategoryById(): void
    {
        $category = $this->podium()->category->getCategoryById(1);
        $this->assertEquals(1, $category->getId());
    }

    public function testNonExistingCategory(): void
    {
        $this->assertEmpty($this->podium()->category->getCategoryById(999));
    }

    public function testGetCategoriesByFilterEmpty(): void
    {
        $categories = $this->podium()->category->getCategories();
        $this->assertEquals(2, $categories->getTotalCount());
        $this->assertEquals([1, 2], $categories->getKeys());
    }

    public function testGetCategoriesByFilter(): void
    {
        $filter = new ActiveDataFilter([
            'searchModel' => function () {
                return (new \yii\base\DynamicModel(['id' => null]))->addRule('id', 'integer');
            }
        ]);
        $filter->load(['filter' => ['id' => 2]], '');
        $categories = $this->podium()->category->getCategories($filter);
        $this->assertEquals(1, $categories->getTotalCount());
        $this->assertEquals([2], $categories->getKeys());
    }
}