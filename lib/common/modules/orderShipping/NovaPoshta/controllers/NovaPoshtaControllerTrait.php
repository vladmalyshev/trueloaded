<?php
declare (strict_types=1);


namespace common\modules\orderShipping\NovaPoshta\controllers;


trait NovaPoshtaControllerTrait
{
    public function actionGetAreas(string $areaRef = '', int $page = 0)
    {
        $areas = $this->addressApi->getAreas($areaRef, $page);
        $result = [];
        foreach ($areas as $area) {
            $result[] = [
                'Ref' => $area->getRef(),
                'Description' => $area->getDescription(),
            ];
        }
        return $this->asJson([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function actionGetCities(string $cityRef = '', string $findByString = '', int $page = 0)
    {
        $cities = $this->addressApi->getCities($cityRef, $findByString, $page);
        $result = [];
        foreach ($cities as $city) {
            $result[] = [
                'Ref' => $city->getRef(),
                'Area' => $city->getAreaRef(),
                'Description' => $city->getDescription(),
            ];
        }
        return $this->asJson([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function actionGetWarehouses(string $cityRef = '', string $cityName = '', int $page = 0, int $limit = 0)
    {
        $warehouses = $this->addressApi->getWarehouses($cityRef, $cityName, $page, $limit);
        $result = [];
        foreach ($warehouses as $warehouse) {
            $result[] = [
                'Ref' => $warehouse->getRef(),
                'Description' => $warehouse->getDescription(),
            ];
        }
        return $this->asJson([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function actionSetSessionShippingParams(){
        $post = \Yii::$app->request->post();
        $this->novaPoshtaService->saveTemporaryShippingDataInStorage($post, $this->storage);
        return $this->asJson([ACCEPT]);
    }

    public function getViewPath()
    {
        return \Yii::getAlias('@nova-poshta/views');
    }
}
