<?php

namespace api\modules\v1\controllers\member;

use Yii;
use common\enums\StatusEnum;
use common\helpers\ResultHelper;
use common\models\member\Auth;
use common\helpers\UploadHelper;
use api\controllers\UserAuthController;

/**
 * 第三方授权
 *
 * Class AuthController
 * @package api\modules\v1\controllers\member
 * @author jianyan74 <751393839@qq.com>
 */
class AuthController extends UserAuthController
{
    /**
     * @var Auth
     */
    public $modelClass = Auth::class;

    /**
     * 绑定第三方信息
     *
     * @return array|mixed|\yii\db\ActiveRecord|null
     */
    public function actionCreate()
    {
        $member_id = Yii::$app->user->identity->member_id;
        $oauthClient = Yii::$app->request->post('oauth_client');
        $oauthClientUserId = Yii::$app->request->post('oauth_client_user_id');

        /** @var Auth $model */
        if (!($model = Yii::$app->services->memberAuth->findByMemberIdOauthClient($oauthClient, $member_id))) {
            $model = new $this->modelClass();
            $model = $model->loadDefaultValues();
            $model->attributes = Yii::$app->request->post();
        }

        if (!$model->isNewRecord && $model->status == StatusEnum::ENABLED) {
            return ResultHelper::json(422, '请先解除该账号绑定');
        }

        if ($model->head_portrait) {
            // 下载图片
            $upload = new UploadHelper(['writeTable' => StatusEnum::DISABLED], 'images');
            $imgData = $upload->verifyUrl($model->head_portrait);
            $upload->save($imgData);
            $baseInfo = $upload->getBaseInfo();
        }

        $model->head_portrait = $baseInfo['url'] ?? '';
        $model->oauth_client = $oauthClient;
        $model->oauth_client_user_id = $oauthClientUserId;
        $model->member_id = Yii::$app->user->identity->member_id;
        $model->status = StatusEnum::ENABLED;
        if (!$model->save()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        // 更改用户信息
        if ($member = Yii::$app->services->member->get($model->member_id)) {
            !$member->head_portrait && $member->head_portrait = $model->head_portrait;
            !$member->gender && $member->gender = $model->gender;
            !$member->nickname && $member->nickname = $model->nickname;
            $member->save();
        }

        return $model;
    }

    /**
     * @param $id
     * @return array|bool|mixed
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $member_id = Yii::$app->user->identity->member_id;
        $member = Yii::$app->services->member->get($member_id);
        if (empty($member['mobile']) && Yii::$app->services->memberAuth->getCountByMemberId($member_id) == 1) {
            return ResultHelper::json(422, '无法解除该账号绑定');
        }

        $model = $this->findModel($id);
        $model->status = StatusEnum::DELETE;

        return $model->save();
    }

    /**
     * @return array|mixed
     */
    public function actionIsBinding()
    {
        $oauthClient = Yii::$app->request->post('oauth_client');

        $model = Yii::$app->services->memberAuth->findOauthClientByMemberId($oauthClient, Yii::$app->user->identity->member_id);
        if ($model) {
            return [
                'openid' => $model['oauth_client_user_id']
            ];
        }

        return [
            'openid' => ''
        ];
    }
}