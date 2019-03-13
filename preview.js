function addPreviewSidebar(previewtoken, previewurl) {
    var wp = window.wp;
    document.addEventListener("DOMContentLoaded", () => {

        var registerPlugin = wp.plugins.registerPlugin;
        var PluginSidebar = wp.editPost.PluginSidebar;
        var el = wp.element.createElement;
        var Link = wp.components.ExternalLink;

        if (wp.plugins.getPlugin("preview-sidebar")){
            wp.plugins.unregisterPlugin("preview-sidebar");
        }

        registerPlugin('preview-sidebar', {
            render: function () {
                return el(PluginSidebar, {
                        name: 'preview-sidebar',
                        icon: 'welcome-view-site',
                        title: 'Preview',
                    },
                    el(Link, {
                        href: previewurl,
                        onclick: function () {
                            console.log("clicked")
                        }
                    }, "Preview link")
                );
            }
        });
    });
}