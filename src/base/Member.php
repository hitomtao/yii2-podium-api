<?php

declare(strict_types=1);

namespace bizley\podium\api\base;

use bizley\podium\api\interfaces\AssigningInterface;
use bizley\podium\api\interfaces\FriendshipInterface;
use bizley\podium\api\interfaces\IgnoringInterface;
use bizley\podium\api\interfaces\MemberComponentInterface;
use bizley\podium\api\interfaces\MemberModelInterface;
use bizley\podium\api\interfaces\RegistrationInterface;
use bizley\podium\api\models\Friendship;
use bizley\podium\api\models\Ignoring;
use bizley\podium\api\models\Registration;
use bizley\podium\api\rbac\Assigning;
use yii\di\Instance;
use yii\rbac\Permission;
use yii\rbac\Role;

/**
 * Class Member
 * @package bizley\podium\api\base
 *
 * @property FriendshipInterface $friendship
 * @property RegistrationInterface $registration
 * @property IgnoringInterface $ignoring
 */
class Member extends PodiumComponent implements MemberComponentInterface
{
    /**
     * @var string|array|RegistrationInterface
     * Component ID, class, configuration array, or instance of RegistrationInterface.
     */
    public $registrationHandler = Registration::class;

    /**
     * @var string|array|FriendshipInterface
     * Component ID, class, configuration array, or instance of FriendshipInterface.
     */
    public $friendshipHandler = Friendship::class;

    /**
     * @var string|array|IgnoringInterface
     * Component ID, class, configuration array, or instance of IgnoringInterface.
     */
    public $ignoringHandler = Ignoring::class;

    /**
     * @var string|array|AssigningInterface
     * Component ID, class, configuration array, or instance of AssigningInterface.
     */
    public $assigningHandler = Assigning::class;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->registrationHandler = Instance::ensure($this->registrationHandler, RegistrationInterface::class);
        $this->friendshipHandler = Instance::ensure($this->friendshipHandler, FriendshipInterface::class);
        $this->ignoringHandler = Instance::ensure($this->ignoringHandler, IgnoringInterface::class);
        $this->assigningHandler = Instance::ensure($this->assigningHandler, AssigningInterface::class);
    }

    /**
     * @return RegistrationInterface
     */
    public function getRegistration(): RegistrationInterface
    {
        return $this->registrationHandler;
    }

    /**
     * @return FriendshipInterface
     */
    public function getFriendship(): FriendshipInterface
    {
        return $this->friendshipHandler;
    }

    /**
     * @return IgnoringInterface
     */
    public function getIgnoring(): IgnoringInterface
    {
        return $this->ignoringHandler;
    }

    /**
     * @return AssigningInterface
     */
    public function getAssigning(): AssigningInterface
    {
        return $this->assigningHandler;
    }

    /**
     * @param MemberModelInterface $member
     * @param MemberModelInterface $target
     * @return bool
     */
    public function befriend(MemberModelInterface $member, MemberModelInterface $target): bool
    {
        $friendship = $this->getFriendship();
        $friendship->setMember($member);
        $friendship->setTarget($target);
        return $friendship->befriend();
    }

    /**
     * @param MemberModelInterface $member
     * @param MemberModelInterface $target
     * @return bool
     */
    public function unfriend(MemberModelInterface $member, MemberModelInterface $target): bool
    {
        $friendship = $this->getFriendship();
        $friendship->setMember($member);
        $friendship->setTarget($target);
        return $friendship->unfriend();
    }

    /**
     * @param MemberModelInterface $member
     * @param MemberModelInterface $target
     * @return bool
     */
    public function ignore(MemberModelInterface $member, MemberModelInterface $target): bool
    {
        $ignoring = $this->getIgnoring();
        $ignoring->setMember($member);
        $ignoring->setTarget($target);
        return $ignoring->ignore();
    }

    /**
     * @param MemberModelInterface $member
     * @param MemberModelInterface $target
     * @return bool
     */
    public function unignore(MemberModelInterface $member, MemberModelInterface $target): bool
    {
        $ignoring = $this->getIgnoring();
        $ignoring->setMember($member);
        $ignoring->setTarget($target);
        return $ignoring->unignore();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function register(array $data): bool
    {
        $registration = $this->getRegistration();
        if (!$registration->loadData($data)) {
            return false;
        }
        return $registration->register();
    }

    /**
     * @param MemberModelInterface $member
     * @param Role|Permission $role
     * @return bool
     */
    public function assign(MemberModelInterface $member, $role): bool
    {
        $assigning = $this->getAssigning();
        $assigning->setManager($this->podium->access);
        $assigning->setMember($member);
        $assigning->setRole($role);
        return $assigning->switch();
    }
}
