<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-15
 * Time: 下午3:51
 */

namespace app\Controllers;


use Server\CoreBase\Controller;
use app\Models\TestModel;


class TestController extends Controller
{
    /**
     * @var TestTask
     */
    public $is_login;



    public function __construct()
    {
        parent::__construct();

    }

    public function http_tcpClient()
    {
        $data = ['controller_name' => "TestController", "method_name" => "testTcp", "data" => "test"];
        $this->rpc->setPath("TestController/testTcp", $data);
        $result = $this->rpc->coroutineSend($data);
        $this->http_output->end($result);
    }

    public function http_map_add()
    {
        $cache = Cache::getCache('TestCache');
        $cache->addMap('123');
        $this->http_output->end($cache->getAllMap());
    }

    public function http_tcp()
    {
        $this->sdrpc = get_instance()->getAsynPool('RPC');
        $data = $this->sdrpc->helpToBuildSDControllerQuest($this->context, 'MathService', 'add');
        $data['params'] = [1, 2];
        $result = $this->sdrpc->coroutineSend($data);
        $this->http_output->end($result);
    }

    public function http_ex()
    {
        throw new \Exception("test");
    }

    public function http_error()
    {
        $a = [];
        $a[1];
    }

    public function http_testModelMysql()
    {
        secho('title', 'start--');
        $model = $this->loader->model(TestModel::class, $this);
        $result = yield $model->msgTotal();
        $this->http_output->end($result);
    }

    public function http_testModelRedis()
    {
        $model = $this->loader->model(TestModel::class, $this);
        $result = $model->testRedis();
        $this->http_output->end($result);
    }

    /**
     * tcp的测试
     */
    public function testTcp()
    {
        var_dump($this->client_data->data);
        $this->send($this->client_data->data);
    }

    public function add()
    {
        $max = $this->client_data->max;
        if (empty($max)) {
            $max = 100;
        }
        $sum = 0;
        for ($i = 0; $i < $max; $i++) {
            $sum += $i;
        }
        $this->send($max);
    }

    public function http_testContext()
    {
        $this->getContext()['test'] = 1;
        $this->testModel = $this->loader->model('TestModel', $this);
        $this->testModel->contextTest();
        $this->http_output->end($this->getContext());
    }

    /**
     * mysql 事务协程测试
     */
    public function http_mysql_begin_coroutine_test()
    {
        $this->db->begin(function () {
            $result = $this->db->select("*")->from("account")->query();
            var_dump($result['client_id']);
            $result = $this->db->select("*")->from("account")->query();
            var_dump($result['client_id']);
        });
        $this->http_output->end(1);
    }


    /**
     * 绑定uid
     */
    public function bind_uid()
    {
        $this->bindUid($this->client_data->data, true);
    }

    /**
     * 效率测试
     * @throws \Server\CoreBase\SwooleException
     */
    public function efficiency_test()
    {
        $data = $this->client_data->data;
        $this->sendToUid(mt_rand(1, 100), $data);
    }

    /**
     * 效率测试
     * @throws \Server\CoreBase\SwooleException
     */
    public function efficiency_test2()
    {
        $data = $this->client_data->data;
        $this->send($data);
    }

    /**
     * mysql效率测试
     * @throws \Server\CoreBase\SwooleException
     */
    public function http_smysql()
    {
        $result = $this->db->select('*')->from('user')->limit(1)->query();

        $this->http_output->end($result, false);
    }

    public function http_amysql()
    {
        $result = get_instance()->getMysql()->select('*')->from('user')->pdoQuery();

        $this->http_output->end($result, false);
    }

    /**
     * 获取mysql语句
     */
    public function http_mysqlStatement()
    {
        $value = $this->mysql_pool->dbQueryBuilder->insertInto('account')->intoColumns(['uid', 'static'])->intoValues([[36, 0], [37, 0]])->getStatement(true);
        $this->http_output->end($value);
    }

    /**
     * http测试
     */
    public function http_test()
    {

        $this->http_output->end(22222);
    }

    public function http_redirect()
    {
        $this->redirectController('TestController', 'test');
    }

    /**
     * health
     */
    public function http_health()
    {
        $this->http_output->end('1');
    }

    /**
     * http redis 测试
     */
    public function http_redis()
    {
        $result = $this->redis_pool->getCoroutine()->get('testroute');
        $this->http_output->end($result, false);
    }

    /**
     * http redis 测试
     */
    public function http_redis2()
    {
        $this->redis_pool->getRedisPool()->get('testroute', function () {
            $this->http_output->end(1, false);
        });
    }

    /**
     * http redis 测试
     */
    public function http_setRedis()
    {
        $result = $this->redis_pool->getCoroutine()->set('testroute', 21, ["XX", "EX" => 10]);
        $this->http_output->end($result);
    }

    /**
     * http 同步redis 测试
     */
    public function http_aredis()
    {
        $value = get_instance()->getRedis()->get('testroute');
        $this->http_output->end($value, false);
    }

    public function http_demo()
    {
        if (empty($views)) {
            $views = 'app::Msg/msgList'; $array = [ 'controller' => 'TestController\html_test', 'message' => '页面不存在！', ];
        } else {
            $array = [ 'dumps' => [ 'ddd' => 3434, ], ];
        }
        $template = $this->loader->view($views);
        $data = [ 'dumps' => $_SERVER, ];
        $array = array_merge($array, $data);
        $this->http_output->response->end($template->render($array));
    }

    /**
     * html测试
     */
    //前台用户留言页面
    public function http_msgAdd()
    {
        $template = $this->loader->view('app::Home/msgAdd');

        $this->http_output->end($template->render());

    }
    //留言提交
    public function http_addRes()
    {

        $user_name = $this->http_input->getPost('username');
        $email = $this->http_input->getPost('email');
        $contnet= $this->http_input->getPost('content');
        $time=time();
        //数据验证
       if(empty($user_name) || empty($email) || empty($contnet)){
           $msg=0;
       }else{
           $model = $this->loader->model('TestModel', $this);
           $res = yield $model->insert($user_name,$email,$contnet);
          // $sql="insert into fb_msg (username, email , time, content)values('{$user_name}','{$email}',$time,'{$contnet}')";
           //$res= get_instance()->getMysql()->pdoQuery($sql);
           $msg=$res['result'];
       }

        $this->http_output->end($msg);


    }
    //前台留言列表
    public function http_msgList()
    {
        $template = $this->loader->view('app::Home/msgList');
        $model = $this->loader->model('TestModel', $this);
        $result = yield $model->select();
        $this->http_output->end($template->render($result));

    }

    //后台留言列表
    public function http_list()
    {

        $this->is_login();//登陆验证
        $template = $this->loader->view('app::Msg/msgList');
        $model = $this->loader->model('TestModel', $this);
        $data = yield $model->select();

        $this->http_output->end($template->render($data));



    }

    //ajax分页
    public function http_ajaxlist()
    {
        $size = 5;//每页显示数量和js里对应
        $page = $this->http_input->getPost('page');
        $pages = (isset($page) && $page) ? trim($page): 1;
        $offset=($pages-1) *  $size;
        $data= get_instance()->getMysql()->select('*')->from('fb_msg')->where('del',1)->limit($size,$offset)->pdoQuery();
        $this->http_output->end(json_encode($data['result']));

    }

    //留言删除
    public function http_delete()
    {
        //登陆验证
        if(empty($_SESSION['name'])){
            $msg=2;
        }else{
            $id = $this->http_input->getPost('id');
            //id验证
            if(!isset($id)){
                $msg=0;
            }else{
                $model = $this->loader->model('TestModel', $this);
                $res = yield $model->update($id);
                $msg=$res['result'];
            }
        }


        $this->http_output->end($msg);

    }


    //回复页面
    public function http_reply()
    {

        $this->is_login();//登陆验证
        $id = $this->http_input->getPost('id');
        $template = $this->loader->view('app::Msg/reply');
        $data = [
            'array' => $id

        ];
        $this->http_output->end($template->render($data));

    }

    //回复提交
    public function http_replyRes()
    {

        //登陆验证
        if(empty($_SESSION['name'])){
            $msg=2;
        }else{
            $uid = $this->http_input->getPost('uid');
            $data = get_instance()->getMysql()->select('id')->from('fb_msg')->where('del',1)->pdoQuery();
            $ids=$data ['result'];
            $contnet= $this->http_input->getPost('content');
            $time=time();
            $uids=array();
            foreach($ids as $v){
                $uids[]=$v['id'];
            }
            //uid验证
            if (!in_array($uid,$uids)){
                $msg=0;
            }else{
                $sql="insert into fb_chat (uid, content,time )values('{$uid}','{$contnet}',$time)";
                $res= get_instance()->getMysql()->pdoQuery($sql);
                $msg=$res['result'];
            }
        }

        $this->binduid();
        $this->http_output->end($msg);

    }

    //查看回复记录
    public function http_chat()
    {

        $this->is_login();//id验证
        $uid = $this->http_input->getPost('id');
        $res = get_instance()->getMysql()->select('*')->from('fb_chat')->where('uid',$uid)->pdoQuery();
        $data=$res['result'];
        $this->http_output->end($data);

    }

    public function http_danmu()
    {

        $template = $this->loader->view('app::danmu/index');

        $this->http_output->end($template->render());

    }

    public function http_ws()
    {

        //创建websocket服务器对象，监听0.0.0.0:9505端口
        $ws = new swoole_websocket_server("192.168.10.10", 8083);
        //监听WebSocket连接打开事件
        $ws->on('open', function ($ws, $request) {
            //var_dump($request->fd, $request->get, $request->server);
            //相当于记录一个日志吧，有连接时间和连接ip
            echo $request->fd.'-----time:'.date("Y-m-d H:i:s",$request->server['request_time']).'--IP--'.$request->server['remote_addr'].'-----';
        });

        //监听WebSocket消息事件
        $ws->on('message', function ($ws, $frame) {
            //记录收到的消息，可以写到日志文件中
            echo "Message: {$frame->data}\n";

            //遍历所有连接，循环广播
            foreach($ws->connections as $fd){
                //如果是某个客户端，自己发的则加上isnew属性，否则不加
                if($frame->fd == $fd){
                    $ws->push($frame->fd, $frame->data.',"isnew":""');
                }else{
                    $ws->push($fd, "{$frame->data}");
                }
            }
        });

        //监听WebSocket连接关闭事件
        $ws->on('close', function ($ws, $fd) {
            echo "client-{$fd} is closed\n";
        });

        $ws->start();

    }

    public function http_stone()
    {


        $danmu = $this->http_input->getPost('danmu');

        $sql="INSERT INTO `danmu`(danmu) VALUES ('{$danmu}')";

        $res = get_instance()->getMysql()->pdoQuery($sql);
        $data=$res['result'];
        $this->http_output->end($data);




    }
    public function http_query()
    {


        $sql="SELECT `danmu` FROM `danmu`";
        $res = get_instance()->getMysql()->pdoQuery($sql);
        $data=$res['result'];
        $array=[];
        foreach($data as $v){

            $array[]=$v['danmu'];
        }
        $this->http_output->end($array);

    }





    public function http_getAllTask()
    {
        $messages = get_instance()->getServerAllTaskMessage();
        $this->http_output->end(json_encode($messages));
    }

    /**
     * @return boolean
     */
    public function isIsDestroy()
    {
        return $this->is_destroy;
    }

    public function http_lock()
    {
        $lock = new Lock('test1');
        $result = $lock->coroutineLock();
        $this->http_output->end($result);
    }

    public function http_unlock()
    {
        $lock = new Lock('test1');
        $result = $lock->coroutineUnlock();
        $this->http_output->end($result);
    }

    public function http_destroylock()
    {
        $lock = new Lock('test1');
        $lock->destroy();
        $this->http_output->end(1);
    }

    public function http_testTask()
    {
        $testTask = $this->loader->task(TestTask::class, $this);
        $result = $testTask->test();
        $this->http_output->end($result);
    }

    public function http_testConsul()
    {
        $rest = ConsulServices::getInstance()->getRESTService('MathService', $this->context);
        $rest->setQuery(['one' => 1, 'two' => 2]);
        $reuslt = $rest->add();
        $this->http_output->end($reuslt['body']);
    }

    public function http_testConsul2()
    {
        $rest = ConsulServices::getInstance()->getRPCService('MathService', $this->context);
        $reuslt = $rest->add(1, 2);
        $this->http_output->end($reuslt);
    }

    public function http_testConsul3()
    {
        $rest = ConsulServices::getInstance()->getRPCService('MathService', $this->context);
        $reuslt = $rest->call('sum', [10000000], false, function (TcpClientRequestCoroutine $clientRequestCoroutine) {
            $clientRequestCoroutine->setTimeout(1000);
            $clientRequestCoroutine->setDowngrade(function () {
                return 123;
            });
        });
        $this->http_output->end($reuslt);
    }

    public function http_testRedisLua()
    {
        $value = $this->redis_pool->getCoroutine()->evalSha(getLuaSha1('sadd_from_count'), ['testlua', 100], 2, [1, 2, 3]);
        $this->http_output->end($value);
    }

    public function http_testTaskStop()
    {
        $task = $this->loader->task('TestTask', $this);
        $task->testStop();
    }

    public function http_echo()
    {
        $this->http_output->end(123, false);
    }

    /**
     * 事件处理
     */
    public function http_getEvent()
    {
        $data = EventDispatcher::getInstance()->addOnceCoroutine('unlock', function (EventCoroutine $e) {
            $e->setTimeout(10000);
        });
        //这里会等待事件到达，或者超时
        $this->http_output->end($data);
    }

    public function http_sendEvent()
    {
        EventDispatcher::getInstance()->dispatch('unlock', 'hello block');
        $this->http_output->end('ok');
    }

    public function http_testWhile()
    {
        $this->testModel = $this->loader->model('TestModel', $this);
        $this->testModel->testWhile();
        $this->http_output->end(1);
    }

    public function http_testMysqlRaw()
    {
        $selectMiner = $this->mysql_pool->dbQueryBuilder->select('*')->from('account');
        $selectMiner = $selectMiner->where('', '(status = 1 and dec in ("ss", "cc")) or name = "kk"', Miner::LOGICAL_RAW);
        $this->http_output->end($selectMiner->getStatement(false));
    }

    public function http_getAllUids()
    {
        $uids = get_instance()->coroutineGetAllUids();
        $this->http_output->end($uids);
    }

    public function http_testSC1()
    {
        $result = CatCacheRpcProxy::getRpc()->offsetExists('test.bc');
        $this->http_output->end($result, false);
    }

    public function http_testSC2()
    {
        unset(CatCacheRpcProxy::getRpc()['test.a']);
        $this->http_output->end(1, false);
    }


    public function http_testSC3()
    {
        CatCacheRpcProxy::getRpc()['test.a'] = ['a' => 'a', 'b' => [1, 2, 3]];
        $this->http_output->end(1, false);
    }

    public function http_testSC4()
    {
        $result = CatCacheRpcProxy::getRpc()->offsetGet('test');
        $this->http_output->end($result, false);
    }

    public function http_testSC5()
    {
        $result = CatCacheRpcProxy::getRpc()->getAll();
        $this->http_output->end($result, false);
    }

    public function http_testTimerCallBack()
    {
        $token = TimerCallBack::addTimer(2, TestModel::class, 'testTimerCall', [123]);
        $this->http_output->end($token);
    }

    public function http_testActor()
    {
        Actor::create(TestActor::class, "Test1");
        Actor::create(TestActor::class, "Test2");
        $this->http_output->end(123);
    }

    public function http_testActor2()
    {
        $rpc = Actor::getRpc("Test2");
        $beginid = $rpc->beginCo(function () use ($rpc) {
            $result = $rpc->test1();
            $result = $rpc->test2();
            //var_dump($result);
            $result = $rpc->test3();
        });
        $this->http_output->end(1);
    }

}
