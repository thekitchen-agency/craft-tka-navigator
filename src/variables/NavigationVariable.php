<?php

namespace thekitchenagency\crafttkanavigation\variables;

use thekitchenagency\crafttkanavigation\elements\Navigation;

class NavigationVariable
{
    /**
     * Get a navigation by its handle.
     *
     * @param string $handle
     * @param int|null $siteId
     * @return Navigation|null
     */
    public function get(string $handle, ?int $siteId = null): ?Navigation
    {
        $query = Navigation::find()->handle($handle);
        if ($siteId !== null) {
            $query->siteId($siteId);
        }
        return $query->one();
    }
}
