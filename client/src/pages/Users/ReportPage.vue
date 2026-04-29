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
          <button @click="reloadReports" class="refresh-btn" :disabled="isLoading">
            <i class="fas fa-sync-alt" :class="{ 'spinning': isLoading }"></i>
            {{ isLoading ? 'Reloading...' : 'Reload' }}
          </button>
        </div>
      </div>

      <!-- Date Range Filter -->
      <div class="filter-bar">
        <div class="filter-group">
          <label><i class="fas fa-calendar"></i> From</label>
          <input v-model="filters.startDate" type="date" class="date-input" />
        </div>
        <div class="filter-group">
          <label><i class="fas fa-calendar"></i> To</label>
          <input v-model="filters.endDate" type="date" class="date-input" />
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
            @click="setActiveTab(tab.id)"
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
          <div class="report-actions">
            <button class="download-btn excel" @click="downloadDSRS" :disabled="downloadingDSRS">
              <i class="fas fa-file-excel"></i>
              {{ downloadingDSRS ? 'Downloading...' : 'Download DSRS (Excel)' }}
            </button>
            <button class="download-btn excel" @click="downloadSalesSummary" :disabled="downloadingSummary">
              <i class="fas fa-file-excel"></i>
              {{ downloadingSummary ? 'Downloading...' : 'Download Sales Summary (Excel)' }}
            </button>
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
                  <tr><th>Sale ID</th><th v-if="isAdminRole">Cashier</th><th>Amount</th><th>Items</th><th>Customer</th><th>Payment</th><th>M-Pesa Receipt</th><th>Date & Time</th></tr>
                </thead>
                <tbody>
                  <tr v-for="sale in reports.sales.transactions" :key="sale.id">
                    <td class="sale-id">#{{ sale.id }}</td>
                    <td v-if="isAdminRole">{{ sale.cashier || 'Unknown' }}</td>
                    <td class="amount">Ksh {{ formatNumber(sale.total) }}</td>
                    <td>{{ sale.items_count || 0 }} items</td>
                    <td>{{ sale.customer_name || 'Walk-in' }}</td>
                    <td>{{ sale.payment_method || 'Cash' }}</td>
                    <td>{{ sale.mpesa_receipt_number || '-' }}</td>
                    <td>{{ formatDate(sale.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="pagination.sales.lastPage > 1" class="pagination-controls">
                <button class="pagination-btn" :disabled="pagination.sales.currentPage <= 1 || isLoading" @click="changeSalesPage(pagination.sales.currentPage - 1)">Previous</button>
                <span class="pagination-label">Page {{ pagination.sales.currentPage }} of {{ pagination.sales.lastPage }} ({{ pagination.sales.total }} records)</span>
                <button class="pagination-btn" :disabled="pagination.sales.currentPage >= pagination.sales.lastPage || isLoading" @click="changeSalesPage(pagination.sales.currentPage + 1)">Next</button>
              </div>
            </div>
          </div>

          <div v-if="isAdminRole" class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-user-clock"></i> Cashier Daily Performance</h2></div>
            <div class="metrics-grid" style="margin-bottom: 16px;">
              <div class="metric-card">
                <div class="metric-icon">👥</div>
                <div class="metric-content">
                  <h3>Active Staff</h3>
                  <p class="metric-value">{{ reports.sales.cashier_summary?.length || 0 }}</p>
                  <small>In selected range</small>
                </div>
              </div>
              <div class="metric-card">
                <div class="metric-icon">🧾</div>
                <div class="metric-content">
                  <h3>Daily Rows</h3>
                  <p class="metric-value">{{ reports.sales.cashier_daily_performance?.length || 0 }}</p>
                  <small>Cashier-day insights</small>
                </div>
              </div>
              <div class="metric-card">
                <div class="metric-icon">⏱️</div>
                <div class="metric-content">
                  <h3>Tracked Time</h3>
                  <p class="metric-value">{{ formatDurationMinutes(totalCashierTrackedMinutes) }}</p>
                  <small>Estimated from login/activity</small>
                </div>
              </div>
            </div>
            <div class="table-wrapper">
              <div v-if="!reports.sales.cashier_daily_performance?.length" class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No cashier performance data for this period</p>
              </div>
              <table v-else class="modern-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th>First Login</th>
                    <th>First Sale</th>
                    <th>Last Sale</th>
                    <th>Active Time</th>
                    <th>Transactions</th>
                    <th>Total Sales</th>
                    <th>Avg Ticket</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in reports.sales.cashier_daily_performance" :key="`${row.activity_date}-${row.user_id}`">
                    <td>{{ formatDate(row.activity_date) }}</td>
                    <td>{{ row.cashier_name }}</td>
                    <td>{{ row.first_login_at ? formatDate(row.first_login_at) : '-' }}</td>
                    <td>{{ row.first_sale_at ? formatDate(row.first_sale_at) : '-' }}</td>
                    <td>{{ row.last_sale_at ? formatDate(row.last_sale_at) : '-' }}</td>
                    <td>{{ formatDurationMinutes(row.active_minutes) }}</td>
                    <td>{{ row.transactions || 0 }}</td>
                    <td class="amount">Ksh {{ formatNumber(row.total_sales || 0) }}</td>
                    <td>Ksh {{ formatNumber(row.avg_transaction || 0) }}</td>
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
          <div class="report-actions">
            <button class="download-btn excel" @click="downloadTransfersReport" :disabled="downloadingTransfers">
              <i class="fas fa-file-excel"></i>
              {{ downloadingTransfers ? 'Downloading...' : 'Download Transfers (Excel)' }}
            </button>
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
              <div v-if="pagination.transfers.lastPage > 1" class="pagination-controls">
                <button class="pagination-btn" :disabled="pagination.transfers.currentPage <= 1 || isLoading" @click="changeTransfersPage(pagination.transfers.currentPage - 1)">Previous</button>
                <span class="pagination-label">Page {{ pagination.transfers.currentPage }} of {{ pagination.transfers.lastPage }} ({{ pagination.transfers.total }} records)</span>
                <button class="pagination-btn" :disabled="pagination.transfers.currentPage >= pagination.transfers.lastPage || isLoading" @click="changeTransfersPage(pagination.transfers.currentPage + 1)">Next</button>
              </div>
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
          <div class="report-actions">
            <button class="download-btn excel" @click="downloadPromotionsReport" :disabled="downloadingPromotions">
              <i class="fas fa-file-excel"></i>
              {{ downloadingPromotions ? 'Downloading...' : 'Download Promotions (CSV)' }}
            </button>
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

        <!-- Suppliers Tab -->
        <div v-if="activeTab === 'suppliers'" class="tab-pane active">
          <div class="metrics-grid">
            <div class="metric-card">
              <div class="metric-icon">🏢</div>
              <div class="metric-content">
                <h3>Total Suppliers</h3>
                <p class="metric-value">{{ reports.suppliers.total_suppliers || 0 }}</p>
                <small>Registered</small>
              </div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📦</div>
              <div class="metric-content">
                <h3>Products Supplied</h3>
                <p class="metric-value">{{ reports.suppliers.total_products_supplied || 0 }}</p>
                <small>Unique products</small>
              </div>
            </div>
            <div class="metric-card">
              <div class="metric-icon">📝</div>
              <div class="metric-content">
                <h3>Recent Purchases</h3>
                <p class="metric-value">{{ reports.suppliers.recent_purchases_count || 0 }}</p>
                <small>Last 30 days</small>
              </div>
            </div>
          </div>
          <div class="report-actions">
            <button class="download-btn excel" @click="downloadSuppliersReport" :disabled="downloadingSuppliers">
              <i class="fas fa-file-excel"></i>
              {{ downloadingSuppliers ? 'Downloading...' : 'Download Suppliers (CSV)' }}
            </button>
          </div>
          <div class="report-card">
            <div class="card-header"><h2 class="card-title"><i class="fas fa-truck"></i> Suppliers List</h2></div>
            <div class="table-wrapper">
              <div v-if="!reports.suppliers.list?.length" class="empty-state"><i class="fas fa-inbox"></i><p>No suppliers found</p></div>
              <table v-else class="modern-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Products Supplied</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in reports.suppliers.list" :key="s.id">
                    <td>{{ s.name }}</td>
                    <td>{{ s.contact_person }}</td>
                    <td>{{ s.email }}</td>
                    <td>{{ s.phone }}</td>
                    <td>{{ s.products_supplied }}</td>
                    <td>{{ s.notes }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="pagination.invoices.lastPage > 1" class="pagination-controls">
                <button class="pagination-btn" :disabled="pagination.invoices.currentPage <= 1 || loadingInvoices" @click="changeInvoicesPage(pagination.invoices.currentPage - 1)">Previous</button>
                <span class="pagination-label">Page {{ pagination.invoices.currentPage }} of {{ pagination.invoices.lastPage }} ({{ pagination.invoices.total }} records)</span>
                <button class="pagination-btn" :disabled="pagination.invoices.currentPage >= pagination.invoices.lastPage || loadingInvoices" @click="changeInvoicesPage(pagination.invoices.currentPage + 1)">Next</button>
              </div>
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
          <div class="report-actions">
            <button class="download-btn excel" @click="downloadCustomersReport" :disabled="downloadingCustomers">
              <i class="fas fa-file-excel"></i>
              {{ downloadingCustomers ? 'Downloading...' : 'Download Customers (CSV)' }}
            </button>
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

        <!-- Invoices Tab -->
        <div v-if="activeTab === 'invoices'" class="tab-pane active">
          <div class="report-actions">
            <button class="add-btn" @click="showInvoiceModal = true">
              <i class="fas fa-plus"></i> New Invoice
            </button>
          </div>
          <div class="report-card">
            <div class="card-header">
              <h2 class="card-title"><i class="fas fa-file-invoice"></i> Invoices</h2>
            </div>
            <div class="table-wrapper">
              <div v-if="loadingInvoices" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
              <div v-else-if="!invoices.length" class="empty-state"><i class="fas fa-inbox"></i><p>No invoices found</p></div>
              <table v-else class="modern-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Supplier/Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>M-Pesa Receipt</th>
                    <th>Status</th>
                    <th>Units</th>
                    <th>Due Date</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="inv in invoices" :key="inv.id">
                    <td>{{ inv.id }}</td>
                    <td>{{ inv.type }}</td>
                    <td>
                      <span v-if="inv.type === 'purchase'">{{ inv.supplier?.name || '-' }}</span>
                      <span v-else-if="inv.type === 'sale'">{{ inv.customer?.name || '-' }}</span>
                      <span v-else>-</span>
                    </td>
                    <td>{{ inv.total | currency }}</td>
                    <td>{{ inv.payment_method || '-' }}</td>
                    <td>{{ inv.mpesa_receipt_number || '-' }}</td>
                    <td>{{ inv.status }}</td>
                    <td>
                      <span class="badge-scope">{{ getInvoiceUomSummary(inv) }}</span>
                    </td>
                    <td>{{ inv.due_date ? inv.due_date.substr(0,10) : '-' }}</td>
                    <td>{{ inv.created_at ? inv.created_at.substr(0,10) : '-' }}</td>
                    <td>
                      <button @click="viewInvoice(inv)" title="View"><i class="fas fa-eye"></i></button>
                      <button v-if="inv.status !== 'reversed'" @click="reverseInvoice(inv)" title="Reverse"><i class="fas fa-undo"></i></button>
                      <button @click="deleteInvoice(inv)" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Invoice Modal -->
          <div v-if="showInvoiceModal" class="modal-overlay">
            <div class="modal">
              <h3>{{ invoiceForm.id ? 'Edit Invoice' : 'New Invoice' }}</h3>
              <form @submit.prevent="saveInvoice">
                <div class="form-group">
                  <label>Type
                    <select v-model="invoiceForm.type" required @change="resetInvoiceForm">
                      <option value="purchase">Purchase</option>
                      <option value="sale">Sale</option>
                      <option value="service">Service</option>
                      <option value="other">Other</option>
                    </select>
                  </label>
                </div>

                <!-- Purchase Type Fields -->
                <div v-if="invoiceForm.type === 'purchase'" class="form-group">
                  <label>Supplier
                    <select v-model="invoiceForm.supplier_id" required>
                      <option value="">-- Select Supplier --</option>
                      <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </label>
                </div>

                <!-- Sale Type Fields -->
                <div v-if="invoiceForm.type === 'sale'" class="form-group">
                  <label>Customer
                    <select v-model="invoiceForm.customer_id" required>
                      <option value="">-- Select Customer --</option>
                      <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                  </label>
                </div>

                <!-- Service Type Fields -->
                <div v-if="invoiceForm.type === 'service'" class="form-group">
                  <label>Service Description
                    <textarea v-model="invoiceForm.service_description" placeholder="Enter service details..." required></textarea>
                  </label>
                  <label>Service Amount
                    <input type="number" v-model.number="invoiceForm.service_amount" min="0" step="0.01" placeholder="Amount" required />
                  </label>
                </div>

                <div class="form-group">
                  <label>Due Date
                    <input type="date" v-model="invoiceForm.due_date" />
                  </label>
                </div>

                <div class="form-group">
                  <label>Notes
                    <textarea v-model="invoiceForm.notes" placeholder="Add any additional notes..."></textarea>
                  </label>
                </div>

                <!-- Items Section for Purchase and Sale -->
                <div v-if="['purchase', 'sale'].includes(invoiceForm.type)" class="form-group">
                  <label class="section-label">
                    <i class="fas fa-box"></i> Items
                  </label>
                  <div v-for="(item, idx) in invoiceForm.items" :key="idx" class="invoice-item-row">
                    <select v-model="item.product_id" required @change="onInvoiceProductChange(item)">
                      <option value="">-- Select Product --</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <select v-model="item.uom_id" style="width:100px" @change="onInvoiceUomChange(item)">
                      <option :value="null">UOM</option>
                      <option v-for="u in getProductUomOptions(item.product_id)" :key="u.id" :value="u.id">
                        {{ u.abbreviation || u.name }}
                      </option>
                    </select>
                    <input type="number" v-model.number="item.quantity" min="1" placeholder="Qty" required style="width:60px" />
                    <input type="number" v-model.number="item.unit_price" min="0" step="0.01" placeholder="Unit Price" required style="width:90px" />
                    <button type="button" @click="removeInvoiceItem(idx)" v-if="invoiceForm.items.length > 1" class="btn-remove">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                  <button type="button" @click="addInvoiceItem" class="btn-add-item">
                    <i class="fas fa-plus"></i> Add Item
                  </button>
                </div>

                <div class="modal-actions">
                  <button type="submit" class="btn-primary">{{ invoiceForm.id ? 'Update' : 'Create' }}</button>
                  <button type="button" @click="closeInvoiceModal" class="btn-secondary">Cancel</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Invoice View Modal -->
          <div v-if="viewingInvoice" class="modal-overlay" @click="viewingInvoice = null">
            <div class="invoice-modal" @click.stop>
              <!-- Header -->
              <div class="invoice-header">
                <div>
                  <h3>Invoice #{{ viewingInvoice.id }}</h3>
                  <p class="invoice-date">{{ viewingInvoice.created_at ? new Date(viewingInvoice.created_at).toLocaleDateString() : '-' }}</p>
                </div>
                <div class="invoice-status-badge" :class="'status-' + viewingInvoice.status">
                  {{ viewingInvoice.status?.toUpperCase() || 'PENDING' }}
                </div>
              </div>

              <!-- Invoice Details -->
              <div class="invoice-details-grid">
                <div class="detail-section">
                  <h4>Details</h4>
                  <div class="detail-item">
                    <span class="label">Type:</span>
                    <span class="value">{{ viewingInvoice.type?.toUpperCase() || 'N/A' }}</span>
                  </div>
                  <div class="detail-item" v-if="viewingInvoice.supplier">
                    <span class="label">Supplier:</span>
                    <span class="value">{{ viewingInvoice.supplier.name }}</span>
                  </div>
                  <div class="detail-item" v-if="viewingInvoice.customer">
                    <span class="label">Customer:</span>
                    <span class="value">{{ viewingInvoice.customer.name }}</span>
                  </div>
                </div>

                <div class="detail-section">
                  <h4>Dates</h4>
                  <div class="detail-item">
                    <span class="label">Created:</span>
                    <span class="value">{{ viewingInvoice.created_at ? viewingInvoice.created_at.substr(0, 10) : '-' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">Due Date:</span>
                    <span class="value">{{ viewingInvoice.due_date ? viewingInvoice.due_date.substr(0, 10) : '-' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">Payment:</span>
                    <span class="value">{{ viewingInvoice.payment_method || '-' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">M-Pesa Receipt:</span>
                    <span class="value">{{ viewingInvoice.mpesa_receipt_number || '-' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">M-Pesa Phone:</span>
                    <span class="value">{{ viewingInvoice.mpesa_phone_number || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Notes Section -->
              <div v-if="viewingInvoice.notes" class="notes-section">
                <h4>Notes</h4>
                <div class="notes-content">{{ viewingInvoice.notes }}</div>
              </div>

              <!-- Service Details (for service type invoices) -->
              <div v-if="viewingInvoice.type === 'service'" class="service-section">
                <h4>Service Details</h4>
                <div class="service-detail-box">
                  <p><b>Description:</b> {{ viewingInvoice.service_description || 'N/A' }}</p>
                  <p><b>Amount:</b> Ksh {{ Number(viewingInvoice.service_amount || viewingInvoice.total || 0).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</p>
                </div>
              </div>

              <!-- Items Table (for purchase/sale invoices) -->
              <div v-else class="items-section">
                <h4>Invoice Items</h4>
                <div class="items-table-wrapper">
                  <table class="items-table" v-if="viewingInvoice.items && viewingInvoice.items.length > 0">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>UOM</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in viewingInvoice.items" :key="item.id">
                        <td>{{ item.product?.name || item.description || 'N/A' }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.uom?.abbreviation || item.uom?.name || 'Base' }}</td>
                        <td>Ksh {{ Number(item.unit_price || 0).toLocaleString() }}</td>
                        <td class="total-cell">Ksh {{ Number(item.total_price || 0).toLocaleString() }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-else class="empty-items">No items in this invoice</div>
                </div>
              </div>

              <!-- Summary -->
              <div class="invoice-summary">
                <div class="summary-row">
                  <span>Subtotal:</span>
                  <span>Ksh {{ Number((viewingInvoice.total || 0) * 0.9).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</span>
                </div>
                <div class="summary-row total-row">
                  <span>Total:</span>
                  <span>Ksh {{ Number(viewingInvoice.total || 0).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="invoice-actions">
                <button class="btn btn-secondary" @click="viewingInvoice = null">Close</button>
                <button class="btn btn-primary">
                  <i class="fas fa-download"></i> Download
                </button>
                <button class="btn btn-primary">
                  <i class="fas fa-print"></i> Print
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Alert Modal -->
    <div v-if="customAlert.show" class="modal-overlay" @click="customAlert.show = false">
      <div class="alert-modal" @click.stop :class="'alert-' + customAlert.type">
        <div class="alert-icon">
          <i v-if="customAlert.type === 'success'" class="fas fa-check-circle"></i>
          <i v-else-if="customAlert.type === 'error'" class="fas fa-times-circle"></i>
          <i v-else-if="customAlert.type === 'warning'" class="fas fa-exclamation-circle"></i>
          <i v-else class="fas fa-info-circle"></i>
        </div>
        <div class="alert-content">
          <h3>{{ customAlert.title }}</h3>
          <p>{{ customAlert.message }}</p>
        </div>
        <div class="alert-actions">
          <button @click="customAlert.show = false" class="btn-alert-close">{{ customAlert.buttonText || 'Close' }}</button>
        </div>
      </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div v-if="confirmation.show" class="modal-overlay" @click="confirmation.show = false">
      <div class="confirm-modal" @click.stop>
        <div class="confirm-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="confirm-content">
          <h3>{{ confirmation.title }}</h3>
          <p>{{ confirmation.message }}</p>
        </div>
        <div class="confirm-actions">
          <button @click="confirmation.onConfirm()" class="btn-confirm-yes">
            {{ confirmation.confirmText || 'Confirm' }}
          </button>
          <button @click="confirmation.show = false" class="btn-confirm-no">
            {{ confirmation.cancelText || 'Cancel' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { cachedGet } from '../../services/api'

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
        { id: 'customers', name: 'Customers', icon: 'fas fa-users' },
        { id: 'suppliers', name: 'Suppliers', icon: 'fas fa-truck' },
        { id: 'invoices', name: 'Invoices', icon: 'fas fa-file-invoice' }
      ],
      filters: {
        startDate: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
        endDate: new Date().toISOString().split('T')[0]
      },
      reports: {
        sales: { total_revenue: 0, total_transactions: 0, avg_transaction: 0, total_items: 0, transactions: [], cashier_summary: [], cashier_daily_performance: [] },
        transfers: { total_count: 0, items_in: 0, items_out: 0, list: [] },
        analytics: { total_products: 0, in_stock: 0, low_stock: 0, out_of_stock: 0, top_products: [], low_stock_items: [] },
        promotions: { active_count: 0, total_discount: 0, total_usage: 0, list: [] },
        customers: { total_unique: 0, total_served: 0, walk_ins: 0, avg_spend: 0, top_customers: [] },
        suppliers: { total_suppliers: 0, total_products_supplied: 0, recent_purchases_count: 0, list: [] }
      },
      userRole: '',
      // Cache system to prevent redundant API calls
      dataCache: {},
      cacheKey: '',
      lastFetchTime: null,
      downloadingDSRS: false,
      downloadingSummary: false,
      downloadingTransfers: false,
      downloadingPromotions: false,
      downloadingCustomers: false,
      downloadingSuppliers: false,
      // Invoice management
      invoices: [],
      loadingInvoices: false,
      invoiceDependenciesLoaded: false,
      showInvoiceModal: false,
      viewingInvoice: null,
      suppliers: [],
      customers: [],
      products: [],
      invoiceForm: {
        id: null,
        type: 'purchase',
        supplier_id: null,
        customer_id: null,
        due_date: '',
        notes: '',
        items: [{ product_id: null, uom_id: null, quantity: 1, unit_price: 0 }],
        service_description: '',
        service_amount: 0
      },
      // Custom alerts and confirmations
      customAlert: {
        show: false,
        type: 'success', // success, error, warning, info
        title: '',
        message: '',
        buttonText: 'Close'
      },
      confirmation: {
        show: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        onConfirm: () => {}
      },
      loadedTabs: {
        sales: false,
        transfers: false,
        analytics: false,
        promotions: false,
        customers: false,
        suppliers: false,
        invoices: false
      },
      pagination: {
        sales: { currentPage: 1, perPage: 100, lastPage: 1, total: 0 },
        transfers: { currentPage: 1, perPage: 100, lastPage: 1, total: 0 },
        invoices: { currentPage: 1, perPage: 50, lastPage: 1, total: 0 }
      }
    }
  },
  mounted() {
    this.userRole = this.resolveUserRole()
    // Load only the active tab at startup for faster initial render.
    this.loadActiveTabData()
  },
  computed: {
    isAdminRole() {
      return ['admin', 'administrator', 'superuser', 'super_user', 'super user'].includes(this.userRole)
    },
    totalCashierTrackedMinutes() {
      const rows = this.reports?.sales?.cashier_daily_performance || []
      return rows.reduce((sum, row) => sum + Number(row.active_minutes || 0), 0)
    }
  },
  methods: {
    resolveUserRole() {
      const candidateKeys = ['userData', 'user', 'pendingSession']
      for (const key of candidateKeys) {
        try {
          const parsed = JSON.parse(localStorage.getItem(key) || 'null')
          const roleName = parsed?.role?.name || parsed?.role
          if (roleName) return String(roleName).toLowerCase().trim()
        } catch (error) {
          // Ignore malformed values.
        }
      }
      return ''
    },
    async setActiveTab(tabId) {
      this.activeTab = tabId
      await this.loadActiveTabData()
    },
    async loadActiveTabData(force = false) {
      const tab = this.activeTab
      if (!force && this.loadedTabs[tab]) return

      this.isLoading = true
      try {
        if (tab === 'sales') await this.fetchSalesReport(force)
        else if (tab === 'transfers') await this.fetchTransfersReport(force)
        else if (tab === 'analytics') await this.fetchAnalyticsReport(force)
        else if (tab === 'promotions') await this.fetchPromotionsReport(force)
        else if (tab === 'customers') await this.fetchCustomersReport(force)
        else if (tab === 'suppliers') await this.fetchSuppliersReport(force)
        else if (tab === 'invoices') await this.fetchInvoices(force)

        this.loadedTabs[tab] = true
      } catch (err) {
        console.error('Error loading active tab data:', err)
      } finally {
        this.isLoading = false
      }
    },
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
    
    async reloadReports() {
      // Always fetch fresh data for current filters
      this.isLoading = true
      try {
        await this.loadActiveTabData(true)
      } catch (err) {
        console.error('Error loading reports:', err)
      } finally {
        this.isLoading = false
      }
    },
    parsePaginationMeta(rawPagination, fallback = {}) {
      const currentPage = Number(rawPagination?.current_page || rawPagination?.currentPage || fallback.currentPage || 1)
      const perPage = Number(rawPagination?.per_page || rawPagination?.perPage || fallback.perPage || 50)
      const lastPage = Number(rawPagination?.last_page || rawPagination?.lastPage || fallback.lastPage || 1)
      const total = Number(rawPagination?.total || fallback.total || 0)

      return {
        currentPage,
        perPage,
        lastPage,
        total
      }
    },
    async changeSalesPage(nextPage) {
      const page = Number(nextPage)
      if (!page || page < 1 || page === this.pagination.sales.currentPage) return
      this.pagination.sales.currentPage = page
      this.isLoading = true
      try {
        await this.fetchSalesReport(true)
      } finally {
        this.isLoading = false
      }
    },
    async changeTransfersPage(nextPage) {
      const page = Number(nextPage)
      if (!page || page < 1 || page === this.pagination.transfers.currentPage) return
      this.pagination.transfers.currentPage = page
      this.isLoading = true
      try {
        await this.fetchTransfersReport(true)
      } finally {
        this.isLoading = false
      }
    },
    async changeInvoicesPage(nextPage) {
      const page = Number(nextPage)
      if (!page || page < 1 || page === this.pagination.invoices.currentPage) return
      this.pagination.invoices.currentPage = page
      await this.fetchInvoices(true)
    },
    async fetchSalesReport(forceRefresh = false) {
      console.log('🔍 Fetching Sales Report', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
        page: this.pagination.sales.currentPage,
      })
      try {
        const salesPerPage = this.pagination.sales.perPage || 100
        const data = await cachedGet('/api/reports/sales', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate,
            per_page: salesPerPage,
            page: this.pagination.sales.currentPage
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/api/reports/sales:${this.filters.startDate}:${this.filters.endDate}:${salesPerPage}:${this.pagination.sales.currentPage}`
        })
        console.log('✅ Sales Report Received', data)
        this.reports.sales = {
          ...data,
          cashier_summary: Array.isArray(data?.cashier_summary) ? data.cashier_summary : [],
          cashier_daily_performance: Array.isArray(data?.cashier_daily_performance) ? data.cashier_daily_performance : []
        }
        this.pagination.sales = this.parsePaginationMeta(data?.pagination, {
          currentPage: this.pagination.sales.currentPage,
          perPage: salesPerPage,
          lastPage: 1,
          total: Array.isArray(data?.transactions) ? data.transactions.length : 0
        })
      } catch (err) {
        console.error('❌ Failed to load sales report:', err.response?.data || err.message)
        this.reports.sales = { total_revenue: 0, total_transactions: 0, avg_transaction: 0, total_items: 0, transactions: [], cashier_summary: [], cashier_daily_performance: [] }
        this.pagination.sales = { currentPage: 1, perPage: this.pagination.sales.perPage || 100, lastPage: 1, total: 0 }
      }
    },
    async fetchTransfersReport(forceRefresh = false) {
      console.log('🚚 Fetching Transfers Report', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
        page: this.pagination.transfers.currentPage,
      })
      try {
        const transfersPerPage = this.pagination.transfers.perPage || 100
        const data = await cachedGet('/reports/transfers', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate,
            per_page: transfersPerPage,
            page: this.pagination.transfers.currentPage
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/reports/transfers:${this.filters.startDate}:${this.filters.endDate}:${transfersPerPage}:${this.pagination.transfers.currentPage}`
        })
        console.log('✅ Transfers Report Received', data)
        this.reports.transfers = data
        this.pagination.transfers = this.parsePaginationMeta(data?.pagination, {
          currentPage: this.pagination.transfers.currentPage,
          perPage: transfersPerPage,
          lastPage: 1,
          total: Array.isArray(data?.list) ? data.list.length : 0
        })
      } catch (err) {
        console.error('❌ Failed to load transfers report:', err.response?.data || err.message)
        this.reports.transfers = { total_count: 0, items_in: 0, items_out: 0, list: [] }
        this.pagination.transfers = { currentPage: 1, perPage: this.pagination.transfers.perPage || 100, lastPage: 1, total: 0 }
      }
    },
    async fetchAnalyticsReport(forceRefresh = false) {
      console.log('📊 Fetching Analytics Report', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
      })
      try {
        const data = await cachedGet('/reports/analytics', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/reports/analytics:${this.filters.startDate}:${this.filters.endDate}`
        })
        console.log('✅ Analytics Report Received', data)
        this.reports.analytics = data
      } catch (err) {
        console.error('❌ Failed to load analytics report:', err.response?.data || err.message)
        this.reports.analytics = { total_products: 0, in_stock: 0, low_stock: 0, out_of_stock: 0, top_products: [], low_stock_items: [] }
      }
    },
    async fetchPromotionsReport(forceRefresh = false) {
      console.log('🎁 Fetching Promotions Report', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
      })
      try {
        const data = await cachedGet('/reports/promotions', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/reports/promotions:${this.filters.startDate}:${this.filters.endDate}`
        })
        console.log('✅ Promotions Report Received', data)
        this.reports.promotions = data
      } catch (err) {
        console.error('❌ Failed to load promotions report:', err.response?.data || err.message)
        this.reports.promotions = { active_count: 0, total_discount: 0, total_usage: 0, list: [] }
      }
    },
    async fetchSuppliersReport(forceRefresh = false) {
      try {
        const data = await cachedGet('/reports/suppliers', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/reports/suppliers:${this.filters.startDate}:${this.filters.endDate}`
        })
        this.reports.suppliers = data
      } catch (err) {
        console.error('❌ Failed to load suppliers report:', err.response?.data || err.message)
        this.reports.suppliers = { total_suppliers: 0, total_products_supplied: 0, recent_purchases_count: 0, list: [] }
      }
    },
    async fetchCustomersReport(forceRefresh = false) {
      try {
        const data = await cachedGet('/reports/customers', {
          params: {
            start_date: this.filters.startDate,
            end_date: this.filters.endDate
          },
          ttlMs: 45 * 1000,
          forceRefresh,
          cacheKey: `/reports/customers:${this.filters.startDate}:${this.filters.endDate}`
        })
        this.reports.customers = data
      } catch (err) {
        console.error('❌ Failed to load customers report:', err.response?.data || err.message)
        this.reports.customers = { total_unique: 0, total_served: 0, walk_ins: 0, avg_spend: 0, top_customers: [] }
      }
    },
    async applyFilters() {
      // Force refresh when filters change
      await this.reloadReports()
    },
    resetFilters() {
      const today = new Date()
      const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
      this.filters.startDate = thirtyDaysAgo.toISOString().split('T')[0]
      this.filters.endDate = today.toISOString().split('T')[0]
      this.pagination.sales.currentPage = 1
      this.pagination.transfers.currentPage = 1
      this.pagination.invoices.currentPage = 1
      // Do not auto-reload, user must click Reload
    },
    async refreshAllReports() {
      // Force refresh bypasses cache
      await this.reloadReports()
    },
    formatNumber(num) {
      if (!num) return '0'
      return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num)
    },
    formatDate(date) {
      if (!date) return '-'
      return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(date))
    },
    formatDurationMinutes(minutes) {
      const total = Number(minutes || 0)
      if (!Number.isFinite(total) || total <= 0) return '0m'
      const hrs = Math.floor(total / 60)
      const mins = total % 60
      if (!hrs) return `${mins}m`
      return `${hrs}h ${mins}m`
    },
    formatPromoType(type) {
      const map = { percentage: 'Percentage', fixed_amount: 'Fixed Amount', buy_x_get_y: 'Buy X Get Y', spend_save: 'Spend & Save', bulk_discount: 'Bulk Discount' }
      return map[type] || type
    },
    formatPromoScope(scope) {
      const map = { all: 'All', category: 'Category', product: 'Product' }
      return map[scope] || scope
    },
    getInvoiceUomSummary(invoice) {
      const items = Array.isArray(invoice?.items) ? invoice.items : []
      if (!items.length) return 'No items'

      const counters = {}
      for (const item of items) {
        const label = item?.uom?.abbreviation || item?.uom?.name || 'Base'
        counters[label] = (counters[label] || 0) + 1
      }

      const parts = Object.entries(counters).map(([label, count]) => {
        return count > 1 ? `${label} x${count}` : label
      })

      const preview = parts.slice(0, 2).join(', ')
      if (parts.length > 2) {
        return `${preview} +${parts.length - 2}`
      }
      return preview
    },
    async downloadDSRS() {
      console.log('📥 Downloading DSRS', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
      })
      this.downloadingDSRS = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        const res = await axios.get('/api/reports/sales/dsrs-export', {
          params,
          responseType: 'blob'
        })
        console.log('✅ DSRS Downloaded', { size: res.data.size })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `DSRS_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download DSRS:', err.response?.data || err.message)
        alert('Failed to download DSRS Excel report.')
      } finally {
        this.downloadingDSRS = false
      }
    },
    async downloadSalesSummary() {
      console.log('📄 Downloading Sales Summary', {
        startDate: this.filters.startDate,
        endDate: this.filters.endDate,
      })
      this.downloadingSummary = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        const res = await axios.get('/api/reports/sales/summary-excel', {
          params,
          responseType: 'blob'
        })
        console.log('✅ Sales Summary Downloaded', { size: res.data.size })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        // FIX: Use .csv extension
        link.href = url
        link.setAttribute('download', `Sales_Summary_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download Sales Summary:', err.response?.data || err.message)
        alert('Failed to download Sales Summary Excel report.')
      } finally {
        this.downloadingSummary = false
      }
    },
    async downloadTransfersReport() {
      this.downloadingTransfers = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        const res = await axios.get('/api/reports/transfers/export-excel', {
          params,
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        // FIX: Use .csv extension
        link.href = url
        link.setAttribute('download', `Transfers_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download Transfers report:', err.response?.data || err.message)
        alert('Failed to download Transfers Excel report.')
      } finally {
        this.downloadingTransfers = false
      }
    },
    async downloadPromotionsReport() {
      this.downloadingPromotions = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        // You must implement this endpoint in your backend!
        const res = await axios.get('/api/reports/promotions/export-csv', {
          params,
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `Promotions_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download Promotions report:', err.response?.data || err.message)
        alert('Failed to download Promotions CSV report.')
      } finally {
        this.downloadingPromotions = false
      }
    },
    async downloadCustomersReport() {
      this.downloadingCustomers = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        // You must implement this endpoint in your backend!
        const res = await axios.get('/api/reports/customers/export-csv', {
          params,
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `Customers_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download Customers report:', err.response?.data || err.message)
        alert('Failed to download Customers CSV report.')
      } finally {
        this.downloadingCustomers = false
      }
    },
    async downloadSuppliersReport() {
      this.downloadingSuppliers = true
      try {
        const params = {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate
        }
        const res = await axios.get('/api/reports/suppliers/export-csv', {
          params,
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `Suppliers_${this.filters.startDate}_${this.filters.endDate}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('❌ Failed to download Suppliers report:', err.response?.data || err.message)
        alert('Failed to download Suppliers CSV report.')
      } finally {
        this.downloadingSuppliers = false
      }
    },
    async fetchInvoices(forceRefresh = false) {
      this.loadingInvoices = true
      try {
        const invoicesPerPage = this.pagination.invoices.perPage || 50
        const data = await cachedGet('/invoices', {
          params: {
            per_page: invoicesPerPage,
            page: this.pagination.invoices.currentPage
          },
          ttlMs: 30 * 1000,
          forceRefresh,
          cacheKey: `/invoices:${invoicesPerPage}:${this.pagination.invoices.currentPage}`
        })
        this.invoices = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
        this.pagination.invoices = this.parsePaginationMeta(data, {
          currentPage: this.pagination.invoices.currentPage,
          perPage: invoicesPerPage,
          lastPage: 1,
          total: this.invoices.length
        })

        if (!this.invoiceDependenciesLoaded) {
          await Promise.all([
            this.fetchSuppliers(forceRefresh),
            this.fetchCustomers(forceRefresh),
            this.fetchProducts(forceRefresh)
          ])
          this.invoiceDependenciesLoaded = true
        }
      } catch (err) {
        this.invoices = []
        this.pagination.invoices = { currentPage: 1, perPage: this.pagination.invoices.perPage || 50, lastPage: 1, total: 0 }
      } finally {
        this.loadingInvoices = false
      }
    },
    async fetchSuppliers(forceRefresh = false) {
      const data = await cachedGet('/suppliers', {
        params: { per_page: 200 },
        ttlMs: 60 * 1000,
        forceRefresh,
        cacheKey: '/suppliers:200'
      })
      this.suppliers = data.data || data
    },
    async fetchCustomers(forceRefresh = false) {
      const data = await cachedGet('/customers', {
        params: { per_page: 200 },
        ttlMs: 60 * 1000,
        forceRefresh,
        cacheKey: '/customers:200'
      })
      this.customers = data.data || data
    },
    async fetchProducts(forceRefresh = false) {
      const data = await cachedGet('/products', {
        params: { per_page: 200 },
        ttlMs: 60 * 1000,
        forceRefresh,
        cacheKey: '/products:200'
      })
      this.products = data.data || data
    },
    addInvoiceItem() {
      this.invoiceForm.items.push({ product_id: null, uom_id: null, quantity: 1, unit_price: 0 })
    },
    removeInvoiceItem(idx) {
      this.invoiceForm.items.splice(idx, 1)
    },
    closeInvoiceModal() {
      this.showInvoiceModal = false
      this.resetInvoiceForm()
    },
    resetInvoiceForm() {
      this.invoiceForm = {
        id: null,
        type: 'purchase',
        supplier_id: null,
        customer_id: null,
        due_date: '',
        notes: '',
        items: [{ product_id: null, uom_id: null, quantity: 1, unit_price: 0 }],
        service_description: '',
        service_amount: 0
      }
    },
    getProductById(productId) {
      return this.products.find(p => Number(p.id) === Number(productId)) || null
    },
    getProductSaleUoms(product) {
      if (!product) return []
      if (Array.isArray(product.saleUoms)) return product.saleUoms
      if (Array.isArray(product.sale_uoms)) return product.sale_uoms
      return []
    },
    getProductSaleUom(product) {
      return product?.saleUom || product?.sale_uom || null
    },
    getProductPurchaseUom(product) {
      return product?.purchaseUom || product?.purchase_uom || null
    },
    getProductBaseUom(product) {
      return product?.uom || product?.base_uom || null
    },
    getProductPriceForUom(product, uomId) {
      if (!product) return 0

      const targetUomId = Number(uomId || 0)
      const fallbackPrice = Number(product.price || 0)
      if (!targetUomId) return fallbackPrice

      const uomPrices = product.uom_prices
      if (uomPrices && typeof uomPrices === 'object' && !Array.isArray(uomPrices)) {
        const resolved = uomPrices[targetUomId] ?? uomPrices[String(targetUomId)]
        if (resolved !== undefined && resolved !== null && !Number.isNaN(Number(resolved))) {
          return Number(resolved)
        }
      }

      const prices = Array.isArray(product.prices) ? product.prices : []
      const matched = prices.find(entry => Number(entry?.uom_id) === targetUomId && entry?.price !== undefined && entry?.price !== null)
      return matched ? Number(matched.price) : fallbackPrice
    },
    getProductUomOptions(productId) {
      const product = this.getProductById(productId)
      if (!product) return []

      const saleUoms = this.getProductSaleUoms(product)
      if (saleUoms.length > 0) {
        return saleUoms
      }

      const fallbacks = [this.getProductSaleUom(product), this.getProductPurchaseUom(product), this.getProductBaseUom(product)].filter(Boolean)
      const deduped = []
      const seen = new Set()
      for (const u of fallbacks) {
        if (!seen.has(Number(u.id))) {
          seen.add(Number(u.id))
          deduped.push(u)
        }
      }
      return deduped
    },
    onInvoiceProductChange(item) {
      const product = this.getProductById(item.product_id)
      if (!product) {
        item.uom_id = null
        item.unit_price = 0
        return
      }

      const options = this.getProductUomOptions(item.product_id)
      if (!options.length) {
        item.uom_id = null
      } else if (!options.some(u => Number(u.id) === Number(item.uom_id))) {
        item.uom_id = options[0].id
      }

      item.unit_price = this.getProductPriceForUom(product, item.uom_id)
    },
    onInvoiceUomChange(item) {
      const product = this.getProductById(item.product_id)
      if (!product) return
      item.unit_price = this.getProductPriceForUom(product, item.uom_id)
    },
    async saveInvoice() {
      try {
        let payload = { ...this.invoiceForm }
        
        // For non-service invoices, include items
        if (this.invoiceForm.type !== 'service') {
          payload.items = this.invoiceForm.items.map(i => ({
            product_id: i.product_id,
            uom_id: i.uom_id,
            quantity: i.quantity,
            unit_price: i.unit_price
          }))
        } else {
          // For service invoices, use the service amount as total
          payload.total = this.invoiceForm.service_amount
        }
        
        if (this.invoiceForm.id) {
          await axios.put(`/invoices/${this.invoiceForm.id}`, payload)
          this.showSuccess('Invoice Updated', 'Invoice has been updated successfully!')
        } else {
          await axios.post('/invoices', payload)
          this.showSuccess('Invoice Created', 'Invoice has been created successfully!')
        }
        this.closeInvoiceModal()
        this.fetchInvoices()
      } catch (err) {
        console.error('Failed to save invoice:', err)
        this.showError('Save Failed', err.response?.data?.message || 'Failed to save invoice. Please try again.')
      }
    },
    async deleteInvoice(inv) {
      this.showConfirmation(
        'Delete Invoice',
        `Are you sure you want to delete invoice #${inv.id}? This action cannot be undone.`,
        async () => {
          try {
            await axios.delete(`/invoices/${inv.id}`)
            this.showSuccess('Invoice Deleted', 'Invoice has been deleted successfully!')
            this.fetchInvoices()
          } catch (err) {
            console.error('Failed to delete invoice:', err)
            this.showError('Delete Failed', err.response?.data?.message || 'Failed to delete invoice. Please try again.')
          }
        },
        'Delete',
        'Cancel'
      )
    },
    async reverseInvoice(inv) {
      if (inv.status === 'reversed') {
        this.showInfo('Already Reversed', 'This invoice is already reversed.')
        return
      }

      this.showConfirmation(
        'Reverse Invoice',
        `Reverse invoice #${inv.id}? Items will return to inventory and invoice amounts will be reset.`,
        async () => {
          try {
            await axios.post(`/invoices/${inv.id}/reverse`)
            this.showSuccess('Invoice Reversed', 'Invoice reversed successfully.')

            window.dispatchEvent(new CustomEvent('app:add-notification', {
              detail: {
                type: 'success',
                message: `Invoice #${inv.id} reversed successfully.`
              }
            }))

            this.fetchInvoices()
          } catch (err) {
            console.error('Failed to reverse invoice:', err)
            this.showError('Reverse Failed', err.response?.data?.message || err.response?.data?.error || 'Failed to reverse invoice. Please try again.')
          }
        },
        'Reverse',
        'Cancel'
      )
    },
    viewInvoice(inv) {
      this.viewingInvoice = inv
    },
    // Custom Alert Methods
    showAlert(type, title, message, buttonText = 'Close') {
      this.customAlert = {
        show: true,
        type,
        title,
        message,
        buttonText
      }
    },
    showSuccess(title, message) {
      this.showAlert('success', title, message, 'OK')
    },
    showError(title, message) {
      this.showAlert('error', title, message, 'Close')
    },
    showWarning(title, message) {
      this.showAlert('warning', title, message, 'OK')
    },
    showInfo(title, message) {
      this.showAlert('info', title, message, 'OK')
    },
    // Custom Confirmation Method
    showConfirmation(title, message, onConfirm, confirmText = 'Confirm', cancelText = 'Cancel') {
      this.confirmation = {
        show: true,
        title,
        message,
        confirmText,
        cancelText,
        onConfirm: () => {
          this.confirmation.show = false
          onConfirm()
        }
      }
    }
  },
  filters: {
    currency(val) {
      if (val == null) return '-'
      return 'Ksh ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2 })
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

.pagination-controls {
  margin-top: 1rem;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.pagination-btn {
  border: none;
  border-radius: 8px;
  padding: 0.45rem 0.8rem;
  background: #e2e8f0;
  color: #1f2937;
  font-weight: 600;
  cursor: pointer;
}

.pagination-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.pagination-label {
  color: #475569;
  font-size: 0.9rem;
  font-weight: 600;
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

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeInOverlay 0.3s ease;
  padding: 1rem;
}

@keyframes fadeInOverlay {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 90%;
  max-height: 85vh;
  overflow-y: auto;
  padding: 0;
  animation: slideUp 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.5);
}

.modal h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
  font-weight: 700;
  padding: 2rem 2rem 1.5rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
  border-bottom: 2px solid #e2e8f0;
}

.modal form {
  padding: 2rem;
}

.invoice-modal {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow: 0 25px 80px rgba(0, 0, 0, 0.35);
  max-width: 700px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.5);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Invoice Modal Specific Styles */
.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  padding: 2rem 2rem 1.5rem;
  border-bottom: 2px solid #e2e8f0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.invoice-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.75rem;
  font-weight: 700;
}

.invoice-date {
  margin: 0.5rem 0 0 0;
  color: #a0aec0;
  font-size: 0.9rem;
}

.invoice-status-badge {
  padding: 0.6rem 1.2rem;
  border-radius: 50px;
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  min-width: 120px;
  text-align: center;
}

.invoice-status-badge.status-paid {
  background: #c6f6d5;
  color: #22543d;
}

.invoice-status-badge.status-pending {
  background: #fed7d7;
  color: #742a2a;
}

.invoice-status-badge.status-draft {
  background: #e2e8f0;
  color: #2d3748;
}

.invoice-status-badge.status-reversed {
  background: #e9d8fd;
  color: #553c9a;
}

.invoice-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.detail-section h4 {
  margin: 0 0 1rem 0;
  color: #4a5568;
  font-size: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #667eea;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #edf2f7;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-item .label {
  color: #718096;
  font-weight: 600;
  font-size: 0.9rem;
}

.detail-item .value {
  color: #2d3748;
  font-weight: 500;
}

.notes-section {
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.notes-section h4 {
  margin: 0 0 1rem 0;
  color: #667eea;
  font-size: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.notes-content {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 12px;
  border-left: 4px solid #667eea;
  color: #4a5568;
  line-height: 1.6;
  font-style: italic;
}

.items-section {
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.items-section h4 {
  margin: 0 0 1.5rem 0;
  color: #667eea;
  font-size: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-table-wrapper {
  overflow-x: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}

.items-table th {
  padding: 0.85rem;
  text-align: left;
  color: #4a5568;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-table tbody tr {
  border-bottom: 1px solid #e2e8f0;
  transition: background-color 0.2s ease;
}

.items-table tbody tr:hover {
  background: #f8fafc;
}

.items-table td {
  padding: 0.85rem;
  color: #4a5568;
  font-size: 0.95rem;
}

.items-table td.total-cell {
  color: #667eea;
  font-weight: 600;
}

.empty-items {
  text-align: center;
  padding: 2rem;
  color: #a0aec0;
  font-style: italic;
}

.invoice-summary {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
  border-radius: 12px;
  margin: 2rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  color: #4a5568;
  font-weight: 500;
}

.summary-row.total-row {
  padding: 1.25rem 0;
  border-top: 2px solid #e2e8f0;
  border-bottom: 2px solid #e2e8f0;
  margin: 1rem 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
}

.summary-row span:last-child {
  color: #667eea;
  font-weight: 700;
}

.invoice-actions {
  display: flex;
  gap: 1rem;
  padding: 2rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

.modal h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 1rem;
}

.modal h4 {
  margin: 1.5rem 0 1rem 0;
  color: #4a5568;
  font-size: 1.1rem;
  font-weight: 600;
}

.modal p {
  margin: 0.75rem 0;
  color: #4a5568;
  font-size: 1rem;
  line-height: 1.6;
  display: flex;
  gap: 0.75rem;
}

.modal p b {
  color: #2d3748;
  font-weight: 600;
  min-width: 120px;
}

.modal ul {
  list-style: none;
  padding: 0;
  margin: 1rem 0;
}

.modal li {
  background: #f8fafc;
  padding: 1rem;
  margin: 0.75rem 0;
  border-radius: 12px;
  border-left: 4px solid #667eea;
  color: #4a5568;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.modal li:hover {
  background: #edf2f7;
  transform: translateX(4px);
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.modal-actions button {
  flex: 1;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

/* Add-btn style for modal trigger */
.add-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border: none;
  padding: 0.85rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(72, 187, 120, 0.3);
}

.invoice-item-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  align-items: center;
}

.invoice-item-row select,
.invoice-item-row input {
  padding: 0.6rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.invoice-item-row select:focus,
.invoice-item-row input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.invoice-item-row button {
  padding: 0.6rem 0.75rem;
  background: #e53e3e;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.invoice-item-row button:hover {
  background: #c53030;
  transform: scale(1.05);
}

/* Form Styles */
.modal form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  color: #4a5568;
  font-weight: 600;
  font-size: 0.95rem;
}

.form-group select,
.form-group input,
.form-group textarea {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.3s ease;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.section-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #667eea !important;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  font-weight: 700;
  margin-top: 0.5rem;
}

.btn-add-item {
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  margin-top: 0.5rem;
}

.btn-add-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(72, 187, 120, 0.2);
}

.btn-remove {
  padding: 0.6rem 0.75rem;
  background: #e53e3e;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove:hover {
  background: #c53030;
  transform: scale(1.1);
}

.btn-primary {
  padding: 0.85rem 1.5rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
  padding: 0.85rem 1.5rem;
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn-secondary:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

/* Service Section */
.service-section {
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.service-section h4 {
  margin: 0 0 1rem 0;
  color: #667eea;
  font-size: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.service-detail-box {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
  padding: 1.5rem;
  border-radius: 12px;
  border-left: 4px solid #667eea;
}

.service-detail-box p {
  margin: 0.75rem 0;
  color: #4a5568;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.service-detail-box b {
  color: #2d3748;
  font-weight: 600;
}

@media (max-width: 768px) {
  .invoice-modal {
    width: 100%;
  }

  .invoice-header {
    flex-direction: column;
    gap: 1rem;
  }

  .invoice-details-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1.5rem;
  }

  .modal {
    width: 95%;
    padding: 1.5rem;
    max-height: 90vh;
  }

  .modal h3 {
    font-size: 1.5rem;
  }

  .modal-actions {
    flex-direction: column;
  }

  .invoice-actions {
    flex-direction: column;
  }

  .invoice-item-row {
    flex-wrap: wrap;
  }

  .invoice-summary {
    margin: 1.5rem;
    padding: 1.5rem;
  }

  .form-group label {
    font-size: 0.9rem;
  }

  .invoice-item-row select,
  .invoice-item-row input {
    font-size: 0.85rem;
    padding: 0.5rem;
  }
}

/* Custom Alert Modal Styles */
.alert-modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 450px;
  width: 90%;
  padding: 2.5rem;
  animation: slideUp 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1.5rem;
}

.alert-icon {
  font-size: 3.5rem;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 70px;
  height: 70px;
  border-radius: 50%;
}

.alert-success .alert-icon {
  color: #48bb78;
  background: rgba(72, 187, 120, 0.1);
}

.alert-error .alert-icon {
  color: #e53e3e;
  background: rgba(229, 62, 62, 0.1);
}

.alert-warning .alert-icon {
  color: #ed8936;
  background: rgba(237, 137, 54, 0.1);
}

.alert-info .alert-icon {
  color: #3182ce;
  background: rgba(49, 130, 206, 0.1);
}

.alert-content h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
  font-weight: 700;
}

.alert-content p {
  margin: 0.5rem 0 0 0;
  color: #718096;
  line-height: 1.6;
}

.alert-actions {
  display: flex;
  width: 100%;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-alert-close {
  flex: 1;
  padding: 0.85rem 1.5rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn-alert-close:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

/* Custom Confirmation Modal Styles */
.confirm-modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 450px;
  width: 90%;
  padding: 2.5rem;
  animation: slideUp 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1.5rem;
}

.confirm-icon {
  font-size: 3.5rem;
  color: #ed8936;
  background: rgba(237, 137, 54, 0.1);
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.confirm-content h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
  font-weight: 700;
}

.confirm-content p {
  margin: 0.5rem 0 0 0;
  color: #718096;
  line-height: 1.6;
}

.confirm-actions {
  display: flex;
  width: 100%;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-confirm-yes,
.btn-confirm-no {
  flex: 1;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn-confirm-yes {
  background: linear-gradient(135deg, #e53e3e, #c53030);
  color: white;
}

.btn-confirm-yes:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(229, 62, 62, 0.3);
}

.btn-confirm-no {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-confirm-no:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

@media (max-width: 600px) {
  .alert-modal,
  .confirm-modal {
    max-width: 90%;
    padding: 1.5rem;
  }

  .alert-content h3,
  .confirm-content h3 {
    font-size: 1.25rem;
  }

  .alert-icon,
  .confirm-icon {
    width: 60px;
    height: 60px;
    font-size: 2.5rem;
  }

  .btn-alert-close,
  .btn-confirm-yes,
  .btn-confirm-no {
    padding: 0.75rem 1.25rem;
    font-size: 0.9rem;
  }
}
</style>
