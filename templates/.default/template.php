<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/**
 * @var array $arResult
 */
?>
<div id="text">
    <p class="lead">Как сформировать заказ из файла Excel:</p>
    <ol>
        <li>Скачать пример файла по ссылке ниже</li>
        <li>Заполнить в первом столбце артикул товара, а во втором - необходимое количество</li>
        <li>Выбрать тип артикула, по которому будет осуществляться поиск по базе данных: Артикул производителя или Код 1С</li>
        <li>Загрузить файл на сайт с помощью формы "Файл Excel"</li>
        <li>Проверить данные, полученные из файла, появившиеся под формой (в случае необходимости - можно внести изменения)</li>
        <li>Подтвердить состав заказа нажав на кнопку "Добавить в корзину"</li>
        <li>Вас перенаправит на страницу корзины с выбранными товарам, где Вы сможете оформить заказ</li>
    </ol>
    <p>Скачать пример файла можно <a href="../../../../../../personal/excel2order/order.xlsx" download>по этой ссылке</a></p>
</div>
<?if(count($arResult["ERROR_MESSAGES"])>0):?>
	<?
		foreach($arResult["ERROR_MESSAGES"] as $error)
			echo '<span class="error">'.$error.'</span>';
	?>
<?endif?>

<p  class="lead">Файл Excel</p>
<div class="excel-load-form">
	<form enctype="multipart/form-data" action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
		<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
		<input type="hidden" name="form_name" value="load_formload_form" />

		<div class="custom-logical-table-wrapper">
			<table class="table">
				<tbody>
                    <tr>
                        <td style="width: 25%">Тип артикула:</td>
                        <td>
                            <select name="artnumber_type" class="form-control" style="width: 200px">
                                <option value="arnumber">Артикул производителя</option>
                                <option value="1с-arnumber">Код 1С</option>
                            </select>
                        </td>
                    </tr>
					<tr>
						<td style="width: 25%">Загрузить файл Excel:</td>
						<td>
                            <label for="loadfile" class="file-loader">
                                <input name="userfile" id='loadfile' class='form-control input-file'  size="0" type="file" />
                                <span class="bx-input-file-desc"></span>
                                <div class="input-group">
                                    <span class="input-group-btn" style="padding-right: 10px;">
                                        <span class="btn btn-primary pull-left">Выберите файл</span>
                                    </span>
                                    <span class="filename pull-left" title=""></span>
                                    <span class="no-file pull-left" title="файл не выбран">файл не выбран</span>
                                </div>
                            </label>
                        </td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" value="Отправить" class="btn btn-primary btn-lg"/></td>
					</tr>
				</tbody>
			</table>
		</div>
	</form>
</div>

<?if(count($arResult["ITEMS"])>0):?>
	<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
		<input type="hidden" name="form_name" value="make_order" />
		<input type="hidden" name="artnumber_type" value="<?=$_POST["artnumber_type"]?>" />

		<table class="table">
			<tbody>
				<tr>
					<td style="width: 70%;">Для того чтобы начать оформление заказа, нажмите кнопку "Добавить в корзину".</td>
					<td class="scb-right">
						<span class="custom-button cb-color-2"><input class="btn btn-primary" type="submit" value="Добавить в корзину" /></span>
					</td>
				</tr>
			</tbody>
		</table>
		<div>
			<table class="table">
				<tbody>
					<tr class="clt-h">
						<td class="text-center">Название</td>
						<td class="text-center">Артикул</td>
						<td class="text-center">Количество</td>
						<td class="text-center">Склад</td>
						<td class="text-center">Количество на складе</td>
						<td class="text-center">Удалить</td>
					</tr>
					<?foreach($arResult["ITEMS"] as $key=>$arVal):?>
						<?if(!is_numeric($arVal[1])):?>
							<?continue?>
						<?else:?>
							<tr>
                                <?if(strlen($arVal["NAME"])>0):?>
                                    <td><a href="<?=$arVal["LINK"]?>" target="_blank"><?=$arVal["NAME"]?></a></td>
                                <?else:?>
                                    <td>(не найдено)</td>
                                <?endif?>
								<?$cell = 0?>
								<?foreach($arVal as $data):?>
                                    <?if($cell%2 == 0 && $cell !== 0) continue?>
                                    <?
                                    $classValue = "";
                                    $userQuantity = $arVal[1];
                                    $siteQuantity = $arVal["CATALOG_QUANTITY"];

                                    if($arVal["FOUND"] == false)
                                        $classValue .= "has-error has-feedback";
                                    elseif($siteQuantity < $userQuantity)
                                        $classValue .= "has-warning has-feedback";
                                    ?>
									<td>
                                        <div class="form-group <?= $classValue?>">
                                            <?if($cell == 0):?>
                                                <?//=$data?>
                                                <input type="text" class="form-control" name="artnumber[]" value="<?=$data?>" />
                                            <?else:?>
                                                <input type="number" class="form-control" name="quantity[]" value="<?=intval($data)?>" />
                                                <input type="hidden" name="store[]" value="<?=$arVal["STORE_NAME"]?>"/>
                                            <?endif?>
                                            <?if(!$arVal["FOUND"]):?>
                                                <span class="glyphicon glyphicon-remove form-control-feedback" style="top: 0" title="Артикул товара не найден на сайте!"></span>
                                            <?elseif($siteQuantity < $userQuantity):?>
                                                <span class="glyphicon glyphicon-warning-sign form-control-feedback" style="top: 0" title="На складе недостаточно товара для Вашего заказа!"></span>
                                            <?endif?>

                                        </div>
									</td>
									<?$cell++?>
								<?endforeach?>
                                <td>
                                    <?=$arVal["STORE_NAME"]?>
                                </td>
                                <td class="text-center">
                                    <?if(intval($arVal["CATALOG_QUANTITY"])>0):?>
                                        <?
                                        if($arVal["CATALOG_QUANTITY"] > 20)
                                            echo ">20";
                                        else
                                            echo $arVal["CATALOG_QUANTITY"];
                                        ?>
                                    <?else:?>
                                        &minus;
                                    <?endif?>
                                </td>
                                <td class="text-center"><a href="#" class="remove-excel-item glyphicon"></a></td>
							</tr>
						<?endif?>
					<?endforeach?>
				</tbody>
			</table>
		</div>
		<table class="shop-cart-buttons">
			<tbody>
				<tr>
					<td></td>
					<td class="scb-right">
						<span class="custom-button cb-color-2"><input class="btn btn-primary" type="submit" value="Добавить в корзину" /> </span><br>
						<small>Нажмите эту кнопку, чтобы
						заказать товары</small>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
<?endif?>