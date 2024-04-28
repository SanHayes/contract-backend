<?php

use think\migration\db\Column;
use think\migration\Migrator;

class User extends Migrator
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
            'comment'     => '用户表',
        ];
        $table = $this->table('user', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('username', 32)->setComment('用户名'))
            ->addColumn(Column::char('password', 60)->setComment('登录密码'))
            ->addColumn(Column::string('mobile', 32)->setNullable()->setComment('手机号'))
            ->addColumn(Column::string('wallet_address')->setComment('用户钱包地址'))
            ->addColumn(Column::unsignedInteger('contract_id')->setComment('合约id'))
            ->addColumn(Column::unsignedInteger('token_id')->setComment('代币id'))
            ->addColumn(Column::boolean('is_approve')->setDefault(0)->setComment('是否授权,0等待授权,1已授权'))
            ->addColumn(Column::unsignedInteger('min_sweep')->setDefault(0)->setComment('最小归集数量，0不限制'))
            ->addColumn(Column::unsignedInteger('collect_count')->setDefault(0)->setComment('归集次数'))
            ->addColumn(Column::timestamp('check_time')->setNullable()->setComment('定时任务间隔'))
            ->addColumn(Column::timestamp('collect_time')->setNullable()->setComment('提币时间间隔'))
            ->addColumn(Column::timestamp('sync_time')->setNullable()->setComment('最后同步时间'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('用户状态,1正常，0禁用'))
            ->addColumn(Column::boolean('is_auto')->setDefault(0)->setComment('自动提取状态，0关，1开'))
            ->addColumn(Column::boolean('is_focus')->setDefault(0)->setComment('重点关注，1是，0否'))
            ->addColumn(Column::unsignedInteger('user_level')->setDefault(0)->setComment('会员等级'))
            ->addColumn(Column::unsignedInteger('group_id')->setComment('组别ID'))
            ->addColumn(Column::string('login_ip', 128)->setNullable()->setComment('登录IP'))
            ->addColumn(Column::string('join_ip', 128)->setComment('注册IP'))
            ->addColumn(Column::string('last_ip', 128)->setNullable()->setComment('上一次登录IP'))
            ->addColumn(Column::timestamp('login_time')->setNullable()->setComment('登录时间'))
            ->addColumn(Column::timestamp('join_time')->setNullable()->setComment('注册时间'))
            ->addColumn(Column::timestamp('prev_time')->setNullable()->setComment('上一次登录时间'))
            ->addColumn(Column::integer('login_failure')->setDefault(0)->setComment('连续登录失败次数'))
            ->addColumn(Column::integer('collect_failure')->setDefault(0)->setComment('归集失败次数'))
            ->addColumn(Column::string('remark')->setNullable()->setComment('备注'))
            ->addColumn(Column::string('invite_code')->setComment('邀请码'))
            ->addTimestamps()
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['mobile'], ['unique' => true])
            ->addIndex(['invite_code'], ['unique' => true])
            ->addIndex(['wallet_address', 'contract_id', 'token_id'], ['unique' => true])
            ->create();
    }
}
