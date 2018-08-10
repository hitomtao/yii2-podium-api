<?php

declare(strict_types=1);

namespace bizley\podium\tests\base;

use bizley\podium\api\enums\MemberStatus;
use bizley\podium\api\models\forum\Forum;
use bizley\podium\api\models\thread\ThreadMover;
use bizley\podium\api\repos\ThreadRepo;
use bizley\podium\tests\DbTestCase;
use yii\base\Event;

/**
 * Class ThreadMoverTest
 * @package bizley\podium\tests\base
 */
class ThreadMoverTest extends DbTestCase
{
    /**
     * @var array
     */
    public $fixtures = [
        'podium_member' => [
            [
                'id' => 1,
                'user_id' => '1',
                'username' => 'member',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_category' => [
            [
                'id' => 1,
                'author_id' => 1,
                'name' => 'category1',
                'slug' => 'category1',
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_forum' => [
            [
                'id' => 1,
                'category_id' => 1,
                'author_id' => 1,
                'name' => 'forum1',
                'slug' => 'forum1',
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'id' => 2,
                'category_id' => 1,
                'author_id' => 1,
                'name' => 'forum2',
                'slug' => 'forum2',
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_thread' => [
            [
                'id' => 1,
                'category_id' => 1,
                'forum_id' => 1,
                'author_id' => 1,
                'name' => 'thread1',
                'slug' => 'thread1',
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $eventsRaised = [];

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

    public function testMove(): void
    {
        Event::on(ThreadMover::class, ThreadMover::EVENT_BEFORE_MOVING, function () {
            $this->eventsRaised[ThreadMover::EVENT_BEFORE_MOVING] = true;
        });
        Event::on(ThreadMover::class, ThreadMover::EVENT_AFTER_MOVING, function () {
            $this->eventsRaised[ThreadMover::EVENT_AFTER_MOVING] = true;
        });

        $this->assertTrue($this->podium()->thread->move(ThreadMover::findOne(1), Forum::findOne(2)));
        $this->assertEquals(1, ThreadRepo::findOne(1)->category_id);
        $this->assertEquals(2, ThreadRepo::findOne(1)->forum_id);

        $this->assertArrayHasKey(ThreadMover::EVENT_BEFORE_MOVING, $this->eventsRaised);
        $this->assertArrayHasKey(ThreadMover::EVENT_AFTER_MOVING, $this->eventsRaised);
    }

    public function testMoveEventPreventing(): void
    {
        $handler = function ($event) {
            $event->canMove = false;
        };
        Event::on(ThreadMover::class, ThreadMover::EVENT_BEFORE_MOVING, $handler);

        $this->assertFalse($this->podium()->thread->move(ThreadMover::findOne(1), Forum::findOne(2)));
        $this->assertEquals(1, ThreadRepo::findOne(1)->forum_id);

        Event::off(ThreadMover::class, ThreadMover::EVENT_BEFORE_MOVING, $handler);
    }
}