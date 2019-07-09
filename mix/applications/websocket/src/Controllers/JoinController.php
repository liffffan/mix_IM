<?php

namespace WebSocket\Controllers;

use Mix\Helper\JsonHelper;
use Mix\Redis\Coroutine\RedisConnection;
use Mix\WebSocket\Frame\TextFrame;
use WebSocket\Models\JoinForm;
use WebSocket\Models\LoginForm;
use WebSocket\Models\PersonForm;

/**
 * Class JoinController
 * @package WebSocket\Controllers
 * @author liu,jian <coder.keda@gmail.com>
 */
class JoinController
{

    /**
     * 用户登录
     * @param $params
     * @param $id
     */

    public function actionLogin($params)
    {
        // 验证数据
        $model             = new LoginForm();
        $model->attributes = $params;
        $model->setScenario('actionLogin');
        if (!$model->validate()) {
            $response = new TextFrame([
                'data' => JsonHelper::encode([
                    'result' => [
                        'message' => $model->getError(),
                    ],
                    'id'     => $params['id'],
                ], JSON_UNESCAPED_UNICODE),
            ]);
            app()->ws->push($response);
            return;
        }


        $redis = app()->redisPool->getConnection();
        $redis->set($params['id'], app()->ws->fd);
        $redis->release();


        // 检测消息是否发送成功
        app()->ws->push(new TextFrame([
            'data' => JsonHelper::encode([
                'result' => [
                    'message' => "登录成功",
                ],
                'id'     => $params['id'],
            ], JSON_UNESCAPED_UNICODE),
        ]));



    }




    /**
     * 加入房间
     * @param $params
     * @param $id
     */
    public function actionRoom($params, $id)
    {


        // 验证数据
        $model             = new JoinForm();
        $model->attributes = $params;
        $model->setScenario('actionRoom');
        if (!$model->validate()) {
            $response = new TextFrame([
                'data' => JsonHelper::encode([
                    'result' => [
                        'message' => $model->getError(),
                    ],
                    'id'     => $id,
                ], JSON_UNESCAPED_UNICODE),
            ]);
            app()->ws->push($response);
            return;
        }

        // 权限验证
        /*
        $db     = app()->dbPool->getConnection();
        $ret = $db->table('user')->where(['uid', '=', 1])->get();

        if ($model->token != $ret[0]['token']){
            $response = new TextFrame([
                'data' => JsonHelper::encode([
                    'result' => [
                        'message' => 'token验证不通过'
                    ],
                    'id'     => $id,
                ], JSON_UNESCAPED_UNICODE),
            ]);
            app()->ws->push($response);
            return;
        }

        $db->release();
        */


        // 保存当前加入的房间
        app()->tcpSession->set('roomid', $model->roomid);

        // 重复加入处理
        if ($subConn = app()->tcpSession->get('subConn')) {
            /** @var \Mix\Redis\Coroutine\RedisConnection $subConn */
            $subConn->disabled = true; // 标记废除
            $subConn->disconnect(); // 关闭后会导致 subscribe 的连接抛出错误
        }

        // 订阅房间的频道
        xgo(function () use ($model) {
            // 订阅房间的频道
            $subConn = RedisConnection::newInstance();
            app()->tcpSession->set('subConn', $subConn);
            try {
                $subConn->subscribe(["room_{$model->roomid}"], function ($instance, $channel, $message) {
                    $frame = new TextFrame([
                        'data' => $message,
                    ]);
                    app()->ws->push($frame);
                });
            } catch (\Throwable $e) {
                // redis连接异常断开处理
                if (empty($subConn->disabled)) {
                    // 关闭连接
                    app()->ws->disconnect();
                }
            }
        });

        // 给当前房间其他人发送加入消息
        $name     = app()->tcpSession->get('name');
        $response = JsonHelper::encode([
            'result' => [
                'message' => "{$name} 加入 {$model->roomid} 房间.",
            ],
            'id'     => $id,
        ], JSON_UNESCAPED_UNICODE);
        $conn     = app()->redisPool->getConnection();
        $conn->publish("room_{$model->roomid}", $response);
        $conn->release();

        // 给我自己发送加入消息
        $fd = app()->ws->fd;
        app()->ws->push(new TextFrame([
            'data' => JsonHelper::encode([
                'result' => [
                    'message' => "我加入 {$model->roomid} 房间.",
                ],
                'id'     => $id,
            ], JSON_UNESCAPED_UNICODE),
        ]));

        var_dump(app()->ws->fd);

    }

    public function actionPerson($params)
    {

        // 验证数据
        $model             = new PersonForm();
        $model->attributes = $params;
        $model->setScenario('actionPerson');
        if (!$model->validate()) {
            $response = new TextFrame([
                'data' => JsonHelper::encode([
                    'result' => [
                        'message' => $model->getError(),
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);
            app()->ws->push($response);
            return;
        }



        // 给对应的用户发送消息
        $result = app()->ws->push(new TextFrame([
            'data' => JsonHelper::encode([
                'result' => [
                    'message' => "你好啊.".$params['did'],
                ],
                'id'     => $params['id'],
            ], JSON_UNESCAPED_UNICODE),
        ]), $params['did']);


        var_dump($result);


        // 检测消息是否发送成功
        app()->ws->push(new TextFrame([
            'data' => JsonHelper::encode([
                'result' => [
                    'message' => "ok",
                ],
                'id'     => $params['id'],
            ], JSON_UNESCAPED_UNICODE),
        ]));
    }



}
