<?php

use think\migration\db\Column;
use think\migration\Migrator;

class UserLog extends Migrator
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
            'comment'     => '会员日志表',
        ];
        $table = $this->table('user_log', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::integer('user_id')->setComment('用户ID'))
            ->addColumn(Column::string('domain')->setComment('域名'))
            ->addColumn(Column::string('url')->setComment('url'))
            ->addColumn(Column::text('param')->setNullable()->setComment('参数'))
            ->addColumn(Column::string('ip')->setComment('IP'))
            ->addColumn(Column::string('user_agent')->setComment('User-Agent'))
            ->addTimestamps()
            ->create();
    }
}
