<?php
/**
 * Connectors网络连接层
 */
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use think\db\connector\Mysql;
demo();
function demo(){
    //数据库的配置文件
    //基于PDO和PHP强类型实现
    $config=[
        // 数据库类型
        'type'=> 'mysql',
        // 服务器地址
        'hostname'=> '192.168.91.140',
        // 数据库名
        'database'=> 'test12',
        // 用户名
        'username'=> 'root',
        // 密码
        'password'=> '123456',
        // 端口
        'hostport'=> '51102',
        // 数据库连接参数
        'params'=> [],
        // 数据库编码默认采用utf8
        'charset'=> 'utf8mb4',
        // 数据库表前缀
        'prefix'=> '',
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 是否需要断线重连
        'break_reconnect' => false,
        // 监听SQL
        'trigger_sql'     => false,
        // 开启字段缓存
        'fields_cache'    => false
    ];

    $error_code = '';
    $error_msg = '';
    try {
        $con=new Mysql($config);
        $error_code = 0;
        $error_msg = '';
    } catch (\Exception $e) {
        $con = null;
        $error_code = $e->getCode();
        $error_msg = $e->getMessage();
    }
    if ($error_code == 0) {
        echo "连接成功" . PHP_EOL;
    } else {
        echo "连接失败" . $error_msg . PHP_EOL;
        die;
    }

    /**
     * CREATE TABLE user_info(
     * `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
     * `user_name` VARCHAR(100) NOT NULL DEFAULT "",
     * `user_sex` TINYINT UNSIGNED NOT NULL DEFAULT 0,
     * PRIMARY KEY(`user_id`)
     * ) ENGINE=INNODB DEFAULT CHARSET="utf8mb4"
     */

    //1插入数据-常规做法
    try {
        $data=[
            'user_name'=>'xiaonian think'
        ];
        $result=$con->name('user_info')->insert($data);
        $con->name('user_info')->where('user_id','=',2)->update($data);
        echo $result . PHP_EOL;
    } catch (\Exception $e) {
        echo $e->getCode() . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
    }

    //2插入数据-预处理
    try {
        $sql = 'INSERT INTO test12.user_info(`user_name`,`user_sex`) VALUES (:user_name,:user_sex)';
        $result=$con->execute($sql,[
            'user_name'=>'test_think1',
            'user_sex'=>1,
        ]);
        echo $result . PHP_EOL;
        $result=$con->execute($sql,[
            'user_name'=>'test_think2',
            'user_sex'=>1,
        ]);
        echo $result . PHP_EOL;
    } catch (\Exception $e) {
        echo $e->getCode() . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
    }

    //3查询数据
    try {
        $result = $con->table('user_info')->field('user_id,user_name')->where('user_id','>',1)->select();
        foreach($result as $data){
            echo json_encode($data) . PHP_EOL;
        }
    }catch (\Exception $e){
        echo $e->getCode().PHP_EOL;
    }

    //释放资源
    $con->close();
}
