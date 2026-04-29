<template>
  <div class="sub-page">
    <!-- Tabs -->
    <div class="tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab" 
        @click="activeTab = tab"
        :class="{ active: activeTab === tab }"
        class="tab-btn"
      >
        {{ formatTabName(tab) }}
      </button>
    </div>

    <!-- Subscriptions Tab -->
    <section v-if="activeTab === 'subscriptions'" class="section">
      <div class="section-header">
        <h2>Subscriptions</h2>
        <div class="controls">
          <input v-model="subFilters.q" type="search" placeholder="Search..." class="search-input" @input="fetchSubscriptions" />
          <select v-model="subFilters.status" @change="fetchSubscriptions" class="filter-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
          <button @click="openAddSubscription" class="btn-create">
            <i class="fas fa-plus"></i> Add Subscription
          </button>
        </div>
      </div>

      <div v-if="subLoading" class="loading">Loading subscriptions...</div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Plan</th>
            <th>Monthly Fee</th>
            <th>Status</th>
            <th>Trial</th>
            <th>Expires</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sub in subscriptions" :key="sub.id">
            <td>{{ sub.company?.name }}</td>
            <td>{{ sub.plan?.name }}</td>
            <td>${{ sub.monthly_fee }}</td>
            <td><span class="badge" :class="`badge-${sub.status}`">{{ sub.status }}</span></td>
            <td>
              <span v-if="sub.on_trial" class="badge badge-warning">
                {{ formatDate(sub.trial_ends_at) }}
              </span>
              <span v-else class="text-muted">-</span>
            </td>
            <td>{{ sub.ends_at ? formatDate(sub.ends_at) : '-' }}</td>
            <td class="actions">
              <button @click="renewSubscription(sub)" class="btn-sm btn-primary" title="Renew">
                <i class="fas fa-sync"></i>
              </button>
              <button @click="assignTrial(sub)" class="btn-sm btn-secondary" title="Assign Trial">
                <i class="fas fa-gift"></i>
              </button>
              <button v-if="sub.status === 'active'" @click="deactivateSubscription(sub)" class="btn-sm btn-danger" title="Deactivate">
                <i class="fas fa-ban"></i>
              </button>
              <button v-else @click="activateSubscription(sub)" class="btn-sm btn-success" title="Activate">
                <i class="fas fa-check"></i>
              </button>
              <button @click="openChangePlan(sub)" class="btn-sm btn-secondary" title="Change Plan">
                <i class="fas fa-exchange-alt"></i>
              </button>
              <button @click="viewPaymentHistory(sub)" class="btn-sm btn-info" title="Payment History">
                <i class="fas fa-history"></i>
              </button>
            </td>
          </tr>
          <tr v-if="subscriptions.length === 0">
            <td colspan="7" class="empty">No subscriptions found</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Plans Tab -->
    <section v-if="activeTab === 'plans'" class="section">
      <div class="section-header">
        <h2>Billing Plans</h2>
        <button @click="showPlanForm = true" class="btn-create">
          <i class="fas fa-plus"></i> Create Plan
        </button>
      </div>

      <div v-if="planLoading" class="loading">Loading plans...</div>

      <div v-else class="plans-grid">
        <div v-for="plan in plans" :key="plan.id" class="plan-card">
          <div class="plan-header">
            <h3>{{ plan.name }}</h3>
            <span class="badge" :class="plan.is_active ? 'badge-success' : 'badge-danger'">
              {{ plan.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <p class="plan-desc">{{ plan.description }}</p>
          <div class="plan-price">${{ plan.price }}/{{ plan.billing_cycle }}</div>
          <ul v-if="plan.features" class="plan-features">
            <li v-for="feature in plan.features.slice(0, 3)" :key="feature">
              <i class="fas fa-check"></i> {{ feature }}
            </li>
            <li v-if="plan.features.length > 3" class="more">+{{ plan.features.length - 3 }} more</li>
          </ul>
          <div class="plan-actions">
            <button @click="editPlan(plan)" class="btn-sm btn-secondary">Edit</button>
            <button @click="deletePlan(plan)" class="btn-sm btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Payment History Modal -->
    <div v-if="showPaymentHistory" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>Payment History: {{ selectedSub?.company?.name }}</h2>
        <div v-if="paymentLoading" class="loading">Loading transactions...</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Method</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="trans in paymentHistory" :key="trans.id">
              <td>{{ formatDate(trans.processed_at) }}</td>
              <td>${{ trans.amount }}</td>
              <td><span class="badge" :class="`badge-${trans.status}`">{{ trans.status }}</span></td>
              <td>{{ trans.payment_method }}</td>
            </tr>
          </tbody>
        </table>
        <button @click="showPaymentHistory = false" class="btn-close-modal">Close</button>
      </div>
    </div>

    <!-- Plan Form Modal -->
    <div v-if="showPlanForm" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>{{ editingPlanId ? 'Edit Plan' : 'Create Plan' }}</h2>
        <form @submit.prevent="savePlan" class="form">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="planForm.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Slug *</label>
            <input v-model="planForm.slug" type="text" required />
          </div>
          <div class="form-group">
            <label>Price *</label>
            <input v-model="planForm.price" type="number" required step="0.01" />
          </div>
          <div class="form-group">
            <label>Billing Cycle *</label>
            <select v-model="planForm.billing_cycle" required>
              <option value="monthly">Monthly</option>
              <option value="annual">Annual</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="planForm.description" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Functionalities / Features</label>
            <p class="hint" style="margin-bottom: 1rem;">Tick individual features or use the cluster checkbox to select/deselect a whole group:</p>
            <div class="features-checklist">

              <!-- Global Select All -->
              <div class="features-global-actions">
                <label class="global-check">
                  <input type="checkbox"
                         :checked="planForm.features.length === allFeatures.length"
                         :indeterminate.prop="planForm.features.length > 0 && planForm.features.length < allFeatures.length"
                         @change="toggleAllFeatures" />
                  <strong>Select / Deselect All Features</strong>
                </label>
                <span class="feat-count">{{ planForm.features.length }} of {{ allFeatures.length }} selected</span>
              </div>

              <!-- 1. Sales & Transactions -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['sales','mpesa','credit','promotions'])"
                           :indeterminate.prop="isCategoryIndeterminate(['sales','mpesa','credit','promotions'])"
                           @change="toggleCategory(['sales','mpesa','credit','promotions'])" />
                    <h4 class="category-title">Sales &amp; Transactions</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('sales')" @change="toggleFeature('sales')" />
                    <span class="checkbox-label"><strong>Sales Management</strong><small>Process and record sales transactions</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('mpesa')" @change="toggleFeature('mpesa')" />
                    <span class="checkbox-label"><strong>M-Pesa Integration</strong><small>Accept M-Pesa mobile money payments</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('credit')" @change="toggleFeature('credit')" />
                    <span class="checkbox-label"><strong>Credit System</strong><small>Manage customer credit and debt</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('promotions')" @change="toggleFeature('promotions')" />
                    <span class="checkbox-label"><strong>Promotions</strong><small>Create and manage sales promotions &amp; discounts</small></span>
                  </label>
                </div>
              </div>

              <!-- 2. Products & Inventory -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['products','inventory','purchases','warehouse','stock_transfers'])"
                           :indeterminate.prop="isCategoryIndeterminate(['products','inventory','purchases','warehouse','stock_transfers'])"
                           @change="toggleCategory(['products','inventory','purchases','warehouse','stock_transfers'])" />
                    <h4 class="category-title">Products &amp; Inventory</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('products')" @change="toggleFeature('products')" />
                    <span class="checkbox-label"><strong>Products Catalogue</strong><small>Manage products, categories and pricing</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('inventory')" @change="toggleFeature('inventory')" />
                    <span class="checkbox-label"><strong>Inventory Management</strong><small>Track stock levels and movements</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('purchases')" @change="toggleFeature('purchases')" />
                    <span class="checkbox-label"><strong>Purchase Orders</strong><small>Create and manage purchase orders from suppliers</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('warehouse')" @change="toggleFeature('warehouse')" />
                    <span class="checkbox-label"><strong>Warehouse Management</strong><small>Manage multiple warehouse locations</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('stock_transfers')" @change="toggleFeature('stock_transfers')" />
                    <span class="checkbox-label"><strong>Stock Transfers</strong><small>Transfer stock between warehouses/locations</small></span>
                  </label>
                </div>
              </div>

              <!-- 3. Customers & Suppliers -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['customer_management','suppliers','sms'])"
                           :indeterminate.prop="isCategoryIndeterminate(['customer_management','suppliers','sms'])"
                           @change="toggleCategory(['customer_management','suppliers','sms'])" />
                    <h4 class="category-title">Customers &amp; Suppliers</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('customer_management')" @change="toggleFeature('customer_management')" />
                    <span class="checkbox-label"><strong>Customer Management</strong><small>Manage customer profiles, history and loyalty</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('suppliers')" @change="toggleFeature('suppliers')" />
                    <span class="checkbox-label"><strong>Supplier Management</strong><small>Manage supplier contacts and information</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('sms')" @change="toggleFeature('sms')" />
                    <span class="checkbox-label"><strong>SMS &amp; Email Messaging</strong><small>Send SMS/email notifications to customers &amp; suppliers</small></span>
                  </label>
                </div>
              </div>

              <!-- 4. Financial & Tax -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['tax_configuration','expenses','invoicing','price_groups','accounts'])"
                           :indeterminate.prop="isCategoryIndeterminate(['tax_configuration','expenses','invoicing','price_groups','accounts'])"
                           @change="toggleCategory(['tax_configuration','expenses','invoicing','price_groups','accounts'])" />
                    <h4 class="category-title">Financial &amp; Tax</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('tax_configuration')" @change="toggleFeature('tax_configuration')" />
                    <span class="checkbox-label"><strong>Tax Configuration</strong><small>Configure VAT and other tax settings</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('expenses')" @change="toggleFeature('expenses')" />
                    <span class="checkbox-label"><strong>Expense Tracking</strong><small>Record and categorise business expenses</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('invoicing')" @change="toggleFeature('invoicing')" />
                    <span class="checkbox-label"><strong>Invoicing</strong><small>Create and send professional invoices</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('price_groups')" @change="toggleFeature('price_groups')" />
                    <span class="checkbox-label"><strong>Price Groups</strong><small>Manage customer-specific pricing tiers</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('accounts')" @change="toggleFeature('accounts')" />
                    <span class="checkbox-label"><strong>Accounts / Ledger</strong><small>View and manage financial accounts</small></span>
                  </label>
                </div>
              </div>

              <!-- 5. Reporting & Analytics -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['reports','data_export','audit_logs'])"
                           :indeterminate.prop="isCategoryIndeterminate(['reports','data_export','audit_logs'])"
                           @change="toggleCategory(['reports','data_export','audit_logs'])" />
                    <h4 class="category-title">Reporting &amp; Analytics</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('reports')" @change="toggleFeature('reports')" />
                    <span class="checkbox-label"><strong>Reports &amp; Analytics</strong><small>Generate sales, inventory and financial reports</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('data_export')" @change="toggleFeature('data_export')" />
                    <span class="checkbox-label"><strong>Data Export</strong><small>Export data to CSV / Excel</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('audit_logs')" @change="toggleFeature('audit_logs')" />
                    <span class="checkbox-label"><strong>Audit Logs</strong><small>View transaction and user activity logs</small></span>
                  </label>
                </div>
              </div>

              <!-- 6. System & Administration -->
              <div class="feature-category">
                <div class="category-header">
                  <label class="category-check">
                    <input type="checkbox"
                           :checked="isCategoryAllSelected(['user_management','printer_config','returns','advanced_settings','admin_customization'])"
                           :indeterminate.prop="isCategoryIndeterminate(['user_management','printer_config','returns','advanced_settings','admin_customization'])"
                           @change="toggleCategory(['user_management','printer_config','returns','advanced_settings','admin_customization'])" />
                    <h4 class="category-title">System &amp; Administration</h4>
                  </label>
                </div>
                <div class="checklist-items">
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('user_management')" @change="toggleFeature('user_management')" />
                    <span class="checkbox-label"><strong>User Management</strong><small>Manage staff users and assign roles</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('printer_config')" @change="toggleFeature('printer_config')" />
                    <span class="checkbox-label"><strong>Printer Configuration</strong><small>Configure receipt printer settings</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('returns')" @change="toggleFeature('returns')" />
                    <span class="checkbox-label"><strong>Product Returns</strong><small>Process and manage product returns / refunds</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('advanced_settings')" @change="toggleFeature('advanced_settings')" />
                    <span class="checkbox-label"><strong>Advanced Settings</strong><small>Access advanced system configuration options</small></span>
                  </label>
                  <label class="checkbox-item">
                    <input type="checkbox" :checked="planForm.features.includes('admin_customization')" @change="toggleFeature('admin_customization')" />
                    <span class="checkbox-label"><strong>Admin Customisation</strong><small>Customise branding, receipts and display settings</small></span>
                  </label>
                </div>
              </div>

              <!-- Selected features summary -->
              <div v-if="planForm.features.length > 0" class="selected-features">
                <p class="selected-label">Selected Features ({{ planForm.features.length }}):</p>
                <div class="feature-chips">
                  <span v-for="feat in planForm.features" :key="feat" class="feature-chip">
                    {{ formatFeatureName(feat) }}
                    <button type="button" class="chip-remove" @click="toggleFeature(feat)">&times;</button>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label style="display:flex;gap:0.5rem;align-items:center;cursor:pointer">
              <input type="checkbox" v-model="planForm.is_active" />
              Active (visible to admins when choosing a plan)
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">{{ editingPlanId ? 'Update' : 'Create' }}</button>
            <button type="button" @click="showPlanForm = false" class="btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Plan Modal -->
    <div v-if="showChangePlanModal" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>Change Plan</h2>
        <p class="muted-label">Company: <strong>{{ changePlanTarget?.company?.name }}</strong></p>
        <p class="muted-label">Current plan: <strong>{{ changePlanTarget?.plan?.name || 'None' }}</strong></p>
        <div class="form-group" style="margin-top:1rem">
          <label>New Plan *</label>
          <select v-model="changePlanForm.plan_id">
            <option value="">Select a plan</option>
            <option v-for="plan in plans.filter(p => p.is_active)" :key="plan.id" :value="String(plan.id)">
              {{ plan.name }} — ${{ plan.price }}/{{ plan.billing_cycle }}
            </option>
          </select>
        </div>
        <div class="form-actions">
          <button class="btn-primary" :disabled="!changePlanForm.plan_id || changePlanSaving" @click="submitChangePlan">
            <i :class="changePlanSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i> Save
          </button>
          <button class="btn-secondary" @click="showChangePlanModal = false">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Add Subscription Modal -->
    <div v-if="showAddSubModal" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>Add Subscription</h2>
        <div v-if="addSubLoading" class="loading">Loading companies...</div>
        <div v-else>
          <div class="form-group">
            <label>Company *</label>
            <select v-model="addSubForm.company_id">
              <option value="">Select company</option>
              <option v-for="c in companiesWithoutSub" :key="c.id" :value="String(c.id)">{{ c.name }}{{ c.email ? ` (${c.email})` : '' }}</option>
            </select>
            <p v-if="companiesWithoutSub.length === 0" class="hint">All companies already have a subscription.</p>
          </div>
          <div class="form-group">
            <label>Plan *</label>
            <select v-model="addSubForm.plan_id">
              <option value="">Select a plan</option>
              <option v-for="plan in plans.filter(p => p.is_active)" :key="plan.id" :value="String(plan.id)">
                {{ plan.name }} — ${{ plan.price }}/{{ plan.billing_cycle }}
              </option>
            </select>
          </div>
          <div class="form-actions">
            <button class="btn-primary" :disabled="!addSubForm.company_id || !addSubForm.plan_id || addSubSaving" @click="submitAddSubscription">
              <i :class="addSubSaving ? 'fas fa-spinner fa-spin' : 'fas fa-plus'"></i> Create
            </button>
            <button class="btn-secondary" @click="showAddSubModal = false">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Trial Assignment Modal -->
    <div v-if="showTrialForm" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>Assign Free Trial</h2>
        <div class="form-group">
          <label>Trial Days *</label>
          <input v-model.number="trialDays" type="number" required min="1" max="365" />
        </div>
        <div class="form-actions">
          <button @click="submitTrial" class="btn-primary">Assign Trial</button>
          <button @click="showTrialForm = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Upgrade Requests Tab -->
    <section v-if="activeTab === 'upgrade-requests'" class="section">
      <div class="section-header">
        <h2>Upgrade Requests</h2>
        <div class="controls">
          <select v-model="upgradeFilters.status" @change="fetchUpgradeRequests" class="filter-select">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
          <button @click="fetchUpgradeRequests" class="btn-sm btn-secondary" title="Refresh">
            <i class="fas fa-sync"></i> Refresh
          </button>
        </div>
      </div>

      <div v-if="upgradeLoading" class="loading">Loading upgrade requests...</div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Current Plan</th>
            <th>→ Requested Plan</th>
            <th>Requested By</th>
            <th>Admin Note</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="req in upgradeRequests" :key="req.id">
            <td>{{ req.company?.name || '—' }}</td>
            <td>{{ req.current_plan?.name || 'None' }}</td>
            <td><strong>{{ req.requested_plan?.name || '—' }}</strong></td>
            <td>{{ req.requested_by?.name || '—' }}</td>
            <td class="note-cell">{{ req.admin_notes || '—' }}</td>
            <td>
              <span class="badge" :class="req.status === 'pending' ? 'badge-warning' : req.status === 'approved' ? 'badge-success' : 'badge-inactive'">
                {{ req.status }}
              </span>
            </td>
            <td>
              <div>{{ formatDate(req.created_at) }}</div>
              <small v-if="req.reviewed_at" class="text-muted">Reviewed: {{ formatDate(req.reviewed_at) }}</small>
            </td>
            <td class="actions">
              <template v-if="req.status === 'pending'">
                <button @click="openReview(req, 'approve')" class="btn-sm btn-success" title="Approve">
                  <i class="fas fa-check"></i> Approve
                </button>
                <button @click="openReview(req, 'reject')" class="btn-sm btn-danger" title="Reject">
                  <i class="fas fa-times"></i> Reject
                </button>
              </template>
              <span v-else-if="req.status === 'approved'" class="text-success">
                <i class="fas fa-check-circle"></i> Approved by {{ req.reviewed_by?.name || '—' }}
              </span>
              <span v-else class="text-danger">
                <i class="fas fa-times-circle"></i> Rejected by {{ req.reviewed_by?.name || '—' }}
                <div v-if="req.reviewer_notes" class="note-cell">{{ req.reviewer_notes }}</div>
              </span>
            </td>
          </tr>
          <tr v-if="upgradeRequests.length === 0">
            <td colspan="8" class="empty">No upgrade requests found</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Review Modal (Approve / Reject) -->
    <div v-if="showReviewModal" class="modal-overlay">
      <div class="modal" @click.stop>
        <h2>{{ reviewAction === 'approve' ? '✅ Approve' : '❌ Reject' }} Upgrade Request</h2>
        <p class="muted-label">Company: <strong>{{ reviewTarget?.company?.name }}</strong></p>
        <p class="muted-label">
          Plan change: <strong>{{ reviewTarget?.current_plan?.name || 'None' }}</strong>
          → <strong>{{ reviewTarget?.requested_plan?.name }}</strong>
        </p>
        <div v-if="reviewTarget?.admin_notes" class="review-note-box">
          <strong>Admin note:</strong> {{ reviewTarget.admin_notes }}
        </div>
        <div v-if="reviewAction === 'approve'" class="review-note-box" style="background:#dcfce7;border-color:#86efac;color:#14532d;margin-top:0.75rem">
          <i class="fas fa-info-circle"></i> Approving will immediately switch the company to the new plan and notify the admin.
        </div>
        <div class="form-group" style="margin-top:1rem">
          <label>{{ reviewAction === 'approve' ? 'Approval note (optional)' : 'Rejection reason (optional)' }}</label>
          <textarea v-model="reviewNotes" rows="3" style="padding:0.75rem;border:1px solid #e5e7eb;border-radius:8px;width:100%;box-sizing:border-box;font-family:inherit" :placeholder="reviewAction === 'approve' ? 'e.g. Approved — enjoy the new plan!' : 'e.g. Payment not confirmed yet'"></textarea>
        </div>
        <div class="form-actions">
          <button
            :class="reviewAction === 'approve' ? 'btn-success' : 'btn-danger'"
            style="padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;color:#fff"
            :disabled="reviewSaving"
            @click="submitReview"
          >
            <i :class="reviewSaving ? 'fas fa-spinner fa-spin' : reviewAction === 'approve' ? 'fas fa-check' : 'fas fa-times'"></i>
            {{ reviewSaving ? 'Saving...' : reviewAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}
          </button>
          <button class="btn-secondary" style="padding:0.6rem 1.25rem;border:none;border-radius:8px;font-weight:600;cursor:pointer" @click="showReviewModal = false">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Alert Toast -->
    <div v-if="alert.show" class="alert-toast" :class="`alert-${alert.type}`">
      <i :class="getAlertIcon(alert.type)"></i>
      <span>{{ alert.message }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('subscriptions')
const tabs = ['subscriptions', 'plans', 'upgrade-requests']

// Subscriptions State
const subscriptions = ref([])
const subLoading = ref(false)
const subFilters = reactive({ q: '', status: '' })

// Plans State
const plans = ref([])
const planLoading = ref(false)
const showPlanForm = ref(false)
const editingPlanId = ref(null)
const planForm = reactive({ name: '', slug: '', price: '', billing_cycle: 'monthly', description: '', features: [], is_active: true })
const newFeatureInput = ref('')

// Change Plan
const showChangePlanModal = ref(false)
const changePlanTarget = ref(null)
const changePlanForm = reactive({ plan_id: '' })
const changePlanSaving = ref(false)

// Add Subscription
const showAddSubModal = ref(false)
const companiesWithoutSub = ref([])
const addSubLoading = ref(false)
const addSubForm = reactive({ company_id: '', plan_id: '' })
const addSubSaving = ref(false)

// Payment History
const showPaymentHistory = ref(false)
const paymentLoading = ref(false)
const paymentHistory = ref([])
const selectedSub = ref(null)

// Trial Modal
const showTrialForm = ref(false)
const trialDays = ref(30)
const selectedSubForTrial = ref(null)

// Upgrade Requests
const upgradeRequests = ref([])
const upgradeLoading = ref(false)
const upgradeFilters = reactive({ status: 'pending' })
const showReviewModal = ref(false)
const reviewTarget = ref(null)
const reviewAction = ref('') // 'approve' | 'reject'
const reviewNotes = ref('')
const reviewSaving = ref(false)

// Alert
const alert = reactive({ show: false, type: 'info', message: '' })

async function fetchUpgradeRequests() {
  upgradeLoading.value = true
  try {
    const res = await axios.get('/api/super/upgrade-requests', { params: upgradeFilters })
    upgradeRequests.value = res.data.data || []
  } catch (e) {
    showAlert('error', 'Failed to load upgrade requests')
  } finally {
    upgradeLoading.value = false
  }
}

function openReview(req, action) {
  reviewTarget.value = req
  reviewAction.value = action
  reviewNotes.value = ''
  showReviewModal.value = true
}

async function submitReview() {
  reviewSaving.value = true
  try {
    const url = `/api/super/upgrade-requests/${reviewTarget.value.id}/${reviewAction.value}`
    const res = await axios.post(url, { reviewer_notes: reviewNotes.value || null })
    showAlert('success', res.data?.message || (reviewAction.value === 'approve' ? 'Upgrade approved!' : 'Request rejected'))
    showReviewModal.value = false
    fetchUpgradeRequests()
    if (reviewAction.value === 'approve') {
      fetchSubscriptions()
      // Clear the subscription feature cache so the admin's router re-fetches on next navigation
      localStorage.removeItem('companySubscriptionFeaturesCache')
    }
  } catch (e) {
    showAlert('error', e.response?.data?.error || 'Failed')
  } finally {
    reviewSaving.value = false
  }
}

const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  setTimeout(() => { alert.show = false }, duration)
}

const getAlertIcon = (type) => {
  const icons = { success: 'fas fa-check-circle', error: 'fas fa-exclamation-circle', warning: 'fas fa-exclamation-triangle', info: 'fas fa-info-circle' }
  return icons[type] || icons.info
}

const formatTabName = (tab) => {
  if (tab === 'upgrade-requests') return 'Upgrade Requests'
  return tab.charAt(0).toUpperCase() + tab.slice(1)
}
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-'

async function fetchSubscriptions() {
  subLoading.value = true
  try {
    const res = await axios.get('/api/super/subscriptions', { params: subFilters })
    subscriptions.value = res.data.data || []
  } catch (e) {
    showAlert('error', 'Failed to load subscriptions')
  } finally {
    subLoading.value = false
  }
}

async function fetchPlans() {
  planLoading.value = true
  try {
    const res = await axios.get('/api/super/plans')
    plans.value = res.data.plans || []
  } catch (e) {
    showAlert('error', 'Failed to load plans')
  } finally {
    planLoading.value = false
  }
}

async function activateSubscription(sub) {
  try {
    await axios.patch(`/api/super/subscriptions/${sub.id}/activate`)
    sub.status = 'active'
    showAlert('success', 'Subscription activated')
  } catch (e) {
    showAlert('error', 'Failed to activate')
  }
}

async function deactivateSubscription(sub) {
  try {
    await axios.patch(`/api/super/subscriptions/${sub.id}/deactivate`)
    sub.status = 'inactive'
    showAlert('success', 'Subscription deactivated')
  } catch (e) {
    showAlert('error', 'Failed to deactivate')
  }
}

async function renewSubscription(sub) {
  try {
    await axios.post(`/api/super/subscriptions/${sub.id}/renew`)
    fetchSubscriptions()
    showAlert('success', 'Subscription renewed')
  } catch (e) {
    showAlert('error', 'Failed to renew')
  }
}

function assignTrial(sub) {
  selectedSubForTrial.value = sub
  showTrialForm.value = true
}

async function submitTrial() {
  try {
    await axios.post(`/api/super/subscriptions/${selectedSubForTrial.value.id}/trial`, { trial_days: trialDays.value })
    showTrialForm.value = false
    fetchSubscriptions()
    showAlert('success', 'Trial assigned')
  } catch (e) {
    showAlert('error', 'Failed to assign trial')
  }
}

async function viewPaymentHistory(sub) {
  selectedSub.value = sub
  paymentLoading.value = true
  try {
    const res = await axios.get(`/api/super/subscriptions/${sub.id}/transactions`)
    paymentHistory.value = res.data.data || []
    showPaymentHistory.value = true
  } catch (e) {
    showAlert('error', 'Failed to load payment history')
  } finally {
    paymentLoading.value = false
  }
}

const allFeatures = [
  'sales', 'mpesa', 'credit', 'promotions',
  'products', 'inventory', 'purchases', 'warehouse', 'stock_transfers',
  'customer_management', 'suppliers', 'sms',
  'tax_configuration', 'expenses', 'invoicing', 'price_groups', 'accounts',
  'reports', 'data_export', 'audit_logs',
  'user_management', 'printer_config', 'returns', 'advanced_settings', 'admin_customization'
]

function toggleFeature(feature) {
  const idx = planForm.features.indexOf(feature)
  if (idx > -1) {
    planForm.features.splice(idx, 1)
  } else {
    planForm.features.push(feature)
  }
}

function isCategoryAllSelected(feats) {
  return feats.every(f => planForm.features.includes(f))
}

function isCategoryIndeterminate(feats) {
  const count = feats.filter(f => planForm.features.includes(f)).length
  return count > 0 && count < feats.length
}

function toggleCategory(feats) {
  if (isCategoryAllSelected(feats)) {
    feats.forEach(f => {
      const idx = planForm.features.indexOf(f)
      if (idx > -1) planForm.features.splice(idx, 1)
    })
  } else {
    feats.forEach(f => {
      if (!planForm.features.includes(f)) planForm.features.push(f)
    })
  }
}

function toggleAllFeatures() {
  if (planForm.features.length === allFeatures.length) {
    planForm.features.splice(0)
  } else {
    allFeatures.forEach(f => {
      if (!planForm.features.includes(f)) planForm.features.push(f)
    })
  }
}

function formatFeatureName(feature) {
  const names = {
    'sales': 'Sales Management',
    'products': 'Products Catalogue',
    'mpesa': 'M-Pesa Integration',
    'credit': 'Credit System',
    'promotions': 'Promotions',
    'inventory': 'Inventory Management',
    'purchases': 'Purchase Orders',
    'warehouse': 'Warehouse Management',
    'stock_transfers': 'Stock Transfers',
    'customer_management': 'Customer Management',
    'suppliers': 'Supplier Management',
    'sms': 'SMS & Email Messaging',
    'tax_configuration': 'Tax Configuration',
    'expenses': 'Expense Tracking',
    'invoicing': 'Invoicing',
    'price_groups': 'Price Groups',
    'accounts': 'Accounts / Ledger',
    'reports': 'Reports & Analytics',
    'data_export': 'Data Export',
    'audit_logs': 'Audit Logs',
    'user_management': 'User Management',
    'printer_config': 'Printer Configuration',
    'returns': 'Product Returns',
    'advanced_settings': 'Advanced Settings',
    'admin_customization': 'Admin Customisation'
  }
  return names[feature] || feature.replace(/_/g, ' ').charAt(0).toUpperCase() + feature.slice(1)
}

function addFeature() {
  const val = newFeatureInput.value.trim().toLowerCase().replace(/\s+/g, '_')
  if (val && !planForm.features.includes(val)) planForm.features.push(val)
  newFeatureInput.value = ''
}

function removeFeature(idx) {
  planForm.features.splice(idx, 1)
}

function editPlan(plan) {
  editingPlanId.value = plan.id
  Object.assign(planForm, {
    ...plan,
    features: Array.isArray(plan.features) ? [...plan.features] : [],
    is_active: plan.is_active !== false,
  })
  newFeatureInput.value = ''
  showPlanForm.value = true
}

function openChangePlan(sub) {
  changePlanTarget.value = sub
  changePlanForm.plan_id = sub.plan ? String(sub.plan.id) : ''
  showChangePlanModal.value = true
}

async function submitChangePlan() {
  changePlanSaving.value = true
  try {
    const res = await axios.put(`/api/super/subscriptions/${changePlanTarget.value.id}/plan`, {
      plan_id: Number(changePlanForm.plan_id)
    })
    showAlert('success', res.data?.message || 'Plan changed')
    showChangePlanModal.value = false
    fetchSubscriptions()
  } catch (e) {
    showAlert('error', e.response?.data?.error || 'Failed to change plan')
  } finally {
    changePlanSaving.value = false
  }
}

async function openAddSubscription() {
  addSubForm.company_id = ''
  addSubForm.plan_id = ''
  addSubLoading.value = true
  showAddSubModal.value = true
  try {
    const res = await axios.get('/api/super/companies-without-subscription')
    companiesWithoutSub.value = res.data?.companies || []
  } catch (e) {
    companiesWithoutSub.value = []
  } finally {
    addSubLoading.value = false
  }
}

async function submitAddSubscription() {
  addSubSaving.value = true
  try {
    const res = await axios.post(`/api/super/subscriptions/company/${addSubForm.company_id}`, {
      plan_id: Number(addSubForm.plan_id)
    })
    showAlert('success', res.data?.message || 'Subscription created')
    showAddSubModal.value = false
    fetchSubscriptions()
  } catch (e) {
    showAlert('error', e.response?.data?.error || 'Failed to create subscription')
  } finally {
    addSubSaving.value = false
  }
}

async function savePlan() {
  try {
    const payload = { ...planForm }
    if (editingPlanId.value) {
      await axios.put(`/api/super/plans/${editingPlanId.value}`, payload)
      showAlert('success', 'Plan updated')
    } else {
      await axios.post('/api/super/plans', payload)
      showAlert('success', 'Plan created')
    }
    showPlanForm.value = false
    editingPlanId.value = null
    Object.assign(planForm, { name: '', slug: '', price: '', billing_cycle: 'monthly', description: '', features: [], is_active: true })
    newFeatureInput.value = ''
    fetchPlans()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save plan')
  }
}

async function deletePlan(plan) {
  if (!confirm('Delete this plan?')) return
  try {
    await axios.delete(`/api/super/plans/${plan.id}`)
    fetchPlans()
    showAlert('success', 'Plan deleted')
  } catch (e) {
    showAlert('error', 'Failed to delete')
  }
}

onMounted(() => {
  fetchSubscriptions()
  fetchPlans()
  fetchUpgradeRequests()
})
</script>

<style scoped>
.sub-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e5e7eb; }
.tab-btn { background: none; border: none; padding: 1rem; cursor: pointer; font-weight: 600; color: #6b7280; transition: all 0.3s; border-bottom: 3px solid transparent; }
.tab-btn.active { color: #667eea; border-bottom-color: #667eea; }

.section { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; }
.section-header h2 { margin: 0; }

.controls { display: flex; gap: 0.75rem; }
.search-input, .filter-select { padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.data-table th { background: #f9fafb; font-weight: 600; }

.badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.badge-active { background: #d1fae5; color: #065f46; }
.badge-inactive { background: #fee2e2; color: #991b1b; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #78350f; }

.actions { display: flex; gap: 0.5rem; }
.btn-sm { padding: 0.5rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
.btn-primary { background: #667eea; color: #fff; }
.btn-secondary { background: #e5e7eb; color: #374151; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-success { background: #48bb78; color: #fff; }
.btn-info { background: #3b82f6; color: #fff; }

.plans-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.plan-card { border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; }
.plan-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
.plan-header h3 { margin: 0; }
.plan-price { font-size: 1.5rem; font-weight: 700; color: #667eea; margin: 1rem 0; }
.plan-features { list-style: none; padding: 0; margin: 1rem 0; }
.plan-features li { padding: 0.5rem 0; color: #6b7280; }
.plan-actions { display: flex; gap: 0.5rem; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: flex-start; justify-content: center; z-index: 2000; overflow-y: auto; padding: 2rem; }
.modal { background: #fff; padding: 2rem; border-radius: 12px; width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: auto; }
.muted-label { color: #6b7280; margin: 0 0 4px; font-size: 0.9rem; }

.feature-editor { display: flex; flex-direction: column; gap: 0.5rem; }
.feature-chips { display: flex; flex-wrap: wrap; gap: 6px; min-height: 30px; padding: 6px; border: 1px solid #e5e7eb; border-radius: 8px; }
.feature-chip { display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1e40af; border-radius: 999px; font-size: 0.78rem; font-weight: 600; padding: 4px 8px; }
.chip-remove { background: none; border: none; cursor: pointer; color: #1e40af; font-size: 1rem; padding: 0; line-height: 1; }
.feature-add-row { display: flex; gap: 0.5rem; }
.feature-input { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; font-size: 0.9rem; }
.hint { font-size: 0.78rem; color: #9ca3af; margin: 0; }
.hint code { background: #f3f4f6; padding: 1px 4px; border-radius: 4px; }

/* Features Checklist Styles */
.features-checklist {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.features-global-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
}

.global-check {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
}

.global-check input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  flex-shrink: 0;
}

.feat-count {
  font-size: 0.8rem;
  color: #1e40af;
  font-weight: 600;
}

.feature-category {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.category-header {
  border-bottom: 2px solid #e5e7eb;
}

.category-check {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  padding-bottom: 0.4rem;
}

.category-check input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  flex-shrink: 0;
}

.category-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #374151;
  margin: 0;
}

.checklist-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.checkbox-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  transition: all 0.2s ease;
}

.checkbox-item:hover {
  border-color: #667eea;
  background: #f0f4ff;
}

.checkbox-item input[type="checkbox"] {
  margin-top: 2px;
  width: 18px;
  height: 18px;
  cursor: pointer;
  flex-shrink: 0;
}

.checkbox-label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.checkbox-label strong {
  color: #1f2937;
  font-size: 0.9rem;
}

.checkbox-label small {
  color: #6b7280;
  font-size: 0.8rem;
  font-weight: 400;
}

.selected-features {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid #e5e7eb;
}

.selected-label {
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.75rem 0;
  font-size: 0.9rem;
}

.form { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; }
.form-group input, .form-group select, .form-group textarea { padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; }

.form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
.btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; z-index: 3000; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }

.loading { text-align: center; padding: 2rem; color: #6b7280; }
.empty { text-align: center; padding: 1rem; color: #a0aec0; }
.note-cell { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #6b7280; font-size: 0.85rem; }
.review-note-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 8px; font-size: 0.88rem; color: #374151; }
</style>
