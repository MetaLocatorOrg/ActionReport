<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MetaActionReport\Visualizations;

use Piwik\API\Request as ApiRequest;
use Piwik\Common;
use Piwik\Period;
use Piwik\Plugin\Visualization;
use Piwik\View;

/**
 * DataTable visualization that shows DataTable data in an HTML table.
 *
 * @property Meta\Config $config
 */
class MetaActionHtmlTable extends Visualization
{
    const ID = 'meta_action_table';
    const TEMPLATE_FILE     = "@MetaActionReport/_MetaActionTableViz_htmlTable.twig";
    const FOOTER_ICON       = 'plugins/Morpheus/images/table.png';
    const FOOTER_ICON_TITLE = 'Meta Action Report';

    public function array_insert($a, $value, $index)
    {
        return array_merge(array_slice($a, 0, $index), array($value), array_slice($a, $index));
    }

    /**
     * Hook that is called before loading report data from the API.
     *
     * Use this method to change the request parameters that is sent to the API when requesting
     * data.
     *
     * @api
     */
    public function beforeLoadDataTable()
    {
        $this->requestConfig->request_parameters_to_modify['flat'] = 1;
    }



    public function beforeRender()
    {
        if ($this->requestConfig->idSubtable
            && $this->config->show_embedded_subtable) {

            $this->config->show_visualization_only = true;
        }

        // we do not want to get a datatable\map
        $period = Common::getRequestVar('period', 'day', 'string');
        if (Period\Range::parseDateRange($period)) {
            $period = 'range';
        }
        $this->config->columns_to_display = $this->array_insert($this->config->columns_to_display, 'product_name', 1);

        if ($this->dataTable->getRowsCount()) {
            $request = new ApiRequest(array(
                'method' => 'API.get',
                'module' => 'API',
                'action' => 'get',
                'format' => 'original',
                'filter_limit'  => '-1',
                'disable_generic_filters' => 1,
                'expanded'      => 1,
                'flat'          => 1,
                'filter_offset' => 0,
                'period'        => $period,
                'showColumns'   => implode(',', $this->config->columns_to_display),
                'columns'       => implode(',', $this->config->columns_to_display),
                'pivotBy'       => ''
            ));

            $dataTable = $request->process();
            $this->assignTemplateVar('siteSummary', $dataTable);
        }

        if ($this->isPivoted()) {
            $this->config->columns_to_display = $this->dataTable->getColumns();
        }
    }

    public function beforeGenericFiltersAreAppliedToLoadedDataTable()
    {
        if ($this->isPivoted()) {
            $this->config->columns_to_display = $this->dataTable->getColumns();

            $this->dataTable->applyQueuedFilters();
        }

        parent::beforeGenericFiltersAreAppliedToLoadedDataTable();
    }

    protected function isPivoted()
    {
        return $this->requestConfig->pivotBy || Common::getRequestVar('pivotBy', '');
    }
}

