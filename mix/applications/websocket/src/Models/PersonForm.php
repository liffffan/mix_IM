<?php

namespace WebSocket\Models;

use Mix\Validate\Validator;

/**
 * Class JoinForm
 * @package WebSocket\Models
 * @author liu,jian <coder.keda@gmail.com>
 */
class PersonForm extends Validator
{

    /**
     * 房间id
     * @var int
     */
    public $id;
    public $did;

    /**
     * 规则
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['integer', 'unsigned' => true, 'minLength' => 1, 'maxLength' => 10],
            'did' => ['integer', 'unsigned' => true, 'minLength' => 1, 'maxLength' => 10],
        ];
    }



    /**
     * 场景
     * @return array
     */
    public function scenarios()
    {
        return [
            'actionPerson' => ['required' => ['id', 'did']],
        ];
    }

}
