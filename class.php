<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule("iblock");
class CExcelToOrder extends CBitrixComponent
{
    public $uploadDir = '';
    public $uploadFile = '';
    public $fileName = '';

    public function onPrepareComponentParams($arParams)
    {
        $arParams["CACHE_TIME"] = isset($arParams["CACHE_TIME"]) ? $arParams["CACHE_TIME"] : 36000000;
        $arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
        $arParams["SECTION_ID"] = intval($arParams["SECTION_ID"]);
        $arParams["ELEMENT_ID"] = intval($arParams["ELEMENT_ID"]);

        return $arParams;
    }

    /**
     * Устанавливает свойства класса в зависимости от загруженного файла
     * @param $FILES
     */
    public function initFileVars($FILES)
    {
        global $USER;
        $this->uploadDir = $_SERVER["DOCUMENT_ROOT"].'/upload/xlsx/'.$USER->GetID();
        $this->uploadFile = $this->uploadDir.'/'.basename($FILES['userfile']['name']);
        $this->fileName = $FILES['userfile']['tmp_name'];
    }

    /**
     * Функция проверяет наличие на сайте товара с артикулом $artnumber.
     * Второй параметр служит для определения в каком из двух свойств артикула искать: Артикул или Код 1С
     * @param $artnumber
     * @param $artnumberType
     * @return bool
     */
    protected function checkItemByArtnumber($artnumber, $artnumberType)
    {
        $artnumberProp = "";
        if($artnumberType == "arnumber")
            $artnumberProp = "PROPERTY_ARTICUL_PROIZVODITELYA";
        elseif($artnumberType = "1с-arnumber")
            $artnumberProp = "PROPERTY_CML2_ARTICLE";
        if(strlen($artnumberProp)>0 && strlen($artnumber)>0)
        {
            $resCheck = CIBlockElement::GetList(Array(), Array("IBLOCK_ID" => Array(SKU_DISKS_IBLOCK_ID, SKU_TIRES_IBLOCK_ID), "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", $artnumberProp => $artnumber), false, false, Array("ID", "NAME", "DATE_ACTIVE_FROM"));

            if($obCheck = $resCheck->GetNext())
                return true;
            else
                return false;
        }

        return false;
    }

    /**
     * Получает данные товара:
     * @param $artnumber
     * @param $artnumberType
     * @return array|int
     */
    protected function getProductData($artnumber, $artnumberType)
    {
        $artnumberProp = "";
        if($artnumberType == "arnumber")
            $artnumberProp = "PROPERTY_ARTICUL_PROIZVODITELYA";
        elseif($artnumberType = "1с-arnumber")
            $artnumberProp = "PROPERTY_CML2_ARTICLE";

        $arResult = Array();

        if(strlen($artnumberProp)>0 && strlen($artnumber)>0)
        {
            $resQuantity = CIBlockElement::GetList(Array(), Array("IBLOCK_ID" => Array(SKU_DISKS_IBLOCK_ID, SKU_TIRES_IBLOCK_ID), "ACTIVE_DATE" => "Y", "ACTIVE" => "Y", $artnumberProp => $artnumber), false, Array("nPageSize" => 50), Array("ID", "NAME", "DATE_ACTIVE_FROM", "CATALOG_QUANTITY", "DETAIL_PAGE_URL"));
            if($obQuantity = $resQuantity->GetNextElement())
            {
                $arFieldsQuantity = $obQuantity->GetFields();

                $arResult["CATALOG_QUANTITY"] = $arFieldsQuantity["CATALOG_QUANTITY"];
                $arResult["NAME"] = $arFieldsQuantity["NAME"];
                $arResult["LINK"] = $arFieldsQuantity["DETAIL_PAGE_URL"];
                $arResult["ID"] = $arFieldsQuantity["ID"];

                return $arResult;
            }
            else
                return 0;
        }

        return 0;
    }

    /**
     * @param $dir
     */
    protected function removeDirectory($dir)
    {
        if($dir == '')
            return false;

        if ($objs = glob($dir."/*"))
        {
            foreach($objs as $obj)
            {
                is_dir($obj) ? /*$this->removeDirectory($obj)*/ : unlink($obj);
            }
        }
        rmdir($dir);
    }

    protected function checkStore($storeName)
    {
        $arStore = \Epages\CMultiStore::getStorageByName($storeName);

        if(count($arStore)>0)
            return true;
        else
            return false;
    }

    protected function getQuantityOnStore($productID, $storeName)
    {
        $arStores = \Epages\CMultiStore::getStoreAmount($productID);

        if(count($arStores) > 0)
        {
            foreach($arStores as $arStore)
            {
                if($arStore["TITLE"] == $storeName)
                    return $arStore["PRODUCT_AMOUNT"];
            }
        }
        else
            return 0;
    }

    protected function parseXslx($FILES)
    {
        if($FILES["userfile"]["type"] !== "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
            return Array("ERROR_MASSAGES" => GetMessage("ERROR_WRONG_FILE_TYPE"));
        else
        {
            if(!is_dir($this->uploadDir))
                mkdir($this->uploadDir);

            if (move_uploaded_file($this->fileName, $this->uploadFile))
            {
                $zip = new ZipArchive;
                $zip->open($this->uploadFile);
                $zip->extractTo($this->uploadDir.'/extract');
                $zip->close();

                $xml = simplexml_load_file($this->uploadDir.'/extract/xl/sharedStrings.xml');
                $sharedStringsArr = array();
                foreach ($xml->children() as $item)
                {
                    $sharedStringsArr[] = (string)$item->t;
                }

                $handle = @opendir($this->uploadDir.'/extract/xl/worksheets');
                $out = array();
                while ($file = @readdir($handle))
                {
                    //проходим по всем файлам из директории /xl/worksheets/
                    if ($file != "." && $file != ".." && $file != '_rels')
                    {
                        $xml = simplexml_load_file($this->uploadDir.'/extract/xl/worksheets/' . $file);
                        //по каждой строке
                        $row = 0;
                        foreach ($xml->sheetData->row as $item)
                        {
                            $out[$row] = array();
                            //по каждой ячейке строки
                            $cell = 0;
                            foreach ($item as $child)
                            {
                                $attr = $child->attributes();
                                $value = isset($child->v)? (string)$child->v:false;
                                $out[$row][$cell] = isset($attr['t']) ? $sharedStringsArr[$value] : $value;
                                $cell++;
                            }
                            $row++;
                        }
                    }
                }

                return Array("ITEMS" => $out);
            }
            else
                return Array("ERROR_MASSAGES" => GetMessage("ERROR_LOAD_FILE"));
        }
    }

    public function processPostFile($FILES)
    {
        $arResult = $this->parseXslx($FILES);

        if(array_key_exists("ITEMS", $arResult))
        {
            foreach($arResult["ITEMS"] as &$arProduct)
            {
                $productArtnumber = $arProduct[0];
                $productStoreName = $arProduct[2];
                if(
                    $this->checkItemByArtnumber($productArtnumber, $_POST["artnumber_type"])
                    && $this->checkStore($productStoreName)
                )
                {
                    $arProductData = $this->getProductData($productArtnumber, $_POST["artnumber_type"]);
                    $arProduct["FOUND"] = true;
                    $arProduct["CATALOG_QUANTITY"] = $this->getQuantityOnStore($arProductData["ID"], $productStoreName);
                    $arProduct["NAME"] = $arProductData["NAME"];
                    $arProduct["LINK"] = $arProductData["LINK"];
                    $arProduct["STORE_NAME"] = $productStoreName;
                }
                else
                    $arProduct["FOUND"] = false;
            }
        }

        return $arResult;
    }

    public function doProcessOrder($arPost)
    {
        $arResult = Array();

        $artnumberProp = "";
        if($_POST["artnumber_type"] == "arnumber")
            $artnumberProp = "PROPERTY_ARTICUL_PROIZVODITELYA";
        elseif($_POST["artnumber_type"] = "1с-arnumber")
            $artnumberProp = "PROPERTY_CML2_ARTICLE";

        foreach($arPost["artnumber"] as $key => $artnumber)
        {
            $arSelect = Array("ID", "NAME", "CATALOG_QUANTITY");
            $arFilter = Array("IBLOCK_ID"=>Array(SKU_TIRES_IBLOCK_ID, SKU_DISKS_IBLOCK_ID), "ACTIVE"=>"Y", "$artnumberProp" => $artnumber);
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($ob = $res->GetNextElement())
            {
                $arProductFields = $ob->GetFields();

                $quantity = intval($arPost["quantity"][$key]);

                $storeName = $arPost["store"][$key];
                $storeQuantity = $this->getQuantityOnStore($arProductFields["ID"], $storeName);

                if($quantity < 1)
                    $quantity = 1;
                if($quantity > $storeQuantity)
                    $quantity = $storeQuantity;

                $arStoreInfo = \Epages\CMultiStore::getStorageByName($storeName);
                $arResultTmp[$arProductFields["ID"]][] = Array(
                    "NAME" => $arProductFields["NAME"],
                    "STORE" => Array(
                        "storageId" => $arStoreInfo["ID"],
                        "quantity" => $quantity
                    )
                );
            }
        }

        $arResult = Array();

        if(count($arResultTmp) > 0)
        {
            foreach($arResultTmp as $productId => $arProducts)
            {
                $arStores = Array();
                $productName = "";

                foreach($arProducts as $arProduct)
                {
                    $productName = $arProduct["NAME"];
                    $arStores[] = $arProduct["STORE"];
                }

                $arResult[$productId] = Array(
                    "NAME" => $productName,
                    "STORE" => $arStores
                );
            }
        }

        foreach($arResult as $productId => $arProduct)
        {
            $obStorage = new Epages\CMultiStore($productId, $arProduct["NAME"], $arProduct["STORE"]);
            $obStorage->addToBasketMultiStorage();
            unset($obStorage);
        }

        //$this->removeDirectory($this->uploadDir);
    }
}