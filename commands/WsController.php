<?php
namespace app\commands;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use yii\console\Controller;

class WsController extends Controller{

    public array $connections = [];

    public function actionRun(){
        $ws_worker = new Worker('websocket://localhost:8080');
        $ws_worker->onConnect = [$this, 'onConnect'];
        $ws_worker->onClose = [$this, 'onClose'];
        $ws_worker->onMessage = [$this, 'actionOnMessage'];
        $ws_worker->enviarMensaje = [$this, 'enviarMensaje'];
        Worker::runAll();
    }

    public function onConnect(TcpConnection $connection){
        $this->stdout("New connection: {$connection->id}\n");
        $this -> connections[$connection -> id] = $connection;
    }

    public function onClose(TcpConnection $connection){
        $this->stdout("Connection closed: {$connection->id}\n");
        //unset($this->connections[$connection->id]);
    }

    public function actionOnMessage(TcpConnection $connection, $data){

        foreach($this->connections as $connection2){
            $connection2->send(json_encode(['codigo' => $data]));
        }
        $this->stdout("messa sending: {$connection->id}\n");
    }


    private function enviarMensaje(TcpConnection $connection, $mensaje)
    {
        foreach($this->connections as $connection){
            $connection->send(json_encode(['data' => $mensaje]));
        }
    }
}