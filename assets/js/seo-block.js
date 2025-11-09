/**
 * SEO AI Gutenberg Block
 */

(function(blocks, element, components, editor) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { InspectorControls } = editor;
    const { PanelBody, ToggleControl } = components;
    
    registerBlockType('wpaig/seo-ai', {
        title: '🤖 AI SEO Summary',
        icon: 'search',
        category: 'widgets',
        attributes: {
            showFAQ: {
                type: 'boolean',
                default: true
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return [
                // Inspector controls (sidebar)
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, {
                        title: 'Display Settings',
                        initialOpen: true
                    },
                        el(ToggleControl, {
                            label: 'Show FAQ Section',
                            checked: attributes.showFAQ,
                            onChange: (value) => setAttributes({ showFAQ: value })
                        })
                    )
                ),
                
                // Block preview in editor
                el('div', {
                    key: 'preview',
                    className: 'wpaig-seo-block-editor',
                    style: {
                        padding: '20px',
                        background: '#f9f9f9',
                        borderLeft: '4px solid #2271b1',
                        borderRadius: '4px'
                    }
                },
                    el('h3', { style: { marginTop: 0 } }, '🤖 AI SEO Summary'),
                    el('p', { style: { color: '#666', fontSize: '14px' } },
                        'This block will display AI-generated SEO summary and FAQ when published.'
                    ),
                    el('div', { style: { marginTop: '15px', padding: '10px', background: '#fff', borderRadius: '4px' } },
                        el('p', { style: { margin: 0, fontSize: '13px' } },
                            '📝 Generate SEO data using the "AI SEO Optimizer" panel in the sidebar →'
                        )
                    ),
                    attributes.showFAQ && el('div', { style: { marginTop: '10px', padding: '10px', background: '#e7f3ff', borderRadius: '4px' } },
                        el('strong', { style: { fontSize: '12px' } }, '❓ FAQ section will appear here')
                    )
                )
            ];
        },
        
        save: function() {
            // Server-side rendering
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor
);
