<?php

namespace thekitchenagency\crafttkanavigation\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class NavigationQuery extends ElementQuery
{
    public mixed $handle = null;

    public function handle($value)
    {
        $this->handle = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('tka_navigations');

        $this->query->select([
            'tka_navigations.handle',
            'tka_navigations_sites.nodes',
        ]);

        $this->query->leftJoin(
            '{{%tka_navigations_sites}} tka_navigations_sites',
            '[[tka_navigations_sites.elementId]] = [[elements.id]] AND [[tka_navigations_sites.siteId]] = [[elements_sites.siteId]]'
        );

        if ($this->handle !== null) {
            $this->subQuery->andWhere(Db::parseParam('tka_navigations.handle', $this->handle));
        }

        return parent::beforePrepare();
    }
}
