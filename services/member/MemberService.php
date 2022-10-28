<?php

namespace services\member;

use Yii;
use common\enums\StatusEnum;
use common\components\Service;
use common\models\member\Member;
use common\helpers\EchantsHelper;
use common\helpers\TreeHelper;

/**
 * Class MemberService
 * @package services\member
 * @author jianyan74 <751393839@qq.com>
 */
class MemberService extends Service
{
    /**
     * 用户
     *
     * @var \common\models\member\Member
     */
    protected $member;

    /**
     * @param Member $member
     * @return $this
     */
    public function set(Member $member)
    {
        $this->member = $member;
        return $this;
    }

    /**
     * @param $id
     * @return array|Member|\yii\db\ActiveRecord|null
     */
    public function get($id)
    {
        if (!$this->member || $this->member['id'] != $id) {
            $this->member = $this->findById($id);
        }

        return $this->member;
    }

    /**
     * @return int|string
     */
    public function getCount($merchant_id = '')
    {
        return Member::find()
            ->select('id')
            ->andWhere(['>', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $merchant_id])
            ->count();
    }

    /**
     * 获取区间会员数量
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getBetweenCountStat($type)
    {
        $fields = [
            'count' => '注册会员人数',
        ];

        // 获取时间和格式化
        list($time, $format) = EchantsHelper::getFormatTime($type);
        // 获取数据
        return EchantsHelper::lineOrBarInTime(function ($start_time, $end_time, $formatting) {
            return Member::find()
                ->select(['count(id) as count', "from_unixtime(created_at, '$formatting') as time"])
                ->where(['>', 'status', StatusEnum::DISABLED])
                ->andWhere(['between', 'created_at', $start_time, $end_time])
                ->groupBy(['time'])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->asArray()
                ->all();
        }, $fields, $time, $format);
    }

    /**
     * @param $level
     * @return array|\yii\db\ActiveRecord|null
     */
    public function hasLevel($level)
    {
        return Member::find()
            ->where(['current_level' => $level])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();
    }

    /**
     * 获取所有下级id
     *
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getChildIdsById($id)
    {
        $member = $this->get($id);

        return Member::find()
            ->select(['id'])
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['like', 'tree', $member->tree . TreeHelper::prefixTreeKey($member->id) . '%', false])
            ->andWhere(['<', 'level', $member->level + 3])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->orderBy('id desc')
            ->asArray()
            ->column();
    }

    /**
     * 获取下一级用户id
     *
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getNextChildIdsById($id)
    {
        $member = $this->get($id);

        return Member::find()
            ->select(['id'])
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['like', 'tree', $member->tree . TreeHelper::prefixTreeKey($member->id) . '%', false])
            ->andWhere(['level' => $member->level + 1])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->orderBy('id desc')
            ->asArray()
            ->column();
    }

    /**
     * 根据推广码查询
     *
     * @param $id
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findByPromoCode($promo_code)
    {
        return Member::find()
            ->where(['promo_code' => $promo_code, 'status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();
    }

    /**
     * 根据手机号码查询
     *
     * @param $id
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findByMobile($mobile)
    {
        return Member::find()
            ->where(['mobile' => $mobile, 'status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();
    }

    /**
     * @param $condition
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findByCondition(array $condition)
    {
        return Member::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere($condition)
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();
    }

    /**
     * @param $id
     * @return array|\yii\db\ActiveRecord|null
     */
    public function findById($id)
    {
        return Member::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->one();
    }

    /**
     * @param Member $member
     */
    public function lastLogin(Member $member)
    {
        // 记录访问次数
        $member->visit_count += 1;
        $member->last_time = time();
        $member->last_ip = Yii::$app->request->getUserIP();
        $member->save();
    }
}