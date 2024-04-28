<?php

use think\migration\Migrator;
use think\migration\db\Column;

class WithdrawTransaction extends Migrator
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
            'comment'     => '提现流水记录表',
        ];
        $table = $this->table('withdraw_transaction', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::unsignedInteger('user_id')->setComment('用户id'))
            ->addColumn(Column::unsignedInteger('token_id')->setComment('代币id'))
            ->addColumn(Column::decimal('amount', 32, 16)->setDefault(0)->setComment('金额'))
            ->addColumn(Column::decimal('fee', 32, 16)->setDefault(0)->setComment('手续费'))
            ->addColumn(Column::decimal('real_fee', 32, 16)->setDefault(0)->setComment('真实手续费'))
            ->addColumn(Column::decimal('pay_amount', 32, 16)->setDefault(0)->setComment('实际发币数量'))
            ->addColumn(Column::string('address_to')->setComment('到账地址'))
            ->addColumn(Column::boolean('status')->setDefault(0)->setComment('提现状态0:未审核，1:审核通过，2:审核拒绝'))
            ->addColumn(Column::timestamp('audit_time')->setNullable()->setComment('审核时间戳'))
            ->addTimestamps()
            ->create();
    }

}
