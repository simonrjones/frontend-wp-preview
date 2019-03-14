function addPreviewSidebar(plugin_settings_url, previewurl, current_url) {
    var wp = window.wp;
    document.addEventListener("DOMContentLoaded", () => {

        var registerPlugin = wp.plugins.registerPlugin;
        var PluginSidebar = wp.editPost.PluginSidebar;
        var el = wp.element.createElement;
        var Link = wp.components.ExternalLink;

        var previously_installed = wp.plugins.getPlugin("headless-preview-sidebar");
        if (previously_installed) {
            wp.plugins.unregisterPlugin("headless-preview-sidebar");
        }

        registerPlugin('headless-preview-sidebar', {
            render: function () {
                return el(PluginSidebar, {
                        name: 'headless-preview-sidebar',
                        icon: 'welcome-view-site',
                        title: 'Headless preview'
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