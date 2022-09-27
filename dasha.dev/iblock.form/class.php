<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Promarine\Helper\Constant;

CJSCore::Init(array("jquery"));


class IblockForm extends CBitrixComponent
{
    private const FIELDS = [
        "NAME" => [
            "TYPE" => "S",
            "NAME" => "Имя"
        ],
        "PHONE" => [
            "TYPE" => "S",
            "NAME" => "Телефон"
        ],
        "EMAIL" => [
            "TYPE" => "S",
            "NAME" => "E-mail"
        ],
        "USER_ID" => [
            "USER_TYPE" => "UserID",
            "NAME" => "Пользователь"
        ],
        "SUBDIVISION" => [
            "TYPE" => "L",
            "NAME" => "Подразделение",
            "VALUES" => ["o" => "Офис", "b" => "Бухгалтерия", "op" => "Отдел продаж"]
        ]
    ];

    private const EMAIL_TO = [
        "o" => "office@address.com",
        "op" => "sale@address.com",
        "b" => "accounting@adress.com",
    ];

    private const EVENT_TEMPLATE = "COMPILED_FORM";

    private static $moduleNames = ["iblock"];
    private static $iBlockCode = "zakaulovaaa_iblock";
    private static $iBlockTypeID = "zakaulovaaa";
    private $managerElement;
    private $managerIBlock;
    private $managerIBlockType;
    private $managerIBlockProperty;

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function executeComponent()
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams["ACTION"]) {
                case "add":
                    $result = $this->add();
                    break;
                default:
                    $this->setTemplateData();
                    $this->includeComponentTemplate();
            }
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
        return $result;
    }

    private function getData(): array
    {
        $data = [];

        foreach (self::FIELDS as $field => $arField) {
            $value = $_REQUEST[$field];
            if ($value !== null && $value !== "") {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function add(): array
    {
        $data = $this->getData();

        if (!check_bitrix_sessid()) {
            return [
                "STATUS" => "ERROR",
                "MESSAGE" => "Не найдено поле sessid"
            ];
        }

        if (!($data["NAME"] && $data["PHONE"] && $data["EMAIL"])) {
            return [
                "STATUS" => "ERROR",
                "MESSAGE" => "Заполните все поля"
            ];
        }

        $iblockId = $this->getIBlockId();
        $arFields = [
            "ACTIVE" => "Y",
            "MODIFIED_BY" => ((new CUser())->IsAuthorized() ? (new CUser())->GetID() : false),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $iblockId,
            "DATE_ACTIVE_FROM" => new \Bitrix\Main\Type\DateTime(),
            "NAME" => $data["NAME"] . " " . (new DateTime())->format("d.m.Y"),
            "PROPERTY_VALUES" => [
                "NAME" => $data["NAME"],
                "PHONE" => $data["PHONE"],
                "EMAIL" => $data["EMAIL"],
                "SUBDIVISION" => $this->getSubdivisionIdByXml($iblockId, $data["SUBDIVISION"]),
                "USER_ID" => (new CUser())->GetID()
            ],
        ];
        $elId = $this->managerElement->Add($arFields);
        if ($elId > 0) {
            $this->sendEmail($data, $elId);
            return [
                "STATUS" => "SUCCESS",
                "MESSAGE" => "Ура! Все записалось!"
            ];
        } else {
            return [
                "STATUS" => "ERROR",
                "MESSAGE" => $this->managerElement->LAST_ERROR
            ];
        }
    }

    private function sendEmail(array $data, int $elementId): void
    {
        $fields = [
            "ELEMENT_ID" => $elementId,
            "DATE" => (new \Bitrix\Main\Type\DateTime())->toString(),
            "NAME" => $data["NAME"],
            "PHONE" => $data["PHONE"],
            "EMAIL" => $data["EMAIL"],
            "USER_ID" => $data["USER_ID"],
            "SUBDIVISION" => $data["SUBDIVISION"],
            "EMAIL_TO" => self::EMAIL_TO[$data["SUBDIVISION"]],
        ];

        CEvent::Send(self::EVENT_TEMPLATE, SITE_ID, $fields);
    }


    private function dataManager(): void
    {
        $this->managerElement = new CIBlockElement();
        $this->managerIBlock = new CIBlock();
        $this->managerIBlockType = new CIBlockType();
        $this->managerIBlockProperty = new CIBlockProperty();
    }

    /**
     * @throws LoaderException
     */
    private function loadModules(): void
    {
        foreach (self::$moduleNames as $moduleName) {
            $moduleLoaded = Loader::includeModule($moduleName);
            if (!$moduleLoaded) {
                throw new LoaderException(
                    Loc::getMessage("MODULE_LOAD_ERROR", ["#MODULE_NAME#" => $moduleName])
                );
            }
        }
    }

    /**
     * Set Template Data.
     */
    private function setTemplateData(): void
    {
        $this->arResult["PATH_TO_AJAX"] = $this->getPath() . "/ajax.php";
        $this->arResult["FIELDS"] = self::FIELDS;
    }

    private function getIBlockId() {
        $res = CIBlock::GetList(
            [],
            [
                "CODE"=> self::$iBlockCode
            ],
            true
        );
        if ($arRes = $res->Fetch()) {
            return $arRes["ID"];
        }
        return $this->createIBlockId();
    }

    private function createIBlockId(): string {
        $arFields = Array(
            "ACTIVE" => "Y",
            "NAME" => "Инфоблок для сохранения результатов формы (Закаулова)",
            "CODE" => self::$iBlockCode,
            "IBLOCK_TYPE_ID" => $this->getIBlockTypeId(),
            "SITE_ID" => SITE_ID,
            "SORT" => 100,
        );
        $id = $this->managerIBlock->Add($arFields);
        if ($id > 0) {
            $this->createProperties($id);
            return $id;
        }
        throw new LoaderException(
            Loc::getMessage("ERROR_ADD_IBLOCK")
        );
    }

    private function createProperties($iBlockId) {
        foreach (self::FIELDS as $fieldCode => $arField) {
            $arFields = Array(
                "NAME" => $arField["NAME"],
                "ACTIVE" => "Y",
                "SORT" => "100",
                "CODE" => $fieldCode,
                "IBLOCK_ID" => $iBlockId
            );
            if (!empty($arField["TYPE"]))
                $arFields["PROPERTY_TYPE"] = $arField["TYPE"];

            if (!empty($arField["USER_TYPE"]))
                $arFields["USER_TYPE"] = $arField["USER_TYPE"];

            if ($arFields["PROPERTY_TYPE"] === "L") {
                foreach ($arField["VALUES"] as $xmlId => $value) {
                    $arFields["VALUES"][] = [
                        "VALUE" => $value,
                        "DEF" => "N",
                        "SORT" => "100",
                        "XML_ID" => $xmlId,
                    ];
                }
            }
            $propID = $this->managerIBlockProperty->Add($arFields);
            if ($propID < 0) {
                throw new LoaderException(
                    $this->managerIBlockProperty->LAST_ERROR
                );
            }
        }

    }

    private function getIBlockTypeId() {
        $dbIBlockType = CIBlockType::GetList([], ["ID" => "zakaulovaaa"]);
        if ($type = $dbIBlockType->Fetch()) {
            return $type["ID"];
        }

        return $this->addIBlockType();
    }

    private function addIBlockType() {
        $arFields = Array(
            "ID" => "zakaulovaaa",
            "SECTIONS"=>"Y",
            "SORT" => 100,
            "LANG" => [
                "en"=> [
                    "NAME"=>"zakaulovaaa",
                    "SECTION_NAME"=>"Sections",
                    "ELEMENT_NAME"=>"Products"
                ],
                "ru" => [
                    "NAME"=>"zakaulovaaa",
                    "SECTION_NAME"=>"Разделы",
                    "ELEMENT_NAME"=>"Элементы"
                ]
            ]
        );
        global $DB;
        $DB->StartTransaction();
        $res = $this->managerIBlockType->Add($arFields);
        if(!$res) {
            $DB->Rollback();
            throw new LoaderException(
                $this->managerIBlockType->LAST_ERROR
            );
        } else {
            $DB->Commit();
            return $res;
        }
    }

    private function getSubdivisionIdByXml($iblockId, $xmlId) {
        $propertyEnums = CIBlockPropertyEnum::GetList(
            [],
            [
                "IBLOCK_ID" => $iblockId,
                "CODE" => "SUBDIVISION",
                "XML_ID" => $xmlId
            ]
        );

        if ($field = $propertyEnums->GetNext()) {
            return $field["ID"];
        }
        return null;
    }

}
