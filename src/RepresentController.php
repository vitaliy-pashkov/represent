<?php

namespace vpashkov\represent;

use yii\web\Controller;

class RepresentController extends Controller
{
    public function actionOne($represent, $dicts = false)
    {
        $represent = Represent::create($represent);
        $response = [];
        $response['data'] = $represent->getOne();
        if ($dicts != false) {
            $response['dicts'] = $represent->getDicts();
        }
        return $this->createResponse($response);
    }

    public function actionAll($represent, $count = false, $dicts = false, $meta = false)
    {
        $represent = Represent::create($represent);
        $response = [];
        $response['data'] = $represent->getAll();
        if ($count != false) {
            $response['count'] = $represent->getCount();
        }
        if ($dicts != false) {
            $response['dicts'] = $represent->getDicts();
        }
        if ($meta != false) {
            $response['meta'] = $represent->getMeta();
        }
        return $this->createResponse($response);
    }

    public function actionSave($represent)
    {
        $represent = Represent::create($represent);
        $post = \Yii::$app->request->post();
        $status = ['status' => 'fail'];
        if (array_key_exists('rows', $post)) {
            $status = $represent->saveAll(json_decode(\Yii::$app->request->post("rows"), true));
        } elseif (array_key_exists('row', $post)) {
            $status = $represent->saveOne(json_decode(\Yii::$app->request->post("row"), true));
        }
        return $this->createResponse($status);
    }

    public function actionDelete($represent)
    {
        $represent = Represent::create($represent);

        $post = \Yii::$app->request->post();
        $status = ['status' => 'fail'];
        if (array_key_exists('rows', $post)) {
            $status = $represent->deleteAll(json_decode(\Yii::$app->request->post("rows"), true));
        } elseif (array_key_exists('row', $post)) {
            $status = $represent->deleteOne(json_decode(\Yii::$app->request->post("row"), true));
        }
        return $this->createResponse($status);
    }

    public function actionDicts($represent)
    {
        $represent = Represent::create($represent);
        $dicts = $represent->getDicts();
        return $this->createResponse($dicts);
    }

    public function actionDict($represent, $dictName)
    {
        $represent = Represent::create($represent);
        $dict = $represent->getDict($dictName);
        return $this->createResponse($dict);
    }

    public function actionMeta($represent)
    {
        $represent = Represent::create($represent);
        return $this->createResponse($represent->getMeta());
    }

    public function actionCount($represent)
    {
        $represent = Represent::create($represent);
        return $this->createResponse($represent->getCount());
    }

    protected function createResponse($data)
    {
        $response = \Yii::$app->response;
        $response->format = \Yii\web\Response::FORMAT_JSON;
        $response->data = $data;
        return $response;
    }

    public function actionGetWidgetConfig($represent)
    {
        $represent = Represent::create($represent);
        return $this->createResponse($represent->getWidgetConfig());
    }
}
    
