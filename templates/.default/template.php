<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?//pre($arResult)?>
<p>Скачать пример файла можно <a href="/excel2order/order.xlsx" download>по этой ссылке</a></p>
<?if(count($arResult["ERROR_MESSAGES"])>0):?>
	<?
		foreach($arResult["ERROR_MESSAGES"] as $error)
			echo '<span class="error">'.$error.'</span>';
	?>
<?endif?>

<div class="excel-load-form">
	<form enctype="multipart/form-data" action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
		<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
		<input type="hidden" name="form_name" value="load_formload_form" />
		
		<div class="custom-logical-table-wrapper">
			<table class="custom-logical-table table-borders excel-order">
				<tbody>
					<tr class="clt-h">
						<td colspan="2">Файл Excel</td>
					</tr>
					<tr>
						<td>Загрузить файл Excel:</td>
						<td><input name="userfile" type="file" /></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" value="Отправить" /></td>
					</tr>
				</tbody>
			</table>
		</div>
	</form>
</div>

<?if(count($arResult["ITEMS"])>0):?>
	<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
		<input type="hidden" name="form_name" value="make_order" />
		
		<table class="shop-cart-buttons">
			<tbody>
				<tr>
					<td style="width: 70%;">Для того чтобы начать оформление заказа, нажмите кнопку "Добавить в корзину".</td>
					<td class="scb-right">
						<span class="custom-button cb-color-2"><input class="text-up" type="submit" value="Добавить в корзину" /></span>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="custom-logical-table-wrapper">
			<table class="custom-logical-table table-borders excel-order">
				<tbody>
					<tr class="clt-h">
						<td class="clt-corners">
							<div class="clt-corners-left" style="height: auto;">Штрих-код</div>
						</td>
						<td>Количество</td>
					</tr>
					<?foreach($arResult["ITEMS"] as $key=>$arVal):?>
						<?if(!is_numeric($arVal[1])):?>
							<?continue?>
						<?else:?>
							<tr>
								<?$cell = 0?>
								<?foreach($arVal as $data):?>
									<td class="clt-noborder">
										<?if($cell == 0):?>
											<?//=$data?>
											<input type="text" name="artnumber[]" value="<?=$data?>" />
										<?else:?>
											<input type="number" name="quantity[]" value="<?=intval($data)?>" />
										<?endif?>
									</td>
									<?$cell++?>
								<?endforeach?>
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
						<span class="custom-button cb-color-2"><input class="text-up" type="submit" value="Добавить в корзину" /> </span><br>
						<small>Нажмите эту кнопку, чтобы
						заказать товары</small>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
<?endif?>
