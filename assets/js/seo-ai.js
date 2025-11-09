/**
 * SEO AI Metabox React Component
 */

(function() {
    const { render, createElement: e, useState, useEffect } = wp.element;
    const { Button, Spinner, Notice } = wp.components;
    
    const SEOAIMetabox = () => {
        const [loading, setLoading] = useState(false);
        const [error, setError] = useState(null);
        const [success, setSuccess] = useState(null);
        const [seoData, setSeoData] = useState(null);
        
        const rootEl = document.getElementById('wpaig-seo-metabox-root');
        const postId = rootEl ? rootEl.dataset.postId : null;
        const isPremium = rootEl ? rootEl.dataset.isPremium === '1' : false;
        
        const handleOptimize = async () => {
            setLoading(true);
            setError(null);
            setSuccess(null);
            
            try {
                // Get post content from editor
                let content = '';
                let title = '';
                
                // Try Gutenberg editor
                if (wp.data && wp.data.select('core/editor')) {
                    const editor = wp.data.select('core/editor');
                    content = editor.getEditedPostContent();
                    title = editor.getEditedPostAttribute('title');
                } 
                // Try Classic editor
                else if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    content = tinymce.get('content').getContent();
                    title = document.getElementById('title') ? document.getElementById('title').value : '';
                }
                // Fallback to textarea
                else {
                    const contentEl = document.getElementById('content');
                    const titleEl = document.getElementById('title');
                    content = contentEl ? contentEl.value : '';
                    title = titleEl ? titleEl.value : '';
                }
                
                if (!content || content.trim().length < 50) {
                    setError('Please add some content to your post first (minimum 50 characters).');
                    setLoading(false);
                    return;
                }
                
                // Make AJAX request
                const formData = new FormData();
                formData.append('action', 'wpaig_seo_optimize');
                formData.append('nonce', wpaigSEO.nonce);
                formData.append('post_id', postId);
                formData.append('content', content);
                formData.append('title', title);
                
                const response = await fetch(wpaigSEO.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setSeoData(data.data.data);
                    setSuccess(data.data.message || 'SEO optimized successfully!');
                    
                    // Auto-hide success message after 3 seconds
                    setTimeout(() => setSuccess(null), 3000);
                } else {
                    setError(data.data.message || 'Optimization failed. Please try again.');
                }
            } catch (err) {
                setError('Network error. Please check your connection and try again.');
                console.error('SEO optimization error:', err);
            } finally {
                setLoading(false);
            }
        };
        
        return e('div', { className: 'wpaig-seo-metabox' },
            // Header
            e('div', { className: 'wpaig-seo-header' },
                e('p', { style: { margin: '0 0 10px 0', fontSize: '13px', color: '#666' } },
                    'AI-powered SEO optimization for your content'
                )
            ),
            
            // Error notice
            error && e(Notice, {
                status: 'error',
                isDismissible: true,
                onRemove: () => setError(null)
            }, error),
            
            // Success notice
            success && e(Notice, {
                status: 'success',
                isDismissible: true,
                onRemove: () => setSuccess(null)
            }, success),
            
            // Optimize button
            e(Button, {
                isPrimary: true,
                isBusy: loading,
                disabled: loading,
                onClick: handleOptimize,
                className: 'wpaig-seo-optimize-btn',
                style: { width: '100%', justifyContent: 'center', marginBottom: '10px' }
            }, 
                loading ? 'Analyzing...' : '🤖 AI Optimize SEO'
            ),
            
            // Loading spinner
            loading && e('div', { style: { textAlign: 'center', padding: '10px' } },
                e(Spinner),
                e('p', { style: { fontSize: '12px', color: '#666', margin: '5px 0 0 0' } },
                    'Generating SEO recommendations...'
                )
            ),
            
            // SEO Data display
            seoData && e('div', { className: 'wpaig-seo-results', style: { marginTop: '15px' } },
                // Title
                seoData.title && e('div', { className: 'wpaig-seo-field', style: { marginBottom: '10px' } },
                    e('label', { style: { display: 'block', fontWeight: 'bold', fontSize: '12px', marginBottom: '4px' } },
                        '📝 SEO Title:'
                    ),
                    e('div', { 
                        style: { 
                            padding: '8px', 
                            background: '#f0f0f1', 
                            borderRadius: '4px',
                            fontSize: '12px'
                        } 
                    }, seoData.title)
                ),
                
                // Meta Description
                seoData.meta_description && e('div', { className: 'wpaig-seo-field', style: { marginBottom: '10px' } },
                    e('label', { style: { display: 'block', fontWeight: 'bold', fontSize: '12px', marginBottom: '4px' } },
                        '📄 Meta Description:'
                    ),
                    e('div', { 
                        style: { 
                            padding: '8px', 
                            background: '#f0f0f1', 
                            borderRadius: '4px',
                            fontSize: '12px',
                            lineHeight: '1.5'
                        } 
                    }, seoData.meta_description)
                ),
                
                // Keywords
                seoData.keywords && Array.isArray(seoData.keywords) && seoData.keywords.length > 0 && 
                e('div', { className: 'wpaig-seo-field', style: { marginBottom: '10px' } },
                    e('label', { style: { display: 'block', fontWeight: 'bold', fontSize: '12px', marginBottom: '4px' } },
                        '🔑 Keywords:'
                    ),
                    e('div', { style: { display: 'flex', flexWrap: 'wrap', gap: '4px' } },
                        ...seoData.keywords.map(keyword => 
                            e('span', {
                                key: keyword,
                                style: {
                                    padding: '3px 8px',
                                    background: '#2271b1',
                                    color: '#fff',
                                    borderRadius: '3px',
                                    fontSize: '11px'
                                }
                            }, keyword)
                        )
                    )
                ),
                
                // Premium: Summary
                isPremium && seoData.summary && e('div', { className: 'wpaig-seo-field', style: { marginBottom: '10px' } },
                    e('label', { style: { display: 'block', fontWeight: 'bold', fontSize: '12px', marginBottom: '4px' } },
                        '📋 SEO Summary:'
                    ),
                    e('div', { 
                        style: { 
                            padding: '8px', 
                            background: '#f0f0f1', 
                            borderRadius: '4px',
                            fontSize: '11px',
                            lineHeight: '1.6',
                            maxHeight: '150px',
                            overflowY: 'auto'
                        } 
                    }, seoData.summary)
                ),
                
                // Premium: FAQs
                isPremium && seoData.faqs && Array.isArray(seoData.faqs) && seoData.faqs.length > 0 &&
                e('div', { className: 'wpaig-seo-field' },
                    e('label', { style: { display: 'block', fontWeight: 'bold', fontSize: '12px', marginBottom: '8px' } },
                        '❓ FAQ:'
                    ),
                    ...seoData.faqs.map((faq, idx) => 
                        e('div', {
                            key: idx,
                            style: {
                                padding: '8px',
                                background: '#f0f0f1',
                                borderRadius: '4px',
                                marginBottom: '6px',
                                fontSize: '11px'
                            }
                        },
                            e('strong', { style: { display: 'block', marginBottom: '4px' } }, faq.question),
                            e('span', { style: { color: '#666' } }, faq.answer)
                        )
                    )
                ),
                
                // Copy buttons
                e('div', { style: { marginTop: '10px', display: 'flex', gap: '5px' } },
                    e(Button, {
                        isSecondary: true,
                        isSmall: true,
                        onClick: () => {
                            const text = `Title: ${seoData.title}\n\nMeta: ${seoData.meta_description}\n\nKeywords: ${seoData.keywords.join(', ')}`;
                            navigator.clipboard.writeText(text);
                            setSuccess('Copied to clipboard!');
                            setTimeout(() => setSuccess(null), 2000);
                        }
                    }, '📋 Copy SEO Data')
                )
            ),
            
            // Premium upsell for free users
            !isPremium && e('div', {
                style: {
                    marginTop: '15px',
                    padding: '10px',
                    background: '#fff3cd',
                    borderLeft: '3px solid #ffc107',
                    borderRadius: '4px',
                    fontSize: '11px'
                }
            },
                e('strong', null, '⭐ Premium Features:'),
                e('ul', { style: { margin: '5px 0 0 0', paddingLeft: '20px' } },
                    e('li', null, '300-word SEO summaries'),
                    e('li', null, 'FAQ generation'),
                    e('li', null, 'Advanced keyword analysis'),
                    e('li', null, 'Schema markup suggestions')
                )
            )
        );
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        const rootEl = document.getElementById('wpaig-seo-metabox-root');
        if (rootEl) {
            render(e(SEOAIMetabox), rootEl);
        }
    });
})();
