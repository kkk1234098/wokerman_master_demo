<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
require_once  'Autoloader.php';

// 注意：这里与上个例子不通，使用的是websocket协议
$worker = new Worker("tcp://172.26.78.202:8081");

// 启动4个进程对外提供服务
$worker->count = 1;
//心跳时间
define('HEARTBEAT_TIME', 5);

// 当收到客户端发来的数据后返回hello $data给客户端
$worker->onMessage = function($connection, $data)
{
	
    // 向客户端发送hello $data
    //$connection->worker->id='a2';
    $connection->send('you is id='.$connection->id.'-'.$connection->worker->id);
    $connection->lastMessageTime = time();
    echo "第".$connection->id.'-'.$connection->worker->id."号id的话：".$data."\n";
};



// 进程启动后设置一个每秒运行一次的定时器
$worker->onWorkerStart = function($worker) {
	Timer::add(1, function()use($worker){
		foreach($worker->connections as $connection) {
			$time_now = time();
			// 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
			if (empty($connection->lastMessageTime)) {
				//$connection->worker->id='a1';
				echo $connection->id.'-'.$connection->worker->id."号已经连接\n";
				$connection->lastMessageTime = $time_now;
				continue;
			}
			///检测十秒钟心跳
			if( ($time_now-$connection->lastMessageTime)>HEARTBEAT_TIME  ){
			//	$res=$connection->send('close you is id='.$connection->id.'-'.$connection->worker->id);
 			//	if($res){ echo '成功\n';  }else{ echo '失败';  }
				//sleep(1);
				$connection->close();
				echo $connection->id.'-'.$connection->worker->id."号被踢除\n";
				
				 

			}
			
			
			// 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
			 			if (empty($connection->lastMessageTime)) {
			 				$connection->lastMessageTime = $time_now;
			 				continue;
			 			}
			// 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
			 			if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
			 				$connection->close();
			 			}
		}
	});
};







// 运行worker
Worker::runAll();

?>
