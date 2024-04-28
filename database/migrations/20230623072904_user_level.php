<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UserLevel extends Migrator
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
            'id'          => false,
            'primary_key' => 'id',
            'comment'     => '用户等级表',
        ];
        $table = $this->table('user_level', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('name')->setComment('等级名称'))
            ->addColumn(Column::decimal('balance')->setDefault(0)->setComment('最低USDT数量'))
            ->addColumn(Column::decimal('rate1')->setDefault(0)->setComment('一级佣金率(%)'))
            ->addColumn(Column::decimal('rate2')->setDefault(0)->setComment('二级佣金率(%)'))
            ->addColumn(Column::decimal('rate3')->setDefault(0)->setComment('三级佣金率(%)'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('状态'))
            ->addTimestamps()
            ->create();
    }
}
