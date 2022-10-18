pimcore.registerNS("pimcore.plugin.LemonmindObjectUpdateLoggerBundle");

pimcore.plugin.LemonmindObjectUpdateLoggerBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.LemonmindObjectUpdateLoggerBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("LemonmindObjectUpdateLoggerBundle ready!");
    }
});

var LemonmindObjectUpdateLoggerBundlePlugin = new pimcore.plugin.LemonmindObjectUpdateLoggerBundle();
