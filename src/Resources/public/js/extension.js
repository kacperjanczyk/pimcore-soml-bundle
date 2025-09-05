pimcore.registerNS('pimcore.plugin.soml');

pimcore.plugin.soml = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.soml";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function () {
        this.addMainTab();
    },

    addMainTab: function () {
        if (!pimcore.layout || !pimcore.layout.treepanel || !pimcore.layout.treepanel.tabs) {
            Ext.defer(this.addMainTab.bind(this), 300);
            return;
        }

        var tabs = pimcore.layout.treepanel.tabs;

        if (tabs.getComponent("pimcore_panel_soml")) {
            return;
        }

        var settingsIndex = -1;
        tabs.items.each(function (item, idx) {
            if (item && item.id === "pimcore_panel_settings") {
                settingsIndex = idx;
            }
        });

        var insertIndex = settingsIndex > -1 ? settingsIndex : tabs.items.length;

        var rootNode = new Ext.tree.TreeNode({
            id: 'soml_root',
            text: t('Social Media Library'),
            leaf: true,
            iconCls: 'pimcore_icon_object'
        });

        var tree = new Ext.tree.TreePanel({
            id: "pimcore_panel_soml",
            title: t("Social Media Library"),
            iconCls: "pimcore_icon_object",
            autoScroll: true,
            rootVisible: false,
            root: new Ext.tree.AsyncTreeNode({
                id: 'soml_root_container',
                text: 'Root',
                expanded: true,
                children: [rootNode]
            }),
            listeners: {
                click: function (node) {
                    if (node.id === 'soml_root') {
                        this.openCentralPanel();
                    }
                }.bind(this)
            }
        });

        tabs.insert(insertIndex, tree);
        tabs.doLayout();
    },

    openCentralPanel: function () {
        var id = "soml_settings_panel_central";

        var existing = Ext.getCmp(id);
        if (existing) {
            var mainTabPanel = Ext.getCmp("pimcore_panel_tabs");
            mainTabPanel.setActiveItem(existing);
            return existing;
        }

        var panel = new Ext.Panel({
            id: id,
            title: t("Social Media Library"),
            iconCls: "pimcore_icon_object",
            closable: true,
            layout: 'fit',
            bodyStyle: "padding:15px;",
            html: "<h1>Social Media Library</h1><p>Pusta strona – tutaj pojawią się ustawienia bundle.</p>"
        });

        var mainTabPanel = Ext.getCmp("pimcore_panel_tabs");
        mainTabPanel.add(panel);
        mainTabPanel.setActiveItem(panel);

        return panel;
    }
});

new pimcore.plugin.soml();