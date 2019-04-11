<?php
/**
 * Sequential Edit for Craft CMS 3.x
 *
 * This plugin lets you edit multiple elements (entries, assets, categories ...) in sequence, without being sent back to the index page each time you save your changes.
 *
 * @link      https://nilsenpaul.nl
 * @copyright Copyright (c) 2018 nilsenpaul
 */

namespace nilsenpaul\sequentialedit\services;

use nilsenpaul\sequentialedit\SequentialEdit;
use nilsenpaul\sequentialedit\records\QueuedItem;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ModelEvent;

/**
 * This service handles all logic needed by the plugin
 * @since 1.0
 */
class SequentialEditService extends Component
{
    /*
     * Adds a list of IDs of a certain type to the queue
     * @param Array $ids
     * @param String $type
     * @return void
     */
    public static function addIdsToQueue(Array $ids, String $type)
    {
        $i = 0;

        $itemsToQueue= [];
        $sessionId = Craft::$app->session->id;
        foreach ($ids as $id) {
            list($siteId, $elementId) = explode('-', $id);

            $itemsToQueue[] = [
                'elementType' => $type,
                'elementId' => $elementId,
                'sessionId' => $sessionId,
                'siteId' => $siteId,
                'order' => $i,
            ];

            $i++;
        }

        // Remove old values for this session, just to be sure
        Craft::$app->db->createCommand()
            ->delete('{{%sequentialedit_queueditems}}', [
                'sessionId' => $sessionId,
            ])->execute();
        
        // Insert ID's into DB, for future reference ...
        foreach ($itemsToQueue as $item) {
            $queuedItem = new QueuedItem();
            $queuedItem->elementType = $item['elementType'];
            $queuedItem->elementId = $item['elementId'];
            $queuedItem->sessionId = $item['sessionId'];
            $queuedItem->siteId = $item['siteId'];
            $queuedItem->order = $item['order'];
            $queuedItem->save();
        }
    }

    /*
     * Finds the next queued item of a certain type
     * @param craft\events\ModelEvent $event
     * @param String $type
     * @return void
     */
    public static function sendToNextQueuedItem(ModelEvent $event, $type)
    {
        $remainingItems = self::getRemainingItemQuery($event->sender->siteId, $event->sender->id, $type);
        $nextRemainingItem = $remainingItems->one();

        if ($nextRemainingItem) {
            $element = Craft::$app->elements->getElementById($nextRemainingItem->elementId, null, $nextRemainingItem->siteId);

            // Delete the queued item
            $nextRemainingItem->delete();

            if ($element) {
                return Craft::$app->response->redirect($element->cpEditUrl)->send();
                exit;
            }
        }
    }

    /*
     * Destroys the queue of items that remain to be edited, based on the request segments
     * @return void
     */
    public static function destroyQueueOnAnythingButEdits()
    {
        if (!Craft::$app->request->isAjax) {
            $segments = Craft::$app->request->segments;

            $destroyQueue = false;
            if (empty($segments)) {
                $destroyQueue = true;
            } elseif ($segments[0] === 'commerce') {
                if (!isset($segments[1])) {
                    $destroyQueue = true;
                }

                $controller = $segments[1];
                switch ($controller) {
                    case 'products':
                    case 'subscriptions':
                        if (!isset($segments[3])) {
                            $destroyQueue = true;
                        } else {
                            list($elementId) = explode('-', $segments[3]);
                            
                            if (!((Int)$elementId > 0)) {
                                $destroyQueue = true;
                            }
                        }
                        break;
                }
            } elseif ($segments[0] === 'calendar') {
                if (!isset($segments[1])) {
                    $destroyQueue = true;
                }

                $controller = $segments[1];
                switch ($controller) {
                    case 'events':
                        if (!isset($segments[2])) {
                            $destroyQueue = true;
                        } else {
                            $elementId = $segments[2];
                            
                            if (!((Int)$elementId > 0)) {
                                $destroyQueue = true;
                            }
                        }
                        break;
                }
            } else {
                $controller = $segments[0];

                switch ($controller) {
                    case 'entries':
                    case 'categories':
                        if (!isset($segments[2])) {
                            $destroyQueue = true;
                        } else {
                            list($elementId) = explode('-', $segments[2]);
                            
                            if (!((Int)$elementId > 0)) {
                                $destroyQueue = true;
                            }
                        }
                        break;
                    case 'users':
                        if (!isset($segments[1])) {
                            $destroyQueue = true;
                        } elseif (!($segments[1] > 0) && $segments[1] != 'myaccount') {
                            $destroyQueue = true;
                        }
                        break;
                    default:
                        $destroyQueue = true;
                }
            }

            if ($destroyQueue) {
                Craft::$app->db->createCommand()
                    ->delete('{{%sequentialedit_queueditems}}', [
                        'sessionId' => Craft::$app->session->id,
                    ])->execute();
            }
        }
    }

    public static function getRemainingItemQuery($siteId, $currentElementId, $type)
    {
        return QueuedItem::find()
            ->where([
                'elementType' => $type,
                'sessionId' => Craft::$app->session->id,
                'siteId' => $siteId,
            ])
            ->andWhere(['not', ['elementId' => $currentElementId]])
            ->orderBy(['order' => SORT_ASC]);
    }
}
