<?php

namespace WebSocket\Models;

use Mix\Validate\Validator;

/**
 * Class JoinForm
 * @package WebSocket\Models
 * @author liu,jian <coder.keda@gmail.com>
 */
class JoinForm extends Validator
{

    /**
     * 房间id
     * @var int
     */
    public $roomid;
    public $token;

    /**
     * 规则
     * @return array
     */
    public function rules()
    {
        return [
            'roomid' => ['integer', 'unsigned' => true, 'minLength' => 1, 'maxLength' => 10],
            'token' => ['string']
        ];
    }



    /**
     * 场景
     * @return array
     */
    public function scenarios()
    {
        return [
            'actionRoom' => ['required' => ['roomid', 'token']],
        ];
    }

}
