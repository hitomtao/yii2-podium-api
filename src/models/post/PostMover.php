<?php

declare(strict_types=1);

namespace bizley\podium\api\models\thread;

use bizley\podium\api\events\MoveEvent;
use bizley\podium\api\interfaces\ModelInterface;
use bizley\podium\api\interfaces\MovableInterface;
use bizley\podium\api\repos\PostRepo;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\Exception;

/**
 * Class PostMover
 * @package bizley\podium\api\models\thread
 */
class PostMover extends PostRepo implements MovableInterface
{
    public const EVENT_BEFORE_MOVING = 'podium.post.moving.before';
    public const EVENT_AFTER_MOVING = 'podium.post.moving.after';

    /**
     * @param ModelInterface $thread
     */
    public function setThread(ModelInterface $thread): void
    {
        $this->fetchOldThreadModel();
        $this->setNewThreadModel($thread);

        $this->thread_id = $thread->getId();
        $forum = $thread->getParent();
        $this->forum_id = $forum->getId();
        $this->category_id = $forum->getParent()->getId();
    }

    private $_newThread;

    /**
     * @param ModelInterface $thread
     */
    public function setNewThreadModel(ModelInterface $thread): void
    {
        $this->_newThread = $thread;
    }

    /**
     * @return ModelInterface
     */
    public function getNewThreadModel(): ModelInterface
    {
        return $this->_newThread;
    }

    private $_oldThread;

    public function fetchOldThreadModel(): void
    {
        $this->_oldThread = Thread::findById($this->thread_id);
    }

    /**
     * @return ModelInterface
     */
    public function getOldThreadModel(): ModelInterface
    {
        return $this->_oldThread;
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'timestamp' => TimestampBehavior::class,
        ];
    }

    /**
     * @return bool
     */
    public function beforeMove(): bool
    {
        $event = new MoveEvent();
        $this->trigger(self::EVENT_BEFORE_MOVING, $event);

        return $event->canMove;
    }

    /**
     * @return bool
     */
    public function move(): bool
    {
        if (!$this->beforeMove()) {
            return false;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$this->getOldThreadModel()->updateCounters([
                'posts_count' => -1,
            ])) {
                throw new Exception('Error while updating old thread counters!');
            }
            if ($this->getOldThreadModel()->posts_count === 1) { // last post in thread
                if (!$this->getOldThreadModel()->delete()) {
                    throw new Exception('Error while deleting old empty thread!');
                }
                if (!$this->getOldThreadModel()->getParent()->updateCounters([
                    'posts_count' => -1,
                    'threads_count' => -1,
                ])) {
                    throw new Exception('Error while updating old forum counters!');
                }
            } elseif (!$this->getOldThreadModel()->getParent()->updateCounters([
                'posts_count' => -1,
            ])) {
                throw new Exception('Error while updating old forum counters!');
            }

            if (!$this->getNewThreadModel()->updateCounters([
                'posts_count' => 1,
            ])) {
                throw new Exception('Error while updating new thread counters!');
            }
            if (!$this->getNewThreadModel()->getParent()->updateCounters([
                'posts_count' => 1,
            ])) {
                throw new Exception('Error while updating new forum counters!');
            }

            if (!$this->save(false)) {
                Yii::error(['post.move', $this->errors], 'podium');
                throw new Exception('Error while moving post!');
            }

            $this->afterMove();

            $transaction->commit();
            return true;

        } catch (\Throwable $exc) {
            Yii::error(['post.move.exception', $exc->getMessage(), $exc->getTraceAsString()], 'podium');
            try {
                $transaction->rollBack();
            } catch (\Throwable $excTrans) {
                Yii::error(['post.move.rollback', $excTrans->getMessage(), $excTrans->getTraceAsString()], 'podium');
            }
        }
        return false;
    }

    public function afterMove(): void
    {
        $this->trigger(self::EVENT_AFTER_MOVING, new MoveEvent([
            'model' => $this
        ]));
    }

    /**
     * @param ModelInterface $category
     * @throws NotSupportedException
     */
    public function setCategory(ModelInterface $category): void
    {
        throw new NotSupportedException('Post target category can not be set directly.');
    }

    /**
     * @param ModelInterface $forum
     * @throws NotSupportedException
     */
    public function setForum(ModelInterface $forum): void
    {
        throw new NotSupportedException('Post target forum can not be set directly.');
    }
}