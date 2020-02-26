<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MetaActionReport;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\API\Request;
use Piwik\Piwik;
use Piwik\DbHelper;
use Piwik\Db;
use Piwik\Common;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Plugins\MetaActionReport\MetaDataArray;
use Piwik\Segment;

const DIMENSION_QUERY_COUNT = 20;


function getSubTableRecursive($dataTable, $idSubtable) {
    if ($dataTable->getRowFromIdSubDataTable($idSubtable)) {
        return $dataTable->getRowFromIdSubDataTable($idSubtable)->getSubtable();
    } else {
        foreach ($dataTable->getRows() as $row) {
            $newDataTable = $row->getSubtable();
            if ($newDataTable) {
                $found = getSubTableRecursive($newDataTable, $idSubtable);
                if ($found) {
                    return $found;
                }
            }

        }
    }
    return False;
}

/**
 * API for plugin MetaActionReport
 *
 * @method static \Piwik\Plugins\MetaActionReport\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    private function getDb()
    {
        return Db::get();
    }

    private function createDataTable($rows)
    {
        $useless1 = new DataTable;
        foreach ($rows as $row) {
            $useless1->addRowFromArray(array(Row::COLUMNS => $row));
        }

        return $useless1;
    }

    private function getDimensionQuery($number_of_dimension)
    {
        $result = "";
        for($i = 1; $i < $number_of_dimension + 1; ++$i) {
            $result .= ", log_link_visit_action.`custom_dimension_" . $i ."`";
        }
        return $result;
    }

    private function getGroupby($number_of_dimension)
    {
        $result = "";
        for($i = 1; $i < $number_of_dimension + 1; ++$i) {
            if ($i == 1) {
                $result .= "log_link_visit_action.`custom_dimension_" . $i ."`";
            } else {
                $result .= ", log_link_visit_action.`custom_dimension_" . $i ."`";
            }
        }
        return $result;
    }


    public function getDimensionBySite($idSite, $period, $date, $segment = false)
    {
        $dimensions = Request::processRequest('CustomDimensions.getConfiguredCustomDimensions', ['idSite' => $idSite], []);

        $dimension_list = array();
        foreach ($dimensions as $index => $dimension) {
            if ($dimension['active'] and $dimension['scope'] == "action") {
                $dimension_list["custom_dimension_" . $dimension["idcustomdimension"]] = $dimension["name"];
            }
        }
        /*
        $dimension_list = array(
            "custom_dimension_1" => "Country", 
            "custom_dimension_2" => "State", 
            "custom_dimension_3" => "City", 
            "custom_dimension_4" => "PostalCode"
        );*/
        return $dimension_list;
    }

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getEventAction($idSite, $period, $date, $segment = false, $dimension1Name="", $dimension2Name="", $dimension3Name="", $dimension4Name="", $dimension5Name="", $idAction=null, $idSubtable=null)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $dataTable = new DataTable();
        if (!$idAction or !$dimension1Name) {
            return $dataTable;
        }
        if (strpos($date, ',') === false) {
            $real_period = PeriodFactory::build($period, $date);
        } else {
            $real_period = PeriodFactory::build('range', $date);
        }
        $startDate = $real_period->getDateTimeStart()->getDatetime();
        $endDate = $real_period->getDateTimeEnd()->getDatetime();
        $dimension_query = $this->getDimensionQuery(DIMENSION_QUERY_COUNT);

        $select = "log_link_visit_action.idaction_event_action, log_link_visit_action.idaction_name, log_link_visit_action.server_time, log_link_visit_action.idlink_va, log_link_visit_action.idvisit " . $dimension_query;
        $from = "log_link_visit_action";
        $where = "log_link_visit_action.idsite = ? AND log_link_visit_action.server_time >= ? AND log_link_visit_action.server_time <= ? AND log_link_visit_action.idaction_event_action = ?";
        $whereBind = array($idSite, $startDate, $endDate,  $idAction);
        $orderBy = False;
        $groupBy = False;
        $groupBy_query = $this->getGroupby(DIMENSION_QUERY_COUNT);
        $segment = new Segment($segment, $idSite);
        $queryInfo = $segment->getSelectQuery($select, $from, $where, $whereBind, $orderBy, $groupBy);

        $sql = "SELECT 
                COUNT(DISTINCT idvisit) as meta_unique_action,
                COUNT(*) as meta_action
                $dimension_query 
                FROM ({$queryInfo['sql']}) as log_link_visit_action 
                LEFT JOIN " . Common::prefixTable('log_action') . " a
                ON a.`idaction` = log_link_visit_action.`idaction_event_action`
                LEFT JOIN " . Common::prefixTable('log_action') . " aa
                ON aa.`idaction` = log_link_visit_action.`idaction_name`
                GROUP BY $groupBy_query";
        $bind = $queryInfo['bind'];
        $db = $this->getDb();
        $rows = $db->fetchAll($sql, $bind);
        //$subTablesByKey[$key] = DataTable::makeFromIndexedArray($labelPerKey);
        DataTable::setMaximumDepthLevelAllowedAtLeast(5);
        $allMetricNames = array("meta_action" => "sum", "meta_unique_action" => "sum");
        $metaDataArray = new MetaDataArray($allMetricNames);
        $dataArray = $this->getExampleDataArray();
        # Debug replace $rows by dataArray
        #foreach ($dataArray as $row) {
        foreach ($rows as $row) {
            $firstLevelLabel = "Not defined";
            $secondLevelLabel = "Not defined";
            $thirdLevelLabel = "Not defined";
            $fourthLevelLabel = "Not defined";
            $fifthLevelLabel = "Not defined";

            if (isset($row[$dimension1Name])) {
                $firstLevelLabel = $row[$dimension1Name];
            }
            if ($dimension2Name) {
                if (isset($row[$dimension2Name])) {
                    $secondLevelLabel = $row[$dimension2Name];
                }
            }
            if ($dimension2Name && $dimension3Name) {
                if (isset($row[$dimension3Name])) {
                    $thirdLevelLabel = $row[$dimension3Name];
                }
            }
            if ($dimension2Name && $dimension3Name && $dimension4Name) {
                if (isset($row[$dimension4Name])) {
                    $fourthLevelLabel = $row[$dimension4Name];
                }
            }
            if ($dimension2Name && $dimension3Name && $dimension4Name && $dimension5Name) {
                if (isset($row[$dimension5Name])) {
                    $fifthLevelLabel = $row[$dimension5Name];
                }
            }
            $countArray = ["meta_action" => $row["meta_action"], "meta_unique_action" => $row["meta_unique_action"]];
            $metaDataArray->computeMetrics($countArray, $firstLevelLabel);
            if ($dimension2Name) {
                $metaDataArray->computeMetricsLevel2($countArray, $firstLevelLabel, $secondLevelLabel);
            }
            if ($dimension2Name && $dimension3Name) {
                $metaDataArray->computeMetricsLevel3($countArray, $firstLevelLabel, $secondLevelLabel, $thirdLevelLabel);
            }
            if ($dimension2Name && $dimension3Name && $dimension4Name) {
                $metaDataArray->computeMetricsLevel4($countArray, $firstLevelLabel, $secondLevelLabel, $thirdLevelLabel, $fourthLevelLabel);
            }
            if ($dimension2Name && $dimension3Name && $dimension4Name && $dimension5Name) {
                $metaDataArray->computeMetricsLevel5($countArray, $firstLevelLabel, $secondLevelLabel, $thirdLevelLabel, $fourthLevelLabel, $fifthLevelLabel);
            }
        }
        $dataTable = $metaDataArray->asDataTable();
        $filterSortColumn = Common::getRequestVar('filter_sort_column', false, 'string');
        if ($filterSortColumn !== false) {
            $filterSortOrder = Common::getRequestVar('filter_sort_order', false, 'string');
            if (!$filterSortOrder) {
                $filterSortOrder = "desc";
            }
            $dataTable->filter('Sort', array($filterSortColumn, $filterSortOrder, $naturalSort = false, $expanded=false));
        }

         
        if ($idSubtable) {
            return getSubTableRecursive($dataTable, $idSubtable);
        } 
        return $dataTable;
    }

    private function getExampleDataArray()
    {
        $dataArray = array();
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 1",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 1",
            "custom_dimension_4" => "c4 - 2",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 1",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 2",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 2",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 3",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 3",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_3" => "c3 - 3",
            "custom_dimension_4" => "c4 - 1",
            "custom_dimension_5" => "c5 - 1",
            "custom_dimension_6" => "c6 - 1",
            "custom_dimension_7" => "c7 - 1",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        return $dataArray;
    }
}
