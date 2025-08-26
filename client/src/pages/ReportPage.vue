<template>
  <div class="report-wrapper">
    <div class="report-container">
      <div class="report-header">
        <h1 class="page-title">📊 Reports Dashboard</h1>
        <p class="page-subtitle">Comprehensive business insights and analytics</p>
        <button @click="refreshAllReports" class="refresh-btn" :disabled="isLoading">
          <i class="fas fa-sync-alt" :class="{ 'spinning': isLoading }"></i>
          {{ isLoading ? 'Refreshing...' : 'Refresh Reports' }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner-large"></div>
        <p>Loading reports...</p>
      </div>

      <!-- Main Content -->
      <div v-else>
        <!-- Key Metrics Grid -->
        <div class="metrics-grid">
          <div class="metric-card sales-metric">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
              <h3>Monthly Sales</h3>
              <p class="metric-value">Ksh {{ formatNumber(salesData.total_sales_month) }}</p>
              <small class="metric-label">Total revenue this month</small>
            </div>
          </div>

          <div class="metric-card transactions-metric">
            <div class="metric-icon">🛒</div>
            <div class="metric-content">
              <h3>Transactions</h3>
              <p class="metric-value">{{ salesData.total_transactions_month || 0 }}</p>
              <small class="metric-label">Sales completed</small>
            </div>
          </div>

          <div class="metric-card average-metric">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
              <h3>Avg. Transaction</h3>
              <p class="metric-value">Ksh {{ formatNumber(salesData.average_transaction_value) }}</p>
              <small class="metric-label">Per sale average</small>
            </div>
          </div>

          <div class="metric-card items-metric">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
              <h3>Items Sold</h3>
              <p class="metric-value">{{ salesData.total_items_sold_month || 0 }}</p>
              <small class="metric-label">Units moved</small>
            </div>
          </div>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
          <!-- Inventory Summary -->
          <div class="report-card inventory-card">
            <div class="card-header">
              <h2 class="card-title">
                <i class="fas fa-boxes"></i>
                Inventory Overview
              </h2>
            </div>
            <div class="inventory-stats">
              <div class="stat-item total-stat">
                <span class="stat-label">Total Products</span>
                <span class="stat-value">{{ inventoryData.total_products }}</span>
              </div>
              <div class="stat-item in-stock-stat">
                <span class="stat-label">In Stock</span>
                <span class="stat-value">{{ inventoryData.in_stock }}</span>
              </div>
              <div class="stat-item low-stock-stat">
                <span class="stat-label">Low Stock</span>
                <span class="stat-value">{{ inventoryData.low_stock }}</span>
              </div>
              <div class="stat-item out-stock-stat">
                <span class="stat-label">Out of Stock</span>
                <span class="stat-value">{{ inventoryData.out_of_stock }}</span>
              </div>
            </div>
          </div>

          <!-- Top Selling Products -->
          <div class="report-card top-selling-card">
            <div class="card-header">
              <h2 class="card-title">
                <i class="fas fa-trophy"></i>
                Top Selling Products
              </h2>
            </div>
            <div class="top-products-list">
              <div 
                v-for="(product, index) in topSellingData.top_products" 
                :key="product.product_id" 
                class="top-product-item"
              >
                <div class="product-rank">{{ index + 1 }}</div>
                <div class="product-info">
                  <span class="product-name">{{ product.name }}</span>
                  <span class="product-sales">{{ product.total_sold }} units sold</span>
                </div>
                <div class="product-badge">🏆</div>
              </div>
            </div>
          </div>

          <!-- Low Stock Alert -->
          <div class="report-card low-stock-card">
            <div class="card-header">
              <h2 class="card-title warning-title">
                <i class="fas fa-exclamation-triangle"></i>
                Low Stock Alert
                <span v-if="inventoryData.low_stock_products?.length > 0" class="alert-badge">
                  {{ inventoryData.low_stock_products.length }}
                </span>
              </h2>
            </div>
            <div class="alert-content">
              <div v-if="inventoryData.low_stock_products?.length === 0" class="empty-state">
                <div class="empty-icon">✅</div>
                <p>All products are well stocked!</p>
              </div>
              <div v-else class="alert-list">
                <div 
                  v-for="product in inventoryData.low_stock_products" 
                  :key="product.id" 
                  class="alert-item low-stock-item"
                >
                  <div class="alert-info">
                    <span class="alert-product-name">{{ product.name }}</span>
                    <span class="alert-stock">{{ product.stock_quantity }} remaining</span>
                  </div>
                  <div class="alert-status low-stock">⚠️</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Out of Stock Report -->
          <div class="report-card out-of-stock-card">
            <div class="card-header">
              <h2 class="card-title urgent-title">
                <i class="fas fa-ban"></i>
                Out of Stock Report
                <span v-if="outOfStockProducts.length > 0" class="urgent-badge">
                  {{ outOfStockProducts.length }}
                </span>
              </h2>
            </div>
            <div class="alert-content">
              <div v-if="outOfStockProducts.length === 0" class="empty-state">
                <div class="empty-icon">🎉</div>
                <p>No products are out of stock!</p>
                <small>Great inventory management!</small>
              </div>
              <div v-else class="alert-list">
                <div 
                  v-for="product in outOfStockProducts" 
                  :key="product.id" 
                  class="alert-item out-of-stock-item"
                >
                  <div class="alert-info">
                    <span class="alert-product-name">{{ product.name }}</span>
                    <span class="alert-category">{{ product.category || 'No Category' }}</span>
                    <span class="alert-price">Ksh {{ formatNumber(product.price) }}</span>
                  </div>
                  <div class="alert-status out-of-stock">🚫</div>
                </div>
              </div>
              <div v-if="outOfStockProducts.length > 0" class="card-footer">
                <button @click="goToProducts" class="action-btn urgent-btn">
                  <i class="fas fa-plus"></i>
                  Restock Products
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="report-card recent-sales-card">
          <div class="card-header">
            <h2 class="card-title">
              <i class="fas fa-clock"></i>
              Recent Sales Activity
            </h2>
          </div>
          <div class="table-wrapper">
            <div v-if="salesData.recent_sales?.length === 0" class="empty-table">
              <div class="empty-icon">📋</div>
              <p>No recent sales found</p>
            </div>
            <table v-else class="modern-table">
              <thead>
                <tr>
                  <th>Sale ID</th>
                  <th>Amount</th>
                  <th>Items</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in salesData.recent_sales" :key="sale.id" class="table-row">
                  <td class="sale-id">#{{ sale.id }}</td>
                  <td class="sale-amount">Ksh {{ formatNumber(sale.total) }}</td>
                  <td class="sale-items">{{ sale.sale_items?.length || 0 }} items</td>
                  <td class="sale-date">{{ formatDate(sale.created_at) }}</td>
                  <td class="sale-status">
                    <span class="status-badge completed">✅ Completed</span>
                  </td>
                </tr>
              </tbody>
            </table>
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
      isLoading: true,
      salesData: {
        total_sales_month: 0,
        total_transactions_month: 0,
        average_transaction_value: 0,
        total_items_sold_month: 0,
        recent_sales: []
      },
      inventoryData: {
        total_products: 0,
        in_stock: 0,
        low_stock: 0,
        out_of_stock: 0,
        low_stock_products: []
      },
      topSellingData: {
        top_products: []
      },
      outOfStockProducts: []
    }
  },
  methods: {
    async fetchReports() {
      this.isLoading = true
      try {
        const [salesResponse, inventoryResponse, topSellingResponse, outOfStockResponse] = await Promise.all([
          axios.get('http://localhost:8000/reports/sales-overview'),
          axios.get('http://localhost:8000/reports/inventory-summary'),
          axios.get('http://localhost:8000/reports/top-selling-products'),
          axios.get('http://localhost:8000/products/out-of-stock')
        ])

        this.salesData = salesResponse.data
        this.inventoryData = inventoryResponse.data
        this.topSellingData = topSellingResponse.data
        this.outOfStockProducts = outOfStockResponse.data
      } catch (error) {
        console.error('Error fetching reports:', error)
      } finally {
        this.isLoading = false
      }
    },
    async refreshAllReports() {
      await this.fetchReports()
    },
    formatNumber(value) {
      if (!value || typeof value !== 'number') return '0.00'
      return value.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleString('en-KE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    goToProducts() {
      this.$router.push('/products')
    }
  },
  mounted() {
    this.fetchReports()
  }
}
</script>

<style scoped>
/* Modern Report Page Styling */
.report-wrapper {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.report-container {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  margin: 1rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Header Section */
.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 2.75rem;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.025em;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.125rem;
  margin: 0.5rem 0 0 0;
  font-weight: 500;
}

.refresh-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.5rem;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.refresh-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.15);
}

.refresh-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #64748b;
}

.spinner-large {
  width: 60px;
  height: 60px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.metric-card {
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.sales-metric::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
.transactions-metric::before { background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); }
.average-metric::before { background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%); }
.items-metric::before { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.metric-icon {
  font-size: 3rem;
  line-height: 1;
}

.metric-content h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 0.25rem 0;
  line-height: 1;
}

.metric-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

/* Reports Grid */
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 2.5rem;
}

.report-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.report-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.report-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.card-header {
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid #f3f4f6;
}

.card-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}

.warning-title {
  color: #f59e0b;
}

.urgent-title {
  color: #ef4444;
}

.alert-badge, .urgent-badge {
  background: #f59e0b;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  margin-left: auto;
}

.urgent-badge {
  background: #ef4444;
}

/* Inventory Stats */
.inventory-stats {
  padding: 1.5rem 2rem 2rem 2rem;
  display: grid;
  gap: 1rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid #e2e8f0;
  transition: all 0.3s ease;
}

.stat-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.total-stat { border-left-color: #3b82f6; }
.in-stock-stat { border-left-color: #10b981; }
.low-stock-stat { border-left-color: #f59e0b; }
.out-stock-stat { border-left-color: #ef4444; }

.stat-label {
  font-weight: 500;
  color: #374151;
}

.stat-value {
  font-weight: 700;
  font-size: 1.25rem;
  color: #111827;
}

/* Top Products List */
.top-products-list {
  padding: 1.5rem 2rem 2rem 2rem;
  display: grid;
  gap: 0.75rem;
}

.top-product-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.top-product-item:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.product-rank {
  width: 2rem;
  height: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-name {
  font-weight: 600;
  color: #374151;
}

.product-sales {
  font-size: 0.875rem;
  color: #6b7280;
}

.product-badge {
  font-size: 1.5rem;
}

/* Alert Content */
.alert-content {
  padding: 1.5rem 2rem 2rem 2rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.alert-list {
  display: grid;
  gap: 0.75rem;
}

.alert-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.low-stock-item {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-left: 4px solid #f59e0b;
}

.out-of-stock-item {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  border-left: 4px solid #ef4444;
}

.alert-item:hover {
  transform: translateX(4px);
}

.alert-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.alert-product-name {
  font-weight: 600;
  color: #374151;
}

.alert-stock, .alert-category, .alert-price {
  font-size: 0.875rem;
  color: #6b7280;
}

.alert-status {
  font-size: 1.5rem;
}

.card-footer {
  padding: 1rem 2rem 2rem 2rem;
  border-top: 1px solid #f3f4f6;
}

.action-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.5rem;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  width: 100%;
  justify-content: center;
}

.urgent-btn {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15);
}

/* Recent Sales Table */
.recent-sales-card {
  grid-column: 1 / -1;
}

.table-wrapper {
  padding: 1.5rem 2rem 2rem 2rem;
}

.empty-table {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.modern-table thead {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.modern-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table-row {
  border-bottom: 1px solid #f3f4f6;
  transition: all 0.3s ease;
}

.table-row:hover {
  background: #f8fafc;
}

.table-row:last-child {
  border-bottom: none;
}

.modern-table td {
  padding: 1rem 1.5rem;
  color: #6b7280;
}

.sale-id {
  font-weight: 600;
  color: #374151;
}

.sale-amount {
  font-weight: 600;
  color: #059669;
}

.sale-items {
  color: #8b5cf6;
  font-weight: 500;
}

.sale-date {
  font-size: 0.875rem;
}

.status-badge {
  background: #dcfce7;
  color: #166534;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

/* Animations */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .report-container {
    margin: 0.5rem;
    padding: 1.5rem;
    border-radius: 16px;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .report-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .reports-grid {
    grid-template-columns: 1fr;
  }

  .metric-card {
    padding: 1.5rem;
  }

  .card-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
  }

  .inventory-stats, .alert-content, .table-wrapper {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
  }
}

@media (max-width: 480px) {
  .report-container {
    margin: 0.25rem;
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.75rem;
  }

  .metric-card {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .metric-icon {
    font-size: 2.5rem;
  }

  .modern-table {
    font-size: 0.875rem;
  }

  .modern-table th,
  .modern-table td {
    padding: 0.75rem 1rem;
  }
}
</style>