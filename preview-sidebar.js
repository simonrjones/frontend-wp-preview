function addPreviewSidebar(plugin_settings_url, previewurl, current_url) {
    var wp = window.wp;
    document.addEventListener("DOMContentLoaded", () => {

        var registerPlugin = wp.plugins.registerPlugin;
        var PluginSidebar = wp.editPost.PluginSidebar;
        var el = wp.element.createElement;
        var Link = wp.components.ExternalLink;

        if (wp.plugins.getPlugin("preview-sidebar")) {
            wp.plugins.unregisterPlugin("preview-sidebar");
        }

        registerPlugin('preview-sidebar', {
            render: function () {
                return el(PluginSidebar, {
                        name: 'preview-sidebar',
                        icon: 'welcome-view-site',
                        title: 'Headless preview',
                    },
                    el("div",
                        {className: 'preview-plugin-sidebar-link-content'},
                        el('p',
                            {className: "preview-sidebar-warning"},
                            "Please make sure you have updated/saved your post/draft before clicking the link."
                        ),
                        el(Link, {
                            id: "preview-sidebar-link",
                            href: previewurl
                        }, "Preview link")
                    ),
                    el("div",
                        {className: 'preview-plugin-sidebar-info-content'},
                        el('p',
                            {className: "preview-sidebar-header"},
                            "Settings"
                        ),
                        el(Link, {
                            href: plugin_settings_url,
                            onClick: function (e) {
                                alert("Refresh the page if you edited the settings!");
                            }
                        }, "Plugin settings"),
                        el('p',
                            {className: ""},
                            "Current frontend url: ",
                            el(Link, {
                                href: current_url
                            }, current_url)
                        )
                    )
                );
            }
        });
    });
}