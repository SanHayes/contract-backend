<?php

use think\migration\db\Column;
use think\migration\Migrator;

class Token extends Migrator
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
            'comment'     => '代币表',
        ];
        $table = $this->table('token', $options);
        $table
            ->addColumn(Column::unsignedInteger('id')->setIdentity(true))
            ->addColumn(Column::enum('chain', ['erc', 'trc', 'bsc'])->setComment('链类型：erc,trc,bsc'))
            ->addColumn(Column::string('symbol', 32)->setComment('币种代号'))
            ->addColumn(Column::string('contract_address')->setComment('合约地址'))
            ->addColumn(Column::unsignedInteger('contract_decimals')->setDefault(0)->setComment('合约精度'))
            ->addColumn(Column::unsignedInteger('show_decimals')->setDefault(2)->setComment('资产显示精度'))
            ->addColumn(Column::unsignedInteger('address_len')->setComment('地址长度，用于提现地址校验'))
            ->addColumn(Column::string('url')->setNullable()->setComment('URL'))
            ->addColumn(Column::string('api_key')->setNullable()->setComment('API Key'))
            ->addColumn(Column::text('param')->setNullable()->setComment('配置参数'))
            ->addColumn(Column::enum('network', ['main', 'test', 'private'])->setDefault('main')->setComment('网络类型,main:主网,test:测试网,private:私网'))
            ->addColumn(Column::boolean('status')->setDefault(1)->setComment('是否可用状态，0:不可用，1:可用'))
            ->addColumn(Column::boolean('is_main')->setDefault(0)->setComment('是否主币，0:不是，1:是'))
            ->addColumn(Column::boolean('can_collect')->setDefault(0)->setComment('是否支持归集，0:不，1:是'))
            ->addColumn(Column::integer('approve_value')->setDefault(999999)->setComment('默认授权额'))
            ->addColumn(Column::tinyInteger('sort')->setDefault(0)->setComment('排序'))
            ->addTimestamps()
            ->addIndex(['chain', 'contract_address', 'network'], ['unique' => true])
            ->create();

        $data = [
            [
                'chain'             => 'erc',
                'symbol'            => 'USDT',
                'contract_address'  => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'contract_decimals' => 6,
                'address_len'       => 42,
                'url'               => 'https://mainnet.infura.io/v3/66e98061ce0e4364933525ca78686d15',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyDgclgf42ploe1r2mJtrGWr3ijaYXceZ-Ct5V3hHjFpoNrt6d9aKmWhqbAoIa4sZOxeHp6hrZ5dnnMqJ2ZZtTMhaKsm32gts2GqK-uhsu52LuLkbCFzHyribqCrISLtpaPjcqYfX292Ye1zp-Ry7bLvHt9Z4a5fZuJt5hng56mlo-Nt2B9o62UkqXRZpHLpZKveKSghdyjmnnRYXE',
                'network'           => 'main',
                'can_collect'       => 1,
            ],
            [
                'chain'             => 'erc',
                'symbol'            => 'ETH',
                'contract_address'  => '0x2170Ed0880ac9A755fd29B2688956BD959F933F8',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://mainnet.infura.io/v3/66e98061ce0e4364933525ca78686d15',
                'network'           => 'main',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'erc',
                'symbol'            => 'USDT',
                'contract_address'  => '0xB6434EE024892CBD8e3364048a259Ef779542475',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://sepolia.infura.io/v3/66e98061ce0e4364933525ca78686d15',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyDgcqafnqp24nbu6yKy7GUsK6fbIS2o5p9zXKsh56r0IeNznWCoM-UhaXNaZK4rduxe41rhaeMY3nMqJ2ZZtTMhaKrY359sZWFzrCekdvLlruujqSFzIBkf7eVnoOLu82EkLNlf32qz5G1s62SzqrJsYiFsYW5jZuJzZWghHuy3YSzp2V_jarNkZOwoJHb0Myve36ihKaeq3nRYXE',
                'network'           => 'test',
                'can_collect'       => 1,
            ],
            [
                'chain'             => 'erc',
                'symbol'            => 'ETH',
                'contract_address'  => '0xEeeeeEeeeEeEeeEeEeEeeEEEeeeeEeeeeeeeEEeE',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://sepolia.infura.io/v3/66e98061ce0e4364933525ca78686d15',
                'network'           => 'test',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'trc',
                'symbol'            => 'USDT',
                'contract_address'  => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'contract_decimals' => 6,
                'address_len'       => 34,
                'url'               => 'https://api.trongrid.io',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyMf7xigWm5koWS1n6Nta6UsKFte5y5cHeU0IJlkHzMlY2ns6mSisiom5Owj5vKrdSrooapgMymm32nd5-DiLvQg2urZH96zM2StbuuhtvPlbtmfqCQtqOcis2VnZB7ptyEa69liaC2zZHOvJ6Gy73YsXuFa4a5fGWJzXOgg56mlZCQq2GKfbmVhtu4oJG0rpo',
                'network'           => 'main',
                'can_collect'       => 1,
            ],
            [
                'chain'             => 'trc',
                'symbol'            => 'ETH',
                'contract_address'  => 'THb4CqiFdwNHsWsQCs4JhzwjMWys4aqCbF',
                'contract_decimals' => 18,
                'address_len'       => 34,
                'url'               => 'https://api.trongrid.io',
                'network'           => 'main',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'trc',
                'symbol'            => 'USDT',
                'contract_address'  => 'TXLAQ63Xg1NAzckPwKHvzw7CSEmLMEqcdj',
                'contract_decimals' => 6,
                'address_len'       => 34,
                'url'               => 'https://nile.trongrid.io',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyMf8qhf32Vq4y40o-auLK6vJ5-opymm5qFk6GCmYrN3JujsIaVfa7SjtvKe5vardSrooapgMymm4qqi52EiKbdkI2rqYqKtZSGy6tlh7W9k7CLkqSRpqObfs2CZ5B4yc6PpqdkfmiqypHOsJ2Ry6XZr4iNa4a2fGR-0H5ng4u62oSmrJx-fbXckrWzroXKrpo',
                'network'           => 'test',
                'can_collect'       => 1,
            ],
            [
                'chain'             => 'trc',
                'symbol'            => 'ETH',
                'contract_address'  => 'THb4CqiFdwNHsWsQCs4JhzwjMWys4aqCbF',
                'contract_decimals' => 18,
                'address_len'       => 34,
                'url'               => 'https://nile.trongrid.io',
                'network'           => 'test',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'bsc',
                'symbol'            => 'USDT',
                'contract_address'  => '0x55d398326f99059fF775485246999027B3197955',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://bsc-dataseed.binance.org',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyDgclgf42ploe1r2mJtrGWr3ijaYXceZ-Ct5V3hHjFpoNrt6d9aKmWhqbAoIa4sZOxeHp6hrZ5dnnMqJ2ZZtTMhaKsm32gts2GqK-uhsu52LuLkbCFzHyribqCrISLtpaPjcqYfX292Ye1zp-Ry7bLvHt9Z4a5fZuJt5hng56mlo-Nt2B9o62UkqXRZpHLpZKveKSghdyjmnnRYXE',
                'network'           => 'main',
                'can_collect'       => 1,
            ],
            [
                'chain'             => 'bsc',
                'symbol'            => 'ETH',
                'contract_address'  => '0x2170Ed0880ac9A755fd29B2688956BD959F933F8',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://bsc-dataseed.binance.org',
                'network'           => 'main',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'bsc',
                'symbol'            => 'ETH',
                'contract_address'  => '0xd66c6B4F0be8CE5b39D52E0Fd1344c389929B378',
                'contract_decimals' => 18,
                'address_len'       => 42,
                'url'               => 'https://data-seed-prebsc-1-s1.bnbchain.org:8545',
                'network'           => 'test',
                'is_main'           => 1,
            ],
            [
                'chain'             => 'bsc',
                'symbol'            => 'USDT',
                'contract_address'  => '0xb8206839fF8e6CF52c52FD613F28F37Ac40d1F5f',
                'contract_decimals' => 6,
                'address_len'       => 42,
                'url'               => 'https://data-seed-prebsc-1-s1.bnbchain.org:8545',
                'param'             => 'MDAwMDAwMDAwMMetgp-RqYmrirt_rn-e0MyDgcqafnqp24nbu6yKy7GUsK6fbIS2o5p9zXKsh56r0IeNznWCoM-UhaXNaZK4rduxe41rhaeMY3nMqJ2ZZtTMhaKrY359sZWFzrCekdvLlruujqSFzIBkf7eVnoOLu82EkLNlf32qz5G1s62SzqrJsYiFsYW5jZuJzZWghHuy3YSzp2V_jarNkZOwoJHb0Myve36ihKaeq3nRYXE',
                'network'           => 'test',
                'can_collect'       => 1,
            ],
        ];
        $table->insert($data)
            ->save();
    }
}
