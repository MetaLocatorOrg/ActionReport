$(document).ready(function () {

    setInterval(function () {

        // get the root element for our report
        var $dataTableRoot = $('.dataTable[data-report="MetaActionReport.getEventAction"]');

        // in the UI, the root element of a report has a JavaScript object associated to it.
        // we can use this object to reload the report.
        var dataTableInstance = $dataTableRoot.data('uiControlObject');

        // we want the table to be completely reset, so we'll reset some
        // query parameters then reload the report
        dataTableInstance.resetAllFilters();
        dataTableInstance.reloadAjaxDataTable();

    }, 10 * 10000000000);

});
