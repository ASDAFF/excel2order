<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var array $arParams
 * @var CExcelToOrder $this
 */
	$arPost = $_POST;
	$arResult = Array();
	global $USER;

    if(count($_FILES) > 0)
    {
        $this->initFileVars($_FILES);
    }

    if(isset($arPost["form_name"]) && $arPost["form_name"] == "load_formload_form")
    {
        $arResult = $this->processPostFile($_FILES);
    }

	if(isset($arPost["form_name"]) && $arPost["form_name"] == "make_order" && CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog"))
	{
        $this->doProcessOrder($arPost);
        LocalRedirect($arParams["CART_LINK"]);
    }

	$this->IncludeComponentTemplate();
