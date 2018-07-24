<?php

declare(strict_types=1);

namespace bizley\podium\api\models;

use bizley\podium\api\interfaces\MembershipInterface;
use bizley\podium\api\repos\MemberRepo;

/**
 * Class Member
 * @package bizley\podium\api\models
 */
class Member extends MemberRepo implements MembershipInterface
{
    /**
     * @param int $memberId
     * @return MembershipInterface|null
     */
    public static function findMemberById(int $memberId): ?MembershipInterface
    {
        return static::findOne(['id' => $memberId]);
    }

    /**
     * @param int|string $userId
     * @return MembershipInterface|null
     */
    public static function findMemberByUserId($userId): ?MembershipInterface
    {
        return static::findOne(['user_id' => $userId]);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
