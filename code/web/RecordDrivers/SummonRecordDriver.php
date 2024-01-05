<?php

class SummonRecordDriver extends RecordInterface {
    private $recordData;

    public function __construct($recordData) {
        if (is_string($recordData)) {
        /** @var SearchObject_SummonSearcher $summonSearcher */
        $summonSearcher = SearchObjectFactory::initSearchObject("Summon");

        }
    }
}

