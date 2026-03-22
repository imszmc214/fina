    <script>
    // Global variables
    let tnvsCategories = <?php echo json_encode($tnvs_categories); ?>;
    
    // Hierarchical COA Data for Modal
    const modalCategories = <?php echo json_encode($gl_categories); ?>;
    const modalSubcategories = <?php echo json_encode($gl_subcategories); ?>;
    const modalGLAccounts = <?php echo json_encode($gl_accounts_by_sub); ?>;

    function updateGlobalAllocationTotal() {
        // Find all subcategory inputs
        const subcatInputs = document.querySelectorAll('input[oninput*="distributeSubcategoryBudget"]');
        let total = 0;
        
        subcatInputs.forEach(input => {
            total += getRawValue(input.value);
        });
        
        const disp1 = document.getElementById('current-mapping-total');
        const disp2 = document.getElementById('calculated-total-summary');
        
        if (disp1) disp1.textContent = formatCurrency(total);
        if (disp2) disp2.textContent = formatCurrency(total);
        
        const totalBudgetField = document.querySelector('input[name="total_budget"]');
        if (totalBudgetField) {
            totalBudgetField.value = total.toFixed(2);
        }
        
        // Budget limit check - if globalRemainingBudget is 0, any total > 0 is over.
        // We use a threshold of 0.01 for floating point safety.
        const isOverBudget = (total - globalRemainingBudget) > 0.01;
        const submitBtn = document.getElementById('submit-btn');

        if (isOverBudget) {
            // Header Badge
            const badge = disp1?.closest('span');
            if (badge) {
                badge.classList.remove('bg-emerald-50', 'text-emerald-600');
                badge.classList.add('bg-red-50', 'text-red-600');
            }
            
            // Summary Box (Bottom)
            if (disp2) {
                const summaryBox = disp2.parentElement;
                summaryBox.classList.add('bg-red-50', 'border-red-200', 'animate-shake');
                summaryBox.classList.remove('bg-indigo-50', 'border-indigo-100');
                disp2.classList.add('text-red-600', 'font-black');
            }
            
            // Subcategory Inputs
            subcatInputs.forEach(input => {
                if (getRawValue(input.value) > 0) {
                    input.classList.add('bg-red-50', 'border-red-300', 'text-red-700');
                    input.classList.remove('bg-slate-50', 'border-slate-200');
                }
            });
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        } else {
            // Header Badge
            const badge = disp1?.closest('span');
            if (badge) {
                badge.classList.add('bg-emerald-50', 'text-emerald-600');
                badge.classList.remove('bg-red-50', 'text-red-600');
            }
            
            // Summary Box (Bottom)
            if (disp2) {
                const summaryBox = disp2.parentElement;
                summaryBox.classList.remove('bg-red-50', 'border-red-200', 'animate-shake');
                summaryBox.classList.add('bg-indigo-50', 'border-indigo-100');
                disp2.classList.remove('text-red-600');
            }
            
            // Subcategory Inputs
            subcatInputs.forEach(input => {
                input.classList.remove('bg-red-50', 'border-red-300', 'text-red-700');
                input.classList.add('bg-slate-50', 'border-slate-200');
            });
        }

        updateSuggestedMetrics(total);
        validateCreateBudgetPlan();
    }

    function updateSuggestedMetrics(totalBudget) {
        const revenueInput = document.querySelector('input[name="project_revenue"]');
        const impactInput = document.querySelector('input[name="impact_percentage"]');
        const taxationInput = document.querySelector('input[name="taxation_adj"]');
        
        if (!revenueInput || !impactInput || !taxationInput) return;
        
        const isRevenueAuto = revenueInput.getAttribute('data-suggested') !== 'false';
        const isImpactAuto = impactInput.getAttribute('data-suggested') !== 'false';
        const isTaxationAuto = taxationInput.getAttribute('data-suggested') !== 'false';
        
        if (totalBudget > 0) {
            if (isRevenueAuto) {
                const val = totalBudget * 1.15;
                revenueInput.value = val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                revenueInput.style.borderColor = '#10b981';
            }
            if (isImpactAuto) {
                let suggestedImpact = 10;
                if (totalBudget >= 10000000) suggestedImpact = 85;
                else if (totalBudget >= 5000000) suggestedImpact = 65;
                else if (totalBudget >= 1000000) suggestedImpact = 45;
                else if (totalBudget >= 500000) suggestedImpact = 25;
                impactInput.value = suggestedImpact.toFixed(2);
                impactInput.style.borderColor = '#10b981';
            }
            if (isTaxationAuto) {
                const val = totalBudget * 0.12;
                taxationInput.value = val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                taxationInput.style.borderColor = '#10b981';
            }
        }
    }

    function filterGLAllocationList(query) {
        query = query.toLowerCase().trim();
        const subcategories = document.querySelectorAll('.subcategory-block');
        const categories = document.querySelectorAll('.category-block');

        // Hide/Show subcategories based on name
        subcategories.forEach(sub => {
            const searchable = sub.getAttribute('data-searchable') || '';
            if (searchable.includes(query) || query === '') {
                sub.classList.remove('hidden');
            } else {
                sub.classList.add('hidden');
            }
        });

        // Hide categories if all children are hidden
        categories.forEach(cat => {
            const visibleSubs = cat.querySelectorAll('.subcategory-block:not(.hidden)');
            if (visibleSubs.length === 0 && query !== '') {
                cat.classList.add('hidden');
            } else {
                cat.classList.remove('hidden');
            }
        });
    }

    function updateBudgetDates() {
        const yearInput = document.getElementById('modal_fiscal_year');
        const typeInput = document.getElementById('plan_type_input');
        if (!yearInput || !typeInput) return;
        
        const year = yearInput.value;
        const type = typeInput.value;
        
        const now = new Date();
        const currentYear = now.getFullYear();
        const todayStr = now.toISOString().split('T')[0];

        if (type === 'monthly') {
            const currentMonth = now.getMonth(); // 0-11
            const monthToUse = (parseInt(year) === currentYear) ? currentMonth : 0;
            
            const firstDay = new Date(year, monthToUse, 1);
            const lastDay = new Date(year, monthToUse + 1, 0);
            
            const formatDate = (date) => {
                let d = date.getDate(), m = date.getMonth() + 1, y = date.getFullYear();
                return `${y}-${m < 10 ? '0'+m : m}-${d < 10 ? '0'+d : d}`;
            };
            
            // For start date, if it's the current year and current month, use today as minimum
            let startVal = formatDate(firstDay);
            if (parseInt(year) === currentYear && monthToUse === currentMonth) {
                startVal = todayStr;
            }
            
            if (document.getElementById('modal_start_date')) document.getElementById('modal_start_date').value = startVal;
            if (document.getElementById('modal_end_date')) document.getElementById('modal_end_date').value = formatDate(lastDay);
        } else {
            // Yearly
            let startVal = `${year}-01-01`;
            if (parseInt(year) === currentYear) {
                startVal = todayStr; // Latest valid date is today
            }
            if (document.getElementById('modal_start_date')) document.getElementById('modal_start_date').value = startVal;
            if (document.getElementById('modal_end_date')) document.getElementById('modal_end_date').value = `${year}-12-31`;
        }
        
        validateCreateBudgetPlan();
    }

    function updateProposalDates() {
        try {
            console.log('Initializing proposal dates...');
            const yearInput = document.getElementById('fiscal_year');
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            
            if (!yearInput || !startInput || !endInput) {
                console.warn('Could not find proposal date inputs:', { yearInput, startInput, endInput });
                return;
            }
            
            const year = yearInput.value || <?php echo date('Y'); ?>;
            const now = new Date();
            const currentYear = now.getFullYear();
            const todayStr = now.toISOString().split('T')[0];
            
            // Start Date: Today if current year, else January 1st of selected year
            let startVal = `${year}-01-01`;
            if (parseInt(year) === currentYear) {
                startVal = todayStr;
            } else if (parseInt(year) < currentYear) {
                startVal = todayStr;
            }
            
            startInput.value = startVal;
            
            // End Date: Always Dec 31st of selected fiscal year
            endInput.value = `${year}-12-31`;
            
            console.log(`Dates set: Start=${startVal}, End=${year}-12-31`);
            
            if (typeof calculateDuration === 'function') {
                calculateDuration();
            }
        } catch (err) {
            console.error('Error in updateProposalDates:', err);
        }
    }

    function distributeSubcategoryBudget(subId, totalAmountStr) {
        const subBlock = document.querySelector(`.subcategory-block[data-sub-id="${subId}"]`);
        if (!subBlock) return;
        
        const glInputs = subBlock.querySelectorAll('.gl-input-actual');
        if (glInputs.length === 0) return;
        
        const amount = getRawValue(totalAmountStr);
        
        if (amount <= 0) {
            // Clear all inputs if amount is 0
            glInputs.forEach(input => {
                input.value = '';
            });
        } else {
            const count = glInputs.length;
            const baseShare = Math.floor((amount * 100) / count) / 100; // Round down to 2 decimals
            const remainder = Math.round((amount - (baseShare * count)) * 100) / 100; // Calculate remainder
            
            glInputs.forEach((input, index) => {
                if (index === 0) {
                    // First input gets the base share PLUS the remainder to avoid rounding loss
                    input.value = (baseShare + remainder).toFixed(2);
                } else {
                    // Other inputs get equal base share
                    input.value = baseShare.toFixed(2);
                }
            });
        }
        
        updateGlobalAllocationTotal();
    }
    let costCategories = <?php echo json_encode($cost_categories); ?>;
    let currentStep = 1;
    let myProposalsOnly = false;
    let monitoringChart = null;
    let departmentChart = null;
    let currentPage = {
        plans: 1,
        proposals: 1,
        archived: 1,
        monitoring: 1
    };
    let searchTimeout = null;
    let currentReviewProposalId = null;
    let selectedFiles = [];
    let currentProposalType = 'pending';
    let costItems = [];
    let currentSupportingDocPath = '';
    let costBreakdownChart = null;
    let globalTotalRevenue = 0;
    let globalRemainingBudget = 0;
    
    // Formatting helper
    function formatInputAmount(input) {
        // Remove non-numeric characters except period
        let val = input.value.replace(/[^0-9.]/g, '');
        
        // Handle multiple periods
        const parts = val.split('.');
        if (parts.length > 2) {
            val = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Format with commas
        if (val !== '') {
            const numParts = val.split('.');
            numParts[0] = numParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            input.value = numParts.join('.');
        } else {
            input.value = '';
        }
    }

    // Helper to get raw numeric value
    function getRawValue(val) {
        if (!val) return 0;
        return parseFloat(val.toString().replace(/,/g, '')) || 0;
    }
    
    // Modal management functions
    // (Functions openModal and closeModal are defined later in the script)
    
    // Update subcategories based on selected category
    function updateSubcategories() {
        const categorySelect = document.getElementById('categorySelect');
        const subCategorySelect = document.getElementById('subCategorySelect');
        const selectedCategory = categorySelect.value;
        
        console.log('Selected category:', selectedCategory);
        console.log('Available categories:', tnvsCategories);
        
        // Clear existing options
        subCategorySelect.innerHTML = '<option value="">Select Sub-category</option>';
        
        // If a category is selected and it exists in tnvsCategories
        if (selectedCategory && tnvsCategories[selectedCategory]) {
            console.log('Subcategories for', selectedCategory, ':', tnvsCategories[selectedCategory]);
            
            // Add each subcategory as an option
            tnvsCategories[selectedCategory].forEach(function(subCategory) {
                const option = document.createElement('option');
                option.value = subCategory;
                option.textContent = subCategory;
                subCategorySelect.appendChild(option);
            });
            
            // Enable the subcategory select
            subCategorySelect.disabled = false;
        } else {
            // Disable if no category selected
            subCategorySelect.disabled = true;
        }
    }
    
    // Utility function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Utility function to format currency
    function formatCurrency(amount) {
        if (isNaN(amount)) return '₱0.00';
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Utility function to get file icon
    function getFileIcon(extension) {
        const icons = {
            'pdf': '<i class="fas fa-file-pdf text-red-600"></i>',
            'doc': '<i class="fas fa-file-word text-blue-600"></i>',
            'docx': '<i class="fas fa-file-word text-blue-600"></i>',
            'xls': '<i class="fas fa-file-excel text-green-600"></i>',
            'xlsx': '<i class="fas fa-file-excel text-green-600"></i>',
            'jpg': '<i class="fas fa-file-image text-purple-600"></i>',
            'jpeg': '<i class="fas fa-file-image text-purple-600"></i>',
            'png': '<i class="fas fa-file-image text-purple-600"></i>',
            'zip': '<i class="fas fa-file-archive text-yellow-600"></i>'
        };
        return icons[extension.toLowerCase()] || '<i class="fas fa-file text-gray-600"></i>';
    }
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadPlans();
        loadProposals();
        loadMonitoringData();
        updateDepartmentChart(<?php echo $current_year; ?>);
        loadArchived();
        
        // Initialize dates
        updateProposalDates();
        updateBudgetDates();
        
        // Setup event listeners
        document.getElementById('proposalForm')?.addEventListener('submit', submitProposal);
        document.getElementById('forecastForm')?.addEventListener('submit', saveForecast);
        document.getElementById('archiveForm')?.addEventListener('submit', archivePlan);
        document.getElementById('restoreForm')?.addEventListener('submit', restorePlan);
        document.getElementById('reviewCommentForm')?.addEventListener('submit', addProposalComment);
        
        // Setup file upload
        setupFileUpload();
        
        // Setup filter listeners
        document.getElementById('filterDepartment')?.addEventListener('change', () => {
            currentPage.plans = 1;
            loadPlans();
        });
        document.getElementById('filterYear')?.addEventListener('change', () => {
            currentPage.plans = 1;
            loadPlans();
        });
        document.getElementById('proposalFilterDepartment')?.addEventListener('change', () => {
            currentPage.proposals = 1;
            loadProposals();
        });
        document.getElementById('proposalFilterYear')?.addEventListener('change', () => {
            currentPage.proposals = 1;
            loadProposals();
        });
        const syncMonitoringFilters = (type, value) => {
            const ids = type === 'year' 
                ? ['monitoringYear', 'monitoringTableYear', 'deptMonitoringYear']
                : ['monitoringDepartment', 'monitoringTableDepartment', 'deptMonitoringDepartment'];
            
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = value;
            });
            currentPage.monitoring = 1;
            loadMonitoringData();
        };

        document.getElementById('monitoringYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });
        document.getElementById('monitoringTableYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });
        document.getElementById('deptMonitoringYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });

        document.getElementById('monitoringDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('monitoringTableDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('deptMonitoringDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('departmentGraphYear')?.addEventListener('change', function() {
            updateDepartmentChart(this.value);
        });
        
        // Initialize date fields
        const today = new Date();
        const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        document.querySelector('input[name="start_date"]').valueAsDate = today;
        document.querySelector('input[name="end_date"]').valueAsDate = nextMonth;
        calculateDuration();
        
        // Update budget preview initially
        updateBudgetPreview();
    });
    
    // Tab switching functions
    function switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        document.getElementById(tab + 'Tab').classList.remove('hidden');
        
        const activeBtn = document.querySelector(`[onclick="switchTab('${tab}')"]`);
        if (activeBtn) activeBtn.classList.add('active');
        
        if (tab === 'plans') loadPlans();
        else if (tab === 'proposals') loadProposals();
        else if (tab === 'monitoring') {
            loadMonitoringData();
            if (typeof updateCategoryChart === 'function') updateCategoryChart();
            updateDepartmentChart();
        }
        else if (tab === 'archived') loadArchived();
    }
    
    // Toggle monitoring view between category and department
    let currentMonitoringView = 'category';
    
    function toggleMonitoringView(view) {
        currentMonitoringView = view;
        
        // Update button styles
        const categoryBtn = document.getElementById('categoryViewBtn');
        const departmentBtn = document.getElementById('departmentViewBtn');
        
        if (view === 'category') {
            categoryBtn.classList.add('bg-purple-600', 'text-white');
            categoryBtn.classList.remove('bg-gray-200', 'text-gray-700');
            departmentBtn.classList.remove('bg-purple-600', 'text-white');
            departmentBtn.classList.add('bg-gray-200', 'text-gray-700');
            
            // Show category chart and table, hide department
            document.getElementById('categoryChartSection').classList.remove('hidden');
            document.getElementById('categoryTableSection').classList.remove('hidden');
            document.getElementById('departmentChartSection').classList.add('hidden');
            document.getElementById('departmentTableSection').classList.add('hidden');
        } else {
            departmentBtn.classList.add('bg-purple-600', 'text-white');
            departmentBtn.classList.remove('bg-gray-200', 'text-gray-700');
            categoryBtn.classList.remove('bg-purple-600', 'text-white');
            categoryBtn.classList.add('bg-gray-200', 'text-gray-700');
            
            // Show department chart and table, hide category
            document.getElementById('categoryChartSection').classList.add('hidden');
            document.getElementById('categoryTableSection').classList.add('hidden');
            document.getElementById('departmentChartSection').classList.remove('hidden');
            document.getElementById('departmentTableSection').classList.remove('hidden');
        }
    }
    
    // Proposal step navigation - DISABLED (Single-page form now)
    /*
    function nextStep() {
        if (currentStep < 4) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }
            
            // Hide current step
            document.getElementById(`proposalStep${currentStep}`).classList.add('hidden');
            
            // Update step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-active');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-completed');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = '<i class="fas fa-check"></i>';
            
            // Move to next step
            currentStep++;
            
            // Show next step
            document.getElementById(`proposalStep${currentStep}`).classList.remove('hidden');
            
            // Update next step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-pending');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-active');
            
            // Update previews
            if (currentStep === 2) {
                updateBudgetPreview();
            } else if (currentStep === 4) {
                updateReviewSummary();
                renderCostBreakdownChart();
            }
        }
    }
    
    function previousStep() {
        if (currentStep > 1) {
            // Hide current step
            document.getElementById(`proposalStep${currentStep}`).classList.add('hidden');
            
            // Update step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-active');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-pending');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = currentStep;
            
            // Move to previous step
            currentStep--;
            
            // Show previous step
            document.getElementById(`proposalStep${currentStep}`).classList.remove('hidden');
            
            // Update previous step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-completed');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-active');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = currentStep;
        }
    }
    
    function validateStep(step) {
        const form = document.getElementById('proposalForm');
        
        if (step === 1) {
            const requiredFields = ['proposal_title', 'department', 'project_type', 'start_date', 'end_date', 'project_objectives', 'project_scope', 'project_deliverables', 'implementation_timeline'];
            
            for (const fieldName of requiredFields) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field && !field.value.trim()) {
                    showToast(`Please fill in ${fieldName.replace('_', ' ')}`, 'error');
                    field.focus();
                    return false;
                }
            }
            
            // Validate dates
            const startDate = new Date(form.querySelector('[name="start_date"]').value);
            const endDate = new Date(form.querySelector('[name="end_date"]').value);
            
            if (endDate <= startDate) {
                showToast('End date must be after start date', 'error');
                return false;
            }
        }
        
        return true;
    }
    */
    
    // Calculate project duration
    function calculateDuration() {
        const startDate = document.querySelector('[name="start_date"]')?.value;
        const endDate = document.querySelector('[name="end_date"]')?.value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            
            const durationPreview = document.getElementById('durationPreview');
            if (durationPreview) {
                durationPreview.textContent = duration + ' days';
            }
        }
    }
    
    // Update budget preview
    function updateBudgetPreview() {
        // Check if cost breakdown fields exist (they don't in simplified form)
        const directCostField = document.querySelector('[name="direct_costs"]');
        const indirectCostField = document.querySelector('[name="indirect_costs"]');
        const equipmentCostField = document.querySelector('[name="equipment_costs"]');
        const travelCostField = document.querySelector('[name="travel_costs"]');
        const contingencyPercentageField = document.querySelector('[name="contingency_percentage"]');
        
        // If these fields don't exist, skip the preview update
        if (!directCostField || !indirectCostField || !equipmentCostField || !travelCostField) {
            return;
        }
        
        const direct = parseFloat(directCostField.value) || 0;
        const indirect = parseFloat(indirectCostField.value) || 0;
        const equipment = parseFloat(equipmentCostField.value) || 0;
        const travel = parseFloat(travelCostField.value) || 0;
        const contingencyPercentage = parseFloat(contingencyPercentageField?.value) || 5;
        
        // Calculate subtotal (before taxes)
        const subtotal = direct + indirect + equipment + travel;
        
        // Calculate contingency
        const contingencyAmount = subtotal * (contingencyPercentage / 100);
        
        // Calculate taxes
        const vat = subtotal * 0.12; // 12% VAT
        const withholdingTax = subtotal * 0.02; // 2% Withholding Tax
        
        // Calculate total budget (subtotal + contingency + VAT + withholding tax)
        const total = subtotal + contingencyAmount + vat + withholdingTax;
        
        // Update preview displays (with null checks)
        const subtotalPreview = document.getElementById('subtotalPreview');
        const vatPreview = document.getElementById('vatPreview');
        const wtaxPreview = document.getElementById('wtaxPreview');
        const totalBudgetPreview = document.getElementById('totalBudgetPreview');
        
        if (subtotalPreview) subtotalPreview.textContent = formatCurrency(subtotal);
        if (vatPreview) vatPreview.textContent = formatCurrency(vat);
        if (wtaxPreview) wtaxPreview.textContent = formatCurrency(withholdingTax);
        if (totalBudgetPreview) totalBudgetPreview.textContent = formatCurrency(total);
        
        // Update contingency amount field (with null check)
        const contingencyAmountField = document.querySelector('[name="contingency_amount"]');
        if (contingencyAmountField) {
            contingencyAmountField.value = contingencyAmount.toFixed(2);
        }
        
        // Update hidden total budget field (with null check)
        const totalBudgetField = document.getElementById('totalBudgetField');
        if (totalBudgetField) {
            totalBudgetField.value = total.toFixed(2);
        }
    }
    
    function updateTotalBudget() {
        updateBudgetPreview();
    }
    
    function updateContingency(percentage) {
        document.getElementById('contingencyPercentageDisplay').textContent = percentage + '%';
        document.querySelector('[name="contingency_percentage"]').value = percentage;
        updateBudgetPreview();
    }
    
    function updatePriorityPreview(priority) {
        document.getElementById('priorityPreview').textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
    }
    
    // Add cost item
    function addCostItem() {
        const type = document.getElementById('itemType').value;
        const description = document.getElementById('itemDescription').value;
        const quantity = parseFloat(document.getElementById('itemQuantity').value) || 1;
        const unitCost = parseFloat(document.getElementById('itemUnitCost').value) || 0;
        const totalCost = quantity * unitCost;
        
        if (!description.trim()) {
            showToast('Please enter a description', 'error');
            return;
        }
        
        if (unitCost <= 0) {
            showToast('Please enter a valid unit cost', 'error');
            return;
        }
        
        const item = {
            type: type,
            description: description,
            quantity: quantity,
            unitCost: unitCost,
            totalCost: totalCost
        };
        
        costItems.push(item);
        renderCostItems();
        
        // Update relevant cost category
        updateCostCategory(type, totalCost);
        
        // Clear form
        document.getElementById('itemDescription').value = '';
        document.getElementById('itemQuantity').value = 1;
        document.getElementById('itemUnitCost').value = '';
        
        showToast('Cost item added', 'success');
    }
    
    function updateCostCategory(type, amount) {
        const fieldMap = {
            'direct': 'direct_costs',
            'indirect': 'indirect_costs',
            'equipment': 'equipment_costs',
            'travel': 'travel_costs'
        };
        
        const field = document.querySelector(`[name="${fieldMap[type]}"]`);
        if (field) {
            const currentValue = parseFloat(field.value) || 0;
            field.value = (currentValue + amount).toFixed(2);
            updateBudgetPreview();
        }
    }
    
    function renderCostItems() {
        const table = document.getElementById('costItemsTable');
        table.innerHTML = '';
        
        costItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-3 border-b">
                    <span class="px-2 py-1 text-xs rounded ${getCostTypeColor(item.type)}">
                        ${costCategories[item.type] || item.type}
                    </span>
                </td>
                <td class="px-4 py-3 border-b">${escapeHtml(item.description)}</td>
                <td class="px-4 py-3 border-b">${item.quantity}</td>
                <td class="px-4 py-3 border-b">${formatCurrency(item.unitCost)}</td>
                <td class="px-4 py-3 border-b font-bold">${formatCurrency(item.totalCost)}</td>
                <td class="px-4 py-3 border-b">
                    <button onclick="removeCostItem(${index})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            table.appendChild(row);
        });
    }
    
    function getCostTypeColor(type) {
        const colors = {
            'direct': 'bg-pink-100 text-pink-800',
            'indirect': 'bg-blue-100 text-blue-800',
            'equipment': 'bg-amber-100 text-amber-800',
            'travel': 'bg-emerald-100 text-emerald-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }
    
    function removeCostItem(index) {
        const item = costItems[index];
        
        // Remove from cost category
        updateCostCategory(item.type, -item.totalCost);
        
        costItems.splice(index, 1);
        renderCostItems();
        showToast('Cost item removed', 'success');
    }
    
    // Update proposal sub-categories
    function updateProposalSubCategories(category) {
        const subSelect = document.getElementById('subCategorySelect');
        subSelect.innerHTML = '<option value="">Select Sub-category</option>';
        
        if (category && tnvsCategories[category]) {
            tnvsCategories[category].forEach(sub => {
                const option = document.createElement('option');
                option.value = sub;
                option.textContent = sub;
                subSelect.appendChild(option);
            });
        }
    }
    
    // Update review summary
    function updateReviewSummary() {
        const direct = parseFloat(document.querySelector('[name="direct_costs"]').value) || 0;
        const indirect = parseFloat(document.querySelector('[name="indirect_costs"]').value) || 0;
        const equipment = parseFloat(document.querySelector('[name="equipment_costs"]').value) || 0;
        const travel = parseFloat(document.querySelector('[name="travel_costs"]').value) || 0;
        const contingency = parseFloat(document.querySelector('[name="contingency_amount"]').value) || 0;
        const total = parseFloat(document.getElementById('totalBudgetField').value) || 0;
        
        // Update amounts
        document.getElementById('reviewDirectCosts').textContent = formatCurrency(direct);
        document.getElementById('reviewIndirectCosts').textContent = formatCurrency(indirect);
        document.getElementById('reviewEquipmentCosts').textContent = formatCurrency(equipment);
        document.getElementById('reviewTravelCosts').textContent = formatCurrency(travel);
        document.getElementById('reviewContingency').textContent = formatCurrency(contingency);
        document.getElementById('reviewTotalBudget').textContent = formatCurrency(total);
        
        // Update percentages
        if (total > 0) {
            document.getElementById('reviewDirectPercentage').textContent = ((direct / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewIndirectPercentage').textContent = ((indirect / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewEquipmentPercentage').textContent = ((equipment / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewTravelPercentage').textContent = ((travel / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewContingencyPercentage').textContent = ((contingency / total) * 100).toFixed(1) + '%';
        }
    }
    
    // Render cost breakdown chart
    function renderCostBreakdownChart() {
        const direct = parseFloat(document.querySelector('[name="direct_costs"]').value) || 0;
        const indirect = parseFloat(document.querySelector('[name="indirect_costs"]').value) || 0;
        const equipment = parseFloat(document.querySelector('[name="equipment_costs"]').value) || 0;
        const travel = parseFloat(document.querySelector('[name="travel_costs"]').value) || 0;
        const contingency = parseFloat(document.querySelector('[name="contingency_amount"]').value) || 0;
        
        const data = [direct, indirect, equipment, travel, contingency];
        const labels = ['Direct Costs', 'Indirect Costs', 'Equipment & Supplies', 'Travel & Expenses', 'Contingency'];
        const colors = ['#ec4899', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6'];
        
        if (costBreakdownChart) {
            costBreakdownChart.destroy();
        }
        
        const ctx = document.getElementById('costBreakdownChart');
        costBreakdownChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Handle file selection
    function handleFileSelect(input) {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const fileElement = document.createElement('div');
            fileElement.className = 'file-preview';
            fileElement.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file text-gray-400 mr-2"></i>
                    <span class="text-sm">${escapeHtml(file.name)} (${formatFileSize(file.size)})</span>
                </div>
                <button type="button" onclick="removeFile(${i})" class="remove-file">
                    <i class="fas fa-times"></i>
                </button>
            `;
            fileList.appendChild(fileElement);
        }
    }
    
    // Remove file
    function removeFile(index) {
        const dt = new DataTransfer();
        const input = document.getElementById('supportingDocs');
        const { files } = input;
        
        for (let i = 0; i < files.length; i++) {
            if (index !== i) {
                dt.items.add(files[i]);
            }
        }
        
        input.files = dt.files;
        handleFileSelect(input);
    }
    
    // Handle file selection
    function handleFileSelect(input) {
        const files = input.files;
        const fileList = document.getElementById('fileList');
        
        if (!fileList) return;
        
        // Clear previous file list
        fileList.innerHTML = '';
        selectedFiles = Array.from(files);
        
        // Display selected files
        Array.from(files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded border border-gray-200';
            fileItem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file text-purple-600 mr-2"></i>
                    <span class="text-sm">${escapeHtml(file.name)}</span>
                    <span class="text-xs text-gray-500 ml-2">(${formatFileSize(file.size)})</span>
                </div>
                <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            `;
            fileList.appendChild(fileItem);
        });
    }
    
    // Remove file from selection
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        // Update file input
        const fileInput = document.getElementById('supportingDocs');
        if (fileInput) {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            handleFileSelect(fileInput);
        }
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Setup file upload
    function setupFileUpload() {
        const fileInput = document.getElementById('supportingDocs');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                handleFileSelect(this);
            });
        }
    }
    
    // Save draft
    async function saveDraft() {
        const form = document.getElementById('proposalForm');
        const formData = new FormData(form);
        formData.append('action', 'save_proposal_draft');
        
        try {
            showToast('Saving draft...', 'info');
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('newProposalModal');
                resetProposalForm();
                loadProposals();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred: ' + error.message, 'error');
        }
    }
    
    // Submit proposal - FIXED VERSION
    async function submitProposal(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Validation behavior matching Request Portal
        const proposalTitle = form.querySelector('[name="proposal_title"]')?.value.trim();
        const department = form.querySelector('[name="department"]')?.value;
        const fiscalYear = form.querySelector('[name="fiscal_year"]')?.value;
        const startDate = form.querySelector('[name="start_date"]')?.value;
        const endDate = form.querySelector('[name="end_date"]')?.value;
        const objectives = form.querySelector('[name="project_objectives"]')?.value.trim();
        const totalBudget = parseFloat(form.querySelector('[name="total_budget"]')?.value) || 0;
        const catId = document.getElementById('proposal_category_select').value;
        const subId = document.getElementById('subcategorySelect').value;
        
        if (!proposalTitle || !department || !fiscalYear || !startDate || !endDate || !objectives || !catId || !subId) {
            showToast('Please fill in all required fields marked with *', 'error');
            return;
        }

        // Strict Validation Check
        const subRecord = Object.values(glSubcategories).flat().find(s => s.id == subId);
        if (subRecord && subRecord.parent_id != catId) {
            showToast('Selected subcategory does not match the chosen category.', 'error');
            return;
        }

        const breakdownItems = document.getElementsByName('breakdown_account[]');
        if (breakdownItems.length === 0) {
            showToast("Please add at least one GL account to the budget breakdown.", "error");
            return;
        }

        if (totalBudget <= 0) {
            showToast('Amount must be greater than 0 and calculated from breakdown.', 'error');
            return;
        }
        
        try {
            const submitBtn = document.getElementById('submitProposalBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitBtn.disabled = true;

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message || 'Proposal submitted successfully!', 'success');
                closeModal('newProposalModal');
                loadProposals();
                loadStats();
                form.reset();
                document.getElementById('breakdownBody').innerHTML = `
                    <tr id="emptyBreakdownRow">
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 italic font-medium bg-gray-50/30">No GL accounts selected yet.</td>
                    </tr>`;
                document.getElementById('breakdownTotalDisplay').textContent = '₱ 0.00';
            } else {
                showToast(result.message || 'Failed to submit proposal', 'error');
            }
        } catch (error) {
            console.error('Error submitting proposal:', error);
            showToast('An unexpected error occurred', 'error');
        } finally {
            const submitBtn = document.getElementById('submitProposalBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Submit Proposal';
                submitBtn.disabled = false;
            }
        }
    }

    // Hierarchical COA Selection for Proposals
    const glCategories = <?= json_encode($gl_categories) ?>;
    const glSubcategories = <?= json_encode($gl_subcategories) ?>;
    const glAccountsBySub = <?= json_encode($gl_accounts_by_sub) ?>;

    function updateSubcategoriesProp() {
        const catId = document.getElementById('proposal_category_select').value;
        const subSelect = document.getElementById('subcategorySelect');
        const glSelect = document.getElementById('glSelect');
        const errorMsg = document.getElementById('subcategory-error');

        // Reset subcategory and GL select
        subSelect.innerHTML = '<option value="">Select a Subcategory</option>';
        subSelect.disabled = !catId;
        glSelect.innerHTML = '<option value="">Select Subcategory first...</option>';
        glSelect.disabled = true;
        errorMsg.classList.add('hidden');

        if (!catId) return;

        const subs = glSubcategories[catId] || [];
        // Sort subcategories alphabetically
        subs.sort((a, b) => a.name.localeCompare(b.name));

        subs.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = sub.name;
            subSelect.appendChild(option);
        });

        // Validation rule: Prevent saving if subcategory does not belong to category (implicit here)
    }

    function filterGLAccountsProp() {
        const subId = document.getElementById('subcategorySelect').value;
        const glSelect = document.getElementById('glSelect');
        const catId = document.getElementById('proposal_category_select').value;
        const errorMsg = document.getElementById('subcategory-error');

        if (!subId) {
            glSelect.disabled = true;
            glSelect.innerHTML = '<option value="">Select Subcategory first...</option>';
            return;
        }

        // Strict Validation Check
        const subcategoryRecord = Object.values(glSubcategories).flat().find(s => s.id == subId);
        if (subcategoryRecord && subcategoryRecord.parent_id != catId) {
            errorMsg.classList.remove('hidden');
            errorMsg.textContent = "Selected subcategory does not match the chosen category.";
            glSelect.disabled = true;
            return;
        } else {
            errorMsg.classList.add('hidden');
        }

        glSelect.disabled = false;
        glSelect.innerHTML = '<option value="">Select a GL Account</option>';

        const accounts = glAccountsBySub[subId] || [];
        // Sort accounts alphabetically
        accounts.sort((a, b) => a.name.localeCompare(b.name));

        accounts.forEach(acc => {
            const option = document.createElement('option');
            option.value = acc.code;
            option.textContent = `${acc.code} - ${acc.name}`;
            glSelect.appendChild(option);
        });
    }

    function addAccountToPropBreakdown() {
        const glCode = document.getElementById('glSelect').value;
        const subId = document.getElementById('subcategorySelect').value;
        const catId = document.getElementById('proposal_category_select').value;
        
        if (!glCode || !subId || !catId) return;

        const accounts = glAccountsBySub[subId] || [];
        const acc = accounts.find(a => a.code == glCode);
        if (!acc) return;

        const catName = glCategories.find(c => c.id == catId)?.name || '';
        const subName = Object.values(glSubcategories).flat().find(s => s.id == subId)?.name || '';

        const existingRow = document.querySelector(`#breakdownBody tr[data-code="${glCode}"]`);
        if (existingRow) {
            showToast('This account is already in the list.', 'info');
            document.getElementById('glSelect').value = '';
            return;
        }

        const tbody = document.getElementById('breakdownBody');
        const emptyRow = document.getElementById('emptyBreakdownRow');
        if (emptyRow) emptyRow.remove();

        const row = document.createElement('tr');
        row.setAttribute('data-code', glCode);
        row.className = "hover:bg-indigo-50/30 transition-colors group";
        row.innerHTML = `
            <td class="px-4 py-4 font-mono font-bold text-indigo-600">${acc.code}</td>
            <td class="px-4 py-4 font-semibold text-gray-700">${acc.name}</td>
            <td class="px-4 py-4 text-gray-500 text-[10px] font-bold uppercase tracking-tight">${catName}</td>
            <td class="px-4 py-4 text-gray-500 text-[10px] font-bold uppercase tracking-tight">${subName}</td>
            <td class="px-4 py-4">
                <div class="relative max-w-[160px] ml-auto">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400 font-bold text-[10px]">₱</span>
                    <input type="number" name="proposal_items[${glCode}][total_cost]" required step="0.01" min="0" oninput="calcPropTotal()" class="w-full pl-7 pr-3 py-2.5 border border-indigo-100 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-right font-black text-gray-800 bg-white shadow-sm" placeholder="0.00">
                    <input type="hidden" name="proposal_items[${glCode}][description]" value="${acc.name}">
                    <input type="hidden" name="proposal_items[${glCode}][category_id]" value="${catId}">
                    <input type="hidden" name="proposal_items[${glCode}][subcategory_id]" value="${subId}">
                    <input type="hidden" name="proposal_items[${glCode}][type]" value="direct">
                    <input type="hidden" name="proposal_items[${glCode}][quantity]" value="1">
                    <input type="hidden" name="proposal_items[${glCode}][unit_cost]" value="0">
                    <input type="hidden" name="breakdown_account[]" value="${glCode}">
                </div>
            </td>
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="removePropRow(this)" class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100 border border-transparent hover:border-red-100 shadow-sm">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        document.getElementById('glSelect').value = '';
    }

    function removePropRow(btn) {
        btn.closest('tr').remove();
        const tbody = document.getElementById('breakdownBody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyBreakdownRow">
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400 italic font-medium bg-gray-50/30">No GL accounts selected yet.</td>
                </tr>`;
        }
        calcPropTotal();
    }

    function calcPropTotal() {
        const amounts = document.querySelectorAll('#breakdownBody input[type="number"]');
        let total = 0;
        amounts.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
            const row = input.closest('tr');
            const unitCostInput = row?.querySelector('input[name*="unit_cost"]');
            if (unitCostInput) unitCostInput.value = val;
        });
        
        document.getElementById('breakdownTotalDisplay').textContent = '₱ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalBudgetInput').value = total.toFixed(2);
    }
    
    // Reset proposal form
    function resetProposalForm() {
        currentStep = 1;
        costItems = [];
        
        // Reset steps (added null checks since form was simplified)
        for (let i = 1; i <= 4; i++) {
            const step = document.getElementById(`proposalStep${i}`);
            const indicator = document.getElementById(`step${i}Indicator`);
            
            if (step) {
                if (i === 1) step.classList.remove('hidden');
                else step.classList.add('hidden');
            }
            
            if (indicator) {
                if (i === 1) {
                    indicator.classList.add('step-active');
                    indicator.classList.remove('step-completed', 'step-pending');
                } else {
                    indicator.classList.remove('step-active', 'step-completed');
                    indicator.classList.add('step-pending');
                }
                indicator.textContent = i;
            }
        }
        
        // Reset form
        const form = document.getElementById('proposalForm');
        if (form) form.reset();
        
        const costItemsTable = document.getElementById('costItemsTable');
        if (costItemsTable) costItemsTable.innerHTML = '';
        
        const fileList = document.getElementById('fileList');
        if (fileList) fileList.innerHTML = '';
        
        // Reset previews (with null checks)
        const totalBudgetPreview = document.getElementById('totalBudgetPreview');
        if (totalBudgetPreview) totalBudgetPreview.textContent = '₱0.00';
        
        const durationPreview = document.getElementById('durationPreview');
        if (durationPreview) durationPreview.textContent = '0 days';
        
        const contingencyPreview = document.getElementById('contingencyPreview');
        if (contingencyPreview) contingencyPreview.textContent = '5%';
        
        const priorityPreview = document.getElementById('priorityPreview');
        if (priorityPreview) priorityPreview.textContent = 'Medium';
        
        // Reset date fields via auto-logic
        updateProposalDates();
        
        // Reset sub-category select
        const subCategorySelect = document.getElementById('subCategorySelect');
        if (subCategorySelect) subCategorySelect.innerHTML = '<option value="">Select Sub-category</option>';
    }
    
    // Modal functions
    function openModal(id) {
        console.log('openModal called with id:', id);
        const modal = document.getElementById(id);
        console.log('Modal element:', modal);
        if (modal) {
            modal.classList.remove('hidden');
            console.log('Modal opened successfully:', id);
            // IMPORTANT: Do NOT add overflow-hidden - it hides the scrollbar!
            
            // Auto-initialize dates when opening relevant modals
            if (id === 'newProposalModal') {
                updateProposalDates();
            } else if (id === 'createPlanModal') {
                updateBudgetDates();
                loadStats(); // Ensure we have the latest budget capacity basis
            }
        } else {
            console.error('Modal not found with id:', id);
        }
    }
    
    function closeModal(id) {
        if (id) {
            document.getElementById(id).classList.add('hidden');
        } else {
            document.querySelectorAll('.fixed.inset-0.z-50').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    }
    
    // Switch tabs in review modal
    function switchReviewTab(tabName) {
        const detailsTab = document.getElementById('detailsTab');
        const documentsTab = document.getElementById('documentsTab');
        const pastTab = document.getElementById('pastTransactionsTab');
        const detailsContent = document.getElementById('detailsTabContent');
        const documentsContent = document.getElementById('documentsTabContent');
        const pastContent = document.getElementById('pastTransactionsTabContent');
        
        // Reset all tabs
        [detailsTab, documentsTab, pastTab].forEach(tab => {
            if (tab) {
                tab.classList.remove('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
                tab.classList.add('border-transparent', 'text-gray-500');
            }
        });
        
        [detailsContent, documentsContent, pastContent].forEach(content => {
            if (content) content.classList.add('hidden');
        });
        
        if (tabName === 'details') {
            detailsTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            detailsTab.classList.remove('border-transparent', 'text-gray-500');
            detailsContent.classList.remove('hidden');
        } else if (tabName === 'documents') {
            documentsTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            documentsTab.classList.remove('border-transparent', 'text-gray-500');
            documentsContent.classList.remove('hidden');
        } else if (tabName === 'past_transactions') {
            pastTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            pastTab.classList.remove('border-transparent', 'text-gray-500');
            pastContent.classList.remove('hidden');
        }
    }

    // Modal Action Handlers
    let currentEditItem = null;

    function openCommentModal(category) {
        document.getElementById('commentCategoryLabel').textContent = `Expense Account: ${category}`;
        document.getElementById('commentText').value = '';
        document.getElementById('commentType').value = 'internal_note';
        document.getElementById('commentPriority').value = 'normal';
        openModal('addCommentModal');
    }

    function submitComment() {
        const text = document.getElementById('commentText').value;
        const type = document.getElementById('commentType').value;
        const priority = document.getElementById('commentPriority').value;

        if (!text.trim()) {
            showToast('Please enter a comment', 'warning');
            return;
        }

        // For now, just show success and close
        showToast('Comment posted successfully', 'success');
        closeModal('addCommentModal');
    }

    function openEditLineItem(category, requested, recommended) {
        currentEditItem = { category, requested, recommended };
        
        document.getElementById('editCategory').value = category;
        document.getElementById('editRequestedAmount').value = requested;
        document.getElementById('editRecommendedAmount').value = recommended;
        document.getElementById('editAdjustmentReason').value = '';
        
        updateEditVariance();
        openModal('editLineItemModal');
    }

    function updateEditVariance() {
        const requested = parseFloat(document.getElementById('editRequestedAmount').value) || 0;
        const recommended = parseFloat(document.getElementById('editRecommendedAmount').value) || 0;
        
        // Prev Variance (from initial open)
        const prevRequested = currentEditItem.requested;
        const prevRecommended = currentEditItem.recommended;
        const prevVariance = prevRecommended - prevRequested;
        
        // New Variance
        const newVariance = recommended - requested;
        
        const prevVarEl = document.getElementById('editPrevVariance');
        const newVarEl = document.getElementById('editNewVariance');
        
        prevVarEl.textContent = (prevVariance >= 0 ? '+' : '') + formatCurrency(prevVariance);
        prevVarEl.className = `font-bold ${prevVariance >= 0 ? 'text-emerald-600' : 'text-red-600'}`;
        
        newVarEl.textContent = (newVariance >= 0 ? '+' : '') + formatCurrency(newVariance);
        newVarEl.className = `font-bold ${newVariance >= 0 ? 'text-emerald-600' : 'text-red-600'}`;
    }

    async function saveLineItemEdit() {
        // Implementation for saving the edit
        showToast('Changes saved successfully', 'success');
        closeModal('editLineItemModal');
        // Ideally reload the review modal data here
    }

    function openAdjustPercentage(category, currentAmount) {
        currentEditItem = { category, currentAmount };
        
        document.getElementById('adjustCategoryLabel').textContent = `Expense Account: ${category}`;
        document.getElementById('adjustCurrentAmount').value = formatCurrency(currentAmount);
        document.getElementById('adjustPercentage').value = '';
        document.getElementById('adjustNewAmount').value = formatCurrency(currentAmount);
        document.getElementById('adjustReason').value = '';
        
        openModal('adjustPercentageModal');
    }

    function calculateNewAmount() {
        const currentAmount = currentEditItem.currentAmount;
        const percentage = parseFloat(document.getElementById('adjustPercentage').value) || 0;
        
        const adjustment = currentAmount * (percentage / 100);
        const newAmount = currentAmount + adjustment;
        
        document.getElementById('adjustNewAmount').value = formatCurrency(newAmount);
    }

    function applyPercentageAdjustment() {
        const reason = document.getElementById('adjustReason').value;
        if (!reason.trim()) {
            showToast('Please provide an adjustment reason', 'warning');
            return;
        }
        
        showToast('Percentage adjustment applied', 'success');
        closeModal('adjustPercentageModal');
    }
    
    // Toggle filter menu
    function toggleFilterMenu(event, menuId) {
        event.stopPropagation();
        
        // Close all other menus first
        document.querySelectorAll('.filter-menu').forEach(menu => {
            if (menu.id !== menuId) menu.classList.add('hidden');
        });
        
        const menu = document.getElementById(menuId);
        menu.classList.toggle('hidden');
    }
    
    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.filter-menu') && !event.target.closest('.filter-btn-sleek')) {
            document.querySelectorAll('.filter-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    function applyFilters(type) {
        if (type === 'plans') {
            document.getElementById('planFilterMenu').classList.add('hidden');
            currentPage.plans = 1;
            loadPlans();
        } else if (type === 'proposals') {
            document.getElementById('proposalFilterMenu').classList.add('hidden');
            currentPage.proposals = 1;
            loadProposals();
        }
    }

    function resetFilters(type) {
        if (type === 'plans') {
            document.getElementById('filterYear').value = "0";
            applyFilters('plans');
        } else if (type === 'proposals') {
            document.getElementById('proposalFilterDepartment').value = "";
            document.getElementById('proposalFilterYear').value = "0";
            applyFilters('proposals');
        }
    }
    
    // Toggle my proposals
    function toggleMyProposals() {
        myProposalsOnly = !myProposalsOnly;
        const btn = document.getElementById('myProposalsBtn');
        if (myProposalsOnly) {
            btn.classList.add('bg-blue-100', 'text-blue-800');
            btn.classList.remove('border-gray-300');
        } else {
            btn.classList.remove('bg-blue-100', 'text-blue-800');
            btn.classList.add('border-gray-300');
        }
        currentPage.proposals = 1;
        loadProposals();
    }
    
    // Switch proposal type
    function switchProposalType(type) {
        currentProposalType = type;
        
        // Update tab styles
        document.querySelectorAll('.proposal-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.getElementById(`tab${type.charAt(0).toUpperCase() + type.slice(1)}`).classList.add('active');
        
        // Reload proposals
        currentPage.proposals = 1;
        loadProposals();
    }
    
    // Load stats
    
    // Load plans
    async function loadPlans() {
        const search = document.getElementById('searchInput').value;
        const year = document.getElementById('filterYear').value;
        const page = currentPage.plans;
        
        const params = new URLSearchParams({
            ajax: 'load_plans',
            page: page,
            search: search
        });
        
        if (year && year != '0') params.append('year', year);
        
        // Show loading state (subtle)
        document.getElementById('plansTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('plansTableBody').style.opacity = '1';
            document.getElementById('plansLoading').classList.add('hidden');
            
            if (data.success) {
                // Update counts
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('plansPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                // Render table
                const tableBody = document.getElementById('plansTableBody');
                if(tableBody) tableBody.innerHTML = '';
                
                let tableHtml = '';
                if (data.plans && data.plans.length > 0) {
                    data.plans.forEach(plan => {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch(plan.status) {
                            case 'approved':
                                statusClass = 'badge-approved';
                                statusText = 'Approved';
                                break;
                            case 'pending_review':
                                statusClass = 'badge-pending_review';
                                statusText = 'Pending Review';
                                break;
                            case 'archived':
                                statusClass = 'badge-archived';
                                statusText = 'Archived';
                                break;
                            default:
                                statusClass = 'badge-draft';
                                statusText = plan.status;
                        }
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(plan.plan_name)}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold uppercase tracking-wider">${escapeHtml(plan.plan_type || 'Yearly')}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    ${plan.start_date ? new Date(plan.start_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'2-digit'}) : 'N/A'} - 
                                    ${plan.end_date ? new Date(plan.end_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'2-digit'}) : 'N/A'}
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">${formatCurrency(plan.planned_amount)}</td>
                                <td class="px-6 py-4">
                                    <span class="${statusClass}">${statusText}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex space-x-2">
                                        <button onclick="openViewPlan(${plan.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = tableHtml;
                    document.getElementById('plansEmpty').classList.add('hidden');
                } else {
                    document.getElementById('plansTableBody').innerHTML = ''; // Clear table if empty
                    document.getElementById('plansEmpty').classList.remove('hidden');
                }
                
                // Update pagination buttons
                const prevBtn = document.getElementById('plansPrevBtn');
                const nextBtn = document.getElementById('plansNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading plans:', error);
            document.getElementById('plansLoading').classList.add('hidden');
            showToast('Error loading plans: ' + error.message, 'error');
        }
    }
    

    
    // Open archive plan modal

    
    // Load proposals
    async function loadProposals() {
        const search = document.getElementById('proposalSearchInput').value;
        const department = document.getElementById('proposalFilterDepartment').value;
        const year = document.getElementById('proposalFilterYear').value;
        const page = currentPage.proposals;
        
        const params = new URLSearchParams({
            ajax: 'load_proposals',
            page: page,
            search: search,
            my_proposals: myProposalsOnly,
            proposal_type: currentProposalType
        });
        
        if (department) params.append('department', department);
        if (year && year != '0') params.append('year', year);
        
        // Show loading (subtle)
        document.getElementById('proposalsTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('proposalsTableBody').style.opacity = '1';
            document.getElementById('proposalsLoading').classList.add('hidden');
            
            if (data.success) {
                // Update stats
                if (data.stats) {
                    document.getElementById('totalProposals').textContent = data.stats.total || 0;
                    document.getElementById('pendingProposals').textContent = data.stats.pending || 0;
                    document.getElementById('approvedStatCount').textContent = data.stats.approved || 0;
                    document.getElementById('rejectedProposals').textContent = data.stats.rejected || 0;
                    
                    // Update main navigation stats
                    const pendingEl = document.getElementById('pendingPlans');
                    if (pendingEl) {
                         pendingEl.textContent = data.stats.pending || 0;
                    }
                }
                
                // Render table
                let tableHtml = '';
                if (data.proposals && data.proposals.length > 0) {
                    data.proposals.forEach(proposal => {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch(proposal.status) {
                            case 'draft':
                                statusClass = 'badge-draft';
                                statusText = 'Draft';
                                break;
                            case 'submitted':
                                statusClass = 'badge-submitted';
                                statusText = 'Submitted';
                                break;
                            case 'pending_review':
                                statusClass = 'badge-pending_review';
                                statusText = 'Pending Review';
                                break;
                            case 'pending_executive':
                                statusClass = 'badge-pending_executive';
                                statusText = 'Pending Executive Approval';
                                break;
                            case 'approved':
                                statusClass = 'badge-approved';
                                statusText = 'Approved';
                                break;
                            case 'executive_approved':
                                statusClass = 'badge-executive_approved';
                                statusText = 'Executive Approved';
                                break;
                            case 'rejected':
                                statusClass = 'badge-rejected';
                                statusText = 'Rejected';
                                break;
                            default:
                                statusClass = 'badge-draft';
                                statusText = proposal.status;
                        }
                        
                        let priorityClass = '';
                        let priorityText = '';
                        
                        switch(proposal.priority_level) {
                            case 'low':
                                priorityClass = 'badge-priority-low';
                                priorityText = 'Low';
                                break;
                            case 'medium':
                                priorityClass = 'badge-priority-medium';
                                priorityText = 'Medium';
                                break;
                            case 'high':
                                priorityClass = 'badge-priority-high';
                                priorityText = 'High';
                                break;
                            case 'critical':
                                priorityClass = 'badge-priority-critical';
                                priorityText = 'Critical';
                                break;
                            default:
                                priorityClass = 'badge-priority-medium';
                                priorityText = proposal.priority_level;
                        }
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(proposal.proposal_title)}</div>
                                    <div class="text-sm text-gray-500">${proposal.proposal_code}</div>
                                </td>
                                <td class="px-6 py-4">${escapeHtml(proposal.department)}</td>
                                <td class="px-6 py-4 font-bold">${formatCurrency(proposal.requested_amount)}</td>
                                <td class="px-6 py-4">
                                    <span class="${priorityClass}">${priorityText}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="${statusClass}">${statusText}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="viewProposal(${proposal.id})" class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-eye mr-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('proposalsTableBody').innerHTML = tableHtml;
                    document.getElementById('proposalsEmpty').classList.add('hidden');
                } else {
                    document.getElementById('proposalsTableBody').innerHTML = '';
                    document.getElementById('proposalsEmpty').classList.remove('hidden');
                }
                
                // Update pagination
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('proposalsPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                const prevBtn = document.getElementById('proposalsPrevBtn');
                const nextBtn = document.getElementById('proposalsNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading proposals:', error);
            document.getElementById('proposalsLoading').classList.add('hidden');
            showToast('Error loading proposals: ' + error.message, 'error');
        }
    }
    
    // View proposal - FIXED VERSION
    async function viewProposal(proposalId) {
        try {
            const response = await fetch(`?ajax=get_proposal_details&proposal_id=${proposalId}`);
            const data = await response.json();
            
            if (data.success && data.proposal) {
                const proposal = data.proposal;
                currentReviewProposalId = proposalId;
                
                // Build proposal information HTML
                const totalBudget = parseFloat(proposal.total_budget) || 0;
                const statusText = (proposal.status || 'draft').replace('_', ' ').charAt(0).toUpperCase() + 
                                  (proposal.status || 'draft').replace('_', ' ').slice(1);
                
                // Get status badge color
                let statusClass = 'bg-gray-100 text-gray-800';
                switch(proposal.status) {
                    case 'draft': statusClass = 'bg-gray-100 text-gray-800'; break;
                    case 'submitted':
                    case 'pending_review': statusClass = 'bg-yellow-100 text-yellow-800'; break;
                    case 'approved': statusClass = 'bg-green-100 text-green-800'; break;
                    case 'rejected': statusClass = 'bg-red-100 text-red-800'; break;
                }
                
                const modalContent = `
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">${escapeHtml(proposal.proposal_title || 'Untitled Proposal')}</h4>
                                <p class="text-sm text-gray-600 mt-1">${proposal.proposal_code || `PROP-${proposal.id}`}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span>
                                <p class="text-xl font-bold text-purple-700 mt-2">${formatCurrency(totalBudget)}</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Submitted by: ${proposal.submitted_by || 'Unknown'} on ${new Date(proposal.submitted_at || proposal.created_at || new Date()).toLocaleDateString()}</p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Department</p>
                                <p class="font-medium text-sm">${proposal.department || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Project Type</p>
                                <p class="font-medium text-sm">${proposal.project_type || 'Operational'}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Fiscal Year</p>
                                <p class="font-medium text-sm">${proposal.fiscal_year || 'N/A'}${proposal.quarter ? ' Q' + proposal.quarter : ''}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Start Date</p>
                                <p class="font-medium text-sm">${proposal.start_date ? new Date(proposal.start_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">End Date</p>
                                <p class="font-medium text-sm">${proposal.end_date ? new Date(proposal.end_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <div class="border-t pt-3">
                            <p class="text-xs text-gray-500 mb-1">Description / Purpose</p>
                            <div class="bg-gray-50 rounded p-2 max-h-40 overflow-y-auto">
                                <p class="text-sm text-gray-700">${escapeHtml(proposal.project_objectives || proposal.justification || 'Not provided')}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('reviewModalContent').innerHTML = modalContent;
                
                // Fetch Budget data for table rendering
                    try {
                    const budgetRes = await fetch(`?ajax=get_budget&department=${encodeURIComponent(proposal.department)}&year=${proposal.fiscal_year}`);
                    const budgetData = await budgetRes.json();
                    
                    const glBudgets = budgetData.gl_budgets || {};
                    const glActuals = budgetData.gl_actuals || {};
                    
                    const breakdownContent = document.getElementById('costBreakdownContent');

                    if (proposal.detailed_breakdown_array && proposal.detailed_breakdown_array.length > 0) {
                        
                        // Bottom Section: Detailed Table
                        let tableRows = '';
                        let totalRequested = 0;
                        
                        proposal.detailed_breakdown_array.forEach(item => {
                            const accountCode = item.account_code || '';
                            const allocated = glBudgets[accountCode] || 0;
                            
                            // Sample Data Logic for demo
                            let actualLastYear = glActuals[accountCode] || 0;
                            if (actualLastYear === 0) {
                                // Generate a sample value between 70% and 120% of requested amount for demo
                                actualLastYear = parseFloat(item.amount) * (0.7 + Math.random() * 0.5);
                            }

                            const requested = parseFloat(item.amount) || 0;
                            const financeRec = requested; // Same as requested by default
                            const variance = financeRec - requested;
                            
                            totalRequested += requested;

                            // Category icon based on category name
                            let categoryIcon = 'fa-folder';
                            const catLower = (item.category || '').toLowerCase();
                            if (catLower.includes('salary') || catLower.includes('benefit')) categoryIcon = 'fa-user';
                            else if (catLower.includes('marketing')) categoryIcon = 'fa-bullhorn';
                            else if (catLower.includes('software') || catLower.includes('tool')) categoryIcon = 'fa-laptop';

                            tableRows += `
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-5 py-4 border-b border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                                <i class="fas ${categoryIcon} text-indigo-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">${escapeHtml(item.name || item.description || 'General')}</p>
                                                <p class="text-xs text-gray-500">${escapeHtml(item.account_code || 'N/A')}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-gray-900">${formatCurrency(requested)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-gray-500">${formatCurrency(actualLastYear)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-emerald-600">${formatCurrency(financeRec)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold ${variance >= 0 ? 'text-emerald-600' : 'text-red-600'}">${variance >= 0 ? '+' : ''}${formatCurrency(variance)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openCommentModal('${escapeHtml(item.name || item.description || 'General')}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Add Comment">
                                                <i class="fas fa-comment-alt"></i>
                                            </button>
                                            <button onclick="openEditLineItem('${escapeHtml(item.name || item.description || 'General')}', ${requested}, ${financeRec})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Edit Amount">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openAdjustPercentage('${escapeHtml(item.name || item.description || 'General')}', ${financeRec})" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" title="Adjust by Percentage">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        breakdownContent.innerHTML = tableRows;
                        
                        // Update final review amount
                        document.getElementById('finalReviewAmount').textContent = formatCurrency(totalRequested);
                        
                    } else if (proposal.items && proposal.items.length > 0) {
                        // Fallback for simple items
                        
                        let itemsHtml = '';
                        let totalRequested = 0;
                        
                        proposal.items.forEach(item => {
                            const desc = item.description || 'No description';
                            const total = parseFloat(item.total_cost || item.amount || 0);
                            
                            // Sample Data Logic for demo
                            let actualLastYear = 0;
                            // Generate a sample value between 70% and 120% of total for demo
                            actualLastYear = total * (0.7 + Math.random() * 0.5);

                            totalRequested += total;

                            itemsHtml += `
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-5 py-4 border-b border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                                <i class="fas fa-box text-gray-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">${escapeHtml(desc)}</p>
                                                <p class="text-xs text-gray-500">${escapeHtml(item.account_code || 'GL Account')}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-gray-900">${formatCurrency(total)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-gray-500">${formatCurrency(actualLastYear)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-emerald-600">${formatCurrency(total)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-emerald-600">0.00</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openCommentModal('${escapeHtml(desc)}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Add Comment">
                                                <i class="fas fa-comment-alt"></i>
                                            </button>
                                            <button onclick="openEditLineItem('${escapeHtml(desc)}', ${total}, ${total})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Edit Amount">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openAdjustPercentage('${escapeHtml(desc)}', ${total})" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" title="Adjust by Percentage">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        breakdownContent.innerHTML = itemsHtml;
                        
                        // Update final review amount
                        document.getElementById('finalReviewAmount').textContent = formatCurrency(totalRequested);
                    }

                } catch (e) {
                    console.error('Error loading budget data:', e);
                }
                
                // Document Viewer - Always initialize automated view, then check for files
                renderAutoGeneratedProposal(proposal);
                renderPastTransactions(proposal);

                displaySupportingDocuments(proposal.supporting_docs_array || []);
                
                // Update proposal code in header
                document.getElementById('viewProposalCode').textContent = proposal.proposal_code || `PROP-${proposal.id}`;
                
                // Ensure Details tab is active when modal opens
                switchReviewTab('details');
                
                // Open modal
                openModal('proposalReviewModal');
                
                // Show/hide action buttons based on status
                const approveBtn = document.querySelector('button[onclick="approveFromReview()"]');
                const rejectBtn = document.querySelector('button[onclick="showRejectFromReview()"]');
                
                if (proposal.status === 'pending_review' || proposal.status === 'submitted') {
                    approveBtn.classList.remove('hidden');
                    rejectBtn.classList.remove('hidden');
                } else {
                    approveBtn.classList.add('hidden');
                    rejectBtn.classList.add('hidden');
                }
            } else {
                showToast(data.message || 'Failed to load proposal details', 'error');
            }
        } catch (error) {
            console.error('Error loading proposal:', error);
            showToast('Error loading proposal details: ' + error.message, 'error');
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        
        let icon = 'fa-info-circle';
        let bgColor = 'bg-blue-50';
        let borderColor = 'border-blue-200';
        let textColor = 'text-blue-800';
        
        switch(type) {
            case 'success':
                icon = 'fa-check-circle';
                bgColor = 'bg-green-50';
                borderColor = 'border-green-200';
                textColor = 'text-green-800';
                break;
            case 'error':
                icon = 'fa-times-circle';
                bgColor = 'bg-red-50';
                borderColor = 'border-red-200';
                textColor = 'text-red-800';
                break;
            case 'warning':
                icon = 'fa-exclamation-triangle';
                bgColor = 'bg-yellow-50';
                borderColor = 'border-yellow-200';
                textColor = 'text-yellow-800';
                break;
        }
        
        toast.className = `toast ${bgColor} ${borderColor} ${textColor} border rounded-lg shadow-lg p-4 max-w-sm`;
        
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-3"></i>
                <div class="flex-1">${escapeHtml(message)}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
    
    // Approve proposal
    
    // Review modal action functions (matching payables_ia.php style)
    function showCustomConfirm(title, message, icon, onConfirm, colorClass = 'indigo') {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');
        const iconEl = document.getElementById('confirmModalIcon');
        const headerEl = document.getElementById('confirmModalHeader');
        const proceedBtn = document.getElementById('confirmProceedBtn');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.className = `fas ${icon} text-3xl`;
        
        // Dynamic colors
        headerEl.className = `p-6 bg-gradient-to-r from-${colorClass}-600 to-${colorClass}-700 text-white`;
        proceedBtn.className = `flex-1 px-6 py-3 bg-${colorClass}-600 text-white rounded-xl font-bold hover:bg-${colorClass}-700 transition-all shadow-md hover:shadow-lg`;
        
        proceedBtn.onclick = async () => {
            closeModal('confirmModal');
            await onConfirm();
        };
        
        openModal('confirmModal');
    }

    async function approveFromReview() {
        if (!currentReviewProposalId) {
            showToast('No proposal selected', 'error');
            return;
        }
        
        showCustomConfirm(
            'Approve Proposal',
            'Are you sure you want to approve this budget proposal? This will mark it as approved for the next strategic phase.',
            'fa-check-circle',
            async () => {
                await updateProposalStatus(currentReviewProposalId, 'approved');
            },
            'green'
        );
    }
    
    function showRejectFromReview() {
        document.getElementById('rejectFormInReview').classList.remove('hidden');
    }
    
    function cancelRejectFromReview() {
        document.getElementById('rejectFormInReview').classList.add('hidden');
        document.getElementById('rejectReasonInReview').value = '';
    }
    
    async function confirmRejectFromReview() {
        const reason = document.getElementById('rejectReasonInReview').value.trim();
        
        if (!reason) {
            showToast('Please provide a reason for rejection', 'error');
            return;
        }
        
        if (!currentReviewProposalId) {
            showToast('No proposal selected', 'error');
            return;
        }
        
        await updateProposalStatus(currentReviewProposalId, 'rejected', reason);
        cancelRejectFromReview();
    }
    
    async function approveProposal() {
        await updateProposalStatus(currentReviewProposalId, 'approved');
    }
    
    // Load and update dashboard statistics/cards
    async function loadStats() {
        try {
            console.log('Loading stats...');
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`?ajax=get_stats&_=${timestamp}`);
            const data = await response.json();
            
            console.log('Stats data received:', data);
            
            if (data.success && data.stats) {
                const stats = data.stats;
                console.log('Updating cards with stats:', stats);
                
                // Update Revenue Card (New)
                const revenueEl = document.getElementById('totalRevenue');
                if (revenueEl) {
                    revenueEl.textContent = formatCurrency(stats.total_revenue || 0);
                }

                // Update Allocated Budget card
                const totalBudgetEl = document.getElementById('totalBudget');
                if (totalBudgetEl) {
                    totalBudgetEl.textContent = formatCurrency(stats.total_planned || 0);
                }
                
                const totalActual = stats.total_actual || 0;
                const totalPlanned = stats.total_planned || 0;
                const totalBudgetMetricEl = document.getElementById('totalBudgetMetric');
                if (totalBudgetMetricEl) {
                    totalBudgetMetricEl.innerHTML = 
                        `<span class="metric-neutral">vs Actual: ${formatCurrency(totalActual)}</span>`;
                }
                
                // Update Approved Plans card
                const approvedCount = stats.approved_plans || 0;
                const totalPlans = approvedCount + (stats.draft_plans || 0) + (stats.pending_plans || 0);
                const approvedPlansEl = document.getElementById('approvedPlans');
                if (approvedPlansEl) {
                    approvedPlansEl.textContent = approvedCount;
                }
                
                const approvedPlansMetricEl = document.getElementById('approvedPlansMetric');
                if (approvedPlansMetricEl) {
                    approvedPlansMetricEl.innerHTML = 
                        `<span class="metric-neutral">out of ${totalPlans} total</span>`;
                }
                
                // Update Pending Review card (Use Pending Plans count, matching the table)
                const pendingPlansEl = document.getElementById('pendingPlans');
                if (pendingPlansEl) {
                    pendingPlansEl.textContent = stats.pending_plans || 0;
                }
                
                const pendingPlansMetricEl = document.getElementById('pendingPlansMetric');
                if (pendingPlansMetricEl) {
                    pendingPlansMetricEl.innerHTML = 
                        `<span class="metric-neutral">plans pending review</span>`;
                }
                
                // Update Remaining (Unallocated) Budget card
                const remaining = stats.remaining_budget || 0;
                const totalRevenue = stats.total_revenue || 0;
                const totalAllocated = stats.total_planned || 0;
                
                const remainingBudgetEl = document.getElementById('remainingBudget');
                if (remainingBudgetEl) {
                    remainingBudgetEl.textContent = formatCurrency(remaining);
                }
                
                const remainingPercent = totalRevenue > 0 ? ((remaining / totalRevenue) * 100).toFixed(1) : 0;
                const remainingBudgetMetricEl = document.getElementById('remainingBudgetMetric');
                if (remainingBudgetMetricEl) {
                    remainingBudgetMetricEl.innerHTML = 
                        remainingPercent >= 0 ?
                        `<span class="metric-positive">${remainingPercent}% of Revenue</span>` :
                        `<span class="metric-negative">${remainingPercent}% (Over Allocated)</span>`;
                }

                console.log('Stats loaded successfully!');

                // Update global budget constraints
                globalTotalRevenue = stats.total_revenue || 0;
                globalRemainingBudget = stats.remaining_budget || 0;

                const modalBasis = document.getElementById('modal-available-budget');
                if (modalBasis) {
                    modalBasis.textContent = formatCurrency(globalRemainingBudget);
                }
                updateGlobalAllocationTotal();
            } else {
                console.error('Stats response not successful:', data);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }
    
    // Apply amount adjustment
    async function applyAmountAdjustment() {
        const adjustedAmount = document.getElementById('adjustedAmount').value;
        if (!adjustedAmount || isNaN(adjustedAmount) || parseFloat(adjustedAmount) <= 0) {
            showToast('Please enter a valid amount', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_proposal_status');
            formData.append('proposal_id', currentReviewProposalId);
            formData.append('status', 'pending_review');
            formData.append('adjusted_amount', adjustedAmount);
            formData.append('notes', `Amount adjusted from ${document.getElementById('originalAmount').textContent} to ${formatCurrency(adjustedAmount)}`);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(`Amount adjusted to ${formatCurrency(adjustedAmount)}`, 'success');
                // Update the display
                document.getElementById('reviewRequestedAmount').textContent = formatCurrency(adjustedAmount);
                document.getElementById('originalAmount').textContent = formatCurrency(adjustedAmount);
                
                // Reload proposal to update all data
                viewProposal(currentReviewProposalId);
                loadStats(); // Update dashboard cards
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error applying adjustment:', error);
            showToast('Error applying amount adjustment', 'error');
        }
    }
    
    // Show rejection section
    function showRejectionSection() {
        document.getElementById('rejectionSection').classList.remove('hidden');
    }
    
    // Hide rejection section
    function hideRejectionSection() {
        document.getElementById('rejectionSection').classList.add('hidden');
        document.getElementById('rejectionReason').value = '';
    }
    
    // Submit rejection
    async function submitRejection() {
        const reason = document.getElementById('rejectionReason').value;
        if (!reason.trim()) {
            showToast('Please provide a rejection reason', 'error');
            return;
        }
        
        await updateProposalStatus(currentReviewProposalId, 'rejected', reason);
        hideRejectionSection();
    }
    
    // Update proposal status
    async function updateProposalStatus(proposalId, status, reason = '') {
        const formData = new FormData();
        formData.append('action', 'update_proposal_status');
        formData.append('proposal_id', proposalId);
        formData.append('status', status);
        
        // Get adjusted amount if it exists in the review modal
        const adjustedInput = document.getElementById('reviewAdjustedAmount');
        if (adjustedInput) {
            const adjustedVal = adjustedInput.value.replace(/[^0-9.]/g, '');
            if (adjustedVal && !isNaN(adjustedVal) && parseFloat(adjustedVal) > 0) {
                formData.append('adjusted_amount', adjustedVal);
            }
        }
        
        if (reason) {
            if (status === 'rejected') {
                formData.append('rejection_reason', reason);
            }
            formData.append('notes', reason);
        }
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('proposalReviewModal');
                loadProposals();
                loadPlans();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Add proposal comment
    async function addProposalComment(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'add_proposal_comment');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                form.reset();
                // Reload proposal to show new comment
                viewProposal(currentReviewProposalId);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Save forecast
    async function saveForecast(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'save_forecast');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('forecastModal');
                form.reset();
                loadMonitoringData();
                loadStats(); // Update dashboard cards
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Archive plan
    async function archivePlan(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'archive_plan');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('archiveModal');
                form.reset();
                loadPlans();
                loadArchived();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Restore plan
    async function restorePlan(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'restore_plan');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('restoreModal');
                form.reset();
                loadPlans();
                loadArchived();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Open restore plan modal
    function openRestorePlan(archiveId) {
        document.getElementById('restoreArchiveId').value = archiveId;
        openModal('restoreModal');
    }
    
    // Load monitoring data
    async function loadMonitoringData() {
        let year, department;
        if (currentMonitoringView === 'department') {
            year = document.getElementById('deptMonitoringYear')?.value || <?php echo $current_year; ?>;
            department = document.getElementById('deptMonitoringDepartment')?.value || '';
        } else {
            year = document.getElementById('monitoringYear')?.value || <?php echo $current_year; ?>;
            department = document.getElementById('monitoringDepartment')?.value || '';
        }
        
        const params = new URLSearchParams({ 
            ajax: 'load_monitoring_data',
            year: year,
            page: currentPage.monitoring
        });
        if (department) params.append('department', department);
        
        // Show loading (subtle)
        document.getElementById('monitoringTableBody').style.opacity = '0.5';
        document.getElementById('deptMonitoringTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('monitoringTableBody').style.opacity = '1';
            document.getElementById('deptMonitoringTableBody').style.opacity = '1';
            document.getElementById('monitoringLoading')?.classList.add('hidden');
            document.getElementById('deptMonitoringLoading')?.classList.add('hidden');
            
            if (data.success) {
                // Update alerts count
                document.getElementById('alertCount').textContent = data.alerts?.length || 0;
                document.getElementById('alertsCount').textContent = data.alerts?.length || 0;
                
                // Render alerts
                let alertsHtml = '';
                if (data.alerts && data.alerts.length > 0) {
                    data.alerts.forEach(alert => {
                        alertsHtml += `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-red-800">${escapeHtml(alert.message)}</p>
                                        <p class="text-xs text-red-600 mt-1">${new Date(alert.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    alertsHtml = '<p class="text-gray-500 text-center py-4">No active alerts</p>';
                }
                document.getElementById('alertsList').innerHTML = alertsHtml;
                
                // Render monitoring table by GL Category
                let tableHtml = '';
                if (data.monitoring_data && data.monitoring_data.length > 0) {
                    data.monitoring_data.forEach(item => {
                        const variance = item.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        const utilization = item.utilization || 0;
                        let utilizationClass = 'bg-green-500';
                        if (utilization > 90) utilizationClass = 'bg-red-500';
                        else if (utilization > 70) utilizationClass = 'bg-yellow-500';
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">${escapeHtml(item.category || 'Uncategorized')}</td>
                                <td class="px-6 py-4">${escapeHtml(item.gl_category || 'Uncategorized')}</td>
                                <td class="px-6 py-4">${item.dept_count} departments</td>
                                <td class="px-6 py-4">${formatCurrency(item.planned)}</td>
                                <td class="px-6 py-4">${formatCurrency(item.actual)}</td>
                                <td class="px-6 py-4 ${varianceClass}">
                                    ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                    (${variance >= 0 ? '+' : ''}${(item.variance_percentage || 0).toFixed(1)}%)
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full ${utilizationClass}" style="width: ${Math.min(utilization, 100)}%"></div>
                                        </div>
                                        <span class="text-sm">${utilization.toFixed(1)}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('monitoringTableBody').innerHTML = tableHtml;
                    
                    // Update pagination info
                    if (data.pagination) {
                        const { total, page, pages, per_page } = data.pagination;
                        const start = total === 0 ? 0 : ((page - 1) * per_page) + 1;
                        const end = Math.min(page * per_page, total);
                        document.getElementById('monitoringPaginationInfo').textContent = 
                            `Showing ${start} to ${end} of ${total} entries`;
                        
                        const prevBtn = document.getElementById('monitoringPrevBtn');
                        const nextBtn = document.getElementById('monitoringNextBtn');
                        
                        prevBtn.disabled = page <= 1;
                        nextBtn.disabled = page >= pages;
                    }
                }
                
                // Render monitoring table by department
                let deptTableHtml = '';
                if (data.dept_monitoring_data && data.dept_monitoring_data.length > 0) {
                    data.dept_monitoring_data.forEach(item => {
                        const variance = item.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        const utilization = item.utilization || 0;
                        let utilizationClass = 'bg-green-500';
                        if (utilization > 90) utilizationClass = 'bg-red-500';
                        else if (utilization > 70) utilizationClass = 'bg-yellow-500';
                        
                        deptTableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">${escapeHtml(item.department)}</td>
                                <td class="px-6 py-4">${item.category_count} categories</td>
                                <td class="px-6 py-4">${formatCurrency(item.planned)}</td>
                                <td class="px-6 py-4">${formatCurrency(item.actual)}</td>
                                <td class="px-6 py-4 ${varianceClass}">
                                    ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                    (${variance >= 0 ? '+' : ''}${(item.variance_percentage || 0).toFixed(1)}%)
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full ${utilizationClass}" style="width: ${Math.min(utilization, 100)}%"></div>
                                        </div>
                                        <span class="text-sm">${utilization.toFixed(1)}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('deptMonitoringTableBody').innerHTML = deptTableHtml;
                }
                
                // Render forecasts
                let forecastsHtml = '';
                if (data.forecasts && data.forecasts.length > 0) {
                    data.forecasts.forEach(forecast => {
                        const variance = forecast.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        
                        forecastsHtml += `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">${escapeHtml(forecast.department)} - ${escapeHtml(forecast.category)}</p>
                                        <p class="text-xs text-blue-600 mt-1">${forecast.forecast_period}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold">${formatCurrency(forecast.forecasted_amount)}</p>
                                        <p class="text-sm ${varianceClass}">
                                            ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                            (${variance >= 0 ? '+' : ''}${(forecast.variance_percentage || 0).toFixed(1)}%)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    forecastsHtml = '<p class="text-gray-500 text-center py-4">No forecasts available</p>';
                }
                document.getElementById('forecastsList').innerHTML = forecastsHtml;
                
                // Update charts
                updateMonitoringChart(data.chart_data);
            }
        } catch (error) {
            console.error('Error loading monitoring data:', error);
            document.getElementById('monitoringLoading')?.classList.add('hidden');
            document.getElementById('deptMonitoringLoading')?.classList.add('hidden');
            showToast('Error loading monitoring data: ' + error.message, 'error');
        }
    }
    
    // Update monitoring chart
    function updateMonitoringChart(data) {
        if (!data || data.length === 0) return;
        
        const categories = data.map(item => item.category || 'Uncategorized').slice(0, 8);
        const planned = data.map(item => item.planned || 0).slice(0, 8);
        const actual = data.map(item => item.actual || 0).slice(0, 8);
        
        const ctx = document.getElementById('monitoringChart');
        
        if (monitoringChart) {
            monitoringChart.destroy();
        }
        
        monitoringChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'Planned',
                        data: planned,
                        backgroundColor: 'rgba(124, 58, 237, 0.7)',
                        borderColor: 'rgba(124, 58, 237, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual',
                        data: actual,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += formatCurrency(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update department chart
    async function updateDepartmentChart(year) {
        try {
            const response = await fetch(`?ajax=get_department_stats&year=${year}`);
            const data = await response.json();
            
            if (data.success && data.departments && data.departments.length > 0) {
                const departments = data.departments.map(dept => dept.department);
                const budgets = data.departments.map(dept => dept.budget || 0);
                const spent = data.departments.map(dept => dept.spent || 0);
                
                const ctx = document.getElementById('departmentChart');
                
                if (departmentChart) {
                    departmentChart.destroy();
                }
                
                departmentChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: departments,
                        datasets: [
                            {
                                label: 'Budget',
                                data: budgets,
                                backgroundColor: 'rgba(124, 58, 237, 0.7)',
                                borderColor: 'rgba(124, 58, 237, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Spent',
                                data: spent,
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += formatCurrency(context.raw);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error loading department chart data:', error);
        }
    }
    
    // Load archived
    async function loadArchived() {
        const page = currentPage.archived;
        
        // Show loading (subtle)
        document.getElementById('archivedTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?ajax=load_archived&page=${page}`);
            const data = await response.json();
            
            document.getElementById('archivedTableBody').style.opacity = '1';
            document.getElementById('archivedLoading').classList.add('hidden');
            
            if (data.success) {
                // Update counts
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('archivedPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                // Render table
                let tableHtml = '';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(archived => {
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(archived.plan_name)}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(archived.department)}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-slate-50 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider">${escapeHtml(archived.plan_type || 'Yearly')}</span>
                                </td>
                                <td class="px-6 py-4 text-sm">${new Date(archived.archived_at).toLocaleDateString()}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">${archived.archive_reason || 'Archived'}</span>
                                </td>
                                <td class="px-6 py-4 font-bold">${formatCurrency(archived.planned_amount)}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="openRestorePlan(${archived.id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Restore Plan">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('archivedTableBody').innerHTML = tableHtml;
                } else {
                    document.getElementById('archivedTableBody').innerHTML = '';
                }
                
                // Update pagination buttons
                const prevBtn = document.getElementById('archivedPrevBtn');
                const nextBtn = document.getElementById('archivedNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading archived:', error);
            document.getElementById('archivedLoading').classList.add('hidden');
            showToast('Error loading archived data: ' + error.message, 'error');
        }
    }
    
    // Pagination functions
    function previousPage(type) {
        if (currentPage[type] > 1) {
            currentPage[type]--;
            if (type === 'plans') loadPlans();
            else if (type === 'proposals') loadProposals();
            else if (type === 'archived') loadArchived();
        }
    }
    
    function nextPage(type) {
        currentPage[type]++;
        if (type === 'plans') loadPlans();
        else if (type === 'proposals') loadProposals();
        else if (type === 'archived') loadArchived();
    }
    
    // Debounce search functions
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage.plans = 1;
            loadPlans();
        }, 500);
    }
    
    function debounceProposalSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage.proposals = 1;
            loadProposals();
        }, 500);
    }
    // Create Plan Modal Logic
    function validateField(input) {
        const msg = input.parentElement.querySelector('.validation-msg');
        if (!input.value.trim()) {
            input.classList.add('border-red-500', 'bg-red-50');
            if (msg) msg.classList.remove('hidden');
        } else {
            input.classList.remove('border-red-500', 'bg-red-50');
            if (msg) msg.classList.add('hidden');
        }
        validateCreateBudgetPlan();
    }

    function validateCreateBudgetPlan() {
        const modal = document.getElementById('createPlanModal');
        if (!modal || modal.classList.contains('hidden')) return;

        const form = document.getElementById('createPlanForm');
        const submitBtn = document.getElementById('submit-btn');
        if (!form || !submitBtn) return;

        const requiredFields = form.querySelectorAll('input[required], textarea[required]');
        let allFilled = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFilled = false;
            }
        });

        const totalBudgetInput = form.querySelector('input[name="total_budget"]');
        const totalBudget = parseFloat(totalBudgetInput.value) || 0;
        
        let allocationSum = 0;
        form.querySelectorAll('.gl-input-actual').forEach(input => {
            const val = parseFloat(input.value) || 0;
            allocationSum += val;
        });

        const isSumValid = allocationSum > 0 && (globalRemainingBudget === 0 || allocationSum <= globalRemainingBudget);
        
        if (allFilled && isSumValid) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        }
    }

    // Add listeners for real-time validation
    document.addEventListener('input', function(e) {
        if (e.target.closest('#createPlanForm')) {
            validateCreateBudgetPlan();
        }
    });

    // defunct impact circle logic removed

    function previewFile(input) {
        const file = input.files[0];
        const previewName = document.getElementById('file-preview-name');
        
        if (file) {
            if (previewName) previewName.textContent = file.name;
        } else {
            if (previewName) previewName.textContent = 'Drop file here or click to upload';
        }
    }

    function setBudgetType(el, type) {
        const form = document.getElementById('createPlanForm');
        if (!form) return;

        // Update hidden input
        document.getElementById('plan_type_input').value = type;
        
        // Update labels for computed section
        const wageLabel = document.getElementById('wage-label');
        const taxLabel = document.getElementById('tax-label');
        if (wageLabel) wageLabel.textContent = (type === 'yearly' ? 'Yearly' : 'Monthly') + " Base Wage";
        if (taxLabel) taxLabel.textContent = (type === 'yearly' ? 'Yearly' : 'Monthly') + " Taxation Cost";
        
        validateCreateBudgetPlan();
        updateBudgetDates();
    }


    function previewFile(input) {
        const file = input.files[0];
        const previewName = document.getElementById('file-preview-name');
        const previewSize = document.getElementById('file-preview-size');
        const previewIcon = document.getElementById('file-preview-icon');
        
        if (file) {
            if (previewName) previewName.textContent = file.name;
            if (previewSize) previewSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            if (previewIcon) {
                previewIcon.classList.remove('text-slate-300');
                previewIcon.classList.add('text-indigo-600');
                
                if (file.type.includes('image')) {
                    previewIcon.innerHTML = '<i class="fas fa-file-image text-3xl"></i>';
                } else if (file.type.includes('pdf')) {
                    previewIcon.innerHTML = '<i class="fas fa-file-pdf text-3xl"></i>';
                } else {
                    previewIcon.innerHTML = '<i class="fas fa-file-csv text-3xl"></i>';
                }
            }
        }
    }

    // Handle Create Plan Form Submission
    // Handle Create Plan Form Submission
    document.getElementById('createPlanForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'create_plan');
        
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (pErr) {
                console.error('Create Plan Single Modal Validation Error: JSON Parse Failed', text);
                throw new Error("Create Plan Single Modal Validation Error: Server returned invalid JSON");
            }
            
            if (data.success) {
                showToast('Budget plan created successfully!', 'success');
                closeModal('createPlanModal');
                
                // Reset Form
                this.reset();
                const feedback = document.getElementById('allocation-status-feedback');
                if (feedback) feedback.innerHTML = '';
                
                loadPlans(); // Refresh the list
                loadStats(); // Update dashboard cards
            } else {
                console.error("Create Plan Single Modal Validation Error:", data.message);
                showToast(data.message || 'Failed to submit budget plan', 'error');
            }
        } catch (error) {
            console.error('Create Plan Single Modal Validation Error:', error);
            showToast('An error occurred while submitting the plan.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            validateCreateBudgetPlan();
        }
    });
    
    // Open View Plan Modal
    async function openViewPlan(planId) {
        try {
            const response = await fetch(`?ajax=get_plan_details&plan_id=${planId}`);
            const data = await response.json();
            
            if (data.success && data.plan) {
                const plan = data.plan;
                
                // Populate modal fields
                document.getElementById('viewPlanTitle').textContent = plan.plan_name || 'Plan Details';
                document.getElementById('viewPlanCode').textContent = plan.plan_code || ('BATCH-' + plan.created_at.replace(/[- :]/g, ''));
                document.getElementById('viewPlanAmount').textContent = formatCurrency(parseFloat(plan.planned_amount || 0));
                document.getElementById('viewPlanFooterAmount').textContent = formatCurrency(parseFloat(plan.planned_amount || 0));
                document.getElementById('viewPlanYear').textContent = plan.plan_year || '';
                document.getElementById('viewPlanType').textContent = (plan.plan_type || 'yearly').toUpperCase();
                
                // Status Badge
                const statusBadge = document.getElementById('viewPlanStatusBadge');
                if (statusBadge) {
                    const status = (plan.status || 'approved').toLowerCase();
                    statusBadge.textContent = status;
                    statusBadge.className = 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest ';
                    if (status === 'approved') statusBadge.classList.add('bg-emerald-100', 'text-emerald-700', 'border', 'border-emerald-200');
                    else if (status === 'pending') statusBadge.classList.add('bg-amber-100', 'text-amber-700', 'border', 'border-amber-200');
                    else statusBadge.classList.add('bg-slate-100', 'text-slate-700', 'border', 'border-slate-200');
                }

                // Date Formatting
                const formatDateStr = (dateStr) => {
                    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return 'N/A';
                    try {
                        const parts = dateStr.split(/[- :]/);
                        if (parts.length >= 3) {
                            const d = new Date(parts[0], parts[1] - 1, parts[2]);
                            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        }
                        return dateStr;
                    } catch(e) { return dateStr; }
                };

                document.getElementById('viewPlanStartDate').textContent = formatDateStr(plan.start_date);
                document.getElementById('viewPlanEndDate').textContent = formatDateStr(plan.end_date);

                // Financial metrics
                document.getElementById('viewPlanRevenue').textContent = formatCurrency(parseFloat(plan.project_revenue || 0));
                document.getElementById('viewPlanImpact').textContent = (parseFloat(plan.impact_percentage || 0)).toFixed(2) + '%';
                document.getElementById('viewPlanTaxation').textContent = formatCurrency(parseFloat(plan.taxation_adj || 0));
                
                // Rationale & Document
                const rationaleEl = document.getElementById('viewPlanRationale');
                const breakdownBody = document.getElementById('viewPlanBreakdownBody');
                const docContainer = document.getElementById('viewPlanDocContainer');
                
                let rationaleText = plan.description || 'No rationale provided.';
                let breakdownToRender = plan.breakdown || [];

                // Handle justification doc
                if (plan.justification_doc) {
                    try {
                        const docs = JSON.parse(plan.justification_doc);
                        if (docs && docs.length > 0) {
                            docContainer.classList.remove('hidden');
                            document.getElementById('viewPlanDocLink').href = 'uploads/budget_proposals/' + docs[0];
                            document.getElementById('viewPlanDocName').textContent = docs[0];
                        } else { docContainer.classList.add('hidden'); }
                    } catch(e) { docContainer.classList.add('hidden'); }
                } else { docContainer.classList.add('hidden'); }

                // (Definitions already handled above)

                if (typeof plan.description === 'string' && plan.description.trim().startsWith('{')) {
                    try {
                        const parsed = JSON.parse(plan.description);
                        if (parsed.justification) rationaleText = parsed.justification;
                    } catch(e) {}
                }

                if (rationaleEl) rationaleEl.textContent = rationaleText;
                if (breakdownBody) breakdownBody.innerHTML = '';
                
                let renderTotal = 0;
                // Double-Layer Fallback: If both server-side and client-side breakdown are empty, use primary record
                if (!breakdownToRender || !Array.isArray(breakdownToRender) || breakdownToRender.length === 0) {
                    breakdownToRender = [{
                        name: plan.plan_name || 'Budget Item',
                        category: plan.category || 'General',
                        subcategory: plan.sub_category || 'Miscellaneous',
                        amount: parseFloat(plan.planned_amount || 0)
                    }];
                }

                let currentCategory = '';
                let currentSubcategory = '';

                breakdownToRender.forEach(item => {
                    renderTotal += parseFloat(item.amount || 0);
                    if (breakdownBody) {
                        // Category Header
                        if (item.category && item.category !== currentCategory) {
                            currentCategory = item.category;
                            currentSubcategory = ''; // Reset subcategory on category change
                            const headRow = document.createElement('tr');
                            headRow.innerHTML = `
                                <td colspan="2" class="px-6 py-2.5 bg-slate-100 text-[10px] font-black text-indigo-800 uppercase tracking-widest border-y border-slate-200">
                                    <i class="fas fa-folder-open mr-2 text-indigo-500"></i>${escapeHtml(currentCategory)}
                                </td>
                            `;
                            breakdownBody.appendChild(headRow);
                        }

                        // Subcategory Header
                        if (item.subcategory && item.subcategory !== currentSubcategory) {
                            currentSubcategory = item.subcategory;
                            const subRow = document.createElement('tr');
                            subRow.innerHTML = `
                                <td colspan="2" class="px-8 py-2 bg-slate-50/50 text-[9px] font-bold text-slate-500 uppercase tracking-tight border-b border-slate-100">
                                    <i class="fas fa-chevron-right mr-2 text-indigo-300"></i>${escapeHtml(currentSubcategory)}
                                </td>
                            `;
                            breakdownBody.appendChild(subRow);
                        }

                        // GL Account Row
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-indigo-50/30', 'transition-all', 'group');
                        row.innerHTML = `
                            <td class="px-10 py-3 border-b border-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-200 group-hover:bg-indigo-400"></div>
                                    <div>
                                        <div class="font-bold text-slate-700 text-xs group-hover:text-indigo-600 transition-colors">${escapeHtml(item.name || 'Account')}</div>
                                        <div class="text-[9px] text-slate-400 font-mono mt-0.5 tracking-tighter">${escapeHtml(item.account_code || '')}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right font-black text-slate-600 text-sm border-b border-slate-50">
                                <span class="text-xs text-slate-300 mr-1 font-normal">₱</span>${(parseFloat(item.amount || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        `;
                        breakdownBody.appendChild(row);
                    }
                });

                // Final sync of totals to be absolutely sure they match the breakdown
                if (document.getElementById('viewPlanBreakdownTotal')) {
                    document.getElementById('viewPlanBreakdownTotal').textContent = formatCurrency(renderTotal);
                }
                document.getElementById('viewPlanAmount').textContent = formatCurrency(renderTotal);
                const consolidatedTotalEls = [
                    document.getElementById('viewPlanAmount'),
                    document.getElementById('viewPlanFooterAmount')
                ];
                consolidatedTotalEls.forEach(el => {
                    if (el) el.textContent = formatCurrency(renderTotal);
                });
                
                openModal('viewPlanModal');
            } else {
                showToast(data.message || 'Error fetching plan details', 'error');
            }
        } catch (error) {
            console.error('Error viewing plan:', error);
            showToast('An unexpected error occurred.', 'error');
        }
    }
    
    // Open Archive Plan Modal
    // Auto-suggest Financial Metrics based on Total Budget
    function suggestFinancialMetrics() {
        // Just call the unified global total update which now handles everything
        updateGlobalAllocationTotal();
    }
    
    // Mark field as custom when user edits it
    function markAsCustomValue(input) {
        if (input.getAttribute('data-suggested') === 'true') {
            input.style.borderColor = '#6366f1'; // Indigo border for custom
            input.setAttribute('data-suggested', 'false');
        }
    }
    
    // Attach event listeners to financial metric inputs
    document.addEventListener('DOMContentLoaded', function() {
        const revenueInput = document.querySelector('input[name="project_revenue"]');
        const impactInput = document.querySelector('input[name="impact_percentage"]');
        const taxationInput = document.querySelector('input[name="taxation_adj"]');
        
        if (revenueInput) {
            revenueInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        if (impactInput) {
            impactInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        if (taxationInput) {
            taxationInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        // Attach to all GL allocation inputs
        const glInputs = document.querySelectorAll('input[name^="gl_allocation"]');
        glInputs.forEach(input => {
            input.addEventListener('input', suggestFinancialMetrics);
        });
        
        // Also trigger on subcategory budget distribution
        const subcatInputs = document.querySelectorAll('input[oninput*="distributeSubcategoryBudget"]');
        subcatInputs.forEach(input => {
            const originalOnInput = input.getAttribute('oninput');
            input.setAttribute('oninput', originalOnInput + '; suggestFinancialMetrics();');
        });
        
        // Use event delegation to capture ALL inputs (including dynamic ones)
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[oninput*="distributeSubcategoryBudget"]') || 
                e.target.matches('input[name^="gl_allocation"]')) {
                suggestFinancialMetrics();
            }
        });

        // Monitoring page filter change listeners
        const monitoringFilterSelects = document.querySelectorAll('#monitoringFilterForm select');
        monitoringFilterSelects.forEach(select => {
            select.addEventListener('change', function() {
                currentPage.monitoring = 1; // Reset to first page on filter change
                loadMonitoringData();
            });
            // Sync filter select values from URL on load
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has(select.name)) {
                select.value = urlParams.get(select.name);
            }
        });
    });
    
    function changeMonitoringPage(delta) {
        currentPage.monitoring += delta;
        loadMonitoringData();
    }

    function displaySupportingDocuments(docs) {
        const tray = document.getElementById('receiptsTray');
        const list = document.getElementById('receiptsList');
        const count = document.getElementById('receiptsCount');
        const controls = document.getElementById('documentControls');
        
        if (!list || !tray) return;

        list.innerHTML = '';
        const totalDocs = (docs ? docs.length : 0);
        count.textContent = (totalDocs + 1) + ' Views';
        
        // Always add official proposal tab
        const officialTab = document.createElement('div');
        officialTab.className = `receipt-tab-item px-3 py-2 rounded-lg border border-purple-700 bg-purple-900/30 cursor-pointer flex items-center gap-2 transition-all active-receipt-tab`;
        officialTab.innerHTML = `
            <i class="fas fa-file-contract text-purple-400 text-sm"></i>
            <span class="text-[10px] font-bold">Official Proposal</span>
        `;
        officialTab.onclick = () => {
            document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
            officialTab.classList.add('active-receipt-tab');
            document.getElementById('automatedProposalView').classList.remove('hidden');
            document.getElementById('documentViewerContainer').classList.add('hidden');
        };
        list.appendChild(officialTab);

        if (docs && docs.length > 0) {
            docs.forEach((file, index) => {
                const ext = file.split('.').pop().toLowerCase();
                let icon = 'fa-file';
                let color = 'text-gray-400';
                
                if (ext === 'pdf') { icon = 'fa-file-pdf'; color = 'text-red-400'; }
                else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) { icon = 'fa-file-image'; color = 'text-blue-400'; }
                else if (['doc', 'docx'].includes(ext)) { icon = 'fa-file-word'; color = 'text-indigo-400'; }
                else if (['xls', 'xlsx'].includes(ext)) { icon = 'fa-file-excel'; color = 'text-green-400'; }
                
                const tab = document.createElement('div');
                tab.className = `receipt-tab-item px-3 py-2 rounded-lg border border-gray-700 bg-gray-800/50 cursor-pointer flex items-center gap-2 transition-all`;
                tab.innerHTML = `
                    <i class="fas ${icon} ${color} text-sm"></i>
                    <span class="text-[10px] font-bold truncate max-w-[100px]">${escapeHtml(file)}</span>
                `;
                
                tab.onclick = () => {
                    document.getElementById('automatedProposalView').classList.add('hidden');
                    document.getElementById('documentViewerContainer').classList.remove('hidden');
                    openSupportingDocViewer(file, tab);
                };
                list.appendChild(tab);
            });
        }
        
        tray.classList.remove('hidden');
        controls.classList.remove('hidden');
        
        // Show official proposal by default
        document.getElementById('automatedProposalView').classList.remove('hidden');
        document.getElementById('documentViewerContainer').classList.add('hidden');
    }

    function renderAutoGeneratedProposal(proposal) {
        const container = document.getElementById('automatedProposalView');
        if (!container) return;

        const dateStr = new Date(proposal.submitted_at || proposal.created_at || new Date()).toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        });

        const breakdown = proposal.detailed_breakdown_array || [];
        let itemsHtml = '';
        breakdown.forEach(item => {
            itemsHtml += `
                <tr class="border-b border-gray-100">
                    <td class="py-3 text-sm text-gray-700 font-medium">${escapeHtml(item.name || item.description || 'General Expense')}</td>
                    <td class="py-3 text-xs text-gray-500 font-mono">${escapeHtml(item.account_code || '---')}</td>
                    <td class="py-3 text-right text-sm font-bold text-gray-900">${formatCurrency(item.amount)}</td>
                </tr>
            `;
        });

        if (!itemsHtml) {
            itemsHtml = `<tr><td colspan="3" class="py-6 text-center text-gray-400 italic">No detailed items recorded.</td></tr>`;
        }

        container.innerHTML = `
            <div class="max-w-4xl mx-auto bg-white shadow-2xl p-16 min-h-[1000px] relative border border-gray-200">
                <!-- Watermark -->
                <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none rotate-[-45deg] select-none">
                    <p class="text-[140px] font-black uppercase tracking-[20px]">FINANCIAL PROPOSAL</p>
                </div>

                <!-- Header Area -->
                <div class="flex justify-between items-start mb-12 border-b-4 border-indigo-600 pb-8 relative z-10">
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-university text-white text-3xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase mb-0">ViaHale Corporation</h1>
                                <p class="text-xs font-bold text-indigo-600 tracking-[0.2em] uppercase">Strategic Financial Services</p>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 space-y-1">
                            <p>123 Strategic Plaza, Makati City</p>
                            <p>finance.compliance@viahale.ph</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-gray-100 px-6 py-4 rounded-2xl inline-block border border-gray-200">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Proposal Reference</p>
                            <p class="text-lg font-black text-gray-900 mb-2">${proposal.proposal_code || 'N/A'}</p>
                            <p class="text-[10px] font-bold text-gray-500 uppercase">${dateStr}</p>
                        </div>
                    </div>
                </div>

                <!-- Title Section -->
                <div class="mb-12 relative z-10">
                    <h2 class="text-2xl font-black text-gray-900 mb-2 underline decoration-indigo-200 decoration-8 underline-offset-4">${escapeHtml(proposal.proposal_title || 'BUDGET PROPOSAL')}</h2>
                    <p class="text-sm text-gray-600 max-w-2xl leading-relaxed">${escapeHtml(proposal.project_objectives || proposal.justification || 'No objectives provided for this proposal.')}</p>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-12 mb-12 border-y border-gray-100 py-8 relative z-10">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Requesting Department</p>
                            <p class="text-sm font-bold text-gray-900">${escapeHtml(proposal.department || 'General')}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Fiscal Allocation</p>
                            <p class="text-sm font-bold text-gray-900">FY ${proposal.fiscal_year || new Date().getFullYear()}${proposal.quarter ? ' Q' + proposal.quarter : ''}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Proposal Class</p>
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-wide">${escapeHtml(proposal.project_type || 'Operational')}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Project Period</p>
                            <p class="text-sm font-bold text-gray-900">${proposal.start_date ? new Date(proposal.start_date).toLocaleDateString() : '---'} to ${proposal.end_date ? new Date(proposal.end_date).toLocaleDateString() : '---'}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="mb-12 relative z-10">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i class="fas fa-list text-indigo-600"></i> Cost Breakdown Schedule
                    </h3>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                <th class="py-3 px-0 text-left">Description of Requirement</th>
                                <th class="py-3 px-0 text-left">Account Code</th>
                                <th class="py-3 px-0 text-right">Requested Allocation</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-900">
                                <td colspan="2" class="py-4 text-sm font-black text-gray-900 uppercase tracking-widest">Total Proposed Budget</td>
                                <td class="py-4 text-right text-xl font-black text-indigo-700">${formatCurrency(proposal.total_budget || 0)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Approval Tracking -->
                <div class="mt-auto pt-12 relative z-10">
                    <div class="grid grid-cols-3 gap-8">
                        <div class="border-t border-gray-300 pt-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-2">Submitted By</p>
                            <p class="text-sm font-black text-gray-900 mb-1">${escapeHtml(proposal.submitted_by || 'Unauthorized Personnel')}</p>
                            <p class="text-[9px] text-gray-400">${dateStr}</p>
                        </div>
                        <div class="border-t border-gray-200 pt-3 opacity-30">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-8">Department Head Approval</p>
                            <div class="w-24 border-b border-gray-300"></div>
                        </div>
                        <div class="border-t border-gray-200 pt-3 opacity-30">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-8">Finance Review Marker</p>
                            <div class="w-24 border-b border-gray-300"></div>
                        </div>
                    </div>
                </div>

                <!-- Footer Text -->
                <div class="mt-12 text-[9px] text-gray-400 italic text-center border-t border-gray-100 pt-4">
                    This document is a formal system-generated budget proposal. Internal use only. Unauthorized replication is strictly prohibited under ViaHale Data Privacy Policy 2024.
                </div>
            </div>
        `;
    }

    function renderPastTransactions(proposal) {
        const body = document.getElementById('pastTransactionsBody');
        const avgEl = document.getElementById('avgMonthlySpend');
        const peakEl = document.getElementById('peakSpend');
        const badge = document.getElementById('trendStatusBadge');
        
        if (!body) return;

        // Generate Sample Data for the specific department
        const depts = ['HR', 'Finance', 'Operations', 'Marketing', 'IT'];
        const seed = (proposal.department || 'General').length;
        
        let totalVal = 0;
        let peakVal = 0;
        let rowsHtml = '';
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const currentYear = new Date().getFullYear();
        
        for (let i = 2; i < 26; i++) {
            const amount = 5000 + (Math.sin(seed + i) * 3000) + (Math.random() * 1000);
            totalVal += amount;
            if (amount > peakVal) peakVal = amount;
            
            const d = new Date();
            d.setMonth(d.getMonth() - i);
            const period = months[d.getMonth()] + ' ' + d.getFullYear();
            const ref = 'EXP-' + (d.getFullYear() % 100) + '-' + (Math.floor(Math.random() * 9000) + 1000);
            const utilization = 80 + (Math.random() * 15);
            
            rowsHtml += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="text-sm font-bold text-gray-900">${period}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs font-mono text-gray-500">${ref}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs text-gray-700 font-medium">Monthly Allocation</p>
                        <p class="text-[10px] text-gray-400">${proposal.department || 'Operations'}</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <p class="text-sm font-black text-gray-900">${formatCurrency(amount)}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                             <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: ${utilization}%"></div>
                             </div>
                             <span class="text-[10px] font-bold text-gray-500">${utilization.toFixed(0)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        body.innerHTML = rowsHtml;
        avgEl.textContent = formatCurrency(totalVal / 24);
        peakEl.textContent = formatCurrency(peakVal);
        
        // Random badge
        const trends = [
            { text: 'Stable', class: 'bg-emerald-100 text-emerald-700' },
            { text: 'Increasing', class: 'bg-amber-100 text-amber-700' },
            { text: 'Optimized', class: 'bg-blue-100 text-blue-700' }
        ];
        const trend = trends[seed % trends.length];
        badge.textContent = trend.text;
        badge.className = `px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tighter ${trend.class}`;
    }

    function openSupportingDocViewer(fileName, element = null) {
        const frame = document.getElementById('pdfFrame');
        const placeholder = document.getElementById('pdfViewerPlaceholder');
        const imgContainer = document.getElementById('imageViewerContainer');
        const img = document.getElementById('docImage');
        const ext = fileName.split('.').pop().toLowerCase();
        
        currentSupportingDocPath = `uploads/${fileName}`;
        
        // Highlight active tab
        if (element) {
            document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
            element.classList.add('active-receipt-tab');
        }

        placeholder.classList.add('hidden');
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            frame.classList.add('hidden');
            img.src = currentSupportingDocPath;
            imgContainer.classList.remove('hidden');
        } else if (ext === 'pdf') {
            imgContainer.classList.add('hidden');
            frame.src = `view_pdf.php?file=${encodeURIComponent(fileName)}`;
            frame.classList.remove('hidden');
        } else {
            // For other files, show placeholder with download link
            imgContainer.classList.add('hidden');
            frame.classList.add('hidden');
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = `
                <div class="text-center p-8">
                    <i class="fas fa-file-download text-5xl text-purple-300 mb-4 block"></i>
                    <p class="text-gray-500 font-medium">${escapeHtml(fileName)}</p>
                    <p class="text-gray-400 text-sm mt-1">This file type cannot be previewed directly.</p>
                    <a href="${currentSupportingDocPath}" download class="inline-block mt-4 px-6 py-2 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all shadow-md">
                        <i class="fas fa-download mr-2"></i>Download File
                    </a>
                </div>
            `;
        }
    }

    function openCurrentDocInNewTab() {
        if (currentSupportingDocPath) {
            window.open(currentSupportingDocPath, '_blank');
        }
    }
    
    // Initialize page - load stats on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
    });
