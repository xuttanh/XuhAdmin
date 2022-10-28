<?php

namespace common\models\websocket;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\redis\ActiveRecord;
use addons\TinyService\common\enums\TypeEnum;
use common\models\base\BaseModel;

/**
 * Class FdMemberMap
 *
 * @property int $fd
 * @property int $member_id
 * @property int $merchant_id
 * @property string $type 用户类别
 * @property string $nickname 昵称
 * @property string $head_portrait 头像
 * @property string $mobile 手机号码
 * @property string $ip ip
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class FdMemberMap extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%addon_tiny_service_fd_member_map}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fd', 'member_id', 'merchant_id'], 'required'],
            [['fd', 'member_id', 'merchant_id', 'max_reception_num', 'now_reception_num', 'unread_num', 'gender', 'status'], 'integer'],
            [['nickname', 'head_portrait', 'qq', 'mobile', 'type', 'job_number', 'ip'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fd' => '客户端id',
            'job_number' => '工号',
            'unread_num' => '未读消息数量',
            'type' => '用户类型',
            'member_id' => '用户id',
            'merchant_id' => '商户id',
            'nickname' => '昵称',
            'head_portrait' => '头像',
            'gender' => '性别',
            'qq' => 'qq',
            'mobile' => '手机号',
            'ip' => 'ip地址',
            'max_reception_num' => '最大接待人数',
            'now_reception_num' => '当前接待人数',
            'residue_reception_num' => '剩余可接待人数',
            'status' => '状态',
            'created_at' => '发送时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        // 记录到总数据
        Yii::$app->tinyServiceService->fdMemberMap->set($this->fd, [
            'member_id' => $this->member_id,
            'app_id' => $this->type,
        ]);

        switch ($this->type) {
            case TypeEnum::MEMBER :
                Yii::$app->tinyServiceService->memberFdMap->set($this->member_id, $this->fd);
                break;
            case TypeEnum::BACKEND :
                Yii::$app->tinyServiceService->backendFdMap->set($this->member_id, $this->fd);
                break;
            case TypeEnum::MERCHANT :
                Yii::$app->tinyServiceService->merchantFdMap->set($this->member_id, $this->fd);
                break;
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
}