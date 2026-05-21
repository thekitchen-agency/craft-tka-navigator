<?php

namespace thekitchenagency\crafttkanavigation\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\Html;
use thekitchenagency\crafttkanavigation\elements\db\NavigationQuery;

class Navigation extends Element
{
    public ?string $handle = null;
    public ?array $nodes = null;

    public static function displayName(): string
    {
        return Craft::t('tka-navigation', 'Navigation');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('tka-navigation', 'Navigations');
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new NavigationQuery(static::class);
    }

    protected static function defineTableAttributes(string $source = null): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'handle' => Craft::t('tka-navigation', 'Handle'),
            'dateCreated' => Craft::t('app', 'Date Created'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source = null): array
    {
        return ['title', 'handle', 'dateCreated'];
    }

    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'handle':
                return Html::encode($this->handle);
            default:
                return parent::attributeHtml($attribute);
        }
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['handle'], 'required'];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [['nodes'], function($attribute) {
            $settings = \thekitchenagency\crafttkanavigation\TKANavigator::getInstance()->getSettings();
            if ($settings->maxDepth > 0 && !empty($this->nodes)) {
                $depth = $this->_getNodesMaxDepth($this->nodes);
                if ($depth > $settings->maxDepth) {
                    $this->addError($attribute, Craft::t('tka-navigation', 'The navigation hierarchy exceeds the maximum allowed nesting depth of {max}.', [
                        'max' => $settings->maxDepth
                    ]));
                }
            }
        }];
        return $rules;
    }

    public function afterSave(bool $isNew): void
    {
        // Save to tka_navigations
        Db::upsert('{{%tka_navigations}}', [
            'id' => $this->id,
            'handle' => $this->handle,
        ], [
            'handle' => $this->handle,
        ]);

        // Save to tka_navigations_sites
        Db::upsert('{{%tka_navigations_sites}}', [
            'elementId' => $this->id,
            'siteId' => $this->siteId,
        ], [
            'nodes' => $this->nodes !== null ? json_encode($this->nodes) : null,
            'dateCreated' => Db::prepareDateForDb(new \DateTime()),
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
            'uid' => $this->uid ?? \craft\helpers\StringHelper::UUID(),
        ]);

        // Clear resolved nodes cache
        $cacheKey = 'tka-navigation:' . $this->id . ':' . $this->siteId;
        Craft::$app->getCache()->delete($cacheKey);

        parent::afterSave($isNew);
    }

    public function afterDelete(): void
    {
        $cacheKey = 'tka-navigation:' . $this->id . ':' . $this->siteId;
        Craft::$app->getCache()->delete($cacheKey);
        parent::afterDelete();
    }

    public function afterPopulate(): void
    {
        parent::afterPopulate();

        if (is_string($this->nodes)) {
            $this->nodes = json_decode($this->nodes, true) ?: [];
        }
    }

    /**
     * Resolve the nodes array into fully populated nodes with entry titles and resolved URLs.
     *
     * @return array
     */
    public function getResolvedNodes(): array
    {
        if (empty($this->nodes)) {
            return [];
        }

        $settings = \thekitchenagency\crafttkanavigation\TKANavigator::getInstance()->getSettings();
        if ($settings->enableCache) {
            $cacheKey = 'tka-navigation:' . $this->id . ':' . $this->siteId;
            $cache = Craft::$app->getCache();
            $cached = $cache->get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }

            $resolved = $this->_resolveNodes($this->nodes);
            $duration = $settings->cacheDuration ?: 0;
            $cache->set($cacheKey, $resolved, $duration);
            return $resolved;
        }

        return $this->_resolveNodes($this->nodes);
    }

    private function _resolveNodes(array $nodes): array
    {
        $entryIds = $this->_extractEntryIds($nodes);
        $entries = [];
        if (!empty($entryIds)) {
            $entriesList = \craft\elements\Entry::find()->id($entryIds)->siteId($this->siteId)->all();
            foreach ($entriesList as $entry) {
                $entries[$entry->id] = $entry;
            }
        }

        return $this->_buildResolvedNodes($nodes, $entries);
    }

    private function _extractEntryIds(array $nodes): array
    {
        $ids = [];
        foreach ($nodes as $node) {
            if (!empty($node['entryId'])) {
                $ids[] = (int)$node['entryId'];
            }
            if (!empty($node['children'])) {
                $ids = array_merge($ids, $this->_extractEntryIds($node['children']));
            }
        }
        return array_unique($ids);
    }

    private function _buildResolvedNodes(array $nodes, array $entries): array
    {
        $resolved = [];
        foreach ($nodes as $node) {
            $entry = isset($node['entryId']) && isset($entries[$node['entryId']]) ? $entries[$node['entryId']] : null;

            // Resolve URL
            $url = '';
            if ($node['type'] === 'entry' && $entry) {
                $url = $entry->getUrl();
            } elseif ($node['type'] === 'url' || $node['type'] === 'external') {
                $url = $node['url'] ?? '';
            } elseif ($node['type'] === 'anchor') {
                $entryUrl = $entry ? $entry->getUrl() : '';
                $anchor = $node['anchor'] ?? '';
                if ($anchor && strpos($anchor, '#') !== 0) {
                    $anchor = '#' . $anchor;
                }
                $url = $entryUrl . $anchor;
            }

            // Resolve Label
            $label = $node['customLabel'] ?? '';
            if (empty($label) && $entry) {
                $label = $entry->title;
            }
            if (empty($label)) {
                $label = $url;
            }

            $resolvedNode = [
                'type' => $node['type'],
                'entryId' => $node['entryId'] ?? null,
                'entry' => $entry,
                'url' => $url,
                'label' => $label,
                'newTab' => !empty($node['newTab']),
                'cssClass' => $node['cssClass'] ?? '',
                'children' => !empty($node['children']) ? $this->_buildResolvedNodes($node['children'], $entries) : [],
            ];

            $resolved[] = $resolvedNode;
        }
        return $resolved;
    }

    /**
     * Recursively calculate the maximum depth of a nodes list.
     *
     * @param array $nodes
     * @param int $currentDepth
     * @return int
     */
    private function _getNodesMaxDepth(array $nodes, int $currentDepth = 1): int
    {
        $maxDepth = $currentDepth;
        foreach ($nodes as $node) {
            if (!empty($node['children'])) {
                $childDepth = $this->_getNodesMaxDepth($node['children'], $currentDepth + 1);
                if ($childDepth > $maxDepth) {
                    $maxDepth = $childDepth;
                }
            }
        }
        return $maxDepth;
    }
}
