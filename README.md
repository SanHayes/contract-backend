### 合约后端

|软件|版本|备注|
|----|----|----|
|Nginx| 1.22.1||
|MySQL| 8.*||
|PHP|7.4|不兼容5.6和8，会出问题|
|phpMyAdmin| 5.*|可以不安装|
|Redis| 7.* |必须安装|
|宝塔WebHook| 2.2|必须安装|

### WebHook添加 更新-USDT
#!/bin/bash
echo ""
#输出当前时间
date --date='0 days ago' " %Y-%m-%d %H:%M:%S"
echo "Start"
#项目路径，换成实际的web根目录
webroot="/www/wwwroot/usdt.com"
#git repo地址
repo="git@github.com:Lucifer20211202/contract-backend.git"
echo "Web站点路径：$webroot"
    #判断项目路径是否存在
if [ -d "$webroot" ]; then
    cd $webroot
    #判断是否存在git目录
    if [ ! -d ".git" ]; then
	    echo "在该目录下克隆 git"
	    git clone $repo .
    fi
    #拉取最新的项目文件
    git reset --hard origin/main
    git pull
    #composer 更新
    composer install --ignore-platform-reqs
    #重启服务            
    #设置目录权限
    chown -R www:www $webroot
    # 重启php-fpm
    service php-fpm-74 restart
    echo "End"
    exit
else
    echo "该项目路径不存在"
    echo "End"
    exit
fi

### 本地部署
- 准备.env文件并配置数据库链接等
```shell
cp .env.local .env
```
- 安装依赖
```shell
composer install --ignore-platform-reqs
```
- 数据迁移
```shell
php think migrate:run
```

### 特别注意
- PHP取消禁用函数putenv、proc_open

### 计划任务
1分钟执行
1.链上数据同步-批量
/usr/bin/php /www/wwwroot/usdt.com/think batchsync
2.计算质押收益
/usr/bin/php /www/wwwroot/usdt.com/think stake

5分钟执行
轮询获取txid结果
/usr/bin/php /www/wwwroot/usdt.com/think tx

### 伪静态
location ~* (runtime|application)/{
	return 403;
}
location / {
	if (!-e $request_filename){
		rewrite  ^(.*)$  /index.php?s=$1  last;   break;
	}
}

## 测试数据
|用户类型|用户名|密码|
|----|----|----|
|总管理员|admin|123456|
