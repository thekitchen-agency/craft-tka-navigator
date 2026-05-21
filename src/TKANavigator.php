<?php

namespace thekitchenagency\crafttkanavigation;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use thekitchenagency\crafttkanavigation\elements\Navigation;
use thekitchenagency\crafttkanavigation\models\Settings;
use thekitchenagency\crafttkanavigation\variables\NavigationVariable;
use yii\base\Event;

/**
 * tka navigation plugin
 *
 * @method static TKANavigator getInstance()
 * @method Settings getSettings()
 * @author thekitchen.agency <tech@thekitchen.agency>
 * @copyright thekitchen.agency
 * @license https://craftcms.github.io/license/ Craft License
 */
class TKANavigator extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        // Manually require element files to bypass OPCache/optimized autoloader issues in DDEV
        require_once __DIR__ . '/elements/Navigation.php';
        require_once __DIR__ . '/elements/db/NavigationQuery.php';

        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('tka-navigation/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // 1. Register our custom Navigation element type
        Event::on(
            \craft\services\Elements::class,
            \craft\services\Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(\craft\events\RegisterComponentTypesEvent $event) {
                $event->types[] = Navigation::class;
            }
        );

        // 2. Register Control Panel URL rules
        Event::on(
            \craft\web\UrlManager::class,
            \craft\web\UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(\craft\events\RegisterUrlRulesEvent $event) {
                $event->rules['tka-navigation'] = 'tka-navigation/navigation/index';
                $event->rules['tka-navigation/new'] = 'tka-navigation/navigation/edit';
                $event->rules['tka-navigation/edit/<elementId:\d+>'] = 'tka-navigation/navigation/edit';
            }
        );

        // 3. Register Craft Variable helper
        Event::on(
            \craft\web\twig\variables\CraftVariable::class,
            \craft\web\twig\variables\CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var \craft\web\twig\variables\CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('tkaNavigation', NavigationVariable::class);
            }
        );
    }
}
