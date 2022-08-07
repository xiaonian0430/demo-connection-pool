<?php
/**
 * 连接池
 */
declare(strict_types=1);
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Runtime;
use Swoole\Timer;
Runtime::enableCoroutine();
const N = 1024;
$s = microtime(true);
Coroutine\run(function () {

    $pool=DBPool::getInstance()->init()->checkFreeConnection();

    //查询实例
    for($i=0;$i<N;$i++){
        Coroutine::create(function () use ($pool) {
            $pdo = $pool->get();
            if($pdo){
                $statement = $pdo->prepare('SELECT ? + ?');
                if (!$statement) {
                    throw new RuntimeException('Prepare failed');
                }
                $a = mt_rand(1, 100);
                $b = mt_rand(1, 100);
                $result = $statement->execute([$a, $b]);
                if (!$result) {
                    throw new RuntimeException('Execute failed');
                }
                $result = $statement->fetchAll();
                if ($a + $b !== (int)$result[0][0]) {
                    throw new RuntimeException('Bad result');
                }

                //归还连接池
                $pool->put($pdo);
            }
        });
    }
});

class DBPool{
    private int $min_connections; // 最小连接数
    private int $max_connections; // 最大连接数
    private int $count; // 当前连接数
    private float $connect_timeout; // 连接超时时间
    private float $wait_timeout; // 等待超时时间
    private int $heartbeat; // 心跳检测
    private Channel $connections; // 连接池
    private array $useConnections; // 连接池
    protected float $max_idle_time; // 用于空闲连接回收判断

    public static DBPool $instance;

    public function __construct(){
        $this->min_connections=10;
        $this->max_connections=100;
        $this->connect_timeout=10.0;
        $this->wait_timeout=3.0;
        $this->heartbeat=10;
        $this->max_idle_time=60.0;
        $this->connections=new Channel($this->max_connections+1);
    }

    public static function getInstance(): DBPool
    {
        if(is_null(self::$instance)){
            self::$instance=new self();
        }
        return self::$instance;
    }

    private function createConnObject(): PDO
    {
        $server_host = '192.168.91.139';
        $server_port = 51102;
        $username = 'root';
        $password = '123456';
        $database = 'test12';
        $dsn = 'mysql:dbname='.$database.';host=' . $server_host . ';port=' . $server_port;
        //(PHP 5 >= 5.1.0, PHP 7, PHP 8, PECL pdo >= 0.1.0)
        return new \PDO($dsn, $username, $password);
    }

    public function init(): static
    {
        for ($i = 0; $i < $this->min_connections; $i++) {
            try{
                $conn = $this->createConnObject();
            }catch (\Exception $e){
                $conn=false;
            }
            if($conn){
                $this->count++;
                $this->connections->push([
                    'last_used_time'=>time(),
                    'conn' => $conn
                ]);
            }
        }
        return $this;
    }

    /**
     * 获取连接
     * @return PDO|false
     */
    public function get(): PDO|false
    {
        if ($this->connections->isEmpty() && $this->count < $this->max_connections) {
            try{
                $conn = $this->createConnObject();
            }catch (\Exception $e){
                $conn=false;
            }
            if($conn){
                $this->count++;
                $obj=[
                    'conn'=>$conn,
                    'last_used_time'=>time(),
                ];
            }else{
                $obj=[];
            }
        }else{
            $obj = $this->connections->pop($this->wait_timeout);
        }
        if(isset($obj['conn'])){
            $cid=Coroutine::getCid();
            $this->useConnections[$cid]=$obj;
            return $obj['conn'];
        }else{
            return false;
        }
    }

    /**
     * 回收连接
     * @param $conn
     */
    public function put($conn)
    {
        if($conn){
            $obj=[
                'last_used_time' => time(),
                'conn' => $conn
            ];
            $this->connections->push($obj);
        }
    }

    /**
     * 回收空闲连接
     */
    public function checkFreeConnection(): static
    {
        // 每 2 分钟检测一下空闲连接
        Timer::tick($this->heartbeat * 1000,function (){
            while (!$this->connections->isEmpty()) {
                $connObj = $this->connections->pop(0.001);
                $nowTime = time();
                $conn = $connObj['conn'];
                $lastUsedTime = $connObj['last_used_time'];

                // 当前连接数大于最小的连接数，并且回收掉空闲的连接
                if ($this->count > $this->min_connections && ($nowTime - $lastUsedTime > $this->max_idle_time)) {
                    unset($conn);
                    $this->count--;
                } else {
                    $conn->query('select 1');
                    $connObj['last_used_time']=time();
                    $this->connections->push($connObj);
                }
            }

            //检测使用中的连接，如果协程不在了，则停止执行，反之发送心跳
            foreach ($this->useConnections as $cid=>$usedObj){
                $conn = $usedObj['conn'];
                if(Coroutine::exists($cid)){
                    $conn->query('select 1');
                    $connObj['last_used_time']=time();
                    $this->useConnections[$cid]=$connObj;
                }else{
                    unset($conn);
                    unset($this->useConnections[$cid]);
                    $this->count--;
                }
            }
        });
        return $this;
    }
}