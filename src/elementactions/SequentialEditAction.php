<?php
/**
 * Sequential Edit for Craft CMS 3.x
 *
 * This plugin lets you edit multiple elements (entries, assets, categories ...) in sequence, without being sent back to the index page each time you save your changes.
 *
 * @link      https://nilsenpaul.nl
 * @copyright Copyright (c) 2018 nilsenpaul
 */

namespace nilsenpaul\sequentialedit\elementactions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

/**
 * SequentialEditAction adds the 'Edit in Sequence' action button to the element index's action dropdown
 * @since 1.0
 */
class SequentialEditAction extends ElementAction
{
    public $label;

    /*
     * @inheritdoc
     */
    public function init()
    {
        if ($this->label === null) {
            $this->label = Craft::t('sequential-edit', 'Edit in sequence');
        }
    }

    /*
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /*
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-editable');
        },
        activate: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element:first');
            var \$firstId = \$element.data('id');

            var \$remainingIds = [];
            var \$type = \$element.data('type');
            \$selectedItems.each(function() {
                var elementId = $(this).data('id');
                var siteId = $(this).find('.element').data('site-id');

                if (elementId != \$firstId) {
                    var combinedId = siteId + '-' + elementId;
                    \$remainingIds.push(combinedId);
                }
            });            

            var \$elementUrl = \$element.find('a').attr('href');

            if (\$elementUrl.indexOf('?') === -1) {
                Craft.redirectTo(\$element.find('a').attr('href') + '?sequential-type=' + \$type + '&sequential-edits-remaining=' + \$remainingIds.join('|'));
            } else {
                Craft.redirectTo(\$element.find('a').attr('href') + '&sequential-type=' + \$type + '&sequential-edits-remaining=' + \$remainingIds.join('|'));
            }
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
