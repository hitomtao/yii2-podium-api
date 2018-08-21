<?php

declare(strict_types=1);

namespace bizley\podium\api\interfaces;

use yii\data\DataFilter;
use yii\data\DataProviderInterface;
use yii\data\Pagination;
use yii\data\Sort;

/**
 * Interface MemberComponentInterface
 * @package bizley\podium\api\interfaces
 */
interface MemberInterface
{
    /**
     * @param int $id
     * @return MembershipInterface|null
     */
    public function getMemberById(int $id): ?MembershipInterface;

    /**
     * @param int|string $id
     * @return MembershipInterface|null
     */
    public function getMemberByUserId($id): ?MembershipInterface;

    /**
     * @param null|DataFilter $filter
     * @param null|bool|array|Sort $sort
     * @param null|bool|array|Pagination $pagination
     * @return DataProviderInterface
     */
    public function getMembers(?DataFilter $filter = null, $sort = null, $pagination = null): DataProviderInterface;

    /**
     * Returns registration handler.
     * @return RegistrationInterface
     */
    public function getRegistration(): RegistrationInterface;

    /**
     * Registers account.
     * @param array $data
     * @return bool
     */
    public function register(array $data): bool;

    /**
     * @param RemovableInterface $memberRemover
     * @return bool
     */
    public function remove(RemovableInterface $memberRemover): bool;

    /**
     * @param ModelFormInterface $member
     * @param array $data
     * @return bool
     */
    public function edit(ModelFormInterface $member, array $data): bool;

    /**
     * Returns friendship handler.
     * @return FriendshipInterface
     */
    public function getFriendship(): FriendshipInterface;

    /**
     * Makes target friend of member.
     * @param MembershipInterface $member
     * @param MembershipInterface $target
     * @return bool
     */
    public function befriend(MembershipInterface $member, MembershipInterface $target): bool;

    /**
     * Makes target member friend no more.
     * @param MembershipInterface $member
     * @param MembershipInterface $target
     * @return bool
     */
    public function unfriend(MembershipInterface $member, MembershipInterface $target): bool;

    /**
     * Returns ignoring handler.
     * @return IgnoringInterface
     */
    public function getIgnoring(): IgnoringInterface;

    /**
     * Sets target as ignored by member.
     * @param MembershipInterface $member
     * @param MembershipInterface $target
     * @return bool
     */
    public function ignore(MembershipInterface $member, MembershipInterface $target): bool;

    /**
     * Sets target as unignored by member.
     * @param MembershipInterface $member
     * @param MembershipInterface $target
     * @return bool
     */
    public function unignore(MembershipInterface $member, MembershipInterface $target): bool;

    /**
     * @param BanInterface $member
     * @return bool
     */
    public function ban(BanInterface $member): bool;

    /**
     * @param BanInterface $member
     * @return bool
     */
    public function unban(BanInterface $member): bool;
}
