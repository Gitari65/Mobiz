<template>
  <div class="dashboard-container">
    <h1 class="dashboard-title">Dashboard</h1>
    <p class="greeting">Welcome back, Admin!</p>

    <!-- Alerts -->
    <div v-if="alertMessage" class="alert">{{ alertMessage }}</div>

    <!-- Main Grid -->
    <div class="main-grid">
      
      <!-- Stats Section -->
      <div class="stats-grid">
        <div class="card" v-if="loadingStats">
          <div class="spinner"></div>
        </div>
        <div v-else class="card">
          <h3>Total Sales</h3>
          <p>Ksh {{ stats.total_sales.toLocaleString() }}</p>
        </div>

        <div class="card" v-if="loadingStats">
          <div class="spinner"></div>
        </div>
        <div v-else class="card">
          <h3>Low Stock Items</h3>
          <p>{{ stats.low_stock }}</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button @click="goTo('/sales/new')">New Sale</button>
        <button @click="goTo('/products/new')">Add Product</button>
        <button @click="fetchStats">Refresh Data</button>
      </div>

      <!-- Recent Sales -->
      <div class="recent-sales card">
        <h3>Recent Sales</h3>
        <div v-if="loadingStats" class="spinner"></div>
        <ul v-else>
          <li v-for="sale in stats.recent_sales" :key="sale.id">
            Sale #{{ sale.id }} — Ksh {{ sale.total.toLocaleString() }} 
            <small>{{ new Date(sale.created_at).toLocaleString() }}</small>
          </li>
        </ul>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      stats: {
        total_sales: 0,
        low_stock: 0,
        recent_sales: []
      },
      loadingStats: true,
      alertMessage: ''
    }
  },
  methods: {
    async fetchStats() {
      this.loadingStats = true;
      try {
        let res = await axios.get('/dashboard/stats');
        this.stats = res.data;
        this.alertMessage = 'Data refreshed successfully!';
        setTimeout(() => this.alertMessage = '', 3000);
      } catch (error) {
        this.alertMessage = 'Error fetching dashboard data';
      } finally {
        this.loadingStats = false;
      }
    },
    goTo(path) {
      this.$router.push(path);
    }
  },
  mounted() {
    this.fetchStats();
  }
}
</script>

<style scoped>
.dashboard-container {
  padding: 20px;
}
.dashboard-title {
  font-size: 2em;
  font-weight: bold;
}
.greeting {
  margin-bottom: 20px;
  color: #555;
}
.main-grid {
  display: grid;
  gap: 20px;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}
.card {
  background: white;
  padding: 15px;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.quick-actions {
  display: flex;
  gap: 10px;
}
.quick-actions button {
  padding: 10px 15px;
  border: none;
  background: #3490dc;
  color: white;
  border-radius: 5px;
  cursor: pointer;
}
.recent-sales ul {
  list-style: none;
  padding: 0;
}
.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3490dc;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
  margin: auto;
}
.alert {
  background: #d1ecf1;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 15px;
  color: #0c5460;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
