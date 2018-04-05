<?php
/**
 * Sequential Edit for Craft CMS 3.x
 *
 * This plugin lets you edit multiple elements (entries, assets, categories ...) in sequence, without being sent back to the index page each time you save your changes.
 *
 * @link      https://nilsenpaul.nl
 * @copyright Copyright (c) 2018 nilsenpaul
 */


namespace nilsenpaul\sequentialedit\migrations;

use nilsenpaul\sequentialedit\SequentialEdit;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * The Install Migration covers all install migrations
 * @since 1.0
 */
class Install extends Migration
{
    public $driver;

    /*
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /*
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Creates all necessary tables for this plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%sequentialedit_queueditems}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%sequentialedit_queueditems}}',
                [
                    'id' => $this->primaryKey(),
                    'sessionId' => $this->string(64)->notNull(),
                    'elementType' => $this->string(64)->notNull(),
                    'elementId' => $this->integer()->notNull(),
                    'order' => $this->integer()->notNull(),
                    'siteId' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates all necessary indexes for this plugin
     *
     * @return bool
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%sequentialedit_queueditems}}',
                ['sessionId', 'elementId'],
                true
            ),
            '{{%sequentialedit_queueditems}}',
            ['sessionId', 'elementId'],
            true
        );
    }

    
    /**
     * Remove all tables created by this plugin
     *
     * @return bool
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%sequentialedit_queueditems}}');
    }
}
