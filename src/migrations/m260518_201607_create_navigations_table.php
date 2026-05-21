<?php

namespace thekitchenagency\crafttkanavigation\migrations;

use craft\db\Migration;

/**
 * m260518_201607_create_navigations_table migration.
 */
class m260518_201607_create_navigations_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%tka_navigations}}', [
            'id' => $this->integer()->notNull(),
            'handle' => $this->string()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable('{{%tka_navigations_sites}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'nodes' => $this->mediumText(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%tka_navigations}}', 'handle', true);
        $this->createIndex(null, '{{%tka_navigations_sites}}', ['elementId', 'siteId'], true);

        $this->addForeignKey(null, '{{%tka_navigations}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%tka_navigations_sites}}', 'elementId', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%tka_navigations_sites}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%tka_navigations_sites}}');
        $this->dropTableIfExists('{{%tka_navigations}}');
        return true;
    }
}
