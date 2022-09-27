<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 * @var $component
 */

?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
      integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

<div class="container">
    <form>
        <?php foreach ($arResult["FIELDS"] as $fieldCode => $arField):
            if (!in_array($arField["TYPE"], ["L", "S"])) {
                continue;
            }
            ?>
            <div class="form-group">
                <label for="<?=$fieldCode?>"><?=$arField['NAME']?></label>
                <?php switch ($arField["TYPE"]) {
                    case "L": ?>
                        <select class="form-control" name="<?=$fieldCode?>" id="<?=$fieldCode?>">
                            <?php foreach ($arField['VALUES'] as $xml => $value): ?>
                                <option value="<?=$xml?>"><?=$value?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                        break;
                    case "S": ?>
                        <input class="form-control" name="<?=$fieldCode?>" id="<?=$fieldCode?>">
                        <?php
                        break;
                }?>
            </div>

        <?php endforeach; ?>

        <button type="button" onclick="BX.Iblock.Form.add(this);">Отправить</button>
    </form>
</div>
<script>
    BX.Iblock.Form.init({
        ajaxUrl: '<?=CUtil::JSEscape($arResult['PATH_TO_AJAX'])?>',
        signedParams: '<?=CUtil::JSEscape($arResult['SIGNED_PARAMS'])?>',
    });
</script>
