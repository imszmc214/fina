<?php
/**
 * ============================================================================
 * AI RECOMMENDATION CARDS - COMPLETE UI COMPONENTS
 * ============================================================================
 * LOCATION: Domain (admin folder)
 * FILE: ai_recommendation_cards.php
 * 
 * DISPLAYS:
 * 1. Safe to Disburse Card (LOW risk items)
 * 2. Requires Review Card (MEDIUM risk items)
 * 3. Fraud Detection Card (HIGH risk items)
 * 4. AI Insights Dashboard
 * ============================================================================
 */

// This file should be included in payout.php
// Usage: include 'ai_recommendation_cards.php';
?>

<!-- AI Recommendation Cards Container -->
<div id="ai-recommendations-container" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">🤖 AI Payout Analysis Dashboard</h2>
                    <p class="text-sm opacity-90 mt-1">Intelligent transaction validation powered by TensorFlow.js</p>
                </div>
                <button onclick="closeAIRecommendations()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- AI Analysis Summary -->
        <div class="p-6 bg-gray-50 border-b">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Analyzed -->
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="text-gray-500 text-sm font-medium">Total Analyzed</div>
                    <div class="text-3xl font-bold text-gray-800 mt-1" id="ai-total-analyzed">0</div>
                </div>
                
                <!-- Safe to Disburse -->
                <div class="bg-green-50 rounded-lg p-4 shadow-sm border border-green-200">
                    <div class="text-green-700 text-sm font-medium">✅ Safe to Disburse</div>
                    <div class="text-3xl font-bold text-green-600 mt-1" id="ai-safe-count">0</div>
                </div>
                
                <!-- Requires Review -->
                <div class="bg-yellow-50 rounded-lg p-4 shadow-sm border border-yellow-200">
                    <div class="text-yellow-700 text-sm font-medium">⚠️ Requires Review</div>
                    <div class="text-3xl font-bold text-yellow-600 mt-1" id="ai-review-count">0</div>
                </div>
                
                <!-- High Risk / Fraud -->
                <div class="bg-red-50 rounded-lg p-4 shadow-sm border border-red-200">
                    <div class="text-red-700 text-sm font-medium">🚨 High Risk</div>
                    <div class="text-3xl font-bold text-red-600 mt-1" id="ai-fraud-count">0</div>
                </div>
            </div>
        </div>

        <!-- Cards Container -->
        <div class="p-6 space-y-6">
            
            <!-- CARD 1: Safe to Disburse -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-500 text-white rounded-full p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-green-800">Safe to Disburse</h3>
                            <p class="text-sm text-green-600">LOW risk - Approved for immediate disbursement</p>
                        </div>
                    </div>
                    <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full" id="safe-badge">0 items</span>
                </div>
                
                <div id="safe-items-list" class="space-y-2 max-h-60 overflow-y-auto">
                    <!-- Safe items will be dynamically inserted here -->
                    <div class="text-center text-gray-500 py-8" id="safe-empty">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>No safe transactions to display</p>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Requires Manual Review -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-yellow-500 text-white rounded-full p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-yellow-800">Requires Manual Review</h3>
                            <p class="text-sm text-yellow-600">MEDIUM risk - Supervisor approval needed</p>
                        </div>
                    </div>
                    <span class="bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full" id="review-badge">0 items</span>
                </div>
                
                <div id="review-items-list" class="space-y-2 max-h-60 overflow-y-auto">
                    <!-- Review items will be dynamically inserted here -->
                    <div class="text-center text-gray-500 py-8" id="review-empty">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p>No transactions requiring review</p>
                    </div>
                </div>
            </div>

            <!-- CARD 3: Fraud Detection / High Risk -->
            <div class="bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-500 text-white rounded-full p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-red-800">Fraud Detection / High Risk</h3>
                            <p class="text-sm text-red-600">HIGH risk - BLOCKED pending investigation</p>
                        </div>
                    </div>
                    <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full" id="fraud-badge">0 items</span>
                </div>
                
                <div id="fraud-items-list" class="space-y-2 max-h-60 overflow-y-auto">
                    <!-- Fraud items will be dynamically inserted here -->
                    <div class="text-center text-gray-500 py-8" id="fraud-empty">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <p>No high-risk transactions detected</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div class="bg-gray-50 px-6 py-4 rounded-b-xl flex justify-between items-center border-t">
            <div class="text-sm text-gray-600">
                <span class="font-medium">AI Analysis powered by TensorFlow.js</span>
                <span class="mx-2">•</span>
                <span id="ai-analysis-time">Last updated: --:--</span>
            </div>
            <button onclick="closeAIRecommendations()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition">
                Close Dashboard
            </button>
        </div>
    </div>
</div>

<!-- JavaScript for AI Recommendations -->
<script>
let aiAnalysisResults = [];

function showAIRecommendations(results) {
    // Store results
    aiAnalysisResults = Array.isArray(results) ? results : [results];
    
    // Update summary counts
    const safeItems = aiAnalysisResults.filter(r => r.risk_level === 'LOW');
    const reviewItems = aiAnalysisResults.filter(r => r.risk_level === 'MEDIUM');
    const fraudItems = aiAnalysisResults.filter(r => r.risk_level === 'HIGH');
    
    document.getElementById('ai-total-analyzed').textContent = aiAnalysisResults.length;
    document.getElementById('ai-safe-count').textContent = safeItems.length;
    document.getElementById('ai-review-count').textContent = reviewItems.length;
    document.getElementById('ai-fraud-count').textContent = fraudItems.length;
    
    // Update badges
    document.getElementById('safe-badge').textContent = `${safeItems.length} items`;
    document.getElementById('review-badge').textContent = `${reviewItems.length} items`;
    document.getElementById('fraud-badge').textContent = `${fraudItems.length} items`;
    
    // Populate lists
    populateItemsList('safe-items-list', 'safe-empty', safeItems, 'green');
    populateItemsList('review-items-list', 'review-empty', reviewItems, 'yellow');
    populateItemsList('fraud-items-list', 'fraud-empty', fraudItems, 'red');
    
    // Update timestamp
    document.getElementById('ai-analysis-time').textContent = 
        `Last updated: ${new Date().toLocaleTimeString()}`;
    
    // Show container
    document.getElementById('ai-recommendations-container').classList.remove('hidden');
}

function populateItemsList(listId, emptyId, items, color) {
    const list = document.getElementById(listId);
    let empty = document.getElementById(emptyId);
    
    // Toggle empty state
    if (items.length === 0) {
        if (empty) empty.classList.remove('hidden');
        // Clear any item cards (anything that isn't the 'empty' element)
        Array.from(list.children).forEach(child => {
            if (child !== empty) child.remove();
        });
        return;
    }
    
    if (empty) empty.classList.add('hidden');
    
    // Clear previous items
    Array.from(list.children).forEach(child => {
        if (child !== empty) child.remove();
    });
    
    const colorClasses = {
        green: { bg: 'bg-white', border: 'border-green-200', text: 'text-green-700' },
        yellow: { bg: 'bg-white', border: 'border-yellow-200', text: 'text-yellow-700' },
        red: { bg: 'bg-white', border: 'border-red-200', text: 'text-red-700' }
    };
    
    const colors = colorClasses[color];
    
    items.forEach(item => {
        const itemHtml = `
            <div class="${colors.bg} border-2 ${colors.border} rounded-lg p-4 mb-2">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <div class="font-bold ${colors.text}">${item.payout_id}</div>
                        <div class="text-sm text-gray-600 mt-1">Risk Score: ${item.risk_score}/100</div>
                    </div>
                    <span class="text-xs font-bold ${colors.text} px-2 py-1 ${colors.bg} rounded">
                        ${item.risk_level}
                    </span>
                </div>
                <div class="text-sm text-gray-700 space-y-1">
                    ${(item.issues || []).slice(0, 3).map(issue => `
                        <div class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>${issue}</span>
                        </div>
                    `).join('')}
                    ${item.issues && item.issues.length > 3 ? `
                        <div class="text-xs text-gray-500 mt-1">
                            +${item.issues.length - 3} more issues
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        list.insertAdjacentHTML('beforeend', itemHtml);
    });
}

function closeAIRecommendations() {
    document.getElementById('ai-recommendations-container').classList.add('hidden');
}

// Update results without showing modal
function updateAIResults(results) {
    aiAnalysisResults = Array.isArray(results) ? results : [results];
    console.log(`📊 AI Results Updated: ${aiAnalysisResults.length} items`);
}

// Show button for AI dashboard (add to your payout page)
function addAIDashboardButton() {
    const btn = document.createElement('button');
    btn.className = 'fixed bottom-6 right-6 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-full shadow-lg font-medium flex items-center space-x-2 transition z-40';
    btn.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <span>AI Insights</span>
    `;
    btn.onclick = () => {
        // Show current analysis
        if (aiAnalysisResults.length > 0) {
            showAIRecommendations(aiAnalysisResults);
        } else {
            alert('No AI analysis data available. Analyze some transactions first.');
        }
    };
    document.body.appendChild(btn);
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addAIDashboardButton);
} else {
    addAIDashboardButton();
}
</script>

<style>
/* Smooth scrollbar for lists */
#safe-items-list::-webkit-scrollbar,
#review-items-list::-webkit-scrollbar,
#fraud-items-list::-webkit-scrollbar {
    width: 6px;
}

#safe-items-list::-webkit-scrollbar-track,
#review-items-list::-webkit-scrollbar-track,
#fraud-items-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#safe-items-list::-webkit-scrollbar-thumb,
#review-items-list::-webkit-scrollbar-thumb,
#fraud-items-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

#safe-items-list::-webkit-scrollbar-thumb:hover,
#review-items-list::-webkit-scrollbar-thumb:hover,
#fraud-items-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
