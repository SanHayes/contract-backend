<?php

use think\migration\db\Column;
use think\migration\Migrator;

class TransactionFlow extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $adapter = $this->getAdapter();
        $array = $adapter->fetchRow('select @@version as version');
        $isGtMysql8 = version_compare($array['version'], '8.0.0', '>');
        $collation = $isGtMysql8 ? 'utf8mb4_0900_ai_ci' : 'utf8mb4_general_ci';

        $options = [
            'engine'      => 'InnoDB',
            'collation'   => $collation,
            'charset'     => 'utf8mb4',
            'id'          => false,
            'primary_key' => 'id',
            'comment'     => '流水表',
        ];
        $table = $this->table('transaction_flow', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::unsignedInteger('user_id')->setComment('用户id'))
            ->addColumn(Column::unsignedInteger('token_id')->setComment('代币id'))
            ->addColumn(Column::enum('assets_type', ['available', 'frozen', 'stake'])->setComment('账户类型'))
            ->addColumn(Column::decimal('old_balance', 32, 16)->setComment('旧余额'))
            ->addColumn(Column::decimal('amount', 32, 16)->setComment('金额'))
            ->addColumn(Column::decimal('new_balance', 32, 16)->setComment('新余额'))
            ->addColumn(Column::enum('transfer_type', ['exchange', 'withdraw', 'stake'])->setComment('业务类型'))
            ->addColumn(Column::enum('in_out', ['in', 'out'])->setComment('收入支出'))
            ->addColumn(Column::string('remark')->setNullable()->setComment('备注'))
            ->addTimestamps()
            ->create();
    }
}
