<?php

use yii\db\Migration;


/**
 * Class m220616_152616_create_tables
 */
class m220616_152616_create_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
      $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

        $this->createTable('access_token', [
            'id' => $this->primaryKey(11)->unsigned(),
            'token' => $this->string(255)->notNull(),
            'customer_xid' => $this->string(64)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime(),
            'created_by' => $this->string(64),
            'updated_at' => $this->dateTime(),
            'updated_by' => $this->string(64),
        ], $tableOptions);

        $this->createTable('wallet', [
            'id' => $this->primaryKey(11)->unsigned(),
            'customer_xid' => $this->string(64)->notNull(),
            'balance' => $this->decimal(11, 2)->defaultValue(0),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'enabled_at' => $this->dateTime(),
            'disabled_at' => $this->dateTime(),
            'created_at' => $this->dateTime(),
            'created_by' => $this->string(64),
            'updated_at' => $this->dateTime(),
            'updated_by' => $this->string(64),
        ], $tableOptions);

        $this->createTable('transaction', [
            'id' => $this->primaryKey(11)->unsigned(),
            'customer_xid' => $this->string(64)->notNull(),
            'type' => $this->string(32)->notNull(),
            'amount' => $this->decimal(11, 2)->defaultValue(0),
            'reference_id' => $this->string(64)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime(),
            'created_by' => $this->string(64),
            'updated_at' => $this->dateTime(),
            'updated_by' => $this->string(64),
        ], $tableOptions);

        $this->createTable('transaction_log', [
            'id' => $this->primaryKey(11)->unsigned(),
            'transaction_id' => $this->integer(11)->unsigned(),
            'reference_id' => $this->string(64)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime(),
            'created_by' => $this->string(64),
            'updated_at' => $this->dateTime(),
            'updated_by' => $this->string(64),
        ], $tableOptions);

        // foreign key of transaction_id in table `transaction_log`
        $this->addForeignKey('fk_transaction_log_to_transaction', 'transaction_log', 'transaction_id', 'transaction', 'id', 'CASCADE', 'CASCADE');

        // creates index for column `customer_xid` in table `access_token` 
        $this->createIndex(
            'idx-access_token-customer_xid',
            'access_token',
            'customer_xid'
        );

        // creates index for column `customer_xid` in table `wallet` 
        $this->createIndex(
            'idx-wallet-customer_xid',
            'wallet',
            'customer_xid'
        );

        // creates index for column `customer_xid` in table `transaction` 
        $this->createIndex(
            'idx-transaction-customer_xid',
            'transaction',
            'customer_xid'
        );

        // creates index for column `type` in table `transaction` 
        $this->createIndex(
            'idx-transaction-type',
            'transaction',
            'type'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('transaction_log');
        $this->dropTable('transaction');
        $this->dropTable('wallet');
        $this->dropTable('access_token');
    }
}