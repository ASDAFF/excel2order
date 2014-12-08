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
     * 
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
     * - общий остаток по складам
     * - название товара
     * - сслыку на товар
     * - ИД товара
     *
     * возвращает это все в массиве
     *
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
     * Метод проверяет существование склада по имени
     * Возварщает true если найден и false в противном случае
     *
     * @param $storeName
     * @return bool
     */
    protected function checkStore($storeName)
    {
        $arStore = \Epages\CMultiStore::getStorageByName($storeName);

        if(count($arStore)>0)
            return true;
        else
            return false;
    }

    /**
     * Метод получает количество на указанном по имени складе указанного по ID товара
     *
     * @param $productID
     * @param $storeName
     * @return int
     */
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

    /**
     * Метод принимает файл .xslx из $_POST и возвращает его данные в виде PHP массива
     *
     * @param $FILES
     * @return array
     */
    protected function parseXslx($FILES)
    {
        //проверить что файл типа .xslx
        if($FILES["userfile"]["type"] !== "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
            return Array("ERROR_MASSAGES" => GetMessage("ERROR_WRONG_FILE_TYPE"));
        else
        {
            //создать уникальну для каждого пользователя папку
            if(!is_dir($this->uploadDir))
                mkdir($this->uploadDir);

            //сохранить файл в папку на сервере
            if (move_uploaded_file($this->fileName, $this->uploadFile))
            {
                //распаковать .xslx
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

                //читаем данные книги
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

    /**
     * Метод выполняет обраотку файла из $_POST, возвращает массив с данными о товарах из файла для шаблона
     *
     * @param $FILES
     * @return array
     */
    public function processPostFile($FILES)
    {
        //разпарсить файл
        $arResult = $this->parseXslx($FILES);

        if(array_key_exists("ITEMS", $arResult))
        {
            //собрать массив для построения таблицы в шаблоне
            foreach($arResult["ITEMS"] as &$arProduct)
            {
                $productArtnumber = $arProduct[0];
                $productStoreName = $arProduct[2];

                //проверить существование товара и по артикулу и склад по названию
                if(
                    $this->checkItemByArtnumber($productArtnumber, $_POST["artnumber_type"])
                    && $this->checkStore($productStoreName)
                )
                {
                    //получить инфо о товаре
                    $arProductData = $this->getProductData($productArtnumber, $_POST["artnumber_type"]);

                    //пометить товар как найден
                    $arProduct["FOUND"] = true;

                    //получить остаток товара на складе
                    $arProduct["CATALOG_QUANTITY"] = $this->getQuantityOnStore($arProductData["ID"], $productStoreName);

                    //название, ссылка, склад товара
                    $arProduct["NAME"] = $arProductData["NAME"];
                    $arProduct["LINK"] = $arProductData["LINK"];
                    $arProduct["STORE_NAME"] = $productStoreName;
                }
                else //пометить товар как ненайден в противном случае
                    $arProduct["FOUND"] = false;
            }
        }

        return $arResult;
    }

    /**
     * Метод вызывается при отправки формы для добавления товаров из Excel в корзину
     * @param $arPost
     */
    public function doProcessOrder($arPost)
    {
        $arResultTmp = Array();

        //определяем в каком свойстве артикула нужно проверят значение
        $artnumberProp = "";
        if($_POST["artnumber_type"] == "arnumber")
            $artnumberProp = "PROPERTY_ARTICUL_PROIZVODITELYA";
        elseif($_POST["artnumber_type"] = "1с-arnumber")
            $artnumberProp = "PROPERTY_CML2_ARTICLE";

        //цикл по товарам, пришедшим из формы
        foreach($arPost["artnumber"] as $key => $artnumber)
        {
            //получить данные товара по свойству артикула
            $arSelect = Array("ID", "NAME", "CATALOG_QUANTITY");
            $arFilter = Array("IBLOCK_ID"=>Array(SKU_TIRES_IBLOCK_ID, SKU_DISKS_IBLOCK_ID), "ACTIVE"=>"Y", "$artnumberProp" => $artnumber);
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($ob = $res->GetNextElement())
            {
                $arProductFields = $ob->GetFields();

                //получение количества из формы
                $quantity = intval($arPost["quantity"][$key]);

                //и максимальное количество на складе по названию склада ИД товара
                $storeName = $arPost["store"][$key];
                $storeQuantity = $this->getQuantityOnStore($arProductFields["ID"], $storeName);

                //защита от дурака, проверка данных по количеству
                if($quantity < 1)
                    $quantity = 1;

                //нельзя положить в корзину товаров больше, чем есть на складе
                if($quantity > $storeQuantity)
                    $quantity = $storeQuantity;

                //получить данные склада
                $arStoreInfo = \Epages\CMultiStore::getStorageByName($storeName);

                //собрать данные по товарам и складам в одном массиве
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
            //преобразовать массив с товарами и скаладами для отправки в конструктор Epages\CMultiStore()
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