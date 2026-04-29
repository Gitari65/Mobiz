<template>
  <div class="dash-page">
    <div class="dash-shell">
      <header class="dash-header">
        <div>
          <h1>Operations Dashboard</h1>
          <p class="subtitle">Fast overview of sales momentum, stock pressure, and system responsiveness.</p>
          <p class="updated-badge" :class="{ stale: isDataStale }">
            Updated {{ lastUpdatedLabel }}
          </p>
        </div>
        <div class="header-actions">
          <button class="btn btn-soft" :disabled="isRefreshing" @click="refreshAllData(true)">
            {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button class="btn btn-solid" @click="goTo('/sales')">New Sale</button>
        </div>
      </header>

      <p v-if="alertMessage" class="banner">{{ alertMessage }}</p>

      <section class="kpi-grid">
        <article class="panel">
          <p class="label">Today Sales</p>
          <p class="value">{{ formatCurrency(stats.today_sales) }}</p>
          <p class="hint" :class="trendClass">
            {{ salesTrendText }}
          </p>
        </article>

        <article class="panel">
          <p class="label">Average Ticket</p>
          <p class="value">{{ formatCurrency(avgTicket) }}</p>
          <p class="hint">Based on {{ recentSalesCount }} recent sale{{ recentSalesCount === 1 ? '' : 's' }}</p>
        </article>

        <article v-if="isAdminRole" class="panel">
          <p class="label">Low + Out of Stock</p>
          <p class="value">{{ stockPressureTotal }}</p>
          <p class="hint" :class="stockRiskClass">{{ stockRiskText }}</p>
        </article>
        <article v-else class="panel">
          <p class="label">My Transactions Today</p>
          <p class="value">{{ normalizeNumber(stats.today_transactions) }}</p>
          <p class="hint" :class="myTransactionTrendClass">{{ myTransactionTrendText }}</p>
        </article>

        <article v-if="isAdminRole" class="panel">
          <p class="label">Health Score</p>
          <p class="value">{{ healthScore }} / 100</p>
          <p class="hint">Derived from stock pressure and sales trend</p>
        </article>
        <article v-else class="panel">
          <p class="label">My Active Scope</p>
          <p class="value">{{ statsScopeLabel }}</p>
          <p class="hint">Cashier dashboard uses your own sales and activity only.</p>
        </article>
      </section>

      <section class="insights-grid">
        <article class="panel">
          <div class="panel-title-row">
            <h2>{{ roleTitle }}</h2>
            <button class="link-btn" @click="goTo('/products')">Manage Products</button>
          </div>

          <div class="role-cards">
            <article v-for="card in roleInsightCards" :key="card.title" class="role-card">
              <p class="role-card-label">{{ card.title }}</p>
              <p class="role-card-value">{{ card.value }}</p>
              <p class="role-card-hint">{{ card.hint }}</p>
            </article>
          </div>

          <ul class="insight-list">
            <li v-if="stats.out_of_stock > 0">
              {{ stats.out_of_stock }} product{{ stats.out_of_stock === 1 ? '' : 's' }} are out of stock and may block sales.
            </li>
            <li v-if="stats.low_stock > 0">
              {{ stats.low_stock }} product{{ stats.low_stock === 1 ? '' : 's' }} are low stock and should be replenished soon.
            </li>
            <li>
              Sales delta today: {{ salesTrendSigned }}% versus yesterday.
            </li>
            <li v-if="slowestEndpoint">
              Slowest endpoint seen: {{ slowestEndpoint.name }} (avg {{ slowestEndpoint.avgMs }}ms).
            </li>
            <li v-else>
              System metrics will appear after more page activity.
            </li>
          </ul>

          <div v-if="isAdminRole" class="cashier-performance-mini">
            <div class="panel-title-row">
              <h2>Cashier Performance Today</h2>
            </div>
            <div v-if="!stats.cashier_performance.length" class="state">No cashier sales recorded today.</div>
            <ul v-else class="compact-list">
              <li v-for="cashier in topCashierPerformance" :key="cashier.user_id">
                <div>
                  <strong>{{ cashier.cashier_name }}</strong>
                  <small>{{ cashier.transactions }} transaction{{ cashier.transactions === 1 ? '' : 's' }}</small>
                </div>
                <span class="sale-amount">{{ formatCurrency(cashier.total_sales) }}</span>
              </li>
            </ul>
          </div>
        </article>

        <article v-if="isAdminRole" class="panel">
          <div class="panel-title-row">
            <h2>Out of Stock</h2>
            <span class="pill">{{ outOfStockProducts.length }}</span>
          </div>

          <div v-if="loadingOutOfStock" class="state">Loading stock alerts...</div>
          <div v-else-if="outOfStockProducts.length === 0" class="state ok">No out-of-stock products right now.</div>
          <ul v-else class="compact-list">
            <li v-for="product in topOutOfStockProducts" :key="product.id">
              <div>
                <strong>{{ product.name }}</strong>
                <small>{{ product.category || 'Uncategorized' }}</small>
              </div>
              <span class="stock-tag">{{ normalizeNumber(product.stock_quantity) }}</span>
            </li>
          </ul>
        </article>
      </section>

      <section class="panel">
        <div class="panel-title-row">
          <h2>Recent Sales</h2>
          <button class="link-btn" @click="goTo('/reports')">Open Reports</button>
        </div>

        <div v-if="loadingStats" class="state">Loading recent sales...</div>
        <div v-else-if="stats.recent_sales.length === 0" class="state">No recent sales found.</div>
        <ul v-else class="compact-list sales-list">
          <li v-for="sale in stats.recent_sales" :key="sale.id">
            <div>
              <strong>Sale #{{ sale.id }}</strong>
              <small>{{ formatDate(sale.created_at) }}</small>
            </div>
            <span class="sale-amount">{{ formatCurrency(normalizeNumber(sale.total)) }}</span>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      stats: {
        scope: 'company',
        role: '',
        today_sales: 0,
        yesterday_sales: 0,
        today_transactions: 0,
        yesterday_transactions: 0,
        low_stock: 0,
        out_of_stock: 0,
        recent_sales: [],
        cashier_performance: []
      },
      outOfStockProducts: [],
      loadingStats: true,
      loadingOutOfStock: true,
      alertMessage: '',
      isRefreshing: false,
      dashboardCacheTtlMs: 60 * 1000,
      lastStatsLoadedAt: null,
      lastOutOfStockLoadedAt: null,
      nowTickMs: Date.now(),
      clockTimer: null,
      userRole: ''
    }
  },
  computed: {
    salesDeltaRaw() {
      const today = this.normalizeNumber(this.stats.today_sales)
      const yesterday = this.normalizeNumber(this.stats.yesterday_sales)
      if (yesterday === 0) return today > 0 ? 100 : 0
      return ((today - yesterday) / yesterday) * 100
    },
    salesTrendSigned() {
      return this.salesDeltaRaw.toFixed(1)
    },
    salesTrendText() {
      if (this.salesDeltaRaw > 0) return `Up ${this.salesTrendSigned}% vs yesterday`
      if (this.salesDeltaRaw < 0) return `Down ${Math.abs(this.salesDeltaRaw).toFixed(1)}% vs yesterday`
      return 'No change vs yesterday'
    },
    trendClass() {
      if (this.salesDeltaRaw > 0) return 'good'
      if (this.salesDeltaRaw < 0) return 'warn'
      return 'neutral'
    },
    recentSalesCount() {
      return Array.isArray(this.stats.recent_sales) ? this.stats.recent_sales.length : 0
    },
    avgTicket() {
      if (!this.recentSalesCount) return 0
      const total = this.stats.recent_sales.reduce((sum, sale) => sum + this.normalizeNumber(sale.total), 0)
      return total / this.recentSalesCount
    },
    stockPressureTotal() {
      return this.normalizeNumber(this.stats.low_stock) + this.normalizeNumber(this.stats.out_of_stock)
    },
    stockRiskText() {
      const total = this.stockPressureTotal
      if (total >= 20) return 'High stock risk'
      if (total >= 8) return 'Medium stock risk'
      return 'Low stock risk'
    },
    stockRiskClass() {
      const total = this.stockPressureTotal
      if (total >= 20) return 'warn'
      if (total >= 8) return 'neutral'
      return 'good'
    },
    healthScore() {
      const stockPenalty = Math.min(60, this.stockPressureTotal * 2)
      const trendBonus = Math.max(-20, Math.min(20, this.salesDeltaRaw / 2))
      const score = 80 - stockPenalty + trendBonus
      return Math.max(0, Math.min(100, Math.round(score)))
    },
    topOutOfStockProducts() {
      return this.outOfStockProducts.slice(0, 8)
    },
    slowestEndpoint() {
      const metrics = window.__mobizRequestMetrics
      if (!(metrics instanceof Map) || metrics.size === 0) return null

      let slowest = null
      for (const [name, value] of metrics.entries()) {
        const count = this.normalizeNumber(value?.count)
        const totalMs = this.normalizeNumber(value?.totalMs)
        const avgMs = count > 0 ? totalMs / count : 0
        if (!slowest || avgMs > slowest.avgMs) {
          slowest = { name, avgMs: Math.round(avgMs) }
        }
      }

      return slowest
    },
    lastUpdatedAtMs() {
      const last = Math.max(this.lastStatsLoadedAt || 0, this.lastOutOfStockLoadedAt || 0)
      return last || null
    },
    secondsSinceLastUpdate() {
      if (!this.lastUpdatedAtMs) return null
      return Math.max(0, Math.floor((this.nowTickMs - this.lastUpdatedAtMs) / 1000))
    },
    lastUpdatedLabel() {
      if (this.loadingStats || this.loadingOutOfStock) return 'just now'
      if (this.secondsSinceLastUpdate === null) return 'not yet'
      if (this.secondsSinceLastUpdate < 60) return `${this.secondsSinceLastUpdate}s ago`

      const minutes = Math.floor(this.secondsSinceLastUpdate / 60)
      if (minutes < 60) return `${minutes}m ago`

      const hours = Math.floor(minutes / 60)
      return `${hours}h ago`
    },
    isDataStale() {
      return this.secondsSinceLastUpdate !== null && this.secondsSinceLastUpdate > 120
    },
    normalizedRole() {
      return String(this.userRole || '').toLowerCase().trim()
    },
    isAdminRole() {
      return ['admin', 'administrator', 'superuser', 'super_user', 'super user'].includes(this.normalizedRole)
    },
    roleTitle() {
      return this.isAdminRole ? 'Admin Insights' : 'Cashier Insights'
    },
    statsScopeLabel() {
      return this.stats.scope === 'personal' ? 'Personal View' : 'Company View'
    },
    myTransactionTrendText() {
      const todayTx = this.normalizeNumber(this.stats.today_transactions)
      const yesterdayTx = this.normalizeNumber(this.stats.yesterday_transactions)
      if (todayTx > yesterdayTx) return `Up by ${todayTx - yesterdayTx} vs yesterday`
      if (todayTx < yesterdayTx) return `Down by ${yesterdayTx - todayTx} vs yesterday`
      return 'No change vs yesterday'
    },
    myTransactionTrendClass() {
      const todayTx = this.normalizeNumber(this.stats.today_transactions)
      const yesterdayTx = this.normalizeNumber(this.stats.yesterday_transactions)
      if (todayTx > yesterdayTx) return 'good'
      if (todayTx < yesterdayTx) return 'warn'
      return 'neutral'
    },
    topCashierPerformance() {
      return Array.isArray(this.stats.cashier_performance)
        ? this.stats.cashier_performance.slice(0, 6)
        : []
    },
    roleInsightCards() {
      if (this.isAdminRole) {
        const strongestCashier = this.topCashierPerformance[0]
        return [
          {
            title: 'Restock Priority',
            value: `${this.normalizeNumber(this.stats.out_of_stock)} blocked items`,
            hint: 'Items at zero stock that can stop checkout.'
          },
          {
            title: 'Sales Momentum',
            value: `${this.salesTrendSigned}%`,
            hint: 'Trend versus yesterday to guide staffing and promos.'
          },
          {
            title: 'Top Cashier Today',
            value: strongestCashier ? strongestCashier.cashier_name : 'No sales yet',
            hint: strongestCashier ? `${this.formatCurrency(strongestCashier.total_sales)} collected` : 'Will appear after sales activity.'
          }
        ]
      }

      return [
        {
          title: 'Shift Sales',
          value: this.formatCurrency(this.stats.today_sales),
          hint: 'How much this store has sold so far today.'
        },
        {
          title: 'Ticket Focus',
          value: this.formatCurrency(this.avgTicket),
          hint: 'Average receipt value from recent sales.'
        },
        {
          title: 'Checkout Risk',
          value: `${this.normalizeNumber(this.stats.out_of_stock)} out of stock`,
          hint: 'Flag empty products before customers ask for them.'
        }
      ]
    }
  },
  methods: {
    resolveUserRole() {
      const candidateKeys = ['userData', 'user', 'pendingSession']
      for (const key of candidateKeys) {
        try {
          const parsed = JSON.parse(localStorage.getItem(key) || 'null')
          const roleName = parsed?.role?.name || parsed?.role
          if (roleName) return String(roleName).toLowerCase()
        } catch (error) {
          // Ignore malformed local storage values.
        }
      }
      return ''
    },
    normalizeNumber(value) {
      const numeric = Number(value)
      return Number.isFinite(numeric) ? numeric : 0
    },
    formatCurrency(value) {
      return `Ksh ${this.normalizeNumber(value).toLocaleString()}`
    },
    formatDate(value) {
      if (!value) return 'Unknown date'
      const date = new Date(value)
      return Number.isNaN(date.getTime()) ? 'Unknown date' : date.toLocaleString()
    },
    getCachedDashboardSegment(key) {
      try {
        const cached = localStorage.getItem(key)
        if (!cached) return null

        const parsed = JSON.parse(cached)
        if (!parsed || typeof parsed !== 'object' || !('timestamp' in parsed) || !('data' in parsed)) {
          return null
        }

        if (Date.now() - parsed.timestamp > this.dashboardCacheTtlMs) return null
        return parsed.data
      } catch (error) {
        return null
      }
    },
    setCachedDashboardSegment(key, data) {
      try {
        localStorage.setItem(key, JSON.stringify({ timestamp: Date.now(), data }))
      } catch (error) {
        // Ignore cache write failures.
      }
    },
    sanitizeStats(raw) {
      return {
        scope: raw?.scope || 'company',
        role: raw?.role || '',
        today_sales: this.normalizeNumber(raw?.today_sales),
        yesterday_sales: this.normalizeNumber(raw?.yesterday_sales),
        today_transactions: this.normalizeNumber(raw?.today_transactions),
        yesterday_transactions: this.normalizeNumber(raw?.yesterday_transactions),
        low_stock: this.normalizeNumber(raw?.low_stock),
        out_of_stock: this.normalizeNumber(raw?.out_of_stock),
        recent_sales: Array.isArray(raw?.recent_sales) ? raw.recent_sales : [],
        cashier_performance: Array.isArray(raw?.cashier_performance) ? raw.cashier_performance : []
      }
    },
    async fetchStats(forceRefresh = false) {
      this.loadingStats = true
      try {
        const cachedStats = !forceRefresh ? this.getCachedDashboardSegment('dashboard_stats_cache') : null
        if (cachedStats) {
          this.stats = this.sanitizeStats(cachedStats)
          this.lastStatsLoadedAt = Date.now()
          return
        }

        const res = await axios.get('/dashboard-stats')
        const cleaned = this.sanitizeStats(res.data)
        this.stats = cleaned
        this.setCachedDashboardSegment('dashboard_stats_cache', cleaned)
        this.lastStatsLoadedAt = Date.now()
      } catch (error) {
        console.error(error)
        this.alertMessage = 'Could not load dashboard stats.'
      } finally {
        this.loadingStats = false
      }
    },
    async fetchOutOfStockProducts(forceRefresh = false) {
      this.loadingOutOfStock = true
      try {
        const cachedOutOfStock = !forceRefresh ? this.getCachedDashboardSegment('dashboard_out_of_stock_cache') : null
        if (cachedOutOfStock) {
          this.outOfStockProducts = Array.isArray(cachedOutOfStock) ? cachedOutOfStock : []
          this.lastOutOfStockLoadedAt = Date.now()
          return
        }

        const res = await axios.get('/products/out-of-stock')
        this.outOfStockProducts = Array.isArray(res.data) ? res.data : []
        this.setCachedDashboardSegment('dashboard_out_of_stock_cache', this.outOfStockProducts)
        this.lastOutOfStockLoadedAt = Date.now()
      } catch (error) {
        console.error('Error fetching out of stock products:', error)
        this.outOfStockProducts = []
      } finally {
        this.loadingOutOfStock = false
      }
    },
    async refreshAllData(forceRefresh = false) {
      this.isRefreshing = true
      this.alertMessage = ''

      if (this.isAdminRole) {
        await Promise.all([
          this.fetchStats(forceRefresh),
          this.fetchOutOfStockProducts(forceRefresh)
        ])
      } else {
        await this.fetchStats(forceRefresh)
        this.outOfStockProducts = []
        this.loadingOutOfStock = false
      }

      if (!this.loadingStats && !this.loadingOutOfStock) {
        this.alertMessage = 'Dashboard updated.'
        setTimeout(() => {
          this.alertMessage = ''
        }, 2200)
      }

      this.isRefreshing = false
    },
    goTo(path) {
      this.$router.push(path)
    }
  },
  mounted() {
    this.userRole = this.resolveUserRole()
    this.clockTimer = window.setInterval(() => {
      this.nowTickMs = Date.now()
    }, 1000)
    this.refreshAllData()
  },
  beforeUnmount() {
    if (this.clockTimer) {
      window.clearInterval(this.clockTimer)
      this.clockTimer = null
    }
  }
}
</script>

<style scoped>
:root {
  color-scheme: light;
  --primary: #667eea;
  --primary-dark: #5568d3;
  --primary-light: #7b92f5;
  --secondary: #764ba2;
  --accent: #667eea;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --gray-900: #2d3748;
  --gray-800: #4a5568;
  --gray-700: #718096;
  --gray-600: #718096;
  --gray-500: #a0aec0;
  --gray-400: #cbd5e1;
  --gray-300: #e2e8f0;
  --gray-200: #edf2f7;
  --gray-100: #f7fafc;
  --gray-50: #f9fafb;
}

.dash-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem;
  position: relative;
}

.dash-shell {
  max-width: 1320px;
  margin: 0 auto;
  display: grid;
  gap: 1.25rem;
  position: relative;
  z-index: 1;
}

.dash-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  animation: fadeInDown 0.6s ease-out;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

h1 {
  margin: 0;
  font-size: 2rem;
  letter-spacing: -0.02em;
  color: #2d3748;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  font-weight: 800;
}

.subtitle {
  margin: 0.5rem 0 0;
  color: #718096;
  max-width: 600px;
  line-height: 1.6;
  font-size: 0.95rem;
}

.updated-badge {
  display: inline-flex;
  margin: 0.75rem 0 0;
  padding: 0.3rem 0.65rem;
  border-radius: 20px;
  border: 1px solid rgba(102, 126, 234, 0.3);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
  color: #5568d3;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  backdrop-filter: blur(8px);
  transition: all 0.3s ease;
}

.updated-badge.stale {
  border-color: rgba(245, 158, 11, 0.3);
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #b45309;
}

.header-actions {
  display: flex;
  gap: 0.8rem;
}

.btn {
  border: 1px solid transparent;
  border-radius: 12px;
  padding: 0.65rem 1rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

.btn:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.btn-soft {
  border-color: #e2e8f0;
  background: rgba(255, 255, 255, 0.8);
  color: #2d3748;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.btn-soft:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-solid {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
  border: 1px solid transparent;
}

.btn-solid:hover:not(:disabled) {
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
}

.banner {
  margin: 0;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
  border: 1px solid rgba(102, 126, 234, 0.3);
  color: #5568d3;
  font-size: 0.9rem;
  font-weight: 500;
  backdrop-filter: blur(8px);
  animation: slideInDown 0.4s ease-out;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  animation: fadeInUp 0.7s ease-out 0.1s both;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.insights-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 1.25rem;
  animation: fadeInUp 0.7s ease-out 0.2s both;
}

.panel {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, transparent 100%);
  pointer-events: none;
}

.panel:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.label {
  margin: 0;
  color: #718096;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
}

.value {
  margin: 0.6rem 0;
  color: #2d3748;
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.hint {
  margin: 0;
  color: #718096;
  font-size: 0.88rem;
  line-height: 1.4;
}

.good {
  color: #10b981;
  font-weight: 600;
}

.warn {
  color: #ef4444;
  font-weight: 600;
}

.neutral {
  color: #718096;
}

.panel-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e2e8f0;
}

h2 {
  margin: 0;
  font-size: 1.15rem;
  color: #2d3748;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.link-btn {
  border: 0;
  background: transparent;
  color: #667eea;
  cursor: pointer;
  font-size: 0.88rem;
  font-weight: 600;
  transition: all 0.2s ease;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}

.link-btn:hover {
  background: rgba(102, 126, 234, 0.1);
  color: #5568d3;
}

.pill {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%);
  color: #b91c1c;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 700;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.insight-list {
  margin: 0;
  padding-left: 0;
  display: grid;
  gap: 0.6rem;
  color: #2d3748;
  font-size: 0.92rem;
  list-style: none;
}

.insight-list li {
  padding-left: 1.5rem;
  position: relative;
  line-height: 1.5;
  transition: all 0.2s ease;
}

.insight-list li::before {
  content: '→';
  position: absolute;
  left: 0;
  color: #667eea;
  font-weight: bold;
}

.insight-list li:hover {
  color: #667eea;
  padding-left: 2rem;
}

.cashier-performance-mini {
  margin-top: 1rem;
  border-top: 2px solid #e2e8f0;
  padding-top: 1rem;
}

.role-cards {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.role-card {
  background: linear-gradient(135deg, #f7fafc 0%, rgba(102, 126, 234, 0.03) 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.role-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
  transition: all 0.3s ease;
}

.role-card:hover {
  background: linear-gradient(135deg, #f7fafc 0%, rgba(102, 126, 234, 0.08) 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
  border-color: #667eea;
}

.role-card-label {
  margin: 0;
  color: #718096;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.role-card-value {
  margin: 0.35rem 0;
  color: #2d3748;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.role-card-hint {
  margin: 0;
  color: #718096;
  font-size: 0.8rem;
  line-height: 1.4;
}

.compact-list {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.65rem;
}

.compact-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #f7fafc 0%, rgba(102, 126, 234, 0.02) 100%);
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
}

.compact-list li:hover {
  background: linear-gradient(135deg, #f7fafc 0%, rgba(102, 126, 234, 0.08) 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.08);
  transform: translateX(4px);
  border-color: #667eea;
}

.compact-list strong {
  display: block;
  color: #2d3748;
  font-size: 0.95rem;
  font-weight: 700;
}

.compact-list small {
  color: #718096;
  font-size: 0.82rem;
}

.stock-tag {
  border-radius: 8px;
  padding: 0.3rem 0.6rem;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%);
  color: #991b1b;
  font-weight: 700;
  min-width: 2.5rem;
  text-align: center;
  font-size: 0.85rem;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.sale-amount {
  color: #10b981;
  font-weight: 700;
  font-size: 0.95rem;
}

.state {
  border: 2px dashed #e2e8f0;
  padding: 1rem;
  border-radius: 12px;
  color: #718096;
  font-size: 0.92rem;
  text-align: center;
  transition: all 0.3s ease;
}

.state.ok {
  border-color: rgba(16, 185, 129, 0.3);
  background: rgba(16, 185, 129, 0.05);
  color: #047857;
  border-style: solid;
}

@media (max-width: 1200px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .insights-grid {
    grid-template-columns: 1fr;
  }

  h1 {
    font-size: 1.75rem;
  }
}

@media (max-width: 768px) {
  .dash-page {
    padding: 1rem;
  }

  .dash-header {
    flex-direction: column;
    align-items: stretch;
    padding: 1rem;
  }

  h1 {
    font-size: 1.5rem;
  }

  .header-actions {
    width: 100%;
    gap: 0.6rem;
  }

  .btn {
    flex: 1;
  }

  .kpi-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .value {
    font-size: 1.5rem;
  }

  .role-cards {
    grid-template-columns: 1fr;
  }

  .insights-grid {
    gap: 0.75rem;
  }

  .panel {
    padding: 1rem;
  }

  .dash-shell {
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .dash-page {
    padding: 0.75rem;
  }

  .dash-header {
    padding: 0.75rem;
    border-radius: 12px;
  }

  h1 {
    font-size: 1.25rem;
  }

  .subtitle {
    font-size: 0.85rem;
  }

  .value {
    font-size: 1.25rem;
  }

  .btn {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
  }

  .panel {
    padding: 0.75rem;
    border-radius: 12px;
  }

  h2 {
    font-size: 1rem;
  }
}
</style>
