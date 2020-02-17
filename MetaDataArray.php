<?php
/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

namespace Piwik\Plugins\MetaActionReport;

use Piwik\DataTable;

class MetaDataArray extends \Piwik\DataArray
{
    private $emptyRow = array();

    protected $dataThreeLevel = array();
    protected $dataFourLevel = array();
    protected $dataFiveLevel = array();
    private $aggregations = array();

    public $doneFirstLevel = false;
    public $doneSecondLevel = false;

    /**
     * @param string[] $metrics
     */
    public function __construct($metrics)
    {
        parent::__construct($data = array(), $dataArrayByLabel = array());

        $this->aggregations = $metrics;

        foreach ($metrics as $metric => $aggregation) {
            if ($aggregation === 'min') {
                $this->emptyRow[$metric] = null;
            } else {
                $this->emptyRow[$metric] = 0;
            }
        }
    }

    /**
     * Returns an empty row containing default metrics
     *
     * @return array
     */
    public function createEmptyRow($level)
    {
        $row = $this->emptyRow;
        $row['level'] = $level;
        return $row;
    }

    protected function isEmptyLabel($label)
    {
        return !isset($label) || $label === '' || $label === false;
    }

    /**
     * @param $row
     */
    public function computeMetrics($row, $label)
    {
        if ($this->isEmptyLabel($label)) {
            $label = "Not defined"; 
        }

        if (!isset($this->data[$label])) {
            $this->data[$label] = $this->createEmptyRow(1);
        }

        foreach ($row as $column => $value) {
            if (!isset($this->aggregations[$column])) {
                continue;
            }

            if (isset($this->data[$label][$column])) {
                $this->data[$label][$column] += $value;
            } else {
                $this->data[$label][$column] = $value;
            }
        }
    }

    /**
     * @param $row
     */
    public function computeMetricsLevel2($row, $label, $sublabel)
    {
        if (!isset($sublabel)) {
            return;
        }

        if ($this->isEmptyLabel($label)) {
            $label = "Not defined";
        }

        if (!isset($this->data[$label])) {
            $this->data[$label] = $this->createEmptyRow(1);
        }

        if (!isset($this->dataTwoLevels[$label])) {
            $this->dataTwoLevels[$label] = array();
        }

        if (!isset($this->dataTwoLevels[$label][$sublabel])) {
            $this->dataTwoLevels[$label][$sublabel] = $this->createEmptyRow(2);
        }

        foreach ($row as $column => $value) {
            if (!isset($this->aggregations[$column])) {
                continue;
            }
            if (isset($this->dataTwoLevels[$label][$sublabel][$column])) {
                $this->dataTwoLevels[$label][$sublabel][$column] += $value;
            } else {
                $this->dataTwoLevels[$label][$sublabel][$column] = $value;
            }
        }
    }

    public function computeMetricsLevel3($row, $label, $sublabel, $subsublabel)
    {
        if (!isset($subsublabel)) {
            return;
        }

        if ($this->isEmptyLabel($label)) {
            $label = Archiver::LABEL_NOT_DEFINED;
        }

        if (!isset($this->data[$label])) {
            $this->data[$label] = $this->createEmptyRow(1);
        }

        if (!isset($this->dataTwoLevels[$label])) {
            $this->dataTwoLevels[$label] = array();
        }

        if (!isset($this->dataTwoLevels[$label][$sublabel])) {
            $this->dataTwoLevels[$label][$sublabel] = $this->createEmptyRow(2);
        }

        if (!isset($this->dataThreeLevel[$label])) {
            $this->dataThreeLevel[$label] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel])) {
            $this->dataThreeLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel][$subsublabel])) {
            $this->dataThreeLevel[$label][$sublabel][$subsublabel] = $this->createEmptyRow(3);
        }

        foreach ($row as $column => $value) {
            if (!isset($this->aggregations[$column])) {
                continue;
            }

            if (isset($this->dataThreeLevel[$label][$sublabel][$subsublabel][$column])) {
                $this->dataThreeLevel[$label][$sublabel][$subsublabel][$column] += $value;

            } else {
                $this->dataThreeLevel[$label][$sublabel][$subsublabel][$column] = $value;
            }
        }
    }

    public function computeMetricsLevel4($row, $label, $sublabel, $subsublabel, $sublabel_l3)
    {
        if (!isset($subsublabel)) {
            return;
        }

        if ($this->isEmptyLabel($label)) {
            $label = Archiver::LABEL_NOT_DEFINED;
        }

        if (!isset($this->data[$label])) {
            $this->data[$label] = $this->createEmptyRow(1);
        }

        if (!isset($this->dataTwoLevels[$label])) {
            $this->dataTwoLevels[$label] = array();
        }

        if (!isset($this->dataTwoLevels[$label][$sublabel])) {
            $this->dataTwoLevels[$label][$sublabel] = $this->createEmptyRow(2);
        }
        
        // Level 3

        if (!isset($this->dataThreeLevel[$label])) {
            $this->dataThreeLevel[$label] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel])) {
            $this->dataThreeLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel][$subsublabel])) {
            $this->dataThreeLevel[$label][$sublabel][$subsublabel] = $this->createEmptyRow(3);
        }


        // Level 4
        if (!isset($this->dataFourLevel[$label])) {
            $this->dataFourLevel[$label] = array();
        }

        if (!isset($this->dataFourLevel[$label][$sublabel])) {
            $this->dataFourLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataFourLevel[$label][$sublabel][$subsublabel])) {
            $this->dataFourLevel[$label][$sublabel][$subsublabel] = array(); 
        }

        if (!isset($this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3])) {
            $this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3] = $this->createEmptyRow(4);
        }

        foreach ($row as $column => $value) {
            if (!isset($this->aggregations[$column])) {
                continue;
            }

            if (isset($this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$column])) {
                $this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$column] += $value;

            } else {
                $this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$column] = $value;
            }
        }
    }

    public function computeMetricsLevel5($row, $label, $sublabel, $subsublabel, $sublabel_l3, $sublabel_l4)
    {
        if (!isset($subsublabel)) {
            return;
        }

        if ($this->isEmptyLabel($label)) {
            $label = Archiver::LABEL_NOT_DEFINED;
        }

        if (!isset($this->data[$label])) {
            $this->data[$label] = $this->createEmptyRow(1);
        }

        if (!isset($this->dataTwoLevels[$label])) {
            $this->dataTwoLevels[$label] = array();
        }

        if (!isset($this->dataTwoLevels[$label][$sublabel])) {
            $this->dataTwoLevels[$label][$sublabel] = $this->createEmptyRow(2);
        }
        
        // Level 3

        if (!isset($this->dataThreeLevel[$label])) {
            $this->dataThreeLevel[$label] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel])) {
            $this->dataThreeLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataThreeLevel[$label][$sublabel][$subsublabel])) {
            $this->dataThreeLevel[$label][$sublabel][$subsublabel] = $this->createEmptyRow(3);
        }


        // Level 4
        if (!isset($this->dataFourLevel[$label])) {
            $this->dataFourLevel[$label] = array();
        }

        if (!isset($this->dataFourLevel[$label][$sublabel])) {
            $this->dataFourLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataFourLevel[$label][$sublabel][$subsublabel])) {
            $this->dataFourLevel[$label][$sublabel][$subsublabel] = array(); 
        }

        if (!isset($this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3])) {
            $this->dataFourLevel[$label][$sublabel][$subsublabel][$sublabel_l3] = $this->createEmptyRow(4);
        }

        // Level 5
        if (!isset($this->dataFiveLevel[$label])) {
            $this->dataFiveLevel[$label] = array();
        }

        if (!isset($this->dataFiveLevel[$label][$sublabel])) {
            $this->dataFiveLevel[$label][$sublabel] = array();
        }

        if (!isset($this->dataFiveLevel[$label][$sublabel][$subsublabel])) {
            $this->dataFiveLevel[$label][$sublabel][$subsublabel] = array(); 
        }

        if (!isset($this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3])) {
            $this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3] = array();
        }

        if (!isset($this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$sublabel_l4])) {
            $this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$sublabel_l4] = $this->createEmptyRow(5);;
        }

        foreach ($row as $column => $value) {
            if (!isset($this->aggregations[$column])) {
                continue;
            }

            if (isset($this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$sublabel_l4][$column])) {
                $this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$sublabel_l4][$column] += $value;
            } else {
                $this->dataFiveLevel[$label][$sublabel][$subsublabel][$sublabel_l3][$sublabel_l4][$column] = $value;
            }
        }
    }

    public function getThirdLevelData()
    {
        return $this->dataThreeLevel;
    }

    /**
     * Converts array to a datatable
     *
     * @return \Piwik\DataTable
     */
    public function asDataTable()
    {
        if (!empty($this->dataFiveLevel)) {
            $subTable_lv1 = array();

            foreach ($this->dataFiveLevel as $label_lv1 => $keyTables_lv2) {
                $subTablesByKey_lv2 = array();
                foreach ($keyTables_lv2 as $label_lv2 => $keyTables_lv3) {
                    $subTablesByKey_lv3 = array();
                    foreach ($keyTables_lv3 as $label_lv3 => $keyTables_lv4) {
                        $subTablesByKey_lv4 = array();
                        foreach( $keyTables_lv4 as $label_lv4 => $keyTables_lv5) {
                            $subTablesByKey_lv4[$label_lv4] = DataTable::makeFromIndexedArray($keyTables_lv5);
                        }
                        $subTablesByKey_lv3[$label_lv3] = DataTable::makeFromIndexedArray($this->dataFourLevel[$label_lv1][$label_lv2][$label_lv3], $subTablesByKey_lv4);
                    }
                    $subTablesByKey_lv2[$label_lv2] = DataTable::makeFromIndexedArray($this->dataThreeLevel[$label_lv1][$label_lv2], $subTablesByKey_lv3);
                }

                $subTable_lv1[$label_lv1] = DataTable::makeFromIndexedArray($this->dataTwoLevels[$label_lv1], $subTablesByKey_lv2);
            }
            return DataTable::makeFromIndexedArray($this->data, $subTable_lv1);
        }

        if (!empty($this->dataFourLevel)) {
            $subTable_lv1 = array();

            foreach ($this->dataFourLevel as $label_lv1 => $keyTables_lv2) {
                $subTablesByKey_lv2 = array();
                foreach ($keyTables_lv2 as $label_lv2 => $keyTables_lv3) {
                    $subTablesByKey_lv3 = array();
                    foreach ($keyTables_lv3 as $label_lv3 => $keyTables_lv4) {
                        $subTablesByKey_lv3[$label_lv3] = DataTable::makeFromIndexedArray($keyTables_lv4);
                    }
                    $subTablesByKey_lv2[$label_lv2] = DataTable::makeFromIndexedArray($this->dataThreeLevel[$label_lv1][$label_lv2], $subTablesByKey_lv3);
                }

                $subTable_lv1[$label_lv1] = DataTable::makeFromIndexedArray($this->dataTwoLevels[$label_lv1], $subTablesByKey_lv2);
            }

            return DataTable::makeFromIndexedArray($this->data, $subTable_lv1);
        }

        if (!empty($this->dataThreeLevel)) {
            $subTableByParentLabel = array();

            foreach ($this->dataThreeLevel as $label => $keyTables) {
                $subTablesByKey = array();
                foreach ($keyTables as $key => $labelPerKey) {
                    $subTablesByKey[$key] = DataTable::makeFromIndexedArray($labelPerKey);
                }

                $subTableByParentLabel[$label] = DataTable::makeFromIndexedArray($this->dataTwoLevels[$label], $subTablesByKey);
            }
            return DataTable::makeFromIndexedArray($this->data, $subTableByParentLabel);
        }

        return parent::asDataTable();
    }
}
