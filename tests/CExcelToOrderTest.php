<?php
/**
 * User: Rodion Abdurakhimov
 * Mail: rodion@epages.in.ua
 * Date: 12/6/14
 * Time: 23:13
 */
include $_SERVER["DOCUMENT_ROOT"] . "/local/components/custom/excel2order/class.php";

class CExcelToOrderTest extends PHPUnit_Framework_TestCase
{
    protected $component;

    public function setUp()
    {
        initBitrixCore();
        $this->component = new \CExcelToOrder();
    }

    public function testCanSetClassProps()
    {
        $FILES = Array(
            "userfile" => Array(
                "name" => "test.xsls",
                "tmp_name" => "test.xsls",
            )
        );
        $this->component->initFileVars($FILES);

        $this->assertTrue(strlen($this->component->uploadDir)>0, "Не установлено свойство uploadDir");
        $this->assertTrue(strlen($this->component->uploadFile)>0, "Не установлено свойство uploadFile");
        $this->assertTrue(strlen($this->component->fileName)>0, "Не установлено свойство fileName");
    }

    public function testIfModulesInstalled()
    {
        $this->assertTrue(Bitrix\Main\ModuleManager::IsModuleInstalled("iblock"), 'Модуль "iblock" не установлен.');
        $this->assertTrue(Bitrix\Main\ModuleManager::IsModuleInstalled("catalog"), 'Модуль "catalog" не установлен.');
        $this->assertTrue(Bitrix\Main\ModuleManager::IsModuleInstalled("sale"), 'Модуль "sale" не установлен.');
    }

    public function tearDown()
    {
        unset($this->component);
    }
}
 