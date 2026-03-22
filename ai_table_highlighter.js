/**
 * ============================================================================
 * AI TABLE HIGHLIGHTER - VISUAL RISK FEEDBACK
 * ============================================================================
 * FILE: ai_table_highlighter.js
 * 
 * FEATURES:
 * - Highlights table rows based on AI risk level
 * - GREEN = LOW risk (safe to disburse)
 * - YELLOW = MEDIUM risk (requires review)
 * - RED = HIGH risk (blocked)
 * - Works in main table AND bulk selection
 * ============================================================================
 */

class AITableHighlighter {
    constructor() {
        this.riskColors = {
            'LOW': {
                bg: 'ai-risk-low',
                border: 'border-l-8 border-green-500',
                text: 'text-green-800',
                badge: 'bg-green-200 text-green-900'
            },
            'MEDIUM': {
                bg: 'ai-risk-medium',
                border: 'border-l-8 border-yellow-500',
                text: 'text-yellow-800',
                badge: 'bg-yellow-200 text-yellow-900'
            },
            'HIGH': {
                bg: 'ai-risk-high',
                border: 'border-l-8 border-red-500',
                text: 'text-red-800',
                badge: 'bg-red-200 text-red-900'
            }
        };
        this.injectStyles();
    }

    injectStyles() {
        if (document.getElementById('ai-highlighter-styles')) return;
        const style = document.createElement('style');
        style.id = 'ai-highlighter-styles';
        style.innerHTML = `
            .ai-risk-low { background-color: rgba(220, 252, 231, 1) !important; }
            .ai-risk-medium { background-color: rgba(254, 249, 195, 1) !important; }
            .ai-risk-high { background-color: rgba(254, 226, 226, 1) !important; }
            
            /* Apply to cells too if rows have issues */
            tr.ai-risk-low td { background-color: rgba(220, 252, 231, 0.5) !important; }
            tr.ai-risk-medium td { background-color: rgba(254, 249, 195, 0.5) !important; }
            tr.ai-risk-high td { background-color: rgba(254, 226, 226, 0.5) !important; }
            
            .priority-cell { position: relative; }
        `;
        document.head.appendChild(style);
    }

    /**
     * Highlight single row based on AI analysis
     */
    highlightRow(rowElement, riskLevel, riskScore) {
        if (!rowElement || !riskLevel) return;

        const colors = this.riskColors[riskLevel];
        if (!colors) return;

        // Remove old highlighting
        this.clearRowHighlight(rowElement);

        // Add new highlighting
        if (colors.bg) {
            rowElement.classList.add(colors.bg);
            // Also apply to cells for maximum visibility
            rowElement.querySelectorAll('td').forEach(td => td.classList.add(colors.bg));
        }
        if (colors.border) rowElement.classList.add(...colors.border.split(' '));

        // Add risk badge in priority column
        const priorityCell = this.findPriorityCell(rowElement);
        if (priorityCell) {
            const badge = document.createElement('span');
            badge.className = `${colors.badge} text-[10px] font-bold px-2 py-0.5 rounded-full ml-2 shadow-sm border border-current`;
            badge.textContent = `${riskLevel} (${riskScore})`;
            badge.title = `AI Risk Score: ${riskScore}/100`;
            priorityCell.appendChild(badge);
        }
    }

    /**
     * Clear row highlighting
     */
    clearRowHighlight(rowElement) {
        // Remove all risk-related classes
        const allClasses = [
            'ai-risk-low', 'ai-risk-medium', 'ai-risk-high',
            'bg-green-50', 'bg-yellow-50', 'bg-red-50',
            'bg-green-100', 'bg-yellow-100', 'bg-red-100',
            'border-l-4', 'border-l-8', 'border-green-500', 'border-yellow-500', 'border-red-500'
        ];

        rowElement.classList.remove(...allClasses);
        rowElement.querySelectorAll('td').forEach(td => td.classList.remove(...allClasses));

        // Remove risk badges
        const badges = rowElement.querySelectorAll('[title^="AI Risk Score"]');
        badges.forEach(badge => badge.remove());
    }

    /**
     * Find priority cell in row
     */
    findPriorityCell(rowElement) {
        // Try to find by class first
        const priorityByClass = rowElement.querySelector('.priority-cell');
        if (priorityByClass) return priorityByClass;

        // Try to find priority column by content
        const cells = rowElement.querySelectorAll('td');

        // Look for cell containing priority info (usually near the end)
        for (let i = Math.max(0, cells.length - 4); i < cells.length; i++) {
            if (cells[i] && (
                cells[i].textContent.includes('Low') ||
                cells[i].textContent.includes('Medium') ||
                cells[i].textContent.includes('High') ||
                cells[i].textContent.includes('Overdue') ||
                cells[i].classList.contains('priority-cell')
            )) {
                return cells[i];
            }
        }

        // Fallback: return second to last cell (before actions)
        return cells.length > 1 ? cells[cells.length - 2] : null;
    }

    /**
     * Highlight all rows based on AI analysis results
     */
    highlightAllRows(analysisResults) {
        if (!Array.isArray(analysisResults)) {
            analysisResults = [analysisResults];
        }

        analysisResults.forEach(result => {
            const row = this.findRowByPayoutId(result.payout_id);
            if (row) {
                this.highlightRow(row, result.risk_level, result.risk_score);
            }
        });
    }

    /**
     * Find table row by payout ID
     */
    findRowByPayoutId(payoutId) {
        // Search in all tables
        const tables = document.querySelectorAll('table');

        for (let table of tables) {
            const rows = table.querySelectorAll('tr');

            for (let row of rows) {
                // Check if row contains the payout ID
                if (row.textContent.includes(payoutId) ||
                    row.getAttribute('data-id') === payoutId ||
                    row.getAttribute('data-refid') === payoutId) {
                    return row;
                }
            }
        }

        return null;
    }

    /**
     * Show bulk analysis summary
     */
    showBulkSummary(selectedItems, analysisResults) {
        const summary = this.calculateBulkSummary(analysisResults);

        // Create summary element
        const summaryDiv = document.createElement('div');
        summaryDiv.id = 'bulk-ai-summary';
        summaryDiv.className = 'fixed top-20 right-6 bg-white rounded-lg shadow-xl p-4 border-2 border-purple-200 z-50 max-w-sm';

        summaryDiv.innerHTML = `
            <div class="flex justify-between items-start mb-3">
                <h4 class="font-bold text-gray-800 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Bulk AI Analysis
                </h4>
                <button onclick="document.getElementById('bulk-ai-summary').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Total Selected:</span>
                    <span class="font-bold">${summary.total}</span>
                </div>
                
                <div class="flex justify-between items-center p-2 bg-green-50 rounded border border-green-200">
                    <span class="text-green-700 flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                        Safe (LOW)
                    </span>
                    <span class="font-bold text-green-600">${summary.low}</span>
                </div>
                
                <div class="flex justify-between items-center p-2 bg-yellow-50 rounded border border-yellow-200">
                    <span class="text-yellow-700 flex items-center">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                        Review (MEDIUM)
                    </span>
                    <span class="font-bold text-yellow-600">${summary.medium}</span>
                </div>
                
                <div class="flex justify-between items-center p-2 bg-red-50 rounded border border-red-200">
                    <span class="text-red-700 flex items-center">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                        Blocked (HIGH)
                    </span>
                    <span class="font-bold text-red-600">${summary.high}</span>
                </div>
            </div>
            
            ${summary.high > 0 ? `
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded text-sm">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-red-700">
                            <strong>⚠️ Warning:</strong> ${summary.high} high-risk transaction(s) detected. 
                            Bulk disbursement not recommended.
                        </span>
                    </div>
                </div>
            ` : ''}
            
            <div class="mt-3 pt-3 border-t border-gray-200">
                <button onclick="showAIRecommendations(window.lastBulkAnalysis)" 
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-2 rounded-lg transition">
                    View Detailed Analysis
                </button>
            </div>
        `;

        // Remove old summary if exists
        const oldSummary = document.getElementById('bulk-ai-summary');
        if (oldSummary) oldSummary.remove();

        // Add to page
        document.body.appendChild(summaryDiv);

        // Store for later access
        window.lastBulkAnalysis = analysisResults;
    }

    /**
     * Calculate bulk summary statistics
     */
    calculateBulkSummary(results) {
        return {
            total: results.length,
            low: results.filter(r => r.risk_level === 'LOW').length,
            medium: results.filter(r => r.risk_level === 'MEDIUM').length,
            high: results.filter(r => r.risk_level === 'HIGH').length
        };
    }

    /**
     * Add risk indicator icon to action buttons
     */
    addRiskIndicatorToButton(buttonElement, riskLevel) {
        const icons = {
            'LOW': '✅',
            'MEDIUM': '⚠️',
            'HIGH': '🚨'
        };

        const icon = icons[riskLevel] || '🤖';

        // Add icon before button text
        if (buttonElement && !buttonElement.querySelector('.risk-icon')) {
            const iconSpan = document.createElement('span');
            iconSpan.className = 'risk-icon mr-1';
            iconSpan.textContent = icon;
            buttonElement.prepend(iconSpan);
        }
    }
}

// Initialize global highlighter
var aiTableHighlighter;
(function () {
    function init() {
        if (!window.aiTableHighlighter) {
            window.aiTableHighlighter = new AITableHighlighter();
            aiTableHighlighter = window.aiTableHighlighter;
            console.log('✅ AI Table Highlighter ready!');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also expose it immediately if needed
    window.aiTableHighlighter = window.aiTableHighlighter || new AITableHighlighter();
    aiTableHighlighter = window.aiTableHighlighter;
})();
