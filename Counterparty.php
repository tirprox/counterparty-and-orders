<?php

class Counterparty
{
    public $id, $href;
    public $name, $phone, $email;
    public $tags = ['test'];

    var $attributes = [];

    function __construct(string $name = null, string $phone = null, string $email = null)
    {
        $this->name = $name;
        $this->phone = self::prepare_phone($phone);
        $this->email = $email;
    }

    function addTag(string $tag): void {
        $this->tags[]= $tag;
    }

    function setTags(array $tags): void  {
        $this->tags = $tags;
    }

    function removeTag(string $tag): void  {
        if (in_array($tag, $this->tags))
        {
            unset($this->tags[array_search($tag,$this->tags)]);
        }
    }


    function addLastName(string $lastName): void {
        $this->attributes[] = [
            "id" =>  "c6597688-cf9b-11e7-7a6c-d2a9000ec13c",
            "name" => "Фамилия",
            "type" =>  "string",
            "value" => $lastName
        ];
    }

    function addSource(string $source): void {
        $this->attributes[] = [
            "id" => "fe06e4f2-d034-11e7-7a34-5acf0006a4c2",
            "name" => "Источник",
            "type" => "string",
            "value" => $source
        ];
    }

    function addRegisterTimeDate(string $date, string $time): void {
        $this->attributes[] = [
            "id" => "fe06e948-d034-11e7-7a34-5acf0006a4c3",
            "name" => "Дата регистрации анкеты",
            "type" => "time",
            "value" => self::prepare_time($date, $time)
        ];
    }

    function addFeedback(string $feedback): void {
        $this->attributes[] = [
            "id" => "b3d9786a-d361-11e7-7a6c-d2a9001aff01",
            "name" => "Отзыв",
            "type" => "text",
            "value" => $feedback
        ];
    }

    function encodeForMS(): string {
        $encoded = [
            "name" => $this->name,
            "phone" => $this->phone,
            "email" => $this->email,
            "tags" => $this->tags,
            "companyType" => "individual",
            "group" => [
                "meta" => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/group/59c74466-a4ef-11e7-7a69-8f5500021289",
                    "metadataHref" => "https://online.moysklad.ru/api/remap/1.1/entity/group/metadata",
                    "type" => "group",
                    "mediaType" => "application/json"
                ]
            ],
            "attributes" => $this->attributes
        ];
        return json_encode($encoded, JSON_UNESCAPED_UNICODE);
    }

    function parseJson(stdClass $json) : void {
        $this->id = $json->rows[0]->id;
        $this->href = MSExporter::MS_BASE_URL . "counterparty/" . $this->id;
    }



    static function prepare_phone($phone): string {
        $phone = str_replace("+", "", $phone);
        $phone = str_replace("-", "", $phone);
        $phone = str_replace(" ", "", $phone);
        return $phone;
    }

    static function prepare_time($date, $time): string {
        $dateArray = date_parse_from_format("j.n.Y", $date);
        //$dateArray = date_parse($date);
        $timeString = $dateArray['year']. "-" . $dateArray['month']. "-" . $dateArray['day'] . " " . $time;
        return $timeString;
    }
}