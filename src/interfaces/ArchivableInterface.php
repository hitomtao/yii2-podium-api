<?php

declare(strict_types=1);

namespace bizley\podium\api\interfaces;

use bizley\podium\api\base\PodiumResponse;

/**
 * Interface RemovableInterface
 * @package bizley\podium\api\interfaces
 */
interface ArchivableInterface
{
    /**
     * Archives model.
     * @return PodiumResponse
     */
    public function archive(): PodiumResponse;

    /**
     * Revives model.
     * @return PodiumResponse
     */
    public function revive(): PodiumResponse;
}
