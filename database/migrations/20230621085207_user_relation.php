<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UserRelation extends Migrator
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
            'comment'     => '用户关系表',
        ];
        $table = $this->table('user_relation', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::unsignedInteger('user_id')->setComment('用户id'))
            ->addColumn(Column::string('rel')->setNullable()->setComment('推荐关系字符集[不含自身user_id，以上级uid进行拼接]'))
            ->addColumn(Column::unsignedInteger('team_total')->setDefault(0)->setComment('团队总人数'))
            ->addColumn(Column::unsignedInteger('child_num')->setDefault(0)->setComment('子代人数'))
            ->addColumn(Column::unsignedInteger('direct_id')->setDefault(0)->setComment('直推人id'))
            ->addColumn(Column::unsignedInteger('pid1')->setDefault(0)->setComment('1级推荐人id'))
            ->addColumn(Column::unsignedInteger('pid2')->setDefault(0)->setComment('2级推荐人id'))
            ->addColumn(Column::unsignedInteger('pid3')->setDefault(0)->setComment('3级推荐人id'))
            ->addTimestamps()
            ->addSoftDelete()
            ->create();
    }
}
