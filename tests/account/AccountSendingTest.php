<?php

declare(strict_types=1);

namespace bizley\podium\tests\account;

use bizley\podium\api\enums\MemberStatus;
use bizley\podium\api\enums\MessageSide;
use bizley\podium\api\enums\MessageStatus;
use bizley\podium\api\models\member\Member;
use bizley\podium\api\models\message\MessageParticipant;
use bizley\podium\api\models\message\Sending;
use bizley\podium\api\repos\MessageParticipantRepo;
use bizley\podium\api\repos\MessageRepo;
use bizley\podium\tests\AccountTestCase;
use bizley\podium\tests\props\UserIdentity;
use yii\base\Event;

/**
 * Class AccountSendingTest
 * @package bizley\podium\tests\account
 */
class AccountSendingTest extends AccountTestCase
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
                'slug' => 'member',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'id' => 2,
                'user_id' => '2',
                'username' => 'member2',
                'slug' => 'member2',
                'status_id' => MemberStatus::ACTIVE,
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_message' => [
            [
                'id' => 1,
                'subject' => 'subject1',
                'content' => 'content1',
                'created_at' => 1,
                'updated_at' => 1,
            ],
        ],
        'podium_message_participant' => [
            [
                'message_id' => 1,
                'member_id' => 2,
                'side_id' => MessageSide::SENDER,
                'status_id' => MessageStatus::READ,
                'created_at' => 1,
                'updated_at' => 1,
            ],
            [
                'message_id' => 1,
                'member_id' => 1,
                'side_id' => MessageSide::RECEIVER,
                'status_id' => MessageStatus::NEW,
                'created_at' => 1,
                'updated_at' => 1,
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
        \Yii::$app->user->setIdentity(new UserIdentity(['id' => '1']));
    }

    /**
     * @throws \yii\db\Exception
     */
    protected function tearDown(): void
    {
        $this->fixturesDown();
        parent::tearDown();
    }

    public function testSend(): void
    {
        Event::on(Sending::class, Sending::EVENT_BEFORE_SENDING, function () {
            static::$eventsRaised[Sending::EVENT_BEFORE_SENDING] = true;
        });
        Event::on(Sending::class, Sending::EVENT_AFTER_SENDING, function () {
            static::$eventsRaised[Sending::EVENT_AFTER_SENDING] = true;
        });

        $data = [
            'subject' => 'new-subject',
            'content' => 'new-content',
        ];
        $this->assertTrue($this->podium()->account->send($data, Member::findOne(2))->result);

        $message = MessageRepo::findOne(['subject' => 'new-subject']);
        $this->assertEquals(array_merge($data, [
            'reply_to_id' => null,
        ]), [
            'subject' => $message->subject,
            'content' => $message->content,
            'reply_to_id' => $message->reply_to_id,
        ]);

        $messageSender = MessageParticipantRepo::findOne([
            'message_id' => $message->id,
            'side_id' => MessageSide::SENDER,
        ]);
        $this->assertEquals([
            'member_id' => 1,
            'status_id' => MessageStatus::READ,
            'archived' => 0,
        ], [
            'member_id' => $messageSender->member_id,
            'status_id' => $messageSender->status_id,
            'archived' => $messageSender->archived,
        ]);

        $messageReceiver = MessageParticipantRepo::findOne([
            'message_id' => $message->id,
            'side_id' => MessageSide::RECEIVER,
        ]);
        $this->assertEquals([
            'member_id' => 2,
            'status_id' => MessageStatus::NEW,
            'archived' => 0,
        ], [
            'member_id' => $messageReceiver->member_id,
            'status_id' => $messageReceiver->status_id,
            'archived' => $messageReceiver->archived,
        ]);

        $this->assertArrayHasKey(Sending::EVENT_BEFORE_SENDING, static::$eventsRaised);
        $this->assertArrayHasKey(Sending::EVENT_AFTER_SENDING, static::$eventsRaised);
    }

    public function testSendEventPreventing(): void
    {
        $handler = function ($event) {
            $event->canSend = false;
        };
        Event::on(Sending::class, Sending::EVENT_BEFORE_SENDING, $handler);

        $data = [
            'subject' => 'new-subject',
            'content' => 'new-content',
        ];
        $this->assertFalse($this->podium()->account->send($data, Member::findOne(2))->result);

        $this->assertEmpty(MessageRepo::findOne(['subject' => 'new-subject']));

        Event::off(Sending::class, Sending::EVENT_BEFORE_SENDING, $handler);
    }

    public function testSendReply(): void
    {
        $data = [
            'subject' => 'new-subject',
            'content' => 'new-content',
        ];
        $this->assertTrue($this->podium()->account->send($data, Member::findOne(2), MessageParticipant::findOne([
            'message_id' => 1,
            'side_id' => MessageSide::SENDER,
        ]))->result);

        $message = MessageRepo::findOne(['subject' => 'new-subject']);
        $this->assertEquals(array_merge($data, [
            'reply_to_id' => 1,
        ]), [
            'subject' => $message->subject,
            'content' => $message->content,
            'reply_to_id' => $message->reply_to_id,
        ]);

        $messageSender = MessageParticipantRepo::findOne([
            'message_id' => $message->id,
            'side_id' => MessageSide::SENDER,
        ]);
        $this->assertEquals([
            'member_id' => 1,
            'status_id' => MessageStatus::READ,
            'archived' => 0,
        ], [
            'member_id' => $messageSender->member_id,
            'status_id' => $messageSender->status_id,
            'archived' => $messageSender->archived,
        ]);

        $this->assertEquals(MessageStatus::REPLIED, MessageParticipantRepo::findOne([
            'member_id' => 1,
            'message_id' => 1,
        ])->status_id);

        $messageReceiver = MessageParticipantRepo::findOne([
            'message_id' => $message->id,
            'side_id' => MessageSide::RECEIVER,
        ]);
        $this->assertEquals([
            'member_id' => 2,
            'status_id' => MessageStatus::NEW,
            'archived' => 0,
        ], [
            'member_id' => $messageReceiver->member_id,
            'status_id' => $messageReceiver->status_id,
            'archived' => $messageReceiver->archived,
        ]);
    }
}
