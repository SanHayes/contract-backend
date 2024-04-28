<?php

use think\migration\db\Column;
use think\migration\Migrator;

class UserRule extends Migrator
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
            'comment'     => '会员规则表',
        ];
        $table = $this->table('user_rule', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::unsignedInteger('pid')->setDefault(0)->setComment('父ID'))
            ->addColumn(Column::string('name')->setDefault('')->setComment('名称'))
            ->addColumn(Column::string('title')->setDefault('')->setComment('标题'))
            ->addColumn(Column::string('remark')->setDefault('')->setComment('备注'))
            ->addColumn(Column::boolean('is_menu')->setDefault(0)->setComment('是否为菜单'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('状态'))
            ->addColumn(Column::tinyInteger('weigh')->setDefault(0)->setComment('权重'))
            ->addTimestamps()
            ->addSoftDelete()
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }
}
