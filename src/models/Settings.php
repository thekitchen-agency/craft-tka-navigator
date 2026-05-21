<?php

namespace thekitchenagency\crafttkanavigation\models;

use Craft;
use craft\base\Model;

/**
 * tka navigation settings
 */
class Settings extends Model
{
    public int $maxDepth = 0;
    public bool $enableCssClasses = true;
    public bool $defaultExternalNewTab = false;
    public bool $enableCache = false;
    public int $cacheDuration = 3600;

    public function rules(): array
    {
        return [
            [['maxDepth', 'cacheDuration'], 'integer', 'min' => 0],
            [['enableCssClasses', 'defaultExternalNewTab', 'enableCache'], 'boolean'],
            [['maxDepth'], 'default', 'value' => 0],
            [['enableCssClasses'], 'default', 'value' => true],
            [['defaultExternalNewTab'], 'default', 'value' => false],
            [['enableCache'], 'default', 'value' => false],
            [['cacheDuration'], 'default', 'value' => 3600],
        ];
    }
}

