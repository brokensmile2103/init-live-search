(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const i18n = window.InitShortcodeBuilder?.i18n || {};
        const t = (key, fallback) => i18n[key] || fallback;

        const target = document.querySelector('[data-plugin="init-live-search"]');
        if (!target) return;

        const buttons = [
            {
                label: t('init_live_search', 'Init Live Search'),
                shortcode: 'init_live_search',
                attributes: {
                    type: {
                        label: t('type', 'Type'),
                        type: 'select',
                        options: ['icon', 'input'],
                        default: 'icon'
                    },
                    placeholder: {
                        label: t('placeholder', 'Placeholder (input mode)'),
                        type: 'text',
                        default: t('placeholder_default', 'Search...')
                    },
                    label: {
                        label: t('label', 'Label (icon mode)'),
                        type: 'text',
                        default: ''
                    },
                    class: {
                        label: t('custom_class', 'Custom CSS class'),
                        type: 'text',
                        default: ''
                    },
                    id: {
                        label: t('id_attr', 'Element ID'),
                        type: 'text',
                        default: ''
                    },
                    stroke_width: {
                        label: t('stroke_width', 'Stroke Width'),
                        type: 'number',
                        default: 1
                    },
                    radius: {
                        label: t('radius', 'Border Radius (input mode)'),
                        type: 'text',
                        default: '9999px'
                    },
                    // --- New QoL fields ---
                    width: {
                        label: t('width', 'Width'),
                        type: 'text',
                        default: ''
                    },
                    max_width: {
                        label: t('max_width', 'Max Width'),
                        type: 'text',
                        default: ''
                    },
                    align: {
                        label: t('align', 'Align'),
                        type: 'select',
                        options: ['', 'left', 'center', 'right'],
                        default: ''
                    },
                    name: {
                        label: t('input_name', 'Input name (input mode)'),
                        type: 'text',
                        default: ''
                    },
                    aria_label: {
                        label: t('aria_label', 'ARIA Label'),
                        type: 'text',
                        default: ''
                    },
                    button: {
                        label: t('button_visibility', 'Search Button (input mode)'),
                        type: 'select',
                        options: ['show', 'hide'],
                        default: 'show'
                    }
                    // ----------------------
                }
            },
            {
                label: t('related_posts', 'Related Posts'),
                shortcode: 'init_live_search_related_posts',
                attributes: {
                    id: {
                        label: t('post_id', 'Post ID (optional)'),
                        type: 'number',
                        default: ''
                    },
                    count: {
                        label: t('post_count', 'Number of Posts'),
                        type: 'number',
                        default: '5'
                    },
                    keyword: {
                        label: t('keyword_override', 'Keyword (override)'),
                        type: 'text',
                        default: ''
                    },
                    post_type: {
                        label: t('post_type', 'Post Type(s)'),
                        type: 'text',
                        default: ''
                    },
                    template: {
                        label: t('template', 'Template'),
                        type: 'select',
                        options: ['default', 'grid', 'classic', 'compact', 'thumbright'],
                        default: 'default'
                    },
                    css: {
                        label: t('load_css', 'Load CSS'),
                        type: 'select',
                        options: ['1', '0'],
                        default: '1'
                    },
                    schema: {
                        label: t('output_schema', 'Output Schema'),
                        type: 'select',
                        options: ['1', '0'],
                        default: '1'
                    }
                }
            },
            {
                label: t('related_posts_ai', 'AI Related Posts'),
                shortcode: 'init_live_search_related_ai',
                attributes: {
                    id: {
                        label: t('post_id', 'Post ID (optional)'),
                        type: 'number',
                        default: ''
                    },
                    count: {
                        label: t('post_count', 'Number of Posts'),
                        type: 'number',
                        default: '5'
                    },
                    post_type: {
                        label: t('post_type', 'Post Type(s)'),
                        type: 'text',
                        default: 'post' // thân thiện với user
                    },
                    template: {
                        label: t('template', 'Template'),
                        type: 'select',
                        options: ['default', 'grid', 'classic', 'compact', 'thumbright'],
                        default: 'default'
                    },
                    css: {
                        label: t('load_css', 'Load CSS'),
                        type: 'select',
                        options: ['1', '0'],
                        default: '1'
                    },
                    schema: {
                        label: t('output_schema', 'Output Schema'),
                        type: 'select',
                        options: ['1', '0'],
                        default: '1'
                    }
                }
            }
        ];

        const panel = renderShortcodeBuilderPanel({
            title: t('init_live_search', 'Init Live Search'),
            buttons: buttons.map(btn => ({
                label: btn.label,
                dashicon: 'editor-code',
                className: 'button-default',
                onClick: () => {
                    initShortcodeBuilder({
                        shortcode: btn.shortcode,
                        config: {
                            label: btn.label,
                            attributes: btn.attributes
                        }
                    });
                }
            }))
        });

        target.appendChild(panel);
    });
})();
