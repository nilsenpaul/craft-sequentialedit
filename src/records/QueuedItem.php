<?php


namespace nilsenpaul\sequentialedit\records;

use nilsenpaul\sequentialedit\SequentialEdit;

use Craft;
use craft\db\ActiveRecord;

/**
 * This record reflects one queued item in the queued items table
 * @since 1.0
 */
class QueuedItem extends ActiveRecord
{

    /*
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sequentialedit_queueditems}}';
    }
}
