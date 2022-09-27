# form_for_iblock
Компонент, реализующий добавление данных формы в инфоблок и отправку письма.

## Для установки: 

* Скопировать папку dasha.dev в /local/components/

* Создать почтовый шаблон
Для отправки почты необходимо создать почтовое событие COMPILED_FORM. 
В шаблоне для события доступны поля: 

```
    "ELEMENT_ID"
    "DATE"
    "NAME"
    "PHONE"
    "EMAIL"
    "USER_ID"
    "SUBDIVISION"
    "EMAIL_TO"
```


* Подключить компонент:

```
<?php $APPLICATION->IncludeComponent(
    "dasha.dev:iblock.form",
    "",
    []
); ?>
```



PS Тип инфоблока, инфоблок и свойства к нему создаются автоматически.






