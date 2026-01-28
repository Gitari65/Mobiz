<template>
  <div class="report-wrapper">
    <div class="report-container">
      <!-- Header -->
      <div class="report-header">
        <div class="header-top">
          <div>
            <h1 class="page-title">📊 Reports Dashboard</h1>
            <p class="page-subtitle">Comprehensive business insights and analytics</p>
            <small v-if="lastFetchTime" class="cache-info">
              <i class="fas fa-clock"></i> Last updated: {{ formatDate(lastFetchTime) }}
            </small>
          </div>
          <button @click="refreshAllReports" class="refresh-btn" :disabled="isLoading">
            <i class="fas fa-sync-alt" :class="{ 'spinning': isLoading }"></i>
            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>

      <!-- Date Range Filter -->
      <div class="filter-bar">
        <div class="filter-group">
          <label><i class="fas fa-calendar"></i> From</label>
          <input v-model="filters.startDate" type="date" class="date-input" @change="applyFilters" />
        </div>
        <div class="filter-group">
          <label><i class="fas fa-calendar"></i> To</label>
          <input v-model="filters.endDate" type="date" class="date-input" @change="applyFilters" />
        </div>
        <button @click="resetFilters" class="reset-btn"><i class="fas fa-times"></i> Reset</button>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-container">
        <div class="tabs-nav">
          <button 
            v-for="tab in reportTabs" 
            :key="tab.id"
            :class="['tab-btn', { 'tab-active': activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            <i :class="tab.icon"></i>
            {{ tab.name }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner-large"></div>
        <p>Loading reports...</p>
      </div>

      <!-- Tab Content -->
      <div v-else class="tabs-content">
        <!-- Sales Tab -->
        <div v-if="activeTab === 'sales'" class="tab-pane active">
          <div class="metrics-grid">
            <div class="metric-card">
              <div class="metric-icon">💰</div>
              <div class="metric-content">
                <h3>Total Sales</h3>
                <p class="metric-value">Ksh {{ formatNumber(reports.sales.total_revenue) }}</p>
                <small>Total revenue</small>
              </div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">🛒</div>
              <div class="metric-content">
                <h3>Transactions</h3>
                <p class="metric-value">{{ reports.sales.total_transactions || 0 }}</p>
                <small>Sales completed</small>
              </div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📈</div>
              <div class="metric-content">
                <h3>Avg. Transaction</h3>
                <p class="metric-value">Ksh {{ formatNumber(reports.sales.avg_transaction) }}</p>
                <small>Per sale average</small>
              </div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📦</div>
              <div class="metric-content">
                <h3>Items Sold</h3>
                <p class="metric-value">{{ reports.sales.total_items || 0 }}</p>
                <small>Units moved</small>
              </div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-receipt"></i> Sales Transactions</h2></div>
            <div class="table-wrapper">
              <div v-if="!reports.sales.transactions?.length" class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No sales found for the selected period</p>
              </div>
              <table v-else class="modern-table">
                <thead>
                  <tr><th>Sale ID</th><th>Amount</th><th>Items</th><th>Customer</th><th>Date & Time</th></tr>
                </thead>
                <tbody>
                  <tr v-for="sale in reports.sales.transactions" :key="sale.id">
                    <td class="sale-id">#{{ sale.id }}</td>
                    <td class="amount">Ksh {{ formatNumber(sale.total) }}</td>
                    <td>{{ sale.items_count || 0 }} items</td>
                    <td>{{ sale.customer_name || 'Walk-in' }}</td>
                    <td>{{ formatDate(sale.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Transfers Tab -->
        <div v-if="activeTab === 'transfers'" class="tab-pane active">
          <div class="metrics-grid">
            <div class="metric-card">
              <div class="metric-icon">🚚</div>
              <div class="metric-content"><h3>Total Transfers</h3><p class="metric-value">{{ reports.transfers.total_count || 0 }}</p><small>Stock transfers</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📥</div>
              <div class="metric-content"><h3>Items In</h3><p class="metric-value">{{ reports.transfers.items_in || 0 }}</p><small>Items received</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📤</div>
              <div class="metric-content"><h3>Items Out</h3><p class="metric-value">{{ reports.transfers.items_out || 0 }}</p><small>Items transferred</small></div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-exchange-alt"></i> Stock Transfers</h2></div>
            <div class="table-wrapper">
              <div v-if="!reports.transfers.list?.length" class="empty-state"><i class="fas fa-inbox"></i><p>No transfers found</p></div>
              <table v-else class="modern-table">
                <thead><tr><th>Transfer ID</th><th>From</th><th>To</th><th>Items</th><th>Date</th></tr></thead>
                <tbody>
                  <tr v-for="t in reports.transfers.list" :key="t.id">
                    <td class="id">#{{ t.id }}</td><td>{{ t.from_location || 'Main' }}</td><td>{{ t.to_location || 'Branch' }}</td><td>{{ t.items_count || 0 }} items</td><td>{{ formatDate(t.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Analytics Tab -->
        <div v-if="activeTab === 'analytics'" class="tab-pane active">
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-boxes"></i> Inventory Overview</h2></div>
            <div class="inventory-stats">
              <div class="stat-item"><span class="label">Total Products</span><span class="value">{{ reports.analytics.total_products }}</span></div>
              <div class="stat-item"><span class="label">In Stock</span><span class="value in-stock">{{ reports.analytics.in_stock }}</span></div>
              <div class="stat-item"><span class="label">Low Stock</span><span class="value low-stock">{{ reports.analytics.low_stock }}</span></div>
              <div class="stat-item"><span class="label">Out of Stock</span><span class="value out-stock">{{ reports.analytics.out_of_stock }}</span></div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-trophy"></i> Top Selling Products</h2></div>
            <div class="products-list">
              <div v-if="!reports.analytics.top_products?.length" class="empty-state"><p>No sales data</p></div>
              <div v-for="(p, i) in reports.analytics.top_products" :key="p.id" class="product-row">
                <div class="rank">{{ i + 1 }}</div>
                <div class="info"><span class="name">{{ p.name }}</span><span class="sold">{{ p.total_sold }} sold</span></div>
                <div class="badge">🏆</div>
              </div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Alert <span v-if="reports.analytics.low_stock_items?.length > 0" class="badge-count">{{ reports.analytics.low_stock_items.length }}</span></h2></div>
            <div class="alert-list">
              <div v-if="!reports.analytics.low_stock_items?.length" class="empty-state"><i class="fas fa-check-circle"></i><p>All products well stocked!</p></div>
              <div v-for="p in reports.analytics.low_stock_items" :key="p.id" class="alert-row"><div><span class="name">{{ p.name }}</span><span class="qty">{{ p.stock_quantity }} left</span></div><span class="icon">⚠️</span></div>
            </div>
          </div>
        </div>

        <!-- Promotions Tab -->
        <div v-if="activeTab === 'promotions'" class="tab-pane active">
          <div class="metrics-grid">
            <div class="metric-card">
              <div class="metric-icon">🎁</div>
              <div class="metric-content"><h3>Active Promos</h3><p class="metric-value">{{ reports.promotions.active_count || 0 }}</p><small>Running</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">💸</div>
              <div class="metric-content"><h3>Discounts Given</h3><p class="metric-value">Ksh {{ formatNumber(reports.promotions.total_discount) }}</p><small>Total</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">✅</div>
              <div class="metric-content"><h3>Usage Count</h3><p class="metric-value">{{ reports.promotions.total_usage || 0 }}</p><small>Times applied</small></div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-percent"></i> Promotions Activity</h2></div>
            <div class="table-wrapper">
              <div v-if="!reports.promotions.list?.length" class="empty-state"><i class="fas fa-inbox"></i><p>No promotions found</p></div>
              <table v-else class="modern-table">
                <thead><tr><th>Promotion</th><th>Type</th><th>Scope</th><th>Usage</th><th>Discount</th><th>Status</th></tr></thead>
                <tbody>
                  <tr v-for="p in reports.promotions.list" :key="p.id">
                    <td class="name">{{ p.name }}</td><td><span class="badge-type">{{ formatPromoType(p.type) }}</span></td><td><span class="badge-scope">{{ formatPromoScope(p.scope) }}</span></td><td>{{ p.usage_count || 0 }}</td><td class="amount">Ksh {{ formatNumber(p.total_discount || 0) }}</td><td><span class="status" :class="p.is_active ? 'active' : 'inactive'">{{ p.is_active ? '✅' : '❌' }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Customers Tab -->
        <div v-if="activeTab === 'customers'" class="tab-pane active">
          <div class="metrics-grid">
            <div class="metric-card">
              <div class="metric-icon">👥</div>
              <div class="metric-content"><h3>Unique Customers</h3><p class="metric-value">{{ reports.customers.total_unique || 0 }}</p><small>Registered</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">🛍️</div>
              <div class="metric-content"><h3>Total Served</h3><p class="metric-value">{{ reports.customers.total_served || 0 }}</p><small>In period</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">💎</div>
              <div class="metric-content"><h3>Walk-ins</h3><p class="metric-value">{{ reports.customers.walk_ins || 0 }}</p><small>Unregistered</small></div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📊</div>
              <div class="metric-content"><h3>Avg. Spend</h3><p class="metric-value">Ksh {{ formatNumber(reports.customers.avg_spend) }}</p><small>Per customer</small></div>
            </div>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-star"></i> Top Customers</h2></div>
            <div class="table-wrapper">
              <div v-if="!reports.customers.top_customers?.length" class="empty-state"><i class="fas fa-inbox"></i><p>No customer data</p></div>
              <table v-else class="modern-table">
                <thead><tr><th>Rank</th><th>Customer</th><th>Purchases</th><th>Total Spent</th><th>Avg Purchase</th><th>Last Visit</th></tr></thead>
                <tbody>
                  <tr v-for="(c, i) in reports.customers.top_customers" :key="c.id">
                    <td class="rank">{{ i + 1 }}</td><td class="name">{{ c.name }}</td><td>{{ c.purchase_count }}</td><td class="amount">Ksh {{ formatNumber(c.total_spent) }}</td><td class="amount">Ksh {{ formatNumber(c.avg_purchase) }}</td><td>{{ formatDate(c.last_purchase_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      activeTab: 'sales',
      isLoading: true,
      reportTabs: [
        { id: 'sales', name: 'Sales', icon: 'fas fa-receipt' },
        { id: 'transfers', name: 'Transfers', icon: 'fas fa-exchange-alt' },
        { id: 'analytics', name: 'Analytics', icon: 'fas fa-chart-bar' },
        { id: 'promotions', name: 'Promotions', icon: 'fas fa-percent' },
        { id: 'customers', name: 'Customers', icon: 'fas fa-users' }
      ],
      filters: {
        startDate: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0]
      },
      reports: {
        sales: { total_revenue: 0, total_transactions: 0, avg_transaction: 0, total_items: 0, transactions: [] },
        transfers: { total_count: 0, items_in: 0, items_out: 0, list: [] },
        analytics: { total_products: 0, in_stock: 0, low_stock: 0, out_of_stock: 0, top_products: [], low_stock_items: [] },
        promotions: { active_count: 0, total_discount: 0, total_usage: 0, list: [] },
        customers: { total_unique: 0, total_served: 0, walk_ins: 0, avg_spend: 0, top_customers: [] }
      },
      // Cache system to prevent redundant API calls
      dataCache: {},
      cacheKey: '',
      lastFetchTime: null
    }
  },
  mounted() {
    this.loadReports()
  },
  methods: {
    // Generate cache key based on date filters
    generateCacheKey() {
      return `${this.filters.startDate}_${this.filters.endDate}`
    },
    
    // Check if data is cached for current date range
    isCached() {
      const key = this.generateCacheKey()
      return this.dataCache[key] !== undefined
    },
    
    // Get cached data for current date range
    getCachedData() {
      const key = this.generateCacheKey()
      return this.dataCache[key]
    },
    
    // Save data to cache
    cacheData(data) {
      const key = this.generateCacheKey()
      this.dataCache[key] = data
      this.cacheKey = key
      this.lastFetchTime = new Date()
      console.log('✅ Data cached for:', key)
    },
    
    async loadReports(forceRefresh = false) {
      // Check cache first unless force refresh
      if (!forceRefresh && this.isCached()) {
        console.log('📦 Loading from cache...')
        this.reports = this.getCachedData()
        this.isLoading = false
        return
      }
      
      console.log('🌐 Fetching fresh data...')
      this.isLoading = true
      try {
        await Promise.all([
          this.fetchSalesReport(),
          this.fetchTransfersReport(),
          this.fetchAnalyticsReport(),
          this.fetchPromotionsReport(),
          this.fetchCustomersReport()
        ])
        
        // Cache the fetched data
        this.cacheData({ ...this.reports })
      } catch (err) {
        console.error('Error loading reports:', err)
      } finally {
        this.isLoading = false
      }
    },
    async fetchSalesReport() {
      try {
        const res = await axios.get('/api/reports/sales', { params: { start_date: this.filters.startDate, end_date: this.filters.endDate } })
        this.reports.sales = res.data
      } catch (err) {
        console.error('⚠️ Failed to load sales report:', err)
        this.reports.sales = { total_revenue: 0, total_transactions: 0, avg_transaction: 0, total_items: 0, transactions: [] }
      }
    },
    async fetchTransfersReport() {
      try {
        const res = await axios.get('/api/reports/transfers', { params: { start_date: this.filters.startDate, end_date: this.filters.endDate } })
        this.reports.transfers = res.data
      } catch (err) {
        console.error('⚠️ Failed to load transfers report:', err)
        this.reports.transfers = { total_count: 0, items_in: 0, items_out: 0, list: [] }
      }
    },
    async fetchAnalyticsReport() {
      try {
        const res = await axios.get('/api/reports/analytics', { params: { start_date: this.filters.startDate, end_date: this.filters.endDate } })
        this.reports.analytics = res.data
      } catch (err) {
        console.error('⚠️ Failed to load analytics report:', err)
        this.reports.analytics = { total_products: 0, in_stock: 0, low_stock: 0, out_of_stock: 0, top_products: [], low_stock_items: [] }
      }
    },
    async fetchPromotionsReport() {
      try {
        const res = await axios.get('/api/reports/promotions', { params: { start_date: this.filters.startDate, end_date: this.filters.endDate } })
        this.reports.promotions = res.data
      } catch (err) {
        console.error('⚠️ Failed to load promotions report:', err)
        this.reports.promotions = { active_count: 0, total_discount: 0, total_usage: 0, list: [] }
      }
    },
    async fetchCustomersReport() {
      try {
        const res = await axios.get('/api/reports/customers', { params: { start_date: this.filters.startDate, end_date: this.filters.endDate } })
        this.reports.customers = res.data
      } catch (err) {
        console.error('⚠️ Failed to load customers report:', err)
        this.reports.customers = { total_unique: 0, total_served: 0, walk_ins: 0, avg_spend: 0, top_customers: [] }
      }
    },
    async applyFilters() {
      // Force refresh when filters change
      await this.loadReports()
    },
    resetFilters() {
      const today = new Date()
      const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
      this.filters.startDate = thirtyDaysAgo.toISOString().split('T')[0]
      this.filters.endDate = today.toISOString().split('T')[0]
      this.applyFilters()
    },
    async refreshAllReports() {
      // Force refresh bypasses cache
      await this.loadReports(true)
    },
    formatNumber(num) {
      if (!num) return '0'
      return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num)
    },
    formatDate(date) {
      if (!date) return '-'
      return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(date))
    },
    formatPromoType(type) {
      const map = { percentage: 'Percentage', fixed_amount: 'Fixed Amount', buy_x_get_y: 'Buy X Get Y', spend_save: 'Spend & Save', bulk_discount: 'Bulk Discount' }
      return map[type] || type
    },
    formatPromoScope(scope) {
      const map = { all: 'All', category: 'Category', product: 'Product' }
      return map[scope] || scope
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.report-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  padding: 2rem 0;
}

.report-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.report-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  margin-bottom: 2rem;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin: 0;
}

.cache-info {
  display: block;
  margin-top: 0.5rem;
  color: #a0aec0;
  font-size: 0.85rem;
  font-weight: 500;
}

.cache-info i {
  margin-right: 0.25rem;
  color: #667eea;
}

.refresh-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 0.875rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.refresh-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.refresh-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.filter-bar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: flex-end;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-input {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.date-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.reset-btn {
  margin-left: auto;
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.reset-btn:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

.tabs-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px 16px 0 0;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.tabs-nav {
  display: flex;
  gap: 0;
  padding: 0;
  overflow-x: auto;
}

.tab-btn {
  flex: 1;
  padding: 1.25rem 1.5rem;
  border: none;
  background: transparent;
  color: #718096;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  border-bottom: 3px solid transparent;
  white-space: nowrap;
}

.tab-btn:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.tab-btn.tab-active {
  color: #667eea;
  border-bottom-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.tabs-content {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 0 0 16px 16px;
  padding: 2rem;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.2));
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  gap: 1.5rem;
  align-items: center;
  transition: all 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-4px);
  border-color: rgba(102, 126, 234, 0.3);
}

.metric-icon {
  font-size: 2.5rem;
  min-width: 60px;
  text-align: center;
}

.metric-content h3 {
  margin: 0 0 0.5rem 0;
  color: #4a5568;
  font-size: 0.95rem;
  font-weight: 600;
}

.metric-value {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 1.75rem;
  font-weight: 700;
}

.metric-content small {
  color: #a0aec0;
  font-size: 0.875rem;
}

.report-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  margin-bottom: 2rem;
  transition: all 0.3s ease;
}

.report-card:hover {
  border-color: #cbd5e0;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.card-header {
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem;
}

.card-title {
  margin: 0;
  color: #2d3748;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-title i {
  color: #667eea;
}

.badge-count {
  background: #e53e3e;
  color: white;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 600;
  margin-left: auto;
}

.inventory-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  padding: 1.5rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  border-radius: 12px;
  background: #f8fafc;
}

.stat-item .label {
  display: block;
  color: #718096;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.stat-item .value {
  display: block;
  color: #2d3748;
  font-size: 1.75rem;
  font-weight: 700;
}

.stat-item .value.in-stock { color: #48bb78; }
.stat-item .value.low-stock { color: #ed8936; }
.stat-item .value.out-stock { color: #e53e3e; }

.products-list,
.alert-list {
  padding: 1.5rem;
}

.product-row,
.alert-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  background: #f8fafc;
  margin-bottom: 0.75rem;
  transition: all 0.3s ease;
}

.product-row:hover,
.alert-row:hover {
  background: #edf2f7;
  transform: translateX(4px);
}

.product-row .rank,
.product-row .badge {
  font-size: 1.5rem;
}

.product-row .rank {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-weight: 700;
}

.product-row .info,
.alert-row > div {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-row .name,
.alert-row .name {
  color: #2d3748;
  font-weight: 600;
}

.product-row .sold,
.alert-row .qty {
  color: #a0aec0;
  font-size: 0.85rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #718096;
}

.empty-state i {
  font-size: 2.5rem;
  color: #cbd5e0;
  margin-bottom: 1rem;
  display: block;
}

.table-wrapper {
  overflow-x: auto;
  padding: 1.5rem;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
}

.modern-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}

.modern-table th {
  padding: 1rem;
  text-align: left;
  color: #4a5568;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.modern-table tbody tr {
  border-bottom: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
  background: #f8fafc;
}

.modern-table td {
  padding: 1rem;
  color: #4a5568;
  font-size: 0.95rem;
}

.modern-table td.id,
.modern-table td.name {
  color: #667eea;
  font-weight: 600;
}

.modern-table td.amount {
  color: #48bb78;
  font-weight: 600;
}

.modern-table td.rank {
  font-weight: 600;
}

.badge-type,
.badge-scope {
  background: #edf2f7;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  display: inline-block;
}

.status {
  font-size: 1rem;
}

.status.active { color: #48bb78; }
.status.inactive { color: #e53e3e; }

@media (max-width: 1200px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .inventory-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .report-container {
    padding: 0 1rem;
  }
  .report-header {
    padding: 1.5rem;
  }
  .header-top {
    flex-direction: column;
    gap: 1rem;
  }
  .refresh-btn {
    width: 100%;
    justify-content: center;
  }
  .filter-bar {
    flex-direction: column;
    padding: 1rem;
  }
  .filter-group,
  .date-input,
  .reset-btn {
    width: 100%;
  }
  .reset-btn {
    margin-left: 0;
  }
  .page-title {
    font-size: 1.75rem;
  }
  .metrics-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  .metric-card {
    padding: 1rem;
  }
  .metric-icon {
    font-size: 2rem;
    min-width: 50px;
  }
  .metric-value {
    font-size: 1.5rem;
  }
  .inventory-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    padding: 1rem;
  }
  .tab-btn {
    padding: 1rem 1.25rem;
    font-size: 0.9rem;
  }
  .modern-table {
    font-size: 0.85rem;
  }
  .modern-table th,
  .modern-table td {
    padding: 0.75rem 0.5rem;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.5rem;
  }
  .metrics-grid {
    gap: 0.75rem;
  }
  .metric-card {
    flex-direction: column;
    text-align: center;
    padding: 0.75rem;
  }
  .metric-icon {
    font-size: 2rem;
  }
  .metric-value {
    font-size: 1.25rem;
  }
  .inventory-stats {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    padding: 0.75rem;
  }
  .tab-btn {
    padding: 0.75rem 0.5rem;
    font-size: 0.75rem;
  }
  .tab-btn i {
    display: none;
  }
  .modern-table {
    font-size: 0.75rem;
  }
  .modern-table th,
  .modern-table td {
    padding: 0.5rem 0.25rem;
  }
}
</style>
