<?php

declare(strict_types=1);

namespace bizley\podium\api\models\poll;

use bizley\podium\api\interfaces\ModelInterface;
use bizley\podium\api\models\ModelTrait;
use bizley\podium\api\models\post\Post;
use bizley\podium\api\repos\PollRepo;
use yii\base\NotSupportedException;

/**
 * Class Poll
 * @package bizley\podium\api\models\poll
 */
class Poll extends PollRepo implements ModelInterface
{
    use ModelTrait;

    /**
     * @param int $modelId
     * @return ModelInterface|null
     */
    public static function findByPostId(int $modelId): ?ModelInterface
    {
        return static::findOne(['post_id' => $modelId]);
    }

    /**
     * @return ModelInterface
     */
    public function getParent(): ModelInterface
    {
        return Post::findById($this->post_id);
    }

    /**
     * @return int
     * @throws NotSupportedException
     */
    public function getPostsCount(): int
    {
        throw new NotSupportedException('Poll has got no posts.');
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->getParent()->isArchived();
    }
}