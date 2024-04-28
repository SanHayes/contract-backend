<?php

use think\migration\db\Column;
use think\migration\Migrator;

class Attachment extends Migrator
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
            'comment'     => '附件表',
        ];
        $table = $this->table('attachment', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('url')->setComment('物理路径'))
            ->addColumn(Column::unsignedInteger('file_size')->setDefault(0)->setComment('文件大小'))
            ->addColumn(Column::string('storage')->setDefault('local')->setComment('存储位置'))
            ->addColumn(Column::string('sha1')->setComment('文件sha1编码'))
            ->addTimestamps()
            ->addSoftDelete()
            ->create();
    }
}
