<?php

namespace app\controllers;

use app\models\Inventario;
use yii\data\Pagination;

class InventarioController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'create' => ['post'],
                'update' => ['put', 'post'],
                'delete' => ['delete'],
                'get-category' => ['get'],

            ]
        ];
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
        ];
        return $behaviors;
    }
    public function actionIndex($pageSize=10)
    {
        $query = Inventario::find();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]
        );

        $inventaries = $query 
                        ->offset($pagination -> offset())
                        -> limit($pagination -> limit())
                        ->all();
        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();

        $response = [
            'success' => true,
            'inventaries' => $inventaries,
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($inventaries),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
        ];

        return $response; 
    }

}
