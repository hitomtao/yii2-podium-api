<?php

declare(strict_types=1);

namespace bizley\podium\tests\member;

use bizley\podium\api\enums\MemberStatus;
use bizley\podium\api\models\member\MemberRemover;
use bizley\podium\api\repos\MemberRepo;
use bizley\podium\tests\DbTestCase;
use yii\base\Event;

/**
 * Class MemberRemoverTest
 * @package bizley\podium\tests\member
 */
class MemberRemoverTest extends DbTestCase
{
    /**
     * @var array
     */
    public $fixtures = [
        'podium_member' => [
            [
                'id' => 100,
                'user_id' => '100',
                'username' => 'member1',
                'slug' => 'member1',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected static $eventsRaised = [];

    public function testRemove(): void
    {
        Event::on(MemberRemover::class, MemberRemover::EVENT_BEFORE_REMOVING, function () {
            static::$eventsRaised[MemberRemover::EVENT_BEFORE_REMOVING] = true;
        });
        Event::on(MemberRemover::class, MemberRemover::EVENT_AFTER_REMOVING, function () {
            static::$eventsRaised[MemberRemover::EVENT_AFTER_REMOVING] = true;
        });

        $this->assertTrue($this->podium()->member->remove(MemberRemover::findOne(100))->result);

        $this->assertEmpty(MemberRepo::findOne(100));

        $this->assertArrayHasKey(MemberRemover::EVENT_BEFORE_REMOVING, static::$eventsRaised);
        $this->assertArrayHasKey(MemberRemover::EVENT_AFTER_REMOVING, static::$eventsRaised);
    }

    public function testRemoveEventPreventing(): void
    {
        $handler = function ($event) {
            $event->canRemove = false;
        };
        Event::on(MemberRemover::class, MemberRemover::EVENT_BEFORE_REMOVING, $handler);

        $this->assertFalse($this->podium()->member->remove(MemberRemover::findOne(100))->result);

        $this->assertNotEmpty(MemberRepo::findOne(100));

        Event::off(MemberRemover::class, MemberRemover::EVENT_BEFORE_REMOVING, $handler);
    }

    public function testFailedRemove(): void
    {
        $mock = $this->getMockBuilder(MemberRemover::class)->setMethods(['delete'])->getMock();
        $mock->method('delete')->willReturn(false);

        $this->assertFalse($mock->remove()->result);
    }

    public function testExceptionRemove(): void
    {
        $mock = $this->getMockBuilder(MemberRemover::class)->setMethods(['delete'])->getMock();
        $mock->method('delete')->will($this->throwException(new \Exception()));

        $this->assertFalse($mock->remove()->result);
    }
}
