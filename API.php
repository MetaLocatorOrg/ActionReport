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
    public function getEventAction($idSite, $period, $date, $segment = false, $dimension1Name="custom_dimension_1", $dimension2Name="custom_dimension_2", $dimension3Name="custom_dimension_3", $dimension4Name="custom_dimension_4", $dimension5Name="custom_dimension_5", $idAction=1, $idSubtable=null)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $query = "
        SELECT
          la.server_time,
          la.idlink_va,
          la.`custom_dimension_1`,
          la.`custom_dimension_2`,
          la.`custom_dimension_3`,
          la.`custom_dimension_4`,
          la.`custom_dimension_5`,
          a.name AS event_action ,
          aa.name AS action_name,
          CASE
            WHEN LOCATE('No Results', aa.name) > 0
            THEN 1
            ELSE 0
          END AS NO_RESULTS,
          CASE
            WHEN LOCATE('Autofind', aa.name) > 0
            THEN 1
            ELSE 0
          END AS AUTOFIND
        FROM
          " . Common::prefixTable('log_link_visit_action') . " la
          LEFT JOIN " . Common::prefixTable('log_action') . " a
            ON a.`idaction` = la.`idaction_event_action`
          LEFT JOIN " . Common::prefixTable('log_action') . " aa
            ON aa.`idaction` = la.`idaction_name`
        WHERE 1
        AND la.idsite = ?
        AND la.server_time >= ?
        AND la.server_time <= ?
        AND la.`idaction_event_action` = ?
        ORDER BY la.server_time DESC 
        ";
        if (strpos($date, ',') === false) {
            $real_period = PeriodFactory::build('day', $date);
        } else {
            $real_period = PeriodFactory::build('range', $date);
        }
        $startDate = $real_period->getDateTimeStart()->getDatetime();
        $endDate = $real_period->getDateTimeEnd()->getDatetime();
        $dimension_query = $this->getDimensionQuery(20);

        $select = "log_link_visit_action.idaction_event_action, log_link_visit_action.idaction_name, log_link_visit_action.server_time, log_link_visit_action.idlink_va " . $dimension_query;
        $from = "log_link_visit_action";
        $where = "log_link_visit_action.idsite = ? AND log_link_visit_action.server_time >= ? AND log_link_visit_action.server_time <= ? AND log_link_visit_action.idaction_event_action = ?";
        $whereBind = array($idSite, $startDate, $endDate,  $idAction);
        $orderBy = False;
        $groupBy = False;
        $segment = new Segment($segment, $idSite);
        $queryInfo = $segment->getSelectQuery($select, $from, $where, $whereBind, $orderBy, $groupBy);

        $sql = "SELECT 
                log_link_visit_action.server_time,
                log_link_visit_action.idlink_va
                " . $dimension_query . "
                , a.name AS event_action ,
                aa.name AS action_name,
                CASE
                  WHEN LOCATE('No Results', aa.name) > 0
                  THEN 1
                 ELSE 0
                END AS NO_RESULTS,
                CASE
                  WHEN LOCATE('Autofind', aa.name) > 0
                  THEN 1
                  ELSE 0
                END AS AUTOFIND
                FROM ({$queryInfo['sql']}) as log_link_visit_action 
                LEFT JOIN " . Common::prefixTable('log_action') . " a
                ON a.`idaction` = log_link_visit_action.`idaction_event_action`
                LEFT JOIN " . Common::prefixTable('log_action') . " aa
                ON aa.`idaction` = log_link_visit_action.`idaction_name`";
        $bind = $queryInfo['bind'];
        $db = $this->getDb();
        $rows = $db->fetchAll($sql, $bind);
        $dataTable = new DataTable();
        //$subTablesByKey[$key] = DataTable::makeFromIndexedArray($labelPerKey);
        DataTable::setMaximumDepthLevelAllowedAtLeast(5);
        $allMetricNames = array("custom_dimension_1");
        $metaDataArray = new MetaDataArray($allMetricNames);
        $dataArray = $this->getExampleDataArray();
        # Debug replace $rows by dataArray
        #foreach ($dataArray as $row) {
        foreach ($rows as $row) {
            $firstLevelLabel = "Not defined";
            $secondLevelLabel = "Not defined";
            $thirdLevelLabel = "Not defined";
            if (isset($row[$dimension1Name])) {
                $firstLevelLabel = $row[$dimension1Name];
            }
            if (isset($row[$dimension2Name])) {
                $secondLevelLabel = $row[$dimension2Name];
            }
            if (isset($row[$dimension3Name])) {
                $thirdLevelLabel = $row[$dimension3Name];
            }
            $countArray = ["0" => 1];
            $metaDataArray->computeMetrics($countArray, $firstLevelLabel);
            $metaDataArray->computeMetricsLevel2($countArray, $firstLevelLabel, $secondLevelLabel);
            $metaDataArray->computeMetricsLevel3($countArray, $firstLevelLabel, $secondLevelLabel, $thirdLevelLabel);
        }
        $dataTable = $metaDataArray->asDataTable();
         
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
            "custom_dimension_1" => "1",
            "custom_dimension_2" => "a",
            "custom_dimension_3" => "v",
            "custom_dimension_4" => "e",
            "custom_dimension_5" => "f",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_1" => "1",
            "custom_dimension_2" => "b",
            "custom_dimension_3" => "v",
            "custom_dimension_4" => "e",
            "custom_dimension_5" => "e",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_1" => "2",
            "custom_dimension_2" => "d",
            "custom_dimension_3" => "v",
            "custom_dimension_4" => "e",
            "custom_dimension_5" => "f",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_1" => "2",
            "custom_dimension_2" => "d",
            "custom_dimension_3" => "v",
            "custom_dimension_4" => "c",
            "custom_dimension_5" => "e",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        $dataArray[] = array(
            "server_time" => "",
            "idlink_va" => "",
            "custom_dimension_1" => "2",
            "custom_dimension_2" => "d",
            "custom_dimension_3" => "v",
            "custom_dimension_4" => "c",
            "custom_dimension_5" => "e",
            "event_action" => "43",
            "action_name" => "",
            "NO_RESULTS" => 0,
            "AUTOFIND" => 1
        );
        return $dataArray;
    }
}
