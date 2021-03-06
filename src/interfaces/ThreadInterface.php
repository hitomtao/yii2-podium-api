<?php

declare(strict_types=1);

namespace bizley\podium\api\interfaces;

use bizley\podium\api\base\PodiumResponse;
use yii\data\DataFilter;
use yii\data\DataProviderInterface;
use yii\data\Pagination;
use yii\data\Sort;

/**
 * Interface ThreadInterface
 * @package bizley\podium\api\interfaces
 */
interface ThreadInterface
{
    /**
     * @param int $id
     * @return ModelInterface|null
     */
    public function getThreadById(int $id): ?ModelInterface;

    /**
     * @param null|DataFilter $filter
     * @param null|bool|array|Sort $sort
     * @param null|bool|array|Pagination $pagination
     * @return DataProviderInterface
     */
    public function getThreads(?DataFilter $filter = null, $sort = null, $pagination = null): DataProviderInterface;

    /**
     * Returns thread form handler.
     * @return CategorisedFormInterface
     */
    public function getThreadForm(): CategorisedFormInterface;

    /**
     * Creates thread.
     * @param array $data
     * @param MembershipInterface $author
     * @param ModelInterface $forum
     * @return PodiumResponse
     */
    public function create(array $data, MembershipInterface $author, ModelInterface $forum): PodiumResponse;

    /**
     * Updates thread.
     * @param ModelFormInterface $threadForm
     * @param array $data
     * @return PodiumResponse
     */
    public function edit(ModelFormInterface $threadForm, array $data): PodiumResponse;

    /**
     * @param RemovableInterface $threadRemover
     * @return PodiumResponse
     */
    public function remove(RemovableInterface $threadRemover): PodiumResponse;

    /**
     * Moves thread to different forum.
     * @param MovableInterface $threadMover
     * @param ModelInterface $forum
     * @return PodiumResponse
     */
    public function move(MovableInterface $threadMover, ModelInterface $forum): PodiumResponse;

    /**
     * @param PinnableInterface $threadPinner
     * @return PodiumResponse
     */
    public function pin(PinnableInterface $threadPinner): PodiumResponse;

    /**
     * @param PinnableInterface $threadPinner
     * @return PodiumResponse
     */
    public function unpin(PinnableInterface $threadPinner): PodiumResponse;

    /**
     * @param LockableInterface $threadLocker
     * @return PodiumResponse
     */
    public function lock(LockableInterface $threadLocker): PodiumResponse;

    /**
     * @param LockableInterface $threadLocker
     * @return PodiumResponse
     */
    public function unlock(LockableInterface $threadLocker): PodiumResponse;

    /**
     * @param ArchivableInterface $threadArchiver
     * @return PodiumResponse
     */
    public function archive(ArchivableInterface $threadArchiver): PodiumResponse;

    /**
     * @param ArchivableInterface $threadArchiver
     * @return PodiumResponse
     */
    public function revive(ArchivableInterface $threadArchiver): PodiumResponse;

    /**
     * @return SubscribingInterface
     */
    public function getSubscribing(): SubscribingInterface;

    /**
     * @param MembershipInterface $member
     * @param ModelInterface $thread
     * @return PodiumResponse
     */
    public function subscribe(MembershipInterface $member, ModelInterface $thread): PodiumResponse;

    /**
     * @param MembershipInterface $member
     * @param ModelInterface $thread
     * @return PodiumResponse
     */
    public function unsubscribe(MembershipInterface $member, ModelInterface $thread): PodiumResponse;

    /**
     * @return BookmarkingInterface
     */
    public function getBookmarking(): BookmarkingInterface;

    /**
     * @param MembershipInterface $member
     * @param ModelInterface $post
     * @return PodiumResponse
     */
    public function mark(MembershipInterface $member, ModelInterface $post): PodiumResponse;
}
