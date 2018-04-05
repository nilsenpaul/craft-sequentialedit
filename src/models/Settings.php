<?php
/**
 * Sequential Edit for Craft CMS 3.x
 *
 * This plugin lets you edit multiple elements (entries, assets, categories ...) in sequence, without being sent back to the index page each time you save your changes.
 *
 * @link      https://nilsenpaul.nl
 * @copyright Copyright (c) 2018 nilsenpaul
 */

namespace nilsenpaul\sequentialedit\models;

use craft\base\Model;

/**
 * This defines all settings used by the Sequential Edit plugin
 * @since 1.0
 */
class Settings extends Model
{
    public $activeOnElementTypes = [
        'craft\elements\Entry',
        'craft\elements\Category',
        'craft\elements\User',
    ];
}
