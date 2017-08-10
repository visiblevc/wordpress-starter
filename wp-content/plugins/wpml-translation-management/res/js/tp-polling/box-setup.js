jQuery(document).ready(function () {
    var boxPopulation = new WpmlTpPollingPickupPopulateAction(jQuery, TranslationProxyPolling);
    boxPopulation.run();
});