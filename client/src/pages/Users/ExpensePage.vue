<template>
  <div class="modern-expenses">
    <!-- Navigation Bar -->
    <nav class="expenses-nav">
      <div class="nav-brand">
        <i class="fas fa-receipt nav-icon"></i>
        <h1>Expense Manager</h1>
      </div>
      <div class="nav-actions">
        <button @click="showAddExpenseModal = true" class="nav-btn primary">
          <i class="fas fa-plus"></i>
          Add Expense
        </button>
        <button @click="fetchExpenses" class="nav-btn refresh" :disabled="isLoading">
          <i class="fas fa-sync-alt" :class="{ 'spinning': isLoading }"></i>
        </button>
      </div>
    </nav>

    <!-- Main Container -->
    <div class="expenses-main">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading expense data...</p>
      </div>

      <!-- Content -->
      <div v-else class="expenses-content">
        <!-- Dashboard Cards -->
        <section class="dashboard-section">
          <div class="dashboard-grid">
            <div class="stat-card primary">
              <div class="stat-header">
                <i class="fas fa-calendar-month"></i>
                <span>This Month</span>
              </div>
              <div class="stat-value">{{ formatCurrency(monthlyExpenses) }}</div>
              <div class="stat-subtitle">total expenses</div>
            </div>

            <div class="stat-card danger">
              <div class="stat-header">
                <i class="fas fa-clock"></i>
                <span>Pending</span>
              </div>
              <div class="stat-value">{{ pendingCount }}</div>
              <div class="stat-subtitle">awaiting approval</div>
            </div>

            <div class="stat-card success">
              <div class="stat-header">
                <i class="fas fa-chart-line"></i>
                <span>Profit Margin</span>
              </div>
              <div class="stat-value">{{ profitMargin }}%</div>
              <div class="stat-subtitle">this month</div>
            </div>

            <div class="stat-card info">
              <div class="stat-header">
                <i class="fas fa-wallet"></i>
                <span>Net Profit</span>
              </div>
              <div class="stat-value">{{ formatCurrency(netProfit) }}</div>
              <div class="stat-subtitle">revenue - expenses</div>
            </div>
          </div>
        </section>

        <!-- Control Panel -->
        <section class="control-panel">
          <div class="panel-left">
            <div class="filter-group">
              <select v-model="filters.category" class="filter-select">
                <option value="">All Categories</option>
                <option v-for="(cat, key) in categories" :key="key" :value="key">
                  {{ cat.label }}
                </option>
              </select>

              <select v-model="filters.status" class="filter-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="paid">Paid</option>
              </select>

              <input 
                type="date" 
                v-model="filters.startDate" 
                class="filter-input"
                placeholder="Start Date"
              >
              
              <input 
                type="date" 
                v-model="filters.endDate" 
                class="filter-input"
                placeholder="End Date"
              >
            </div>
          </div>

          <div class="panel-right">
            <div class="search-group">
              <div class="search-box">
                <i class="fas fa-search"></i>
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  placeholder="Search expenses..." 
                  class="search-input"
                >
              </div>
              <button @click="exportExpenses" class="action-btn export">
                <i class="fas fa-download"></i>
                Export
              </button>
            </div>
          </div>
        </section>

        <!-- Profit/Loss Insights -->
        <section class="insights-section">
          <div class="insights-header">
            <h2>Financial Insights</h2>
            <div class="period-selector">
              <button 
                v-for="period in periods" 
                :key="period.value" 
                @click="selectedPeriod = period.value"
                class="period-btn"
                :class="{ active: selectedPeriod === period.value }"
              >
                {{ period.label }}
              </button>
            </div>
          </div>
          
          <div class="insights-grid">
            <div class="insight-card revenue">
              <div class="insight-icon">
                <i class="fas fa-arrow-up"></i>
              </div>
              <div class="insight-content">
                <h3>Total Revenue</h3>
                <div class="insight-value">{{ formatCurrency(totalRevenue) }}</div>
                <div class="insight-change positive">
                  <i class="fas fa-arrow-up"></i>
                  +{{ revenueGrowth }}% from last period
                </div>
              </div>
            </div>

            <div class="insight-card expenses">
              <div class="insight-icon">
                <i class="fas fa-arrow-down"></i>
              </div>
              <div class="insight-content">
                <h3>Total Expenses</h3>
                <div class="insight-value">{{ formatCurrency(totalExpenses) }}</div>
                <div class="insight-change" :class="expenseGrowth > 0 ? 'negative' : 'positive'">
                  <i :class="expenseGrowth > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ expenseGrowth > 0 ? '+' : '' }}{{ expenseGrowth }}% from last period
                </div>
              </div>
            </div>

            <div class="insight-card profit">
              <div class="insight-icon">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="insight-content">
                <h3>Net Profit</h3>
                <div class="insight-value" :class="netProfit >= 0 ? 'positive' : 'negative'">
                  {{ formatCurrency(netProfit) }}
                </div>
                <div class="insight-change" :class="profitGrowth > 0 ? 'positive' : 'negative'">
                  <i :class="profitGrowth > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ profitGrowth > 0 ? '+' : '' }}{{ profitGrowth }}% from last period
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Expenses Table -->
        <section class="table-section">
          <div class="table-header">
            <h2>Expense Records</h2>
            <div class="table-actions">
              <button @click="bulkApprove" class="bulk-btn approve" :disabled="selectedExpenses.length === 0">
                <i class="fas fa-check"></i>
                Approve Selected
              </button>
              <button @click="bulkReject" class="bulk-btn reject" :disabled="selectedExpenses.length === 0">
                <i class="fas fa-times"></i>
                Reject Selected
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>
                    <input 
                      type="checkbox" 
                      @change="toggleSelectAll" 
                      :checked="allSelected"
                      class="checkbox"
                    >
                  </th>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="expense in filteredExpenses" :key="expense.id" class="table-row">
                  <td>
                    <input 
                      type="checkbox" 
                      v-model="selectedExpenses" 
                      :value="expense.id"
                      class="checkbox"
                    >
                  </td>
                  <td>{{ formatDate(expense.expense_date) }}</td>
                  <td>
                    <div class="expense-info">
                      <div class="expense-title">{{ expense.description }}</div>
                      <div class="expense-vendor" v-if="expense.vendor_name">
                        {{ expense.vendor_name }}
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="category-badge" :class="getCategoryClass(expense.category)">
                      {{ getCategoryLabel(expense.category) }}
                    </div>
                  </td>
                  <td class="amount-cell">
                    <div class="amount-primary">{{ formatCurrency(expense.amount) }}</div>
                    <div class="amount-tax" v-if="expense.tax_amount > 0">
                      +{{ formatCurrency(expense.tax_amount) }} tax
                    </div>
                  </td>
                  <td>
                    <div class="payment-method">
                      <i :class="getPaymentIcon(expense.payment_method)"></i>
                      {{ getPaymentLabel(expense.payment_method) }}
                    </div>
                  </td>
                  <td>
                    <div class="status-badge" :class="getStatusClass(expense.status)">
                      {{ getStatusLabel(expense.status) }}
                    </div>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button @click="viewExpense(expense)" class="btn-icon view" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button @click="editExpense(expense)" class="btn-icon edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        v-if="expense.status === 'pending'" 
                        @click="approveExpense(expense.id)" 
                        class="btn-icon approve" 
                        title="Approve"
                      >
                        <i class="fas fa-check"></i>
                      </button>
                      <button 
                        v-if="expense.status === 'pending'" 
                        @click="rejectExpense(expense.id)" 
                        class="btn-icon reject" 
                        title="Reject"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                      <button @click="deleteExpense(expense.id)" class="btn-icon delete" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination">
            <button @click="previousPage" :disabled="currentPage === 1" class="page-btn">
              <i class="fas fa-chevron-left"></i>
            </button>
            <span class="page-info">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </section>
      </div>
    </div>

    <!-- Add/Edit Expense Modal -->
    <div v-if="showAddExpenseModal || showEditExpenseModal" class="modal-overlay" @click="closeModals">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ showAddExpenseModal ? 'Add New Expense' : 'Edit Expense' }}</h2>
          <button @click="closeModals" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <form @submit.prevent="saveExpense" class="expense-form">
          <div class="form-grid">
            <div class="form-group">
              <label>Description *</label>
              <input type="text" v-model="expenseForm.description" required class="form-input">
            </div>

            <div class="form-group">
              <label>Amount *</label>
              <input type="number" v-model="expenseForm.amount" step="0.01" required class="form-input">
            </div>

            <div class="form-group">
              <label>Category *</label>
              <select v-model="expenseForm.category" required class="form-select">
                <option value="">Select Category</option>
                <option v-for="(cat, key) in categories" :key="key" :value="key">
                  {{ cat.label }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Subcategory</label>
              <select v-model="expenseForm.subcategory" class="form-select">
                <option value="">Select Subcategory</option>
                <option 
                  v-for="(subcat, key) in subcategories" 
                  :key="key" 
                  :value="key"
                >
                  {{ subcat }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Payment Method *</label>
              <select v-model="expenseForm.payment_method" required class="form-select">
                <option value="">Select Method</option>
                <option v-for="(method, key) in paymentMethods" :key="key" :value="key">
                  {{ method }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Expense Date *</label>
              <input type="date" v-model="expenseForm.expense_date" required class="form-input">
            </div>

            <div class="form-group">
              <label>Vendor Name</label>
              <input type="text" v-model="expenseForm.vendor_name" class="form-input">
            </div>

            <div class="form-group">
              <label>Receipt Number</label>
              <input type="text" v-model="expenseForm.receipt_number" class="form-input">
            </div>

            <div class="form-group">
              <label>Tax Rate (%)</label>
              <input type="number" v-model="expenseForm.tax_rate" step="0.01" class="form-input">
            </div>

            <div class="form-group full-width">
              <label>Notes</label>
              <textarea v-model="expenseForm.notes" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="form-group full-width">
              <label class="checkbox-label">
                <input type="checkbox" v-model="expenseForm.is_recurring" class="form-checkbox">
                <span class="checkmark"></span>
                Recurring Expense
              </label>
            </div>

            <div v-if="expenseForm.is_recurring" class="form-group">
              <label>Frequency</label>
              <select v-model="expenseForm.recurring_frequency" class="form-select">
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="isSubmitting">
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
              {{ showAddExpenseModal ? 'Add Expense' : 'Update Expense' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Expense Modal -->
    <div v-if="showViewExpenseModal" class="modal-overlay" @click="closeModals">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>Expense Details</h2>
          <button @click="closeModals" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="expense-details" v-if="selectedExpense">
          <div class="details-grid">
            <div class="detail-item">
              <label>Description</label>
              <div class="detail-value">{{ selectedExpense.description }}</div>
            </div>
            <div class="detail-item">
              <label>Amount</label>
              <div class="detail-value amount">{{ formatCurrency(selectedExpense.amount) }}</div>
            </div>
            <div class="detail-item">
              <label>Category</label>
              <div class="detail-value">{{ getCategoryLabel(selectedExpense.category) }}</div>
            </div>
            <div class="detail-item">
              <label>Status</label>
              <div class="status-badge" :class="getStatusClass(selectedExpense.status)">
                {{ getStatusLabel(selectedExpense.status) }}
              </div>
            </div>
            <div class="detail-item" v-if="selectedExpense.vendor_name">
              <label>Vendor</label>
              <div class="detail-value">{{ selectedExpense.vendor_name }}</div>
            </div>
            <div class="detail-item">
              <label>Payment Method</label>
              <div class="detail-value">{{ getPaymentLabel(selectedExpense.payment_method) }}</div>
            </div>
            <div class="detail-item" v-if="selectedExpense.notes">
              <label>Notes</label>
              <div class="detail-value">{{ selectedExpense.notes }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ExpensePage',
  data() {
    return {
      isLoading: false,
      isSubmitting: false,
      showAddExpenseModal: false,
      showEditExpenseModal: false,
      showViewExpenseModal: false,
      selectedExpense: null,
      expenses: [],
      selectedExpenses: [],
      searchQuery: '',
      currentPage: 1,
      itemsPerPage: 10,
      
      // Dashboard stats
      monthlyExpenses: 0,
      pendingCount: 0,
      profitMargin: 0,
      netProfit: 0,
      totalRevenue: 0,
      totalExpenses: 0,
      revenueGrowth: 0,
      expenseGrowth: 0,
      profitGrowth: 0,
      
      // Filters
      filters: {
        category: '',
        status: '',
        startDate: '',
        endDate: ''
      },
      
      selectedPeriod: 'month',
      periods: [
        { label: 'This Week', value: 'week' },
        { label: 'This Month', value: 'month' },
        { label: 'This Quarter', value: 'quarter' },
        { label: 'This Year', value: 'year' }
      ],
      
      // Form data
      expenseForm: {
        description: '',
        amount: '',
        category: '',
        subcategory: '',
        payment_method: '',
        expense_date: '',
        vendor_name: '',
        receipt_number: '',
        tax_rate: '',
        notes: '',
        is_recurring: false,
        recurring_frequency: 'monthly'
      },
      
      categories: {},
      paymentMethods: {}
    }
  },
  
  computed: {
    filteredExpenses() {
      // Ensure expenses is always an array
      const expenses = Array.isArray(this.expenses) ? this.expenses : [];
      let filtered = [...expenses];
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(expense => 
          expense.description?.toLowerCase().includes(query) ||
          expense.vendor_name?.toLowerCase().includes(query) ||
          expense.category?.toLowerCase().includes(query)
        );
      }
      
      if (this.filters.category) {
        filtered = filtered.filter(expense => expense.category === this.filters.category);
      }
      
      if (this.filters.status) {
        filtered = filtered.filter(expense => expense.status === this.filters.status);
      }
      
      if (this.filters.startDate) {
        filtered = filtered.filter(expense => expense.expense_date >= this.filters.startDate);
      }
      
      if (this.filters.endDate) {
        filtered = filtered.filter(expense => expense.expense_date <= this.filters.endDate);
      }
      
      return filtered;
    },
    
    totalPages() {
      return Math.ceil(this.filteredExpenses.length / this.itemsPerPage);
    },
    
    allSelected() {
      const expenses = Array.isArray(this.expenses) ? this.expenses : [];
      return expenses.length > 0 && this.selectedExpenses.length === expenses.length;
    },
    
    subcategories() {
      if (!this.expenseForm.category || !this.categories[this.expenseForm.category]) {
        return {};
      }
      const subs = this.categories[this.expenseForm.category].subcategories || {};
      
      // Always include "Other" option for any category
      const result = { ...subs };
      if (!result.other && !result.others) {
        result.other = 'Other';
      }
      
      return result;
    }
  },

  watch: {
    'expenseForm.category'(newCategory, oldCategory) {
      // Clear subcategory when category changes
      if (newCategory !== oldCategory) {
        this.expenseForm.subcategory = '';
      }
    }
  },
  
  mounted() {
    this.fetchExpenses();
    this.fetchCategories();
    this.fetchPaymentMethods();
    this.fetchDashboardData();
  },
  
  methods: {
    async fetchExpenses() {
      this.isLoading = true;
      try {
        const response = await fetch('http://127.0.0.1:8000/expenses');
        const data = await response.json();
        console.log('Expenses response:', data);
        
        // Handle the response structure - ensure expenses is always an array
        if (data.success && data.data) {
          // Check if data.data is paginated (has 'data' property) or direct array
          if (data.data.data && Array.isArray(data.data.data)) {
            this.expenses = data.data.data; // Paginated response
          } else if (Array.isArray(data.data)) {
            this.expenses = data.data; // Direct array
          } else {
            this.expenses = []; // Fallback to empty array
          }
        } else if (Array.isArray(data)) {
          this.expenses = data; // Direct array response
        } else {
          this.expenses = []; // Fallback to empty array
        }
        
        console.log('Processed expenses:', this.expenses);
      } catch (error) {
        console.error('Error fetching expenses:', error);
        this.expenses = []; // Ensure expenses is always an array on error
      } finally {
        this.isLoading = false;
      }
    },
    
    async fetchCategories() {
      try {
        const response = await fetch('http://127.0.0.1:8000/expenses/categories');
        const data = await response.json();
        console.log('Categories response:', data);
        
        // Handle the response structure - extract the data if it's wrapped
        if (data.success && data.data) {
          this.categories = data.data;
        } else {
          this.categories = data;
        }
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    },
    
    async fetchPaymentMethods() {
      try {
        const response = await fetch('http://127.0.0.1:8000/expenses/payment-methods');
        const data = await response.json();
        console.log('Payment methods response:', data);
        
        // Handle the response structure - extract the data if it's wrapped
        if (data.success && data.data) {
          this.paymentMethods = data.data;
        } else {
          this.paymentMethods = data;
        }
      } catch (error) {
        console.error('Error fetching payment methods:', error);
      }
    },
    
    async fetchDashboardData() {
      try {
        const response = await fetch(`http://127.0.0.1:8000/expenses/dashboard?period=${this.selectedPeriod}`);
        
        // Check if response is OK and contains JSON
        if (!response.ok) {
          console.error('Dashboard API error:', response.status, response.statusText);
          return;
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          console.error('Dashboard API returned non-JSON response');
          return;
        }
        
        const data = await response.json();
        console.log('Dashboard response:', data);
        
        // Handle the response structure
        if (data.success && data.data) {
          const dashData = data.data.summary || data.data;
          this.monthlyExpenses = dashData.monthly_expenses || dashData.total_expenses || 0;
          this.pendingCount = dashData.pending_count || 0;
          this.profitMargin = dashData.profit_margin || 0;
          this.netProfit = dashData.net_profit || 0;
          this.totalRevenue = dashData.total_revenue || 0;
          this.totalExpenses = dashData.total_expenses || 0;
          this.revenueGrowth = dashData.revenue_growth || 0;
          this.expenseGrowth = dashData.expense_growth || 0;
          this.profitGrowth = dashData.profit_growth || 0;
        } else {
          // Set default values if no proper data
          this.monthlyExpenses = 0;
          this.pendingCount = 0;
          this.profitMargin = 0;
          this.netProfit = 0;
          this.totalRevenue = 0;
          this.totalExpenses = 0;
          this.revenueGrowth = 0;
          this.expenseGrowth = 0;
          this.profitGrowth = 0;
        }
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        // Set default values on error
        this.monthlyExpenses = 0;
        this.pendingCount = 0;
        this.profitMargin = 0;
        this.netProfit = 0;
        this.totalRevenue = 0;
        this.totalExpenses = 0;
        this.revenueGrowth = 0;
        this.expenseGrowth = 0;
        this.profitGrowth = 0;
      }
    },
    
    async saveExpense() {
      this.isSubmitting = true;
      try {
        const url = this.showEditExpenseModal 
          ? `http://127.0.0.1:8000/expenses/${this.selectedExpense.id}` 
          : 'http://127.0.0.1:8000/expenses';
        const method = this.showEditExpenseModal ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(this.expenseForm)
        });
        
        if (response.ok) {
          await this.fetchExpenses();
          await this.fetchDashboardData();
          this.closeModals();
          this.resetForm();
        }
      } catch (error) {
        console.error('Error saving expense:', error);
      } finally {
        this.isSubmitting = false;
      }
    },
    
    async approveExpense(id) {
      try {
        const response = await fetch(`http://127.0.0.1:8000/expenses/${id}/approve`, {
          method: 'PATCH'
        });
        if (response.ok) {
          await this.fetchExpenses();
          await this.fetchDashboardData();
        }
      } catch (error) {
        console.error('Error approving expense:', error);
      }
    },
    
    async rejectExpense(id) {
      try {
        const response = await fetch(`http://127.0.0.1:8000/expenses/${id}/reject`, {
          method: 'PATCH'
        });
        if (response.ok) {
          await this.fetchExpenses();
          await this.fetchDashboardData();
        }
      } catch (error) {
        console.error('Error rejecting expense:', error);
      }
    },
    
    async deleteExpense(id) {
      if (confirm('Are you sure you want to delete this expense?')) {
        try {
          const response = await fetch(`http://127.0.0.1:8000/expenses/${id}`, {
            method: 'DELETE'
          });
          if (response.ok) {
            await this.fetchExpenses();
            await this.fetchDashboardData();
          }
        } catch (error) {
          console.error('Error deleting expense:', error);
        }
      }
    },
    
    async bulkApprove() {
      for (const id of this.selectedExpenses) {
        await this.approveExpense(id);
      }
      this.selectedExpenses = [];
    },
    
    async bulkReject() {
      for (const id of this.selectedExpenses) {
        await this.rejectExpense(id);
      }
      this.selectedExpenses = [];
    },
    
    async exportExpenses() {
      try {
        const response = await fetch('http://127.0.0.1:8000/expenses/export');
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `expenses_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
      } catch (error) {
        console.error('Error exporting expenses:', error);
      }
    },
    
    viewExpense(expense) {
      this.selectedExpense = expense;
      this.showViewExpenseModal = true;
    },
    
    editExpense(expense) {
      this.selectedExpense = expense;
      this.expenseForm = { ...expense };
      this.showEditExpenseModal = true;
    },
    
    closeModals() {
      this.showAddExpenseModal = false;
      this.showEditExpenseModal = false;
      this.showViewExpenseModal = false;
      this.selectedExpense = null;
      this.resetForm();
    },
    
    resetForm() {
      this.expenseForm = {
        description: '',
        amount: '',
        category: '',
        subcategory: '',
        payment_method: '',
        expense_date: '',
        vendor_name: '',
        receipt_number: '',
        tax_rate: '',
        notes: '',
        is_recurring: false,
        recurring_frequency: 'monthly'
      };
    },
    
    toggleSelectAll() {
      this.selectedExpenses = this.allSelected ? [] : this.expenses.map(e => e.id);
    },
    
    previousPage() {
      if (this.currentPage > 1) this.currentPage--;
    },
    
    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++;
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES'
      }).format(amount || 0);
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-KE');
    },
    
    getCategoryLabel(category) {
      return this.categories[category]?.label || category;
    },
    
    getCategoryClass(category) {
      const classes = {
        operational: 'operational',
        inventory: 'inventory',
        marketing: 'marketing',
        staff: 'staff',
        transport: 'transport',
        technology: 'technology',
        professional: 'professional',
        miscellaneous: 'miscellaneous'
      };
      return classes[category] || 'default';
    },
    
    getStatusLabel(status) {
      const labels = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        paid: 'Paid'
      };
      return labels[status] || status;
    },
    
    getStatusClass(status) {
      return status;
    },
    
    getPaymentLabel(method) {
      return this.paymentMethods[method] || method;
    },
    
    getPaymentIcon(method) {
      const icons = {
        cash: 'fas fa-money-bill',
        bank_transfer: 'fas fa-university',
        credit_card: 'fas fa-credit-card',
        debit_card: 'fas fa-credit-card',
        mobile_money: 'fas fa-mobile-alt',
        cheque: 'fas fa-money-check',
        online_payment: 'fas fa-laptop'
      };
      return icons[method] || 'fas fa-wallet';
    }
  },
  
  watch: {
    selectedPeriod() {
      this.fetchDashboardData();
    }
  }
}
</script>

<style scoped>
/* Base Styles */
.modern-expenses {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Navigation */
.expenses-nav {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1rem 2rem;
  display: flex;
  justify-content: between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #2d3748;
}

.nav-icon {
  font-size: 1.5rem;
  color: #667eea;
}

.nav-brand h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.nav-actions {
  display: flex;
  gap: 1rem;
}

.nav-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-btn.primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.nav-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.nav-btn.refresh {
  background: rgba(107, 114, 128, 0.1);
  color: #374151;
}

.nav-btn.refresh:hover {
  background: rgba(107, 114, 128, 0.2);
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Main Content */
.expenses-main {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  color: white;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top: 3px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

/* Dashboard Section */
.dashboard-section {
  margin-bottom: 2rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card.success::before {
  background: linear-gradient(90deg, #10b981, #059669);
}

.stat-card.danger::before {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.stat-card.info::before {
  background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  color: #6b7280;
}

.stat-header i {
  font-size: 1.25rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.stat-subtitle {
  color: #9ca3af;
  font-size: 0.875rem;
}

/* Control Panel */
.control-panel {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.panel-left, .panel-right {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.filter-select, .filter-input {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: white;
  min-width: 150px;
  transition: all 0.3s ease;
}

.filter-select:focus, .filter-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-group {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 1rem;
  color: #9ca3af;
}

.search-input {
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: white;
  min-width: 250px;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.action-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-btn.export {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.action-btn.export:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

/* Insights Section */
.insights-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
}

.insights-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.insights-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.period-selector {
  display: flex;
  gap: 0.5rem;
}

.period-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.period-btn.active,
.period-btn:hover {
  border-color: #667eea;
  background: #667eea;
  color: white;
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.insight-card {
  padding: 1.5rem;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: all 0.3s ease;
}

.insight-card.revenue {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.insight-card.expenses {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.insight-card.profit {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}

.insight-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.insight-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.insight-content h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  opacity: 0.9;
}

.insight-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.insight-value.positive {
  color: #10b981;
}

.insight-value.negative {
  color: #ef4444;
}

.insight-change {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  opacity: 0.9;
}

.insight-change.positive {
  color: rgba(255, 255, 255, 0.9);
}

.insight-change.negative {
  color: rgba(255, 255, 255, 0.9);
}

/* Table Section */
.table-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  overflow: hidden;
}

.table-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.table-header h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.table-actions {
  display: flex;
  gap: 1rem;
}

.bulk-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.bulk-btn.approve {
  background: #10b981;
  color: white;
}

.bulk-btn.reject {
  background: #ef4444;
  color: white;
}

.bulk-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.table-container {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
}

.modern-table th {
  background: #f9fafb;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.modern-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.table-row:hover {
  background: #f9fafb;
}

.checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.expense-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.expense-title {
  font-weight: 600;
  color: #1f2937;
}

.expense-vendor {
  font-size: 0.875rem;
  color: #6b7280;
}

.category-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.category-badge.operational { background: #dbeafe; color: #1e40af; }
.category-badge.inventory { background: #d1fae5; color: #065f46; }
.category-badge.marketing { background: #fef3c7; color: #92400e; }
.category-badge.staff { background: #e0e7ff; color: #3730a3; }
.category-badge.transport { background: #fce7f3; color: #be185d; }
.category-badge.technology { background: #e0f2fe; color: #0e7490; }
.category-badge.professional { background: #f3e8ff; color: #6b21a8; }
.category-badge.miscellaneous { background: #f1f5f9; color: #475569; }

.amount-cell {
  text-align: right;
}

.amount-primary {
  font-weight: 600;
  color: #1f2937;
}

.amount-tax {
  font-size: 0.75rem;
  color: #6b7280;
}

.payment-method {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  text-align: center;
  min-width: 70px;
}

.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.approved { background: #d1fae5; color: #065f46; }
.status-badge.rejected { background: #fee2e2; color: #991b1b; }
.status-badge.paid { background: #dbeafe; color: #1e40af; }

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-icon.view { background: #f3f4f6; color: #374151; }
.btn-icon.edit { background: #dbeafe; color: #1e40af; }
.btn-icon.approve { background: #d1fae5; color: #065f46; }
.btn-icon.reject { background: #fee2e2; color: #991b1b; }
.btn-icon.delete { background: #fee2e2; color: #991b1b; }

.btn-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Pagination */
.pagination {
  padding: 1rem 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  border-top: 1px solid #e5e7eb;
}

.page-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
  border-color: #667eea;
  background: #667eea;
  color: white;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-weight: 500;
  color: #374151;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 20px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

.modal-content.large {
  max-width: 800px;
}

.modal-header {
  padding: 2rem 2rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.modal-close {
  width: 40px;
  height: 40px;
  border: none;
  background: #f3f4f6;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #e5e7eb;
}

/* Form Styles */
.expense-form {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 600;
  color: #374151;
}

.form-input, .form-select, .form-textarea {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  font-weight: 500;
}

.form-checkbox {
  width: 18px;
  height: 18px;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

/* Expense Details */
.expense-details {
  padding: 2rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item label {
  font-weight: 600;
  color: #6b7280;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-weight: 500;
  color: #1f2937;
}

.detail-value.amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: #059669;
}

/* Responsive Design */
@media (max-width: 768px) {
  .expenses-main {
    padding: 1rem;
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  
  .control-panel {
    flex-direction: column;
    align-items: stretch;
  }
  
  .panel-left, .panel-right {
    justify-content: center;
  }
  
  .filter-group, .search-group {
    flex-direction: column;
  }
  
  .filter-select, .filter-input, .search-input {
    min-width: auto;
    width: 100%;
  }
  
  .insights-grid {
    grid-template-columns: 1fr;
  }
  
  .insight-card {
    text-align: center;
    flex-direction: column;
  }
  
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-actions {
    justify-content: center;
  }
  
  .modern-table {
    font-size: 0.875rem;
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
  
  .modal-overlay {
    padding: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .expenses-nav {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
  }
  
  .nav-actions {
    width: 100%;
    justify-content: center;
  }
  
  .stat-card {
    padding: 1rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .modern-table th,
  .modern-table td {
    padding: 0.5rem;
  }
  
  .btn-icon {
    width: 28px;
    height: 28px;
  }
}
</style>
