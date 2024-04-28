<?php

use think\migration\db\Column;
use think\migration\Migrator;

class Parameter extends Migrator
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
            'comment'     => '配置参数表',
        ];
        $table = $this->table('parameter', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('name')->setComment('变量名'))
            ->addColumn(Column::text('value')->setComment('变量值'))
            ->addColumn(Column::string('tip')->setNullable()->setComment('变量描述'))
            ->addColumn(Column::string('rule')->setDefault('')->setComment('验证规则'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('状态,1:启用,0：禁用'))
            ->addColumn(Column::integer('weigh')->setDefault(0)->setComment('排序'))
            ->addTimestamps()
            ->create();
        $data = [
            [
                'name'  => 'web_name',
                'value' => '网站',
                'tip'   => '网站名称',
            ],
            [
                'name'  => 'online_service',
                'value' => '',
                'tip'   => '在线客服',
            ],
            [
                'name'  => 'whatsapp_service',
                'value' => '',
                'tip'   => 'WhatsApp客服',
            ],
            [
                'name'  => 'telegram_service',
                'value' => '',
                'tip'   => 'Telegram客服',
            ],
            [
                'name'  => 'logo',
                'value' => '',
                'tip'   => 'LOGO',
            ],
            [
                'name'  => 'is_close',
                'value' => '0',
                'tip'   => '网站开关',
            ],
            [
                'name'  => 'home_banner',
                'value' => '',
                'tip'   => '首页Banner图',
            ],
        ];
        $table->insert($data)
            ->save();
    }

}
