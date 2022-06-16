<?php

namespace app\common\controllers;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;

/**
 * Base Admin Controller for `admin` module
 */
class AdminController extends Controller
{
    public function init() {
        parent::init();

        if (Yii::$app->user->isGuest) {
            return $this->redirect(Url::to('/user/login?redirect=/admin'));
        }

        $this->layout = '//admin/main';
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}