<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
	//pre($_FILES);
	
	$arPost = $_POST;
	$arResult = Array();
	
	global $USER;
	$uploadDir = $_SERVER["DOCUMENT_ROOT"].'/upload/xlsx/'.$USER->GetID();
	$uploadFile = $uploadDir.basename($_FILES['userfile']['name']);
	$fileName = $_FILES['userfile']['tmp_name'];
	
	if(isset($arPost["form_name"]) && $arPost["form_name"] == "load_formload_form")
	{
		if($_FILES["userfile"]["type"] !== "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
			$arResult["ERROR_MESSAGES"][] = GetMessage("ERROR_WRONG_FILE_TYPE");
		else
		{	
			if(!is_dir($uploadDir))
				mkdir($uploadDir);

			if (move_uploaded_file($fileName, $uploadFile)) 
			{
				$zip = new ZipArchive; 
				$zip->open($uploadFile); 
				$zip->extractTo($uploadDir.'/extract'); 
				$zip->close();
				
				$xlsx = $xml = simplexml_load_file($uploadDir.'/extract/xl/sharedStrings.xml');
				$sharedStringsArr = array();
				foreach ($xml->children() as $item) 
				{
					$sharedStringsArr[] = (string)$item->t;
				}

				$handle = @opendir($uploadDir.'/extract/xl/worksheets');
				$out = array();
				while ($file = @readdir($handle)) 
				{
					//проходим по всем файлам из директории /xl/worksheets/
					if ($file != "." && $file != ".." && $file != '_rels') 
					{
						$xml = simplexml_load_file($uploadDir.'/extract/xl/worksheets/' . $file);
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
				$arResult["ITEMS"] = $out;
			} 
			else
				echo GetMessage("ERROR_LOAD_FILE");
		}
	}
	if(isset($arPost["form_name"]) && $arPost["form_name"] == "make_order" && CModule::IncludeModule("iblock") && CModule::IncludeModule("catalog"))
	{
		//pre($arPost);
		foreach($arPost["artnumber"] as $key => $artnumber)
		{
			$arSelect = Array("ID", "NAME");
			$arFilter = Array("IBLOCK_ID"=>intval($arParams["IBLOCK_ID"]), "ACTIVE"=>"Y", "PROPERTY_CML2_BAR_CODE" => $artnumber);
			$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
			if($ob = $res->GetNextElement())
			{
				$arProductFields = $ob->GetFields();
				
				$quantity = intval($arPost["quantity"][$key]);
				if($quantity < 1)
					$quantity = 1;
					
				Add2BasketByProductID($arProductFields["ID"], $quantity);
			}
		}
		
		function removeDirectory($dir) 
		{
			if ($objs = glob($dir."/*")) 
			{
				foreach($objs as $obj)
				{
					is_dir($obj) ? removeDirectory($obj) : unlink($obj);
				}
			}
			rmdir($dir);
		}
		removeDirectory($uploadDir);
		
		LocalRedirect($arParams["CART_LINK"]);
	}

	$this->IncludeComponentTemplate();
	return $arResult;

?>