<?php
/**
 * Connectors网络连接层-mysqli面向过程
 */

declare(strict_types=1);

//数据库的配置文件
$server_host='192.168.91.139';
$server_port=51102;
$username='root';
$password='123456';
$database='test12';

$error_code='';
$error_msg='';
try{
    //(PHP 5, PHP 7, PHP 8)
    $con=\mysqli_connect($server_host,$username,$password,$database,$server_port);
    if (!$con) {
        $error_code=-1;
        $error_msg=$con->connect_error;
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
    $result=mysqli_query($con,$sql);
    echo $result.PHP_EOL;
}catch (\Exception $e){
    echo $e->getCode().PHP_EOL;
    echo $e->getMessage().PHP_EOL;
}

//2插入数据-预处理
try{
    $sql='INSERT INTO test12.user_info(`user_name`,`user_sex`) VALUES (?,?)';
    $statement=mysqli_prepare($con,$sql);
    if($statement){
        $statement->bind_param('si',$user_name,$user_sex);

        $user_name='test13';
        $user_sex=1;
        $result=$statement->execute();
        echo $result.PHP_EOL;

        $user_name='test23';
        $user_sex=0;
        $result=$statement->execute();
        echo $result.PHP_EOL;
    }else{
        echo '-1'.PHP_EOL;
    }
}catch(\Exception $e){
    echo $e->getCode().PHP_EOL;
    echo $e->getMessage().PHP_EOL;
}

//3查询数据
$sql='SELECT user_id,user_name FROM test12.user_info WHERE user_id>1';
$result=mysqli_query($con,$sql);
if($result){
    //关联数组
    //fetch_assoc();
    //fetch_array(MYSQLI_ASSOC);
    //索引的值数组
    //fetch_array(MYSQLI_NUM);
    //关联数组和索引数组都包含
    //fetch_array(MYSQLI_BOTH);
    while($data=$result->fetch_array(MYSQLI_ASSOC)){
        echo json_encode($data).PHP_EOL;
    }
}else{
    echo '获取失败'.PHP_EOL;
}


//释放资源
$con->close();

