<?php

use app\admin\auth\AuthGuard;
use think\migration\db\Column;
use think\migration\Migrator;

class Admin extends Migrator
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
            'comment'     => '管理员表',
        ];
        $table = $this->table('admin', $options);
        $table->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::string('nickname', 32)->setDefault('')->setComment('用户昵称'))
            ->addColumn(Column::string('username', 32)->setComment('用户名'))
            ->addColumn(Column::char('password', 60)->setComment('登录密码'))
            ->addColumn(Column::string('mobile', 32)->setNullable()->setComment('手机号'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('用户状态,1正常，0禁用'))
            ->addColumn(Column::string('login_ip', 128)->setNullable()->setComment('登录IP'))
            ->addColumn(Column::string('join_ip', 128)->setComment('注册IP'))
            ->addColumn(Column::string('last_ip', 128)->setNullable()->setComment('上一次登录IP'))
            ->addColumn(Column::timestamp('login_time')->setNullable()->setComment('登录时间'))
            ->addColumn(Column::timestamp('join_time')->setNullable()->setComment('注册时间'))
            ->addColumn(Column::timestamp('prev_time')->setNullable()->setComment('上一次登录时间'))
            ->addColumn(Column::integer('login_failure')->setDefault(0)->setComment('连续登录失败次数'))
            ->addTimestamps()
            ->addSoftDelete()
            ->addIndex(['username'], ['unique' => true])
            ->create();
        //创建管理员
        $auth = AuthGuard::instance();
        $auth->register('admin', '123456', 'admin');
    }
}
