<?php

declare(strict_types=1);

namespace bizley\podium\tests\account;

use bizley\podium\api\enums\AcquaintanceType;
use bizley\podium\api\enums\MemberStatus;
use bizley\podium\api\models\member\Friendship;
use bizley\podium\api\models\member\Member;
use bizley\podium\api\repos\AcquaintanceRepo;
use bizley\podium\tests\AccountTestCase;
use bizley\podium\tests\props\UserIdentity;
use yii\base\Event;

/**
 * Class MemberFriendshipTest
 * @package bizley\podium\tests\account
 */
class AccountFriendshipTest extends AccountTestCase
{
    /**
     * @var array
     */
    public $fixtures = [
        'podium_member' => [
            [
                'id' => 10,
                'user_id' => '10',
                'username' => 'member1',
                'slug' => 'member1',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'id' => 11,
                'user_id' => '11',
                'username' => 'member2',
                'slug' => 'member2',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'id' => 12,
                'user_id' => '12',
                'username' => 'member3',
                'slug' => 'member3',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_acquaintance' => [
            [
                'member_id' => 10,
                'target_id' => 12,
                'type_id' => AcquaintanceType::FRIEND,
                'created_at' => 1,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected static $eventsRaised = [];

    /**
     * @throws \yii\db\Exception
     */
    protected function setUp(): void
    {
        $this->fixturesUp();
        \Yii::$app->user->setIdentity(new UserIdentity(['id' => '10']));
    }

    /**
     * @throws \yii\db\Exception
     */
    protected function tearDown(): void
    {
        $this->fixturesDown();
        parent::tearDown();
    }

    public function testBefriend(): void
    {
        Event::on(Friendship::class, Friendship::EVENT_BEFORE_BEFRIENDING, function () {
            static::$eventsRaised[Friendship::EVENT_BEFORE_BEFRIENDING] = true;
        });
        Event::on(Friendship::class, Friendship::EVENT_AFTER_BEFRIENDING, function () {
            static::$eventsRaised[Friendship::EVENT_AFTER_BEFRIENDING] = true;
        });

        $this->assertTrue($this->podium()->account->befriend(Member::findOne(11))->result);

        $this->assertNotEmpty(AcquaintanceRepo::findOne([
            'member_id' => 10,
            'target_id' => 11,
            'type_id' => AcquaintanceType::FRIEND,
        ]));

        $this->assertArrayHasKey(Friendship::EVENT_BEFORE_BEFRIENDING, static::$eventsRaised);
        $this->assertArrayHasKey(Friendship::EVENT_AFTER_BEFRIENDING, static::$eventsRaised);
    }

    public function testBefriendEventPreventing(): void
    {
        $handler = function ($event) {
            $event->canBeFriends = false;
        };
        Event::on(Friendship::class, Friendship::EVENT_BEFORE_BEFRIENDING, $handler);

        $this->assertFalse($this->podium()->account->befriend(Member::findOne(11))->result);

        $this->assertEmpty(AcquaintanceRepo::findOne([
            'member_id' => 10,
            'target_id' => 11,
            'type_id' => AcquaintanceType::FRIEND,
        ]));

        Event::off(Friendship::class, Friendship::EVENT_BEFORE_BEFRIENDING, $handler);
    }

    public function testBefriendAgain(): void
    {
        $this->assertFalse($this->podium()->account->befriend(Member::findOne(12))->result);
    }

    public function testUnfriend(): void
    {
        Event::on(Friendship::class, Friendship::EVENT_BEFORE_UNFRIENDING, function () {
            static::$eventsRaised[Friendship::EVENT_BEFORE_UNFRIENDING] = true;
        });
        Event::on(Friendship::class, Friendship::EVENT_AFTER_UNFRIENDING, function () {
            static::$eventsRaised[Friendship::EVENT_AFTER_UNFRIENDING] = true;
        });

        $this->assertTrue($this->podium()->account->unfriend(Member::findOne(12))->result);

        $this->assertEmpty(AcquaintanceRepo::findOne([
            'member_id' => 10,
            'target_id' => 12,
            'type_id' => AcquaintanceType::FRIEND,
        ]));

        $this->assertArrayHasKey(Friendship::EVENT_BEFORE_UNFRIENDING, static::$eventsRaised);
        $this->assertArrayHasKey(Friendship::EVENT_AFTER_UNFRIENDING, static::$eventsRaised);
    }

    public function testUnfriendEventPreventing(): void
    {
        $handler = function ($event) {
            $event->canUnfriend = false;
        };
        Event::on(Friendship::class, Friendship::EVENT_BEFORE_UNFRIENDING, $handler);

        $this->assertFalse($this->podium()->account->unfriend(Member::findOne(12))->result);

        $this->assertNotEmpty(AcquaintanceRepo::findOne([
            'member_id' => 10,
            'target_id' => 12,
            'type_id' => AcquaintanceType::FRIEND,
        ]));

        Event::off(Friendship::class, Friendship::EVENT_BEFORE_UNFRIENDING, $handler);
    }

    public function testUnfriendAgain(): void
    {
        $this->assertFalse($this->podium()->account->unfriend(Member::findOne(11))->result);
    }
}
