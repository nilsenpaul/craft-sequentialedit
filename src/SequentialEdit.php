<?php
/**
 * Sequential Edit for Craft CMS 3.x
 *
 * This plugin lets you edit multiple elements (entries, assets, categories ...) in sequence, without being sent back to the index page each time you save your changes.
 *
 * @link      https://nilsenpaul.nl
 * @copyright Copyright (c) 2018 nilsenpaul
 */

namespace nilsenpaul\sequentialedit;

use nilsenpaul\sequentialedit\services\SequentialEditService;
use nilsenpaul\sequentialedit\models\Settings;
use nilsenpaul\sequentialedit\records\QueuedItem;
use nilsenpaul\sequentialedit\elementactions\SequentialEditAction;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\events\RegisterElementActionsEvent;
use craft\services\Plugins;

use yii\base\Event;

/**
 * This main plugin file handles all default plugin functionality, and adds crucial event management
 * @since 1.0
 */
class SequentialEdit extends Plugin
{
    public static $plugin;
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'general' => SequentialEditService::class,
        ]);

        $request = Craft::$app->request;
        $response = Craft::$app->response;
        if ($this->isInstalled && !$request->isConsoleRequest && $request->isCpRequest) {
            foreach ($this->settings->activeOnElementTypes as $elementClassName) {
                Event::on($elementClassName, Element::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
                    array_splice($event->actions, 2, 0, SequentialEditAction::class);
                });

                Event::on($elementClassName, $elementClassName::EVENT_AFTER_SAVE, function(ModelEvent $event) use($elementClassName) {
                    if (!$event->isNew) {
                        $this->general->sendToNextQueuedItem($event, $elementClassName);
                    }
                });
            }

            // Listen for remaining IDS 
            $idsFromParam = $request->getParam('sequential-edits-remaining');
            if (!$request->isAjax && !empty($idsFromParam)) {
                $elementType = $request->getParam('sequential-type');
                $remainingIds = explode('|', $idsFromParam);

                $this->general->addIdsToQueue($remainingIds, $elementType);

                $redirectUrl = '/' . $request->fullPath;
                return $response->redirect($redirectUrl)->send();
                exit;
            }

            // Remove this session's queued item if this is not an edit action of any kind
            if ($request->isCpRequest && !$request->isAjax) {
                $this->general->destroyQueueOnAnythingButEdits();
            }
        }

        Craft::info(
            Craft::t(
                'sequential-edit',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        $commercePlugin = Craft::$app->getPlugins()->getPlugin('commerce');

        return Craft::$app->view->renderTemplate(
            'sequential-edit/settings',
            [
                'settings' => $this->getSettings(),
                'includeCommerce' => $commercePlugin !== null,
            ]
        );
    }
}
