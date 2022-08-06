<?php
/**
 * Connectors
 */
declare(strict_types=1);
use Swoole\Coroutine;
use function Swoole\Coroutine\run as coRun;
use function Swoole\Coroutine\go as coGo;
//让原来的同步IO的代码变成可以协程调度的异步IO，即一键协程化
Coroutine::set(['hook_flags' => SWOOLE_HOOK_TCP]);

//创建协程容器
coRun(function() {

    for($i=0;$i<1;$i++){
        //在协程容器中创建一个协程
        coGo(function () use($i) {
            demo();
        });
    }
});
function demo(){
    //数据库的配置文件
    $server_host='192.168.91.140';
    $server_port=51102;
    $username='root';
    $password='123456';
    $database='test12';

    $error_code='';
    $error_msg='';
    try{
        $dsn='mysql:host='.$server_host.';port='.$server_port;
        //(PHP 5 >= 5.1.0, PHP 7, PHP 8, PECL pdo >= 0.1.0)
        $con=new \PDO($dsn,$username,$password);
        if(!$con){
            $error_code=-1;
            $error_msg='error';
        }else{
            $error_code=0;
            $error_msg='';
        }
    }catch (\Exception $e){
        $con=null;
        $error_code=$e->getCode();
        $error_msg = $e->getMessage();
    }
    if($error_code==0){
        echo "连接成功".PHP_EOL;
    }else{
        echo "连接失败".$error_msg.PHP_EOL;
        die;
    }

    /**
    CREATE TABLE user_info(
    `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_name` VARCHAR(100) NOT NULL DEFAULT "",
    `user_sex` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(`user_id`)
    ) ENGINE=INNODB DEFAULT CHARSET="utf8mb4"
     */

    //1插入数据-常规做法
    try {
        $sql='INSERT INTO test12.user_info(`user_name`) VALUES ("xiaonian")';
        $con->exec($sql);
        echo $con->errorCode().PHP_EOL;
        var_dump($con->errorInfo());
    }catch (\Exception $e){
        echo $e->getCode().PHP_EOL;
        echo $e->getMessage().PHP_EOL;
    }

    //2插入数据-预处理
    try{
        $sql='INSERT INTO test12.user_info(`user_name`,`user_sex`) VALUES (:user_name,:user_sex)';
        $statement=$con->prepare($sql);
        if($statement){
            $statement->bindParam(':user_name',$user_name);
            $statement->bindParam(':user_sex',$user_sex);

            $user_name='test1';
            $user_sex=1;
            $statement->execute();

            echo $statement->errorCode().PHP_EOL;

            $user_name='test2';
            $user_sex=0;
            $statement->execute();
            echo $statement->errorCode().PHP_EOL;
        }else{
            echo '-1'.PHP_EOL;
        }
    }catch(\Exception $e){
        echo $e->getCode().PHP_EOL;
        echo $e->getMessage().PHP_EOL;
    }

    //3查询数据
    $sql='SELECT user_id,user_name FROM test12.user_info WHERE user_id>1';
    $result=$con->query($sql);
    if($result){
        //关联数组
        //fetch(PDO::FETCH_ASSOC);
        //索引的值数组
        //$result->fetch(PDO::FETCH_NUM);
        //关联数组和索引数组都包含
        //fetch(PDO::FETCH_BOTH);
        while($data=$result->fetch(PDO::FETCH_ASSOC)){
            echo json_encode($data).PHP_EOL;
        }
    }else{
        echo '获取失败'.PHP_EOL;
    }

    //释放资源
    $con = null;
}
