<?php

use think\migration\db\Column;
use think\migration\Migrator;

class Problem extends Migrator
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
            'comment'     => 'FAQ表',
        ];
        $table = $this->table('problem', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('title')->setComment('标题'))
            ->addColumn(Column::text('content')->setComment('内容'))
            ->addColumn(Column::string('language')->setComment('语言'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('是否可用状态，0:不可用，1:可用'))
            ->addColumn(Column::tinyInteger('sort')->setDefault(0)->setComment('排序'))
            ->addTimestamps()
            ->create();
//        $data = [
//            [
//                'title'    => '',
//                'content'  => '',
//                'language' => '',
//            ],
//        ];
//        $table->insert($data)
//            ->save();
    }
}
