<?php

declare(strict_types=1);

namespace bizley\podium\api\models\rank;

use bizley\podium\api\base\PodiumResponse;
use bizley\podium\api\events\RemoveEvent;
use bizley\podium\api\interfaces\RemovableInterface;
use bizley\podium\api\repos\RankRepo;
use Yii;

/**
 * Class RankRemover
 * @package bizley\podium\api\models\rank
 */
class RankRemover extends RankRepo implements RemovableInterface
{
    public const EVENT_BEFORE_REMOVING = 'podium.rank.removing.before';
    public const EVENT_AFTER_REMOVING = 'podium.rank.removing.after';

    /**
     * @return bool
     */
    public function beforeRemove(): bool
    {
        $event = new RemoveEvent();
        $this->trigger(self::EVENT_BEFORE_REMOVING, $event);

        return $event->canRemove;
    }

    /**
     * @return PodiumResponse
     */
    public function remove(): PodiumResponse
    {
        if (!$this->beforeRemove()) {
            return PodiumResponse::error();
        }

        try {
            if ($this->delete() === false) {
                Yii::error('Error while deleting rank', 'podium');
                return PodiumResponse::error();
            }

            $this->afterRemove();

            return PodiumResponse::success();

        } catch (\Throwable $exc) {
            Yii::error(['Exception while removing rank', $exc->getMessage(), $exc->getTraceAsString()], 'podium');
            return PodiumResponse::error();
        }
    }

    public function afterRemove(): void
    {
        $this->trigger(self::EVENT_AFTER_REMOVING);
    }
}
