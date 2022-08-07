<?php
/**
 * Connectors网络连接层
 */
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use Hyperf\DbConnection\Db;
demo();
function demo(){

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
        $result=Db::table('user_info')->insert($data);
        Db::table('user_info')->where('user_id','=',2)->update($data);
        echo $result . PHP_EOL;
    } catch (\Exception $e) {
        echo $e->getCode() . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
    }

    //2插入数据-预处理
    try {
        $sql = 'INSERT INTO test12.user_info(`user_name`,`user_sex`) VALUES (?,?)';
        $result=Db::insert($sql,[
            'test_hyperf1',1
        ]);
        echo $result . PHP_EOL;
        $result=Db::insert($sql,[
            'test_hyperf2',1
        ]);
        echo $result . PHP_EOL;
    } catch (\Exception $e) {
        echo $e->getCode() . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
    }

    //3查询数据
    try {
        $result = Db::table('user_info')->select(['user_id,user_name'])->where('user_id','>',1)->get();
        foreach($result as $data){
            echo json_encode($data) . PHP_EOL;
        }
    }catch (\Exception $e){
        echo $e->getCode().PHP_EOL;
    }
}
