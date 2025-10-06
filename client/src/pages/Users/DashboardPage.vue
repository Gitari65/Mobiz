<template>
  <div class="dashboard-wrapper">
    <div class="dashboard-container">
      <h1 class="dashboard-title">Dashboard</h1>
      <p class="greeting">Welcome back, Admin!</p>

      <!-- Alerts -->
      <div v-if="alertMessage" class="alert">{{ alertMessage }}</div>

      <!-- Main Grid -->
      <div class="main-grid">
        
      <!-- Stats Section -->
<div class="stats-grid">
  <!-- Today's Sales -->
  <div v-if="loadingStats" class="card spinner-card">
    <div class="spinner"></div>
  </div>
  <div v-else class="card stat-card" :class="salesTrendClass">
    <h3>
      📊 Today's Sales
    </h3>
    <p>Ksh {{ stats.today_sales.toLocaleString() }}</p>
    <small v-if="stats.today_sales > stats.yesterday_sales" class="trend up">
      📈 {{ trendDifference }}% Higher than yesterday
    </small>
    <small v-else-if="stats.today_sales < stats.yesterday_sales" class="trend down">
      📉 {{ trendDifference }}% Lower than yesterday
    </small>
    <small v-else class="trend neutral">
      ➖ No change from yesterday
    </small>
  </div>

  <!-- Low Stock Items -->
  <div v-if="loadingStats" class="card spinner-card">
    <div class="spinner"></div>
  </div>
  <div v-else class="card stat-card">
    <h3>⚠️ Low Stock Items</h3>
    <p>{{ stats.low_stock }}</p>
  </div>

  <!-- Out of Stock Items -->
  <div v-if="loadingStats" class="card spinner-card">
    <div class="spinner"></div>
  </div>
  <div v-else class="card stat-card" :class="{ 'urgent-card': stats.out_of_stock > 0 }">
    <h3>🚫 Out of Stock Items</h3>
    <p>{{ stats.out_of_stock || 0 }}</p>
    <small v-if="stats.out_of_stock > 0" class="urgent-text">
      🔴 Requires immediate attention
    </small>
    <small v-else class="success-text">
      ✅ All products in stock
    </small>
  </div>
</div>


     <!-- Quick Actions -->
<div class="quick-actions card">
  <h3 class="quick-actions-title">Quick Actions</h3>
  <div class="quick-actions-buttons">
    <button @click="goTo('/sales')">🛒 New Sale</button>
    <button @click="goTo('/products')">➕ Add Product</button>
    <button @click="refreshAllData">🔄 Refresh Data</button>
  </div>
</div>

<!-- Out of Stock Products -->
<div class="out-of-stock-section card">
  <h3 class="section-title">
    <i class="fas fa-exclamation-triangle"></i>
    Out of Stock Products
    <span v-if="outOfStockProducts.length > 0" class="count-badge">{{ outOfStockProducts.length }}</span>
  </h3>

  <div v-if="loadingOutOfStock" class="spinner"></div>

  <div v-else-if="outOfStockProducts.length === 0" class="empty-state">
    <div class="empty-icon">📦</div>
    <p class="empty-message">Great! No products are out of stock</p>
    <small class="empty-subtitle">All your inventory levels are healthy</small>
  </div>

  <div v-else class="out-of-stock-grid">
    <div 
      v-for="product in outOfStockProducts" 
      :key="product.id" 
      class="product-card urgent"
    >
      <div class="product-info">
        <div class="product-header">
          <strong class="product-name">{{ product.name }}</strong>
          <span class="product-sku">{{ product.sku || 'No SKU' }}</span>
        </div>
        <div class="product-details">
          <span class="product-category">{{ product.category || 'No Category' }}</span>
          <span class="product-price">Ksh {{ product.price.toLocaleString() }}</span>
        </div>
      </div>
      <div class="product-status">
        <div class="stock-indicator out-of-stock">
          <span class="stock-count">{{ product.stock_quantity }}</span>
          <small>Stock</small>
        </div>
        <button 
          @click="goTo(`/products`)" 
          class="restock-btn"
          title="Restock this product"
        >
          <i class="fas fa-plus"></i>
          Restock
        </button>
      </div>
    </div>
  </div>

  <div v-if="outOfStockProducts.length > 0" class="section-footer">
    <button @click="goTo('/products')" class="view-all-btn">
      <i class="fas fa-boxes"></i>
      Manage All Products
    </button>
  </div>
</div>

<!-- Recent Sales -->
<div class="recent-sales card">
  <h3 class="section-title">🆕 Recent Sales</h3>

  <div v-if="loadingStats" class="spinner"></div>

  <div v-else class="sales-grid">
    <div 
      v-for="sale in stats.recent_sales" 
      :key="sale.id" 
      class="sale-card"
    >
      <div class="sale-info">
        <strong>Sale #{{ sale.id }}</strong>
        <span class="sale-total">Ksh {{ sale.total.toLocaleString() }}</span>
      </div>
      <div class="sale-meta">
        <small class="sale-date">{{ new Date(sale.created_at).toLocaleString() }}</small>
      </div>
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
      stats: {
        today_sales: 0,
        yesterday_sales: 0,
        low_stock: 0,
        out_of_stock: 0,
        recent_sales: [] // added this so v-for works
      },
      outOfStockProducts: [],
      loadingStats: true,
      loadingOutOfStock: true,
      alertMessage: ''
    }
  },
  computed: {
    // % difference between today and yesterday
    trendDifference() {
      if (this.stats.yesterday_sales === 0) return 100
      return Math.abs(
        ((this.stats.today_sales - this.stats.yesterday_sales) /
         this.stats.yesterday_sales) * 100
      ).toFixed(1)
    },
    // Decide card color class
    salesTrendClass() {
      if (this.stats.today_sales > this.stats.yesterday_sales) return 'trend-up'
      if (this.stats.today_sales < this.stats.yesterday_sales) return 'trend-down'
      return 'trend-neutral'
    }
  },
  methods: {
    async fetchStats() {
      this.loadingStats = true
      try {
        const res = await axios.get('http://localhost:8000/dashboard-stats')
        this.stats = res.data
        this.alertMessage = 'Data refreshed successfully!'
        setTimeout(() => (this.alertMessage = ''), 3000)
      } catch (error) {
        console.error(error)
        this.alertMessage = 'Error fetching dashboard data'
      } finally {
        this.loadingStats = false
      }
    },
    async fetchOutOfStockProducts() {
      this.loadingOutOfStock = true
      try {
        const res = await axios.get('http://localhost:8000/products/out-of-stock')
        this.outOfStockProducts = res.data
      } catch (error) {
        console.error('Error fetching out of stock products:', error)
        this.outOfStockProducts = []
      } finally {
        this.loadingOutOfStock = false
      }
    },
    async refreshAllData() {
      await Promise.all([
        this.fetchStats(),
        this.fetchOutOfStockProducts()
      ])
    },
    goTo(path) {
      this.$router.push(path)
    }
  },
  mounted() {
    this.refreshAllData()
  }
}
</script>

<style scoped>
/* Modern Dashboard Styling */
.dashboard-wrapper {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.dashboard-container {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  margin: 1rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.dashboard-title {
  font-size: 2.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.025em;
}

.greeting {
  margin-bottom: 2rem;
  color: #64748b;
  font-size: 1.125rem;
  font-weight: 500;
}

.main-grid {
  display: grid;
  gap: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.card {
  background: white;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.stat-card h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stat-card p {
  font-size: 2.25rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.75rem;
  line-height: 1;
}

.trend {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.trend.up {
  background: #dcfce7;
  color: #166534;
}

.trend.down {
  background: #fee2e2;
  color: #991b1b;
}

.trend.neutral {
  background: #f3f4f6;
  color: #374151;
}

.trend-up {
  border-left: 4px solid #10b981;
}

.trend-down {
  border-left: 4px solid #ef4444;
}

.trend-neutral {
  border-left: 4px solid #6b7280;
}

.urgent-card {
  border-left: 4px solid #ef4444 !important;
  background: linear-gradient(135deg, #fef7f7 0%, #fef2f2 100%);
}

.urgent-text {
  color: #dc2626;
  font-weight: 600;
}

.success-text {
  color: #059669;
  font-weight: 600;
}

.quick-actions {
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.quick-actions-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quick-actions-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.quick-actions-buttons button {
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.quick-actions-buttons button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.quick-actions-buttons button:hover::before {
  left: 100%;
}

.quick-actions-buttons button:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.quick-actions-buttons button:active {
  transform: translateY(0);
}

.recent-sales {
  padding: 2rem;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sales-grid {
  display: grid;
  gap: 1rem;
}

.sale-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.sale-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.sale-card:hover::before {
  opacity: 1;
}

.sale-card:hover {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  transform: translateX(4px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.sale-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.sale-info strong {
  font-weight: 700;
  color: #374151;
  font-size: 1rem;
}

.sale-total {
  font-weight: 600;
  color: #059669;
  font-size: 1.125rem;
}

.sale-meta {
  text-align: right;
}

.sale-date {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.spinner-card {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 120px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.alert {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
  padding: 1rem 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  border: 1px solid #93c5fd;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
}

/* Out of Stock Section */
.out-of-stock-section {
  padding: 2rem;
  background: linear-gradient(135deg, #fef7f7 0%, #fef2f2 100%);
  border-left: 4px solid #ef4444;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}

.section-title i {
  color: #ef4444;
  font-size: 1.25rem;
}

.count-badge {
  background: #ef4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  min-width: 1.5rem;
  text-align: center;
  margin-left: auto;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #6b7280;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-message {
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-subtitle {
  font-size: 0.875rem;
  color: #9ca3af;
}

.out-of-stock-grid {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.product-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: white;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.product-card.urgent {
  border-left: 4px solid #ef4444;
  background: linear-gradient(135deg, #ffffff 0%, #fef7f7 100%);
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.1), 0 4px 6px -2px rgba(239, 68, 68, 0.05);
}

.product-info {
  flex: 1;
}

.product-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.product-name {
  font-weight: 700;
  color: #374151;
  font-size: 1rem;
}

.product-sku {
  font-size: 0.75rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-weight: 500;
}

.product-details {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.product-category {
  font-size: 0.875rem;
  color: #8b5cf6;
  background: #f3f4f6;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 500;
}

.product-price {
  font-size: 0.875rem;
  color: #059669;
  font-weight: 600;
}

.product-status {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stock-indicator {
  text-align: center;
  padding: 0.75rem;
  border-radius: 12px;
  min-width: 4rem;
}

.stock-indicator.out-of-stock {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  border: 1px solid #f87171;
}

.stock-count {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: #dc2626;
  line-height: 1;
}

.stock-indicator small {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.restock-btn {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
}

.restock-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.3);
}

.restock-btn:active {
  transform: translateY(0);
}

.section-footer {
  text-align: center;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.view-all-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.875rem 1.5rem;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 600;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.view-all-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.15);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-container {
    margin: 0.5rem;
    padding: 1.5rem;
    border-radius: 16px;
  }
  
  .dashboard-title {
    font-size: 2rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-buttons {
    grid-template-columns: 1fr;
  }

  .product-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .product-details {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .product-status {
    flex-direction: column;
    gap: 0.75rem;
  }
}

@media (max-width: 480px) {
  .dashboard-container {
    margin: 0.25rem;
    padding: 1rem;
  }
  
  .card {
    padding: 1.5rem;
  }
  
  .dashboard-title {
    font-size: 1.75rem;
  }

  .product-card {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .product-status {
    flex-direction: row;
    justify-content: space-between;
  }
}
</style>
