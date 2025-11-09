/**
 * WP AI Guardian Dashboard - Pure React (No Build Required)
 * 
 * @package WP_AI_Guardian
 * @since 1.0
 */

(function() {
    'use strict';
    
    const { useState } = React;
    const e = React.createElement;
    
    /**
     * Toast Notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `wpaig-toast wpaig-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('wpaig-toast-show'), 10);
        setTimeout(() => {
            toast.classList.remove('wpaig-toast-show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Modal Component
     */
    function Modal({ isOpen, onClose, children }) {
        if (!isOpen) return null;
        
        return e('div', { className: 'wpaig-modal-overlay', onClick: onClose },
            e('div', { 
                className: 'wpaig-modal-content', 
                onClick: (e) => e.stopPropagation() 
            }, children)
        );
    }
    
    /**
     * Scan Tab Component
     */
    function ScanTab({ isPremium }) {
        const [scanning, setScanning] = useState(false);
        const [progress, setProgress] = useState(0);
        const [results, setResults] = useState([]);
        const [modalOpen, setModalOpen] = useState(false);
        
        const runScan = async () => {
            setScanning(true);
            setProgress(0);
            setResults([]);
            
            // Simulate progress
            const progressInterval = setInterval(() => {
                setProgress(prev => {
                    if (prev >= 90) {
                        clearInterval(progressInterval);
                        return 90;
                    }
                    return prev + 10;
                });
            }, 300);
            
            try {
                const response = await fetch(wpaigData.restUrl + 'wpaig/v1/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpaigData.restNonce
                    }
                });
                
                const data = await response.json();
                clearInterval(progressInterval);
                setProgress(100);
                
                if (data.success) {
                    setResults(data.results || []);
                    showToast(`Scan completed - Found ${data.results.length} issues`, 'success');
                }
            } catch (error) {
                clearInterval(progressInterval);
                showToast('Scan failed - Unable to complete scan', 'error');
            } finally {
                setScanning(false);
            }
        };
        
        const handleAction = (issue) => {
            if (!isPremium) {
                setModalOpen(true);
            } else {
                showToast(`Auto-fix started: ${issue.issue}`, 'info');
            }
        };
        
        return e('div', { className: 'wpaig-tab-content' },
            // Scan Button
            e('div', { className: 'wpaig-scan-actions' },
                e('button', {
                    className: `wpaig-btn wpaig-btn-primary ${scanning ? 'wpaig-btn-loading' : ''}`,
                    onClick: runScan,
                    disabled: scanning
                }, scanning ? 'Scanning...' : 'Run Quick Scan')
            ),
            
            // Progress Bar
            scanning && e('div', { className: 'wpaig-progress-container' },
                e('div', { className: 'wpaig-progress-bar' },
                    e('div', { 
                        className: 'wpaig-progress-fill',
                        style: { width: `${progress}%` }
                    })
                ),
                e('div', { className: 'wpaig-progress-text' }, `${progress}%`)
            ),
            
            // Results Table
            results.length > 0 && e('div', { className: 'wpaig-results-container' },
                e('table', { className: 'wpaig-results-table wp-list-table widefat fixed striped' },
                    e('thead', null,
                        e('tr', null,
                            e('th', null, 'Issue'),
                            e('th', null, 'Severity'),
                            e('th', null, 'Action')
                        )
                    ),
                    e('tbody', null,
                        results.map((result, index) =>
                            e('tr', { key: index },
                                e('td', null, result.issue),
                                e('td', null,
                                    e('span', {
                                        className: `wpaig-badge wpaig-badge-${result.severity}`
                                    }, result.severity)
                                ),
                                e('td', null,
                                    e('button', {
                                        className: `wpaig-btn wpaig-btn-sm ${isPremium ? 'wpaig-btn-success' : 'wpaig-btn-secondary'}`,
                                        onClick: () => handleAction(result)
                                    }, isPremium ? 'Auto-Fix' : 'Fix (Premium)')
                                )
                            )
                        )
                    )
                )
            ),
            
            // Freemium Modal
            e(Modal, { isOpen: modalOpen, onClose: () => setModalOpen(false) },
                e('div', { className: 'wpaig-modal-header' },
                    e('h2', null, '🚀 Unlock Auto-Fix'),
                    e('button', { 
                        className: 'wpaig-modal-close',
                        onClick: () => setModalOpen(false)
                    }, '×')
                ),
                e('div', { className: 'wpaig-modal-body' },
                    e('h3', null, 'Premium Features'),
                    e('ul', { className: 'wpaig-feature-list' },
                        e('li', null, '✓ Automatic issue resolution'),
                        e('li', null, '✓ Performance optimization'),
                        e('li', null, '✓ SEO enhancements'),
                        e('li', null, '✓ Conflict resolution'),
                        e('li', null, '✓ Priority support')
                    ),
                    e('div', { className: 'wpaig-pricing' }, '₹999/month')
                ),
                e('div', { className: 'wpaig-modal-footer' },
                    e('button', { 
                        className: 'wpaig-btn wpaig-btn-secondary',
                        onClick: () => setModalOpen(false)
                    }, 'Cancel'),
                    e('button', { 
                        className: 'wpaig-btn wpaig-btn-primary'
                    }, 'Upgrade Now')
                )
            )
        );
    }
    
    /**
     * Performance Tab
     */
    function PerformanceTab({ isPremium }) {
        const [optimizing, setOptimizing] = useState(false);
        const [progress, setProgress] = useState(0);
        const [report, setReport] = useState(null);
        
        const runOptimization = async () => {
            setOptimizing(true);
            setProgress(0);
            setReport(null);
            
            // Simulate progress
            const progressInterval = setInterval(() => {
                setProgress(prev => {
                    if (prev >= 90) {
                        clearInterval(progressInterval);
                        return 90;
                    }
                    return prev + 15;
                });
            }, 500);
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpaig_optimize_performance',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                clearInterval(progressInterval);
                setProgress(100);
                
                if (data.success) {
                    setReport(data.data);
                    const score = data.data.score.current;
                    showToast(
                        `Optimization complete - Score: ${score}/100`,
                        score >= 75 ? 'success' : 'warning'
                    );
                } else {
                    showToast('Optimization failed', 'error');
                }
            } catch (error) {
                clearInterval(progressInterval);
                showToast('Unable to optimize performance', 'error');
            } finally {
                setOptimizing(false);
            }
        };
        
        return e('div', { className: 'wpaig-tab-content' },
            // Optimize Button
            e('div', { className: 'wpaig-scan-actions' },
                e('button', {
                    className: `wpaig-btn wpaig-btn-primary ${optimizing ? 'wpaig-btn-loading' : ''}`,
                    onClick: runOptimization,
                    disabled: optimizing
                }, optimizing ? 'Optimizing...' : '⚡ Optimize Performance')
            ),
            
            // Progress Bar
            optimizing && e('div', { className: 'wpaig-progress-container' },
                e('div', { className: 'wpaig-progress-bar' },
                    e('div', { 
                        className: 'wpaig-progress-fill',
                        style: { width: `${progress}%` }
                    })
                ),
                e('div', { className: 'wpaig-progress-text' }, `${progress}%`)
            ),
            
            // Results
            report && e('div', { className: 'wpaig-performance-report' },
                // Score Card
                e('div', { className: 'wpaig-score-card' },
                    e('div', { className: 'wpaig-score-value' }, report.score.current),
                    e('div', { className: 'wpaig-score-label' }, 'Performance Score'),
                    e('div', { 
                        className: `wpaig-score-rating wpaig-rating-${report.score.current >= 75 ? 'good' : 'fair'}`
                    }, report.score.rating)
                ),
                
                // Metrics Grid
                e('div', { className: 'wpaig-metrics-grid' },
                    // Query Time
                    e('div', { className: 'wpaig-metric-card' },
                        e('h4', null, 'Query Time'),
                        e('div', { className: 'wpaig-metric-value' }, 
                            parseFloat(report.optimized.query_time || 0).toFixed(2) + 's'),
                        report.improvements.query_time > 0 && e('div', { 
                            className: 'wpaig-metric-improvement',
                            style: { color: '#00a32a' }
                        }, `↓ ${parseFloat(report.improvements.query_time || 0).toFixed(1)}% faster`)
                    ),
                    
                    // Database Queries
                    e('div', { className: 'wpaig-metric-card' },
                        e('h4', null, 'DB Queries'),
                        e('div', { className: 'wpaig-metric-value' }, 
                            report.optimized.db_queries),
                        report.improvements.db_queries && e('div', { 
                            className: 'wpaig-metric-improvement',
                            style: { color: '#00a32a' }
                        }, `↓ ${Math.abs(report.improvements.db_queries).toFixed(0)}% reduced`)
                    ),
                    
                    // Images
                    e('div', { className: 'wpaig-metric-card' },
                        e('h4', null, 'Images Optimized'),
                        e('div', { className: 'wpaig-metric-value' }, 
                            report.optimizations.images_optimized || 0),
                        e('div', { className: 'wpaig-metric-label' }, 
                            'of ' + report.baseline.total_images)
                    ),
                    
                    // Memory
                    e('div', { className: 'wpaig-metric-card' },
                        e('h4', null, 'Memory Usage'),
                        e('div', { className: 'wpaig-metric-value' }, 
                            report.optimized.memory_usage)
                    )
                ),
                
                // Optimizations Applied
                e('div', { className: 'wpaig-optimizations-list' },
                    e('h3', null, 'Optimizations Applied'),
                    e('ul', null,
                        e('li', null, `✓ Lazy loading enabled for images`),
                        e('li', null, `✓ ${report.optimizations.images_optimized} images compressed`),
                        isPremium && report.optimizations.queries_cached && 
                            e('li', null, `✓ ${report.optimizations.queries_cached} queries cached`),
                        isPremium && report.optimizations.assets_minified && 
                            e('li', null, `✓ CSS/JS assets minified`)
                    )
                ),
                
                // AI Recommendations (Premium)
                isPremium && report.optimizations.ai_recommendations && e('div', { 
                    className: 'wpaig-ai-recommendations' 
                },
                    e('h3', null, '🤖 AI Recommendations'),
                    e('div', { className: 'wpaig-ai-content' },
                        (() => {
                            const ai = report.optimizations.ai_recommendations;
                            if (typeof ai === 'string') {
                                return ai;
                            } else if (ai.suggestions && Array.isArray(ai.suggestions)) {
                                // Fallback format - display nicely
                                return e('div', null,
                                    ai.note && e('p', { style: { fontStyle: 'italic', marginBottom: '10px' } }, ai.note),
                                    e('ul', { style: { paddingLeft: '20px' } },
                                        ai.suggestions.map((s, i) => e('li', { key: i, style: { marginBottom: '5px' } }, s))
                                    )
                                );
                            } else {
                                return JSON.stringify(ai, null, 2);
                            }
                        })()
                    )
                )
            )
        );
    }
    
    /**
     * SEO Tab
     */
    function SEOTab({ isPremium }) {
        const [analyzing, setAnalyzing] = useState(false);
        const [seoData, setSeoData] = useState(null);
        const [posts, setPosts] = useState([]);
        
        const analyzeSEO = async () => {
            setAnalyzing(true);
            setSeoData(null);
            setPosts([]);
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpaig_analyze_seo',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setSeoData(data.data.analysis);
                    setPosts(data.data.posts || []);
                    showToast('SEO analysis complete!', 'success');
                } else {
                    showToast('SEO analysis failed', 'error');
                }
            } catch (error) {
                showToast('Unable to analyze SEO', 'error');
            } finally {
                setAnalyzing(false);
            }
        };
        
        return e('div', { className: 'wpaig-tab-content' },
            // Header
            e('div', { className: 'wpaig-card' },
                e('h3', null, '🔍 SEO Analysis'),
                e('p', { style: { marginBottom: '15px', color: '#666' } },
                    'Analyze your site\'s SEO health and get AI-powered recommendations'
                ),
                e('button', {
                    className: 'wpaig-btn wpaig-btn-primary',
                    onClick: analyzeSEO,
                    disabled: analyzing
                }, analyzing ? '⏳ Analyzing...' : '🚀 Analyze SEO')
            ),
            
            // Results
            seoData && e('div', { style: { marginTop: '20px' } },
                // SEO Score
                e('div', { className: 'wpaig-card' },
                    e('div', { style: { display: 'flex', alignItems: 'center', gap: '20px' } },
                        e('div', {
                            style: {
                                width: '80px',
                                height: '80px',
                                borderRadius: '50%',
                                background: seoData.score >= 80 ? '#10b981' : seoData.score >= 60 ? '#f59e0b' : '#ef4444',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '24px',
                                fontWeight: 'bold',
                                color: '#fff'
                            }
                        }, seoData.score),
                        e('div', null,
                            e('h3', { style: { margin: '0 0 5px 0' } }, 'SEO Score'),
                            e('p', { style: { margin: 0, color: '#666' } },
                                seoData.score >= 80 ? '✅ Excellent' :
                                seoData.score >= 60 ? '⚠️ Good, needs improvement' :
                                '❌ Needs significant work'
                            )
                        )
                    )
                ),
                
                // Issues
                seoData.issues && seoData.issues.length > 0 && e('div', { className: 'wpaig-card', style: { marginTop: '20px' } },
                    e('h3', null, '⚠️ SEO Issues Found'),
                    e('div', { className: 'wpaig-issues-list' },
                        ...seoData.issues.map((issue, idx) =>
                            e('div', {
                                key: idx,
                                className: 'wpaig-issue-item',
                                style: {
                                    padding: '12px',
                                    background: issue.severity === 'high' ? '#fef2f2' : issue.severity === 'medium' ? '#fffbeb' : '#f0f9ff',
                                    borderLeft: `4px solid ${issue.severity === 'high' ? '#ef4444' : issue.severity === 'medium' ? '#f59e0b' : '#3b82f6'}`,
                                    marginBottom: '10px',
                                    borderRadius: '4px'
                                }
                            },
                                e('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' } },
                                    e('div', null,
                                        e('strong', null,
                                            issue.severity === 'high' ? '🔴 ' :
                                            issue.severity === 'medium' ? '🟡 ' : '🔵 ',
                                            issue.title
                                        ),
                                        e('p', { style: { margin: '5px 0 0 0', fontSize: '13px', color: '#666' } },
                                            issue.description
                                        )
                                    ),
                                    issue.fixable && isPremium && e('button', {
                                        className: 'wpaig-btn wpaig-btn-small',
                                        onClick: () => showToast('Auto-fix feature coming soon!', 'info')
                                    }, '🔧 Fix')
                                )
                            )
                        )
                    )
                ),
                
                // Posts without SEO
                posts && posts.length > 0 && e('div', { className: 'wpaig-card', style: { marginTop: '20px' } },
                    e('h3', null, '📝 Posts Without SEO Optimization'),
                    e('p', { style: { color: '#666', marginBottom: '15px' } },
                        `${posts.length} posts need SEO optimization. Edit each post and use the AI SEO Optimizer metabox.`
                    ),
                    e('div', { style: { maxHeight: '300px', overflowY: 'auto' } },
                        ...posts.map(post =>
                            e('div', {
                                key: post.id,
                                style: {
                                    padding: '10px',
                                    background: '#f9fafb',
                                    marginBottom: '8px',
                                    borderRadius: '4px',
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center'
                                }
                            },
                                e('div', null,
                                    e('strong', null, post.title),
                                    e('div', { style: { fontSize: '12px', color: '#666', marginTop: '4px' } },
                                        `Published: ${post.date}`
                                    )
                                ),
                                e('a', {
                                    href: post.edit_url,
                                    className: 'wpaig-btn wpaig-btn-small',
                                    target: '_blank'
                                }, '✏️ Optimize')
                            )
                        )
                    )
                ),
                
                // AI Recommendations
                seoData.recommendations && e('div', { className: 'wpaig-card', style: { marginTop: '20px' } },
                    e('h3', null, '🤖 AI Recommendations'),
                    e('div', { style: { lineHeight: '1.8' } },
                        typeof seoData.recommendations === 'string' ?
                            e('p', null, seoData.recommendations) :
                            Array.isArray(seoData.recommendations) ?
                                e('ul', { style: { paddingLeft: '20px' } },
                                    ...seoData.recommendations.map((rec, idx) =>
                                        e('li', { key: idx, style: { marginBottom: '8px' } }, rec)
                                    )
                                ) :
                                e('p', null, 'No recommendations available')
                    )
                )
            ),
            
            // Empty state
            !analyzing && !seoData && e('div', { className: 'wpaig-card', style: { marginTop: '20px', textAlign: 'center', padding: '40px' } },
                e('div', { style: { fontSize: '48px', marginBottom: '10px' } }, '🔍'),
                e('h3', null, 'Analyze Your SEO'),
                e('p', { style: { color: '#666' } },
                    'Click the button above to start analyzing your site\'s SEO health'
                )
            )
        );
    }
    
    /**
     * Conflicts Tab
     */
    function ConflictsTab({ isPremium }) {
        const [scanning, setScanning] = useState(false);
        const [conflicts, setConflicts] = useState([]);
        const [stats, setStats] = useState(null);
        const [modalOpen, setModalOpen] = useState(false);
        const [selectedPlugin, setSelectedPlugin] = useState(null);
        
        const scanConflicts = async () => {
            setScanning(true);
            setConflicts([]);
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpaig_scan_conflicts',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setConflicts(data.data.conflicts || []);
                    setStats({
                        tested: data.data.tested_count,
                        total: data.data.total_plugins,
                        isPremium: data.data.is_premium
                    });
                    
                    showToast(
                        `Scan complete - Found ${data.data.conflicts.length} conflicts`,
                        data.data.conflicts.length > 0 ? 'warning' : 'success'
                    );
                } else {
                    showToast('Conflict scan failed', 'error');
                }
            } catch (error) {
                showToast('Unable to scan conflicts', 'error');
            } finally {
                setScanning(false);
            }
        };
        
        const handleDeactivate = (plugin) => {
            setSelectedPlugin(plugin);
            setModalOpen(true);
        };
        
        const confirmDeactivate = async () => {
            if (!selectedPlugin) return;
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpaig_deactivate_plugin',
                        nonce: wpaigData.nonce,
                        plugin: selectedPlugin.file
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Plugin deactivated successfully', 'success');
                    setModalOpen(false);
                    // Remove from conflicts list
                    setConflicts(conflicts.filter(c => c.file !== selectedPlugin.file));
                } else {
                    showToast(data.data.message || 'Failed to deactivate plugin', 'error');
                }
            } catch (error) {
                showToast('Unable to deactivate plugin', 'error');
            }
        };
        
        return e('div', { className: 'wpaig-tab-content' },
            // Scan Button
            e('div', { className: 'wpaig-scan-actions' },
                e('button', {
                    className: `wpaig-btn wpaig-btn-primary ${scanning ? 'wpaig-btn-loading' : ''}`,
                    onClick: scanConflicts,
                    disabled: scanning
                }, scanning ? 'Scanning Plugins...' : '🔍 Scan for Conflicts'),
                
                stats && e('span', { 
                    style: { marginLeft: '15px', color: '#646970' }
                }, `Tested ${stats.tested} of ${stats.total} plugins`)
            ),
            
            // Conflicts Table
            conflicts.length > 0 && e('div', { className: 'wpaig-results-container' },
                e('table', { className: 'wpaig-results-table wp-list-table widefat fixed striped' },
                    e('thead', null,
                        e('tr', null,
                            e('th', null, 'Plugin'),
                            e('th', null, 'Issue'),
                            e('th', null, 'Severity'),
                            e('th', null, 'Action')
                        )
                    ),
                    e('tbody', null,
                        conflicts.map((conflict, index) =>
                            e('tr', { key: index },
                                e('td', null, 
                                    e('strong', null, conflict.plugin),
                                    conflict.ai_fix && e('div', { 
                                        style: { fontSize: '12px', color: '#646970', marginTop: '5px' }
                                    }, '🤖 AI: ' + (typeof conflict.ai_fix === 'string' 
                                        ? conflict.ai_fix.substring(0, 100) 
                                        : JSON.stringify(conflict.ai_fix).substring(0, 100)) + '...')
                                ),
                                e('td', null, conflict.issue),
                                e('td', null,
                                    e('span', {
                                        className: `wpaig-badge wpaig-badge-${conflict.severity}`
                                    }, conflict.severity)
                                ),
                                e('td', null,
                                    isPremium 
                                        ? e('button', {
                                            className: 'wpaig-btn wpaig-btn-sm wpaig-btn-secondary',
                                            onClick: () => handleDeactivate(conflict)
                                        }, 'Deactivate')
                                        : e('span', { style: { color: '#646970', fontSize: '12px' } }, 
                                            '(Premium only)')
                                )
                            )
                        )
                    )
                )
            ),
            
            // No conflicts message
            !scanning && conflicts.length === 0 && stats && e('div', { 
                className: 'wpaig-alert wpaig-alert-info' 
            },
                e('span', { className: 'wpaig-alert-icon' }, '✓'),
                e('span', null, `No conflicts detected in ${stats.tested} plugins`)
            ),
            
            // Deactivation Confirmation Modal
            e(Modal, { isOpen: modalOpen, onClose: () => setModalOpen(false) },
                e('div', { className: 'wpaig-modal-header' },
                    e('h2', null, '⚠️ Deactivate Plugin'),
                    e('button', { 
                        className: 'wpaig-modal-close',
                        onClick: () => setModalOpen(false)
                    }, '×')
                ),
                e('div', { className: 'wpaig-modal-body' },
                    e('p', null, `Are you sure you want to deactivate "${selectedPlugin?.plugin}"?`),
                    e('p', { style: { color: '#d63638', fontSize: '13px' } }, 
                        '⚠️ Warning: This will immediately deactivate the plugin. Make sure you have a backup.')
                ),
                e('div', { className: 'wpaig-modal-footer' },
                    e('button', { 
                        className: 'wpaig-btn wpaig-btn-secondary',
                        onClick: () => setModalOpen(false)
                    }, 'Cancel'),
                    e('button', { 
                        className: 'wpaig-btn wpaig-btn-primary',
                        onClick: confirmDeactivate,
                        style: { background: '#d63638', borderColor: '#d63638' }
                    }, 'Deactivate Plugin')
                )
            )
        );
    }
    
    /**
     * Automator Tab - Smart Workflows
     */
    function AutomatorTab({ isPremium }) {
        const [loading, setLoading] = useState(true);
        const [workflows, setWorkflows] = useState([]);
        const [triggers, setTriggers] = useState({});
        const [actions, setActions] = useState({});
        const [freeLimit, setFreeLimit] = useState(2);
        const [showForm, setShowForm] = useState(false);
        const [editingWorkflow, setEditingWorkflow] = useState(null);
        
        // Form state
        const [formName, setFormName] = useState('');
        const [formTrigger, setFormTrigger] = useState('');
        const [formAction, setFormAction] = useState('');
        const [formActive, setFormActive] = useState(true);
        
        // Load workflows
        const loadWorkflows = async () => {
            setLoading(true);
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_get_workflows',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    setWorkflows(data.data.workflows || []);
                    setTriggers(data.data.triggers || {});
                    setActions(data.data.actions || {});
                    setFreeLimit(data.data.free_limit || 2);
                }
            } catch (error) {
                showToast('Failed to load workflows', 'error');
            } finally {
                setLoading(false);
            }
        };
        
        React.useEffect(() => {
            loadWorkflows();
        }, []);
        
        // Save workflow
        const saveWorkflow = async () => {
            if (!formName || !formTrigger || !formAction) {
                showToast('Please fill all fields', 'error');
                return;
            }
            
            const workflow = {
                id: editingWorkflow ? editingWorkflow.id : undefined,
                name: formName,
                trigger: formTrigger,
                action: formAction,
                active: formActive,
                executions: editingWorkflow ? editingWorkflow.executions : 0
            };
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_save_workflow',
                        nonce: wpaigData.nonce,
                        workflow: JSON.stringify(workflow)
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    setWorkflows(data.data.workflows || []);
                    resetForm();
                    showToast(data.data.message, 'success');
                } else {
                    showToast(data.data.message || 'Failed to save workflow', 'error');
                }
            } catch (error) {
                showToast('Failed to save workflow', 'error');
            }
        };
        
        // Delete workflow
        const deleteWorkflow = async (workflowId) => {
            if (!confirm('Are you sure you want to delete this workflow?')) return;
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_delete_workflow',
                        nonce: wpaigData.nonce,
                        workflow_id: workflowId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    setWorkflows(data.data.workflows || []);
                    showToast('Workflow deleted', 'success');
                }
            } catch (error) {
                showToast('Failed to delete workflow', 'error');
            }
        };
        
        // Test workflow
        const testWorkflow = async (workflowId) => {
            showToast('Testing workflow...', 'info');
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_test_workflow',
                        nonce: wpaigData.nonce,
                        workflow_id: workflowId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showToast(data.data.message, 'success');
                } else {
                    showToast(data.data.message || 'Test failed', 'error');
                }
            } catch (error) {
                showToast('Failed to test workflow', 'error');
            }
        };
        
        // Edit workflow
        const editWorkflow = (workflow) => {
            setEditingWorkflow(workflow);
            setFormName(workflow.name);
            setFormTrigger(workflow.trigger);
            setFormAction(workflow.action);
            setFormActive(workflow.active);
            setShowForm(true);
        };
        
        // Reset form
        const resetForm = () => {
            setFormName('');
            setFormTrigger('');
            setFormAction('');
            setFormActive(true);
            setEditingWorkflow(null);
            setShowForm(false);
        };
        
        const activeWorkflows = workflows.filter(w => w.active).length;
        const canAddMore = isPremium || activeWorkflows < freeLimit;
        
        return e('div', { className: 'wpaig-tab-content' },
            // Header
            e('div', { className: 'wpaig-card' },
                e('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                    e('div', null,
                        e('h3', { style: { margin: 0 } }, '🔄 Smart Workflows'),
                        e('p', { style: { margin: '5px 0 0 0', color: '#666' } },
                            `Automate tasks with AI-powered workflows. ${!isPremium ? `Free: ${activeWorkflows}/${freeLimit} active` : 'Premium: Unlimited'}`
                        )
                    ),
                    e('button', {
                        className: 'wpaig-btn wpaig-btn-primary',
                        onClick: () => setShowForm(!showForm),
                        disabled: !canAddMore && !showForm
                    }, showForm ? '✖ Cancel' : '➕ New Workflow')
                )
            ),
            
            // Workflow form
            showForm && e('div', { className: 'wpaig-card', style: { marginTop: '20px' } },
                e('h4', null, editingWorkflow ? 'Edit Workflow' : 'Create Workflow'),
                
                // Name
                e('div', { style: { marginBottom: '15px' } },
                    e('label', { style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' } }, 'Workflow Name'),
                    e('input', {
                        type: 'text',
                        className: 'wpaig-input',
                        value: formName,
                        onChange: (ev) => setFormName(ev.target.value),
                        placeholder: 'e.g., Auto-optimize new posts'
                    })
                ),
                
                // Trigger
                e('div', { style: { marginBottom: '15px' } },
                    e('label', { style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' } }, 'Trigger (When)'),
                    e('select', {
                        className: 'wpaig-input',
                        value: formTrigger,
                        onChange: (ev) => setFormTrigger(ev.target.value)
                    },
                        e('option', { value: '' }, '-- Select Trigger --'),
                        ...Object.entries(triggers).map(([key, trigger]) =>
                            e('option', { key, value: key }, `${trigger.icon} ${trigger.label}`)
                        )
                    ),
                    formTrigger && triggers[formTrigger] && e('p', { style: { margin: '5px 0 0 0', fontSize: '12px', color: '#666' } },
                        triggers[formTrigger].description
                    )
                ),
                
                // Action
                e('div', { style: { marginBottom: '15px' } },
                    e('label', { style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' } }, 'Action (Do)'),
                    e('select', {
                        className: 'wpaig-input',
                        value: formAction,
                        onChange: (ev) => setFormAction(ev.target.value)
                    },
                        e('option', { value: '' }, '-- Select Action --'),
                        ...Object.entries(actions).map(([key, action]) =>
                            e('option', { 
                                key, 
                                value: key,
                                disabled: action.premium && !isPremium
                            }, `${action.icon} ${action.label} ${action.premium && !isPremium ? '(Premium)' : ''}`)
                        )
                    ),
                    formAction && actions[formAction] && e('p', { style: { margin: '5px 0 0 0', fontSize: '12px', color: '#666' } },
                        actions[formAction].description
                    )
                ),
                
                // Active toggle
                e('div', { style: { marginBottom: '15px' } },
                    e('label', { style: { display: 'flex', alignItems: 'center', cursor: 'pointer' } },
                        e('input', {
                            type: 'checkbox',
                            checked: formActive,
                            onChange: (ev) => setFormActive(ev.target.checked),
                            style: { marginRight: '8px' }
                        }),
                        e('span', null, 'Active (workflow will run automatically)')
                    )
                ),
                
                // Buttons
                e('div', { style: { display: 'flex', gap: '10px' } },
                    e('button', {
                        className: 'wpaig-btn wpaig-btn-primary',
                        onClick: saveWorkflow
                    }, editingWorkflow ? '💾 Update' : '✓ Create'),
                    e('button', {
                        className: 'wpaig-btn',
                        onClick: resetForm
                    }, 'Cancel')
                )
            ),
            
            // Workflows list
            loading ? e('div', { style: { textAlign: 'center', padding: '40px' } },
                e('p', null, 'Loading workflows...')
            ) : workflows.length === 0 ? e('div', { className: 'wpaig-card', style: { marginTop: '20px', textAlign: 'center', padding: '40px' } },
                e('div', { style: { fontSize: '48px', marginBottom: '10px' } }, '🔄'),
                e('h3', null, 'No Workflows Yet'),
                e('p', { style: { color: '#666' } }, 'Create your first workflow to automate tasks')
            ) : e('div', { style: { marginTop: '20px' } },
                ...workflows.map(workflow =>
                    e('div', {
                        key: workflow.id,
                        className: 'wpaig-card',
                        style: { marginBottom: '15px', opacity: workflow.active ? 1 : 0.6 }
                    },
                        e('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' } },
                            e('div', { style: { flex: 1 } },
                                e('div', { style: { display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' } },
                                    e('h4', { style: { margin: 0 } }, workflow.name),
                                    e('span', {
                                        style: {
                                            padding: '2px 8px',
                                            borderRadius: '3px',
                                            fontSize: '11px',
                                            background: workflow.active ? '#10b981' : '#9ca3af',
                                            color: '#fff'
                                        }
                                    }, workflow.active ? 'Active' : 'Inactive')
                                ),
                                e('div', { style: { fontSize: '14px', color: '#666', marginBottom: '8px' } },
                                    e('span', null,
                                        triggers[workflow.trigger] ? `${triggers[workflow.trigger].icon} ${triggers[workflow.trigger].label}` : workflow.trigger,
                                        ' → ',
                                        actions[workflow.action] ? `${actions[workflow.action].icon} ${actions[workflow.action].label}` : workflow.action
                                    )
                                ),
                                workflow.executions > 0 && e('div', { style: { fontSize: '12px', color: '#9ca3af' } },
                                    `Executed ${workflow.executions} time${workflow.executions !== 1 ? 's' : ''}`,
                                    workflow.last_run && ` • Last run: ${new Date(workflow.last_run).toLocaleString()}`
                                )
                            ),
                            e('div', { style: { display: 'flex', gap: '8px' } },
                                e('button', {
                                    className: 'wpaig-btn wpaig-btn-small',
                                    onClick: () => testWorkflow(workflow.id),
                                    title: 'Test workflow'
                                }, '▶️'),
                                e('button', {
                                    className: 'wpaig-btn wpaig-btn-small',
                                    onClick: () => editWorkflow(workflow),
                                    title: 'Edit workflow'
                                }, '✏️'),
                                e('button', {
                                    className: 'wpaig-btn wpaig-btn-small',
                                    onClick: () => deleteWorkflow(workflow.id),
                                    style: { color: '#ef4444' },
                                    title: 'Delete workflow'
                                }, '🗑️')
                            )
                        )
                    )
                )
            ),
            
            // Premium upsell
            !isPremium && e('div', {
                className: 'wpaig-card',
                style: {
                    marginTop: '20px',
                    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    color: '#fff'
                }
            },
                e('h3', { style: { margin: '0 0 10px 0' } }, '⭐ Upgrade to Premium'),
                e('ul', { style: { margin: '0 0 15px 0', paddingLeft: '20px' } },
                    e('li', null, 'Unlimited workflows'),
                    e('li', null, 'Advanced actions (Performance check, Content analysis, Backup)'),
                    e('li', null, 'Priority execution'),
                    e('li', null, 'Detailed execution logs')
                ),
                e('p', { style: { margin: 0, fontSize: '14px', opacity: 0.9 } },
                    'Current: ', activeWorkflows, '/', freeLimit, ' workflows used'
                )
            )
        );
    }
    
    /**
     * License Tab - Usage & License Management
     */
    function LicenseTab() {
        const [loading, setLoading] = useState(true);
        const [usage, setUsage] = useState({});
        const [license, setLicense] = useState({});
        const [isPremium, setIsPremium] = useState(false);
        const [licenseKey, setLicenseKey] = useState('');
        const [activating, setActivating] = useState(false);
        
        // Load usage data
        const loadUsage = async () => {
            setLoading(true);
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_get_usage',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    setUsage(data.data.usage);
                    setLicense(data.data.license);
                    setIsPremium(data.data.is_premium);
                }
            } catch (error) {
                showToast('Failed to load usage data', 'error');
            } finally {
                setLoading(false);
            }
        };
        
        React.useEffect(() => {
            loadUsage();
        }, []);
        
        // Activate license
        const activateLicense = async () => {
            if (!licenseKey.trim()) {
                showToast('Please enter a license key', 'error');
                return;
            }
            
            setActivating(true);
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_activate_license',
                        nonce: wpaigData.nonce,
                        license_key: licenseKey
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showToast(data.data.message, 'success');
                    setLicenseKey('');
                    loadUsage();
                } else {
                    showToast(data.data.message || 'Activation failed', 'error');
                }
            } catch (error) {
                showToast('Failed to activate license', 'error');
            } finally {
                setActivating(false);
            }
        };
        
        // Deactivate license
        const deactivateLicense = async () => {
            if (!confirm('Are you sure you want to deactivate your license?')) return;
            
            try {
                const response = await fetch(wpaigData.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'wpaig_deactivate_license',
                        nonce: wpaigData.nonce
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showToast('License deactivated', 'success');
                    loadUsage();
                }
            } catch (error) {
                showToast('Failed to deactivate license', 'error');
            }
        };
        
        const getPercentage = (current, limit) => {
            if (limit === 'unlimited') return 0;
            return Math.min(100, (current / limit) * 100);
        };
        
        const getProgressColor = (percentage) => {
            if (percentage >= 90) return '#ef4444';
            if (percentage >= 70) return '#f59e0b';
            return '#10b981';
        };
        
        return e('div', { className: 'wpaig-tab-content' },
            // License Status Card
            e('div', { className: 'wpaig-card' },
                e('h3', null, isPremium ? '⭐ Premium License Active' : '🆓 Free Version'),
                isPremium && license.status === 'valid' ? e('div', { style: { marginTop: '15px' } },
                    e('p', null, 'License Key: ', e('code', null, license.key)),
                    license.expires && e('p', null, 'Expires: ', license.expires),
                    e('button', {
                        className: 'wpaig-btn',
                        onClick: deactivateLicense,
                        style: { marginTop: '10px', background: '#ef4444', borderColor: '#ef4444', color: '#fff' }
                    }, 'Deactivate License')
                ) : e('div', { style: { marginTop: '15px' } },
                    e('p', null, 'Enter your license key to activate premium features'),
                    e('div', { style: { display: 'flex', gap: '10px', marginTop: '10px' } },
                        e('input', {
                            type: 'text',
                            className: 'wpaig-input',
                            value: licenseKey,
                            onChange: (ev) => setLicenseKey(ev.target.value),
                            placeholder: 'WPAIG-XXXX-XXXX-XXXX',
                            style: { flex: 1 }
                        }),
                        e('button', {
                            className: 'wpaig-btn wpaig-btn-primary',
                            onClick: activateLicense,
                            disabled: activating
                        }, activating ? 'Activating...' : 'Activate')
                    ),
                    e('p', { style: { marginTop: '10px', fontSize: '12px', color: '#666' } },
                        'Demo: Use license key starting with "WPAIG-" to activate'
                    )
                )
            ),
            
            // Usage Statistics
            !loading && !isPremium && e('div', { className: 'wpaig-card', style: { marginTop: '20px' } },
                e('h3', null, '📊 Usage Statistics'),
                e('p', { style: { color: '#666', marginBottom: '20px' } }, 'Free tier limits and current usage'),
                
                Object.entries(usage).map(([key, data]) => {
                    const percentage = getPercentage(data.current, data.limit);
                    const color = getProgressColor(percentage);
                    const labels = {
                        ai_calls: 'AI Calls',
                        workflows: 'Workflows',
                        images: 'Images Optimized',
                        seo: 'SEO Optimizations',
                        scans: 'Scans Today'
                    };
                    
                    return e('div', {
                        key,
                        style: { marginBottom: '20px' }
                    },
                        e('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '5px' } },
                            e('strong', null, labels[key] || key),
                            e('span', null, `${data.current} / ${data.limit} per ${data.period}`)
                        ),
                        e('div', {
                            style: {
                                height: '8px',
                                background: '#e0e0e0',
                                borderRadius: '4px',
                                overflow: 'hidden'
                            }
                        },
                            e('div', {
                                style: {
                                    width: percentage + '%',
                                    height: '100%',
                                    background: color,
                                    transition: 'width 0.3s'
                                }
                            })
                        )
                    );
                })
            ),
            
            // Premium upsell
            !isPremium && e('div', {
                className: 'wpaig-card',
                style: {
                    marginTop: '20px',
                    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    color: '#fff',
                    cursor: 'pointer'
                },
                onClick: () => window.wpaigShowUpsell('All Features', 'Free Tier')
            },
                e('h3', { style: { margin: '0 0 15px 0' } }, '⭐ Upgrade to Premium'),
                e('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' } },
                    e('div', null,
                        e('strong', null, 'Free Tier:'),
                        e('ul', { style: { marginTop: '10px', paddingLeft: '20px' } },
                            e('li', null, '50 AI calls/month'),
                            e('li', null, '2 workflows'),
                            e('li', null, '20 images/month'),
                            e('li', null, '30 SEO/month'),
                            e('li', null, '5 scans/day')
                        )
                    ),
                    e('div', null,
                        e('strong', null, 'Premium:'),
                        e('ul', { style: { marginTop: '10px', paddingLeft: '20px' } },
                            e('li', null, '∞ Unlimited AI calls'),
                            e('li', null, '∞ Unlimited workflows'),
                            e('li', null, '∞ Unlimited optimizations'),
                            e('li', null, '⚡ Advanced features'),
                            e('li', null, '🎯 Priority support')
                        )
                    )
                ),
                e('div', { style: { marginTop: '15px', textAlign: 'center' } },
                    e('strong', { style: { fontSize: '18px' } }, 'Starting at ₹999/month'),
                    e('p', { style: { margin: '5px 0 0 0', fontSize: '14px', opacity: 0.9 } }, 
                        'Click to view pricing plans'
                    )
                )
            ),
            
            // Premium features
            isPremium && e('div', { className: 'wpaig-card', style: { marginTop: '20px', background: '#f0fdf4', borderColor: '#10b981' } },
                e('h3', { style: { color: '#10b981', margin: '0 0 15px 0' } }, '✅ Premium Features Unlocked'),
                e('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '10px' } },
                    e('div', null, '✓ Unlimited AI calls'),
                    e('div', null, '✓ Unlimited workflows'),
                    e('div', null, '✓ Unlimited image optimization'),
                    e('div', null, '✓ Unlimited SEO optimization'),
                    e('div', null, '✓ No scan limits'),
                    e('div', null, '✓ Advanced automation actions'),
                    e('div', null, '✓ Premium AI models'),
                    e('div', null, '✓ Priority support')
                )
            )
        );
    }
    
    /**
     * Settings Tab
     */
    function SettingsTab({ isPremium, hasApiKey }) {
        return e('div', { className: 'wpaig-tab-content' },
            e('div', { className: 'wpaig-settings-grid' },
                e('div', { className: `wpaig-settings-card ${isPremium ? 'wpaig-card-success' : ''}` },
                    e('h4', null, 'Premium Status'),
                    e('p', null, isPremium ? '✓ Premium features enabled' : 'Free version active')
                ),
                e('div', { className: `wpaig-settings-card ${hasApiKey ? 'wpaig-card-success' : 'wpaig-card-warning'}` },
                    e('h4', null, 'API Status'),
                    e('p', null, hasApiKey ? '✓ API key configured' : '⚠ API key not set')
                )
            ),
            e('div', { className: 'wpaig-alert wpaig-alert-info' },
                e('span', { className: 'wpaig-alert-icon' }, 'ℹ️'),
                e('span', null, 'Configure settings in the main settings panel above.')
            )
        );
    }
    
    /**
     * Main Dashboard Component
     */
    function Dashboard() {
        const [activeTab, setActiveTab] = useState(0);
        const isPremium = wpaigData.isPremium || false;
        const hasApiKey = wpaigData.hasApiKey || false;
        
        const tabs = [
            { name: 'Scan', icon: '🔍', component: ScanTab },
            { name: 'Performance', icon: '⚡', component: PerformanceTab },
            { name: 'SEO', icon: '📈', component: SEOTab },
            { name: 'Automator', icon: '🔄', component: AutomatorTab },
            { name: 'License', icon: '🔑', component: LicenseTab },
            { name: 'Conflicts', icon: '⚠️', component: ConflictsTab },
            { name: 'Settings', icon: '⚙️', component: SettingsTab }
        ];
        
        const ActiveComponent = tabs[activeTab].component;
        
        return e('div', { className: 'wpaig-dashboard-container' },
            e('h2', { className: 'wpaig-dashboard-title' }, 'WP AI Guardian'),
            
            // Tabs Navigation
            e('div', { className: 'wpaig-tabs' },
                e('div', { className: 'wpaig-tab-list' },
                    tabs.map((tab, index) =>
                        e('button', {
                            key: index,
                            className: `wpaig-tab ${activeTab === index ? 'wpaig-tab-active' : ''}`,
                            onClick: () => setActiveTab(index)
                        }, `${tab.icon} ${tab.name}`)
                    )
                ),
                
                // Tab Content
                e('div', { className: 'wpaig-tab-panel' },
                    e(ActiveComponent, { isPremium, hasApiKey })
                )
            )
        );
    }
    
    /**
     * Initialize React app when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        const rootElement = document.getElementById('wpaig-dashboard-root');
        
        if (rootElement) {
            const root = ReactDOM.createRoot(rootElement);
            root.render(e(Dashboard));
        }
    });
    
})();
