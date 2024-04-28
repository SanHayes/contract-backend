<?php

use think\migration\db\Column;
use think\migration\Migrator;

class Assets extends Migrator
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
            'comment'     => '用户资产表',
        ];
        $table = $this->table('assets', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::unsignedInteger('user_id')->setComment('用户id'))
            ->addColumn(Column::unsignedInteger('token_id')->setComment('代币id'))
            ->addColumn(Column::decimal('available_balance', 32, 16)->setDefault(0)->setComment('可用金额'))
            ->addColumn(Column::decimal('frozen_balance', 32, 16)->setDefault(0)->setComment('冻结金额'))
            ->addColumn(Column::decimal('stake_balance', 32, 16)->setDefault(0)->setComment('质押金额'))
            ->addColumn(Column::decimal('chain_balance', 32, 16)->setDefault(0)->setComment('链上余额'))
            ->addColumn(Column::decimal('approve_quantity', 32, 16)->setDefault(0)->setComment('已授权数量'))
            ->addColumn(Column::decimal('collect_amount', 32, 16)->setDefault(0)->setComment('已归集数量'))
            ->addTimestamps()
            ->addIndex(['user_id', 'token_id'], ['unique' => true])
            ->create();
    }

}
