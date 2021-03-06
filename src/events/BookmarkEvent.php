<?php

declare(strict_types=1);

namespace bizley\podium\api\events;

use bizley\podium\api\interfaces\BookmarkingInterface;
use yii\base\Event;

/**
 * Class BookmarkEvent
 * @package bizley\podium\api\events
 */
class BookmarkEvent extends Event
{
    /**
     * @var bool whether model can be marked
     */
    public $canMark = true;

    /**
     * @var BookmarkingInterface
     */
    public $model;
}
