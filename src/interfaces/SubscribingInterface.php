<?php

declare(strict_types=1);

namespace bizley\podium\api\interfaces;

use bizley\podium\api\base\PodiumResponse;

/**
 * Interface SubscribingInterface
 * @package bizley\podium\api\interfaces
 */
interface SubscribingInterface
{
    /**
     * Initiator of subscribing.
     * @param MembershipInterface $member
     */
    public function setMember(MembershipInterface $member): void;

    /**
     * Target of subscribing.
     * @param ModelInterface $thread
     */
    public function setThread(ModelInterface $thread): void;

    /**
     * Subscribes.
     * @return PodiumResponse
     */
    public function subscribe(): PodiumResponse;

    /**
     * Unsubscribes.
     * @return PodiumResponse
     */
    public function unsubscribe(): PodiumResponse;
}
