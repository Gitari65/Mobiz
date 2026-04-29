<template>
  <div class="accounts-management">
    <div class="page-header">
      <h1>📊 Accounts Management</h1>
      <p>Manage customer credit, invoices, and returns</p>
    </div>

    <div class="tabs-container">
      <div class="tabs-header">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['tab-btn', { active: activeTab === tab.id }]"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </div>

      <div class="tab-content">
        <!-- Credit Management Tab -->
        <div v-if="activeTab === 'credit'" class="credit-section">
          <div class="section-header">
            <h2>💳 Customer Credit Management</h2>
            <button @click="showCreditLimitModal = true" class="primary-btn">
              <i class="fas fa-plus"></i> Set Credit Limit
            </button>
          </div>

          <div class="filters-bar">
            <input 
              v-model="creditSearch" 
              type="text" 
              placeholder="Search customers..."
              class="search-input"
            >
            <select v-model="creditFilter" class="filter-select">
              <option value="all">All Customers</option>
              <option value="with-balance">With Balance</option>
              <option value="over-limit">Over Limit</option>
            </select>
          </div>

          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Current Balance</th>
                  <th>Credit Limit</th>
                  <th>Available Credit</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="customer in filteredCreditCustomers" :key="customer.id">
                  <td>
                    <div class="customer-info">
                      <strong>{{ customer.name }}</strong>
                      <small>{{ customer.phone }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="amount-badge" :class="customer.credit_balance > 0 ? 'warning' : 'success'">
                      Ksh {{ formatPrice(customer.credit_balance || 0) }}
                    </span>
                  </td>
                  <td>Ksh {{ formatPrice(customer.credit_limit || 0) }}</td>
                  <td>Ksh {{ formatPrice((customer.credit_limit || 0) - (customer.credit_balance || 0)) }}</td>
                  <td>
                    <span :class="['status-badge', getCreditStatus(customer)]">
                      {{ getCreditStatusText(customer) }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button @click="editCreditLimit(customer)" class="icon-btn" title="Edit Limit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="viewCreditHistory(customer)" class="icon-btn" title="View History">
                        <i class="fas fa-history"></i>
                      </button>
                      <button v-if="customer.credit_balance > 0" @click="recordPayment(customer)" class="icon-btn success" title="Record Payment">
                        <i class="fas fa-money-bill-wave"></i>
                      </button>
                      <button @click="adjustBalance(customer)" class="icon-btn" title="Adjust Balance">
                        <i class="fas fa-balance-scale"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredCreditCustomers.length">
                  <td colspan="6" class="no-data">No customers found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Invoices Tab -->
        <div v-if="activeTab === 'invoices'" class="invoices-section">
          <div class="section-header">
            <h2>🧾 Invoices</h2>
            <button @click="createInvoice" class="primary-btn">
              <i class="fas fa-plus"></i> Create Invoice
            </button>
          </div>

          <div class="filters-bar">
            <input 
              v-model="invoiceSearch" 
              type="text" 
              placeholder="Search invoices..."
              class="search-input"
            >
            <select v-model="invoiceStatus" class="filter-select">
              <option value="all">All Status</option>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="paid">Paid</option>
              <option value="overdue">Overdue</option>
              <option value="reversed">Reversed</option>
            </select>
            <input 
              v-model="invoiceDateFrom" 
              type="date" 
              class="date-input"
              placeholder="From"
            >
            <input 
              v-model="invoiceDateTo" 
              type="date" 
              class="date-input"
              placeholder="To"
            >
          </div>

          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Invoice #</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Due Date</th>
                  <th>Amount</th>
                  <th>Paid</th>
                  <th>Balance</th>
                  <th>Status</th>
                  <th>Units</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                  <td><strong>{{ invoice.invoice_number }}</strong></td>
                  <td>{{ invoice.customer_name }}</td>
                  <td>{{ formatDate(invoice.date) }}</td>
                  <td>{{ formatDate(invoice.due_date) }}</td>
                  <td>Ksh {{ formatPrice(invoice.total) }}</td>
                  <td>Ksh {{ formatPrice(invoice.paid_amount) }}</td>
                  <td>Ksh {{ formatPrice(invoice.balance) }}</td>
                  <td>
                    <span :class="['status-badge', invoice.status]">
                      {{ invoice.status }}
                    </span>
                  </td>
                  <td>
                    <span class="status-badge draft">{{ getInvoiceUomSummary(invoice) }}</span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button @click="viewInvoice(invoice)" class="icon-btn" title="View">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button @click="editInvoice(invoice)" class="icon-btn" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="printInvoice(invoice)" class="icon-btn" title="Print">
                        <i class="fas fa-print"></i>
                      </button>
                      <button
                        v-if="invoice.status !== 'reversed'"
                        @click="reverseInvoice(invoice)"
                        class="icon-btn warning"
                        title="Reverse"
                      >
                        <i class="fas fa-undo"></i>
                      </button>
                      <button @click="deleteInvoice(invoice)" class="icon-btn danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredInvoices.length">
                  <td colspan="10" class="no-data">No invoices found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Returns Tab -->
        <div v-if="activeTab === 'returns'" class="returns-section">
          <div class="section-header">
            <h2>↩️ Returns & Refunds</h2>
            <button @click="createReturn" class="primary-btn">
              <i class="fas fa-plus"></i> Process Return
            </button>
          </div>

          <div class="filters-bar">
            <input 
              v-model="returnSearch" 
              type="text" 
              placeholder="Search returns..."
              class="search-input"
            >
            <select v-model="returnStatus" class="filter-select">
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="completed">Completed</option>
            </select>
          </div>

          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Return #</th>
                  <th>Sale #</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Refund Amount</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="returnItem in filteredReturns" :key="returnItem.id">
                  <td><strong>{{ returnItem.return_number }}</strong></td>
                  <td>{{ returnItem.sale_number }}</td>
                  <td>{{ returnItem.customer_name }}</td>
                  <td>{{ formatDate(returnItem.date) }}</td>
                  <td>{{ returnItem.items_count }} item(s)</td>
                  <td>Ksh {{ formatPrice(returnItem.refund_amount) }}</td>
                  <td>{{ returnItem.reason }}</td>
                  <td>
                    <span :class="['status-badge', returnItem.status]">
                      {{ returnItem.status }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button @click="viewReturn(returnItem)" class="icon-btn" title="View">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button v-if="returnItem.status === 'pending'" @click="approveReturn(returnItem)" class="icon-btn success" title="Approve">
                        <i class="fas fa-check"></i>
                      </button>
                      <button v-if="returnItem.status === 'pending'" @click="rejectReturn(returnItem)" class="icon-btn danger" title="Reject">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredReturns.length">
                  <td colspan="9" class="no-data">No returns found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Credit Limit Modal -->
    <div v-if="showCreditLimitModal" class="modal-overlay">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingCustomer ? 'Edit' : 'Set' }} Credit Limit</h3>
          <button @click="showCreditLimitModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Customer</label>
            <select v-model="creditForm.customer_id" class="form-control" :disabled="editingCustomer">
              <option value="">Select Customer</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Credit Limit (Ksh)</label>
            <input v-model.number="creditForm.credit_limit" type="number" class="form-control" min="0" step="100">
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="creditForm.notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showCreditLimitModal = false" class="secondary-btn">Cancel</button>
          <button @click="saveCreditLimit" class="primary-btn">Save</button>
        </div>
      </div>
    </div>

    <!-- Payment Recording Modal -->
    <div v-if="showPaymentModal" class="modal-overlay">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Record Payment</h3>
          <button @click="showPaymentModal = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="paymentCustomer" class="form-group">
            <label>Customer</label>
            <p><strong>{{ paymentCustomer.name }}</strong></p>
            <p>Current Balance: <strong>Ksh {{ formatPrice(paymentCustomer.credit_balance || 0) }}</strong></p>
          </div>
          <div class="form-group">
            <label>Amount (Ksh)*</label>
            <input v-model.number="paymentForm.amount" type="number" class="form-control" min="0.01" step="0.01" :max="paymentCustomer?.credit_balance">
          </div>
          <div class="form-group">
            <label>Payment Method*</label>
            <select v-model="paymentForm.payment_method" class="form-control">
              <option value="">Select Method</option>
              <option value="Cash">Cash</option>
              <option value="M-Pesa">M-Pesa</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Card">Card</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
          <div class="form-group">
            <label>Transaction Number</label>
            <input v-model="paymentForm.transaction_number" type="text" class="form-control" placeholder="e.g., M-Pesa code">
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="paymentForm.notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPaymentModal = false" class="secondary-btn">Cancel</button>
          <button @click="savePayment" class="primary-btn">Record Payment</button>
        </div>
      </div>
    </div>

    <!-- Invoice Modal -->
    <div v-if="showInvoiceModal" class="modal-overlay">
      <div class="modal-content scrollable-modal" @click.stop>
        <div class="modal-header">
          <h3>New Invoice</h3>
          <button @click="closeInvoiceModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveInvoice">
            <div class="form-group">
              <label>Type</label>
              <select v-model="invoiceForm.type" class="form-control" required>
                <option value="sale">Sale</option>
                <option value="purchase">Purchase</option>
                <option value="service">Service</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group" v-if="invoiceForm.type === 'sale'">
              <label>Customer</label>
              <select v-model="invoiceForm.customer_id" class="form-control" required>
                <option value="">Select Customer</option>
                <option v-for="c in customersList" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="form-group" v-if="invoiceForm.type === 'purchase'">
              <label>Supplier</label>
              <select
                v-model="invoiceForm.supplier_id"
                class="form-control"
                required
                @change="handleSupplierChange"
              >
                <option value="">Select Supplier</option>
                <option v-for="s in suppliersList" :key="s.id" :value="s.id">{{ s.name }}</option>
                <option value="__new__">Other (Add New Supplier)</option>
              </select>
              
              <!-- New Supplier Form (only show when "__new__" is selected) -->
              <div v-if="showNewSupplierInput.value === true" class="new-supplier-form">
                <div v-if="supplierError" class="alert" style="margin-bottom: 1rem; color: #b91c1c; background: #fee2e2; border: 1px solid #fecaca;">
                  {{ supplierError }}
                </div>
                <div class="form-group">
                  <label>Supplier Name*</label>
                  <input v-model="newSupplier.name" class="form-control" required />
                </div>
                <div class="form-group">
                  <label>Contact Person</label>
                  <input v-model="newSupplier.contact_person" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Phone</label>
                  <input v-model="newSupplier.phone" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input v-model="newSupplier.email" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Address</label>
                  <input v-model="newSupplier.address" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Products Supplied</label>
                  <input v-model="newSupplier.products_supplied" class="form-control" />
                </div>
                <div class="form-group">
                  <label>Notes</label>
                  <textarea v-model="newSupplier.notes" class="form-control"></textarea>
                </div>
                <button type="button" class="primary-btn" @click="saveNewSupplier" :disabled="supplierLoading">
                  <span v-if="supplierLoading"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                  <span v-else>Save Supplier</span>
                </button>
                <button type="button" class="secondary-btn" @click="() => { invoiceForm.supplier_id = ''; showNewSupplierInput.value = false; supplierError.value = '' }">Cancel</button>
              </div>
            </div>
            <div class="form-group">
              <label>Due Date</label>
              <input type="date" v-model="invoiceForm.due_date" class="form-control" />
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea v-model="invoiceForm.notes" class="form-control"></textarea>
            </div>
            <div>
              <h4>Items</h4>
              <div v-for="(item, idx) in invoiceForm.items" :key="idx" class="invoice-item-row">
                <select v-model="item.product_id" class="form-control" required @change="onInvoiceProductChange(item)">
                  <option value="">Select Product</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">
                    {{ p.name }} - Ksh {{ (p.price || 0).toLocaleString() }}
                  </option>
                </select>
                <select v-model="item.uom_id" class="form-control" style="width:130px" @change="onInvoiceUomChange(item)">
                  <option :value="null">UOM</option>
                  <option v-for="u in getProductUomOptions(item.product_id)" :key="u.id" :value="u.id">
                    {{ u.abbreviation || u.name }}
                  </option>
                </select>
                <input type="number" v-model.number="item.quantity" min="1" placeholder="Qty" class="form-control" style="width:70px" required />
                <input type="number" v-model.number="item.unit_price" min="0" step="0.01" placeholder="Unit Price" class="form-control" style="width:100px" required />
                <button type="button" @click="removeInvoiceItem(idx)" v-if="invoiceForm.items.length > 1">✖</button>
              </div>
              <button type="button" @click="addInvoiceItem">Add Item</button>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeInvoiceModal" class="secondary-btn">Cancel</button>
              <button type="submit" class="primary-btn">Create</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Invoice View Modal -->
    <div v-if="viewingInvoice" class="modal-overlay" @click="viewingInvoice = null">
      <div class="invoice-view-modal" @click.stop>
        <div class="ivm-header">
          <div class="ivm-header-info">
            <span class="ivm-label">Invoice</span>
            <h2 class="ivm-number">#{{ viewingInvoice.invoice_number }}</h2>
            <span class="ivm-date">{{ formatDate(viewingInvoice.date || viewingInvoice.invoice_date) }}</span>
          </div>
          <div class="ivm-header-right">
            <span class="ivm-status-badge" :class="'status-' + viewingInvoice.status">{{ viewingInvoice.status }}</span>
            <button class="ivm-close-btn" @click="viewingInvoice = null"><i class="fas fa-times"></i></button>
          </div>
        </div>

        <div class="ivm-body">
          <div class="ivm-details-grid">
            <div class="ivm-detail-card">
              <div class="ivm-detail-label"><i class="fas fa-tag"></i> Type</div>
              <div class="ivm-detail-value type-badge">{{ (viewingInvoice.type || 'sale').toUpperCase() }}</div>
            </div>
            <div class="ivm-detail-card">
              <div class="ivm-detail-label"><i class="fas fa-user"></i> Customer</div>
              <div class="ivm-detail-value">{{ viewingInvoice.customer_name || '—' }}</div>
            </div>
            <div class="ivm-detail-card">
              <div class="ivm-detail-label"><i class="fas fa-calendar"></i> Due Date</div>
              <div class="ivm-detail-value">{{ formatDate(viewingInvoice.due_date) || '—' }}</div>
            </div>
            <div class="ivm-detail-card">
              <div class="ivm-detail-label"><i class="fas fa-boxes"></i> Items</div>
              <div class="ivm-detail-value">{{ viewingInvoice.items?.length || 0 }} item(s)</div>
            </div>
          </div>

          <div class="ivm-financials">
            <div class="ivm-fin-row">
              <span>Total Amount</span>
              <span class="ivm-fin-value">Ksh {{ formatPrice(viewingInvoice.total) }}</span>
            </div>
            <div class="ivm-fin-row">
              <span>Amount Paid</span>
              <span class="ivm-fin-value paid">Ksh {{ formatPrice(viewingInvoice.paid_amount) }}</span>
            </div>
            <div class="ivm-fin-row total">
              <span>Balance</span>
              <span class="ivm-fin-value balance">Ksh {{ formatPrice(viewingInvoice.balance) }}</span>
            </div>
          </div>

          <div v-if="viewingInvoice.items && viewingInvoice.items.length" class="ivm-items">
            <h4 class="ivm-items-title"><i class="fas fa-list"></i> Invoice Items</h4>
            <table class="ivm-items-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>UOM</th>
                  <th>Unit Price</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in viewingInvoice.items" :key="item.id">
                  <td>{{ item.product_name || item.description || '—' }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ item.uom?.abbreviation || item.uom?.name || 'Base' }}</td>
                  <td>Ksh {{ formatPrice(item.unit_price) }}</td>
                  <td>Ksh {{ formatPrice(item.quantity * item.unit_price) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="viewingInvoice.notes" class="ivm-notes">
            <div class="ivm-detail-label"><i class="fas fa-sticky-note"></i> Notes</div>
            <p>{{ viewingInvoice.notes }}</p>
          </div>
        </div>

        <div class="ivm-footer">
          <button class="btn-ivm-print" @click="printInvoice(viewingInvoice)"><i class="fas fa-print"></i> Print</button>
          <button class="btn-ivm-close" @click="viewingInvoice = null"><i class="fas fa-times"></i> Close</button>
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

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('credit')
const tabs = [
  { id: 'credit', label: 'Credit Management', icon: '💳' },
  { id: 'invoices', label: 'Invoices', icon: '🧾' },
  { id: 'returns', label: 'Returns', icon: '↩️' }
]

// Credit Management
const customers = ref([])
const creditSearch = ref('')
const creditFilter = ref('all')
const showCreditLimitModal = ref(false)
const showPaymentModal = ref(false)
const editingCustomer = ref(null)
const paymentCustomer = ref(null)
const creditForm = ref({
  customer_id: '',
  credit_limit: 0,
  notes: ''
})
const paymentForm = ref({
  amount: 0,
  payment_method: '',
  transaction_number: '',
  notes: ''
})

// Invoices
const invoices = ref([])
const invoiceSearch = ref('')
const invoiceStatus = ref('all')
const invoiceDateFrom = ref('')
const invoiceDateTo = ref('')

// Returns
const returns = ref([])
const returnSearch = ref('')
const returnStatus = ref('all')

// Computed
const filteredCreditCustomers = computed(() => {
  let filtered = customers.value

  if (creditSearch.value) {
    const query = creditSearch.value.toLowerCase()
    filtered = filtered.filter(c => 
      c.name.toLowerCase().includes(query) || 
      (c.phone && c.phone.includes(query))
    )
  }

  if (creditFilter.value === 'with-balance') {
    filtered = filtered.filter(c => (c.credit_balance || 0) > 0)
  } else if (creditFilter.value === 'over-limit') {
    filtered = filtered.filter(c => (c.credit_balance || 0) > (c.credit_limit || 0))
  }

  return filtered
})

const filteredInvoices = computed(() => {
  let filtered = invoices.value

  if (invoiceSearch.value) {
    const query = invoiceSearch.value.toLowerCase()
    filtered = filtered.filter(i => 
      i.invoice_number.toLowerCase().includes(query) || 
      i.customer_name.toLowerCase().includes(query)
    )
  }

  if (invoiceStatus.value !== 'all') {
    filtered = filtered.filter(i => i.status === invoiceStatus.value)
  }

  return filtered
})

const filteredReturns = computed(() => {
  let filtered = returns.value

  if (returnSearch.value) {
    const query = returnSearch.value.toLowerCase()
    filtered = filtered.filter(r => 
      r.return_number.toLowerCase().includes(query) || 
      r.customer_name.toLowerCase().includes(query)
    )
  }

  if (returnStatus.value !== 'all') {
    filtered = filtered.filter(r => r.status === returnStatus.value)
  }

  return filtered
})

// Methods
function formatPrice(price) {
  return new Intl.NumberFormat().format(price || 0)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-GB')
}

function getCreditStatus(customer) {
  const balance = customer.credit_balance || 0
  const limit = customer.credit_limit || 0
  
  if (balance > limit) return 'danger'
  if (balance > limit * 0.8) return 'warning'
  return 'success'
}

function getCreditStatusText(customer) {
  const balance = customer.credit_balance || 0
  const limit = customer.credit_limit || 0
  
  if (balance > limit) return 'Over Limit'
  if (balance > limit * 0.8) return 'Near Limit'
  if (balance > 0) return 'Active'
  return 'Good Standing'
}

async function fetchCustomers() {
  try {
    const res = await axios.get('/customers')
    customers.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (err) {
    console.error('Failed to fetch customers', err)
    alert('Failed to load customers')
  }
}

function editCreditLimit(customer) {
  editingCustomer.value = customer
  creditForm.value = {
    customer_id: customer.id,
    credit_limit: customer.credit_limit || 0,
    notes: ''
  }
  showCreditLimitModal.value = true
}

function recordPayment(customer) {
  paymentCustomer.value = customer
  paymentForm.value = {
    amount: customer.credit_balance || 0,
    payment_method: '',
    transaction_number: '',
    notes: ''
  }
  showPaymentModal.value = true
}

async function savePayment() {
  if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
    alert('Please enter a valid amount')
    return
  }
  
  if (!paymentForm.value.payment_method) {
    alert('Please select a payment method')
    return
  }
  
  if (paymentForm.value.amount > paymentCustomer.value.credit_balance) {
    alert('Payment amount cannot exceed customer credit balance')
    return
  }
  
  try {
    await axios.post(`/api/customers/${paymentCustomer.value.id}/credit/payment`, paymentForm.value)
    alert('Payment recorded successfully!')
    showPaymentModal.value = false
    paymentCustomer.value = null
    paymentForm.value = { amount: 0, payment_method: '', transaction_number: '', notes: '' }
    await fetchCustomers()
  } catch (err) {
    console.error('Failed to record payment', err)
    alert(err.response?.data?.error || 'Failed to record payment')
  }
}

async function viewCreditHistory(customer) {
  try {
    const res = await axios.get(`/api/customers/${customer.id}/credit`)
    const transactions = res.data.transactions || []
    
    if (transactions.length === 0) {
      alert(`No credit history for ${customer.name}`)
      return
    }
    
    let history = `Credit History for ${customer.name}\n\nCurrent Balance: Ksh ${formatPrice(customer.credit_balance || 0)}\nCredit Limit: Ksh ${formatPrice(customer.credit_limit || 0)}\n\n`
    transactions.slice(0, 10).forEach(t => {
      history += `${formatDate(t.created_at)} - ${t.type.toUpperCase()}: Ksh ${formatPrice(t.amount)} (Balance: ${formatPrice(t.balance_after)})\n`
      if (t.notes) history += `  Note: ${t.notes}\n`
    })
    
    alert(history)
  } catch (err) {
    console.error('Failed to fetch credit history', err)
    alert('Failed to load credit history')
  }
}

async function adjustBalance(customer) {
  const adjustment = prompt(`Current balance: Ksh ${customer.credit_balance || 0}\n\nEnter adjustment amount (use negative to reduce):`)
  if (adjustment === null) return
  
  const amount = parseFloat(adjustment)
  if (isNaN(amount)) {
    alert('Invalid amount')
    return
  }
  
  const reason = prompt('Enter reason for adjustment:')
  if (!reason) {
    alert('Reason is required')
    return
  }
  
  try {
    await axios.post(`/api/customers/${customer.id}/credit/adjust`, {
      amount,
      reason
    })
    alert('Balance adjusted successfully')
    await fetchCustomers()
  } catch (err) {
    console.error('Failed to adjust balance', err)
    alert(err.response?.data?.error || 'Failed to adjust balance')
  }
}

async function saveCreditLimit() {
  if (!creditForm.value.customer_id) {
    alert('Please select a customer')
    return
  }
  
  try {
    await axios.put(`/api/customers/${creditForm.value.customer_id}/credit/limit`, {
      credit_limit: creditForm.value.credit_limit,
      notes: creditForm.value.notes
    })
    alert('Credit limit saved successfully!')
    showCreditLimitModal.value = false
    editingCustomer.value = null
    creditForm.value = { customer_id: '', credit_limit: 0, notes: '' }
    await fetchCustomers()
  } catch (err) {
    console.error('Failed to save credit limit', err)
    alert(err.response?.data?.error || 'Failed to save credit limit')
  }
}


const showInvoiceModal = ref(false)
const viewingInvoice = ref(null)
const invoiceForm = ref({
  type: 'sale',
  customer_id: '',
  supplier_id: '',
  due_date: '',
  notes: '',
  items: [{ product_id: '', uom_id: null, quantity: 1, unit_price: 0 }]
})
const products = ref([])
const customersList = ref([])
const suppliersList = ref([])
const showNewSupplierInput = ref(false)
const newSupplier = ref({
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  products_supplied: '',
  notes: ''
})
const supplierLoading = ref(false)
const supplierError = ref('')

// Custom alerts and confirmations
const customAlert = ref({
  show: false,
  type: 'success', // success, error, warning, info
  title: '',
  message: '',
  buttonText: 'Close'
})
const confirmation = ref({
  show: false,
  title: '',
  message: '',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  onConfirm: () => {}
})

// ...existing code...

async function fetchProducts() {
  try {
    const res = await axios.get('/products')
    products.value = Array.isArray(res.data?.data) ? res.data.data : Array.isArray(res.data) ? res.data : []
  } catch (err) {
    console.error('Failed to fetch products', err)
    products.value = []
  }
}

async function fetchCustomersList() {
  try {
    const res = await axios.get('/customers')
    customersList.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (err) {
    console.error('Failed to fetch customers', err)
    customersList.value = []
  }
}

async function fetchSuppliersList() {
  try {
    const res = await axios.get('/suppliers')
    suppliersList.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (err) {
    console.error('Failed to fetch suppliers', err)
    suppliersList.value = []
  }
}

function addInvoiceItem() {
  invoiceForm.value.items.push({ product_id: '', uom_id: null, quantity: 1, unit_price: 0 })
}
function removeInvoiceItem(idx) {
  if (invoiceForm.value.items.length > 1) invoiceForm.value.items.splice(idx, 1)
}
function closeInvoiceModal() {
  showInvoiceModal.value = false
  showNewSupplierInput.value = false
  invoiceForm.value = {
    type: 'sale',
    customer_id: '',
    supplier_id: '',
    due_date: '',
    notes: '',
    items: [{ product_id: '', uom_id: null, quantity: 1, unit_price: 0 }]
  }
}

function getProductById(productId) {
  return products.value.find(p => Number(p.id) === Number(productId)) || null
}

function getProductUomOptions(productId) {
  const product = getProductById(productId)
  if (!product) return []

  const saleUoms = getProductSaleUoms(product)
  if (saleUoms.length > 0) {
    return saleUoms
  }

  const fallbacks = [getProductSaleUom(product), getProductPurchaseUom(product), getProductBaseUom(product)].filter(Boolean)
  const deduped = []
  const seen = new Set()
  for (const u of fallbacks) {
    if (!seen.has(Number(u.id))) {
      seen.add(Number(u.id))
      deduped.push(u)
    }
  }
  return deduped
}

function getProductSaleUoms(product) {
  if (!product) return []
  if (Array.isArray(product.saleUoms)) return product.saleUoms
  if (Array.isArray(product.sale_uoms)) return product.sale_uoms
  return []
}

function getProductSaleUom(product) {
  return product?.saleUom || product?.sale_uom || null
}

function getProductPurchaseUom(product) {
  return product?.purchaseUom || product?.purchase_uom || null
}

function getProductBaseUom(product) {
  return product?.uom || product?.base_uom || null
}

function getProductPriceForUom(product, uomId) {
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
}

function onInvoiceProductChange(item) {
  const product = getProductById(item.product_id)
  if (!product) {
    item.uom_id = null
    item.unit_price = 0
    return
  }

  const options = getProductUomOptions(item.product_id)
  if (!options.length) {
    item.uom_id = null
  } else if (!options.some(u => Number(u.id) === Number(item.uom_id))) {
    item.uom_id = options[0].id
  }

  item.unit_price = getProductPriceForUom(product, item.uom_id)
}

function onInvoiceUomChange(item) {
  const product = getProductById(item.product_id)
  if (!product) return
  item.unit_price = getProductPriceForUom(product, item.uom_id)
}

function getInvoiceUomSummary(invoice) {
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
}

// Custom Alert Helper Methods
function showAlert(type, title, message, buttonText = 'Close') {
  customAlert.value = {
    show: true,
    type,
    title,
    message,
    buttonText
  }
}

function showSuccess(title, message) {
  showAlert('success', title, message, 'OK')
}

function showError(title, message) {
  showAlert('error', title, message, 'Close')
}

function showWarning(title, message) {
  showAlert('warning', title, message, 'OK')
}

function showInfo(title, message) {
  showAlert('info', title, message, 'OK')
}

// Custom Confirmation Helper Method
function showConfirmation(title, message, onConfirm, confirmText = 'Confirm', cancelText = 'Cancel') {
  confirmation.value = {
    show: true,
    title,
    message,
    confirmText,
    cancelText,
    onConfirm: () => {
      confirmation.value.show = false
      onConfirm()
    }
  }
}

async function saveInvoice() {
  try {
    // Validate required fields before sending
    if (!invoiceForm.value.items || invoiceForm.value.items.length === 0) {
      showWarning('Validation Error', 'Please add at least one item to the invoice')
      return
    }

    // Validate invoice type-specific required fields
    if (invoiceForm.value.type === 'sale' && !invoiceForm.value.customer_id) {
      showWarning('Validation Error', 'Please select a customer for sale invoices')
      return
    }

    if (invoiceForm.value.type === 'purchase' && !invoiceForm.value.supplier_id) {
      showWarning('Validation Error', 'Please select a supplier for purchase invoices')
      return
    }

    // Validate all items have required fields
    const invalidItems = invoiceForm.value.items.filter(item => 
      !item.product_id || !item.quantity || item.quantity <= 0 || !item.unit_price || item.unit_price < 0
    )

    if (invalidItems.length > 0) {
      showWarning('Validation Error', 'Please ensure all items have valid product, quantity, and unit price')
      return
    }

    // Build the payload matching backend validation
    const payload = {
      type: invoiceForm.value.type,
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: invoiceForm.value.due_date || null,
      notes: invoiceForm.value.notes || null,
      items: invoiceForm.value.items.map(i => ({
        product_id: i.product_id ? parseInt(i.product_id) : null,
        uom_id: i.uom_id ? parseInt(i.uom_id) : null,
        quantity: parseInt(i.quantity),
        unit_price: parseFloat(i.unit_price),
        description: null
      })),
      // For sale invoices, set customer_id; for purchase, set supplier_id
      ...(invoiceForm.value.type === 'sale' ? {
        customer_id: parseInt(invoiceForm.value.customer_id),
        supplier_id: null
      } : {
        supplier_id: parseInt(invoiceForm.value.supplier_id),
        customer_id: null
      }),
      company_id: customersList.value.find(c => c.id === invoiceForm.value.customer_id)?.company_id 
        || suppliersList.value.find(s => s.id === invoiceForm.value.supplier_id)?.company_id
        || 1, // fallback to 1 if not found
      tax: 0,
      discount: 0,
      paid_amount: 0
    }

    console.log('Invoice payload:', payload)

    const res = await axios.post('/invoices', payload)
    
    console.log('Invoice created:', res.data)
    closeInvoiceModal()
    await fetchInvoices()
    showSuccess('Invoice Created', 'Invoice has been created successfully!')
  } catch (err) {
    console.error('Failed to create invoice:', err)
    
    // Detailed error handling
    const errorMessage = err.response?.data?.message 
      || err.response?.data?.error 
      || (err.response?.data?.details ? Object.values(err.response.data.details).join(', ') : '')
      || err.message
    
    showError('Failed to Create Invoice', errorMessage)
  }
}
function handleSupplierChange() {
  if (invoiceForm.value.supplier_id === '__new__') {
    showNewSupplierInput.value = true
  } else {
    showNewSupplierInput.value = false
    supplierError.value = ''
  }
}
async function saveNewSupplier() {
  supplierError.value = ''
  supplierLoading.value = true
  
  if (!newSupplier.value.name) {
    supplierError.value = 'Supplier name is required'
    supplierLoading.value = false
    return
  }
  
  try {
    const res = await axios.post('/suppliers', {
      name: newSupplier.value.name,
      contact_person: newSupplier.value.contact_person,
      phone: newSupplier.value.phone,
      email: newSupplier.value.email,
      address: newSupplier.value.address,
      products_supplied: newSupplier.value.products_supplied,
      notes: newSupplier.value.notes
    })
    
    const supplier = res.data.data || res.data
    if (!supplier || !supplier.id) {
      supplierError.value = 'Failed to add supplier. Please try again.'
      supplierLoading.value = false
      return
    }
    
    suppliersList.value.push(supplier)
    invoiceForm.value.supplier_id = supplier.id
    showNewSupplierInput.value = false
    newSupplier.value = {
      name: '',
      contact_person: '',
      phone: '',
      email: '',
      address: '',
      products_supplied: '',
      notes: ''
    }
    supplierLoading.value = false
    supplierError.value = ''
    alert('Supplier added successfully!')
  } catch (err) {
    supplierError.value =
      err.response?.data?.error ||
      (err.response?.data?.details && Object.values(err.response.data.details).join(', ')) ||
      'Failed to add supplier'
    supplierLoading.value = false
  }
}

function createInvoice() {
  showInvoiceModal.value = true
  fetchProducts()
  fetchCustomersList()
  fetchSuppliersList()
}

// Return methods
function createReturn() {
  alert('Process return feature coming soon!')
}

function viewReturn(returnItem) {
  alert(`View return ${returnItem.return_number} - Feature coming soon!`)
}

async function approveReturn(returnItem) {
  if (!confirm(`Approve return ${returnItem.return_number} for Ksh ${formatPrice(returnItem.refund_amount)}?`)) return
  
  try {
    await axios.post(`/api/returns/${returnItem.id}/approve`)
    alert('Return approved successfully!')
    await fetchReturns()
    await fetchCustomers()
  } catch (err) {
    console.error('Failed to approve return', err)
    alert(err.response?.data?.error || 'Failed to approve return')
  }
}

async function rejectReturn(returnItem) {
  if (!confirm(`Reject return ${returnItem.return_number}?`)) return
  
  const notes = prompt('Enter rejection reason:')
  if (!notes) {
    alert('Rejection reason is required')
    return
  }
  
  try {
    await axios.post(`/api/returns/${returnItem.id}/reject`, { notes })
    alert('Return rejected successfully!')
    await fetchReturns()
  } catch (err) {
    console.error('Failed to reject return', err)
    alert(err.response?.data?.error || 'Failed to reject return')
  }
}

async function fetchInvoices() {
  try {
    const res = await axios.get('/invoices')
    // Handle paginated response from Laravel
    const list = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : res.data?.data || []
    
    invoices.value = list.map(inv => ({
      id: inv.id,
      invoice_number: inv.invoice_number,
      customer_name: inv.customer?.name || 'Unknown',
      date: inv.invoice_date,
      due_date: inv.due_date,
      total: parseFloat(inv.total || 0),
      paid_amount: parseFloat(inv.paid_amount || 0),
      balance: parseFloat(inv.balance || 0),
      status: inv.status,
      type: inv.type,
      items: inv.items || [],
      customer: inv.customer,
      created_at: inv.created_at
    }))
  } catch (err) {
    console.error('Failed to fetch invoices', err)
    invoices.value = []
  }
}

function viewInvoice(invoice) {
  viewingInvoice.value = invoice
}

async function editInvoice(invoice) {
  if (invoice.status === 'reversed') {
    showWarning('Invoice Reversed', 'A reversed invoice cannot be edited.')
    return
  }

  // Show status edit confirmation
  const statusOptions = ['draft', 'sent', 'paid', 'cancelled']
  const currentStatus = invoice.status || 'draft'
  
  showConfirmation(
    'Edit Invoice Status',
    `Current status: ${currentStatus}. Update to a new status?`,
    () => {
      // Prompt for new status
      const newStatus = prompt('Enter new status (draft, sent, paid, cancelled):', currentStatus)
      if (!newStatus) return
      
      const validStatuses = ['draft', 'sent', 'paid', 'cancelled']
      if (!validStatuses.includes(newStatus)) {
        showError('Invalid Status', 'Status must be one of: ' + validStatuses.join(', '))
        return
      }
      
      // Make the update
      updateInvoiceStatus(invoice.id, newStatus)
    },
    'Yes, Edit',
    'Cancel'
  )
}

async function updateInvoiceStatus(invoiceId, newStatus) {
  try {
    await axios.put(`/invoices/${invoiceId}`, { status: newStatus })
    showSuccess('Invoice Updated', 'Invoice status has been updated successfully!')
    await fetchInvoices()
  } catch (err) {
    console.error('Failed to update invoice', err)
    showError('Update Failed', err.response?.data?.error || 'Failed to update invoice')
  }
}

function printInvoice(invoice) {
  // Build printable HTML
  const html = `
    <html>
      <head>
        <title>Invoice ${invoice.invoice_number}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .invoice-header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
          .invoice-number { font-size: 24px; font-weight: bold; }
          .invoice-date { color: #666; }
          table { width: 100%; border-collapse: collapse; margin: 20px 0; }
          th { background: #f0f0f0; padding: 10px; text-align: left; }
          td { padding: 8px; border-bottom: 1px solid #ddd; }
          .total-row { font-weight: bold; }
          .footer { margin-top: 40px; color: #666; }
        </style>
      </head>
      <body>
        <div class="invoice-header">
          <div class="invoice-number">Invoice ${invoice.invoice_number}</div>
          <div class="invoice-date">${formatDate(invoice.date)}</div>
        </div>
        
        <div style="margin-bottom: 20px;">
          <strong>Bill To:</strong>
          <p>${invoice.customer_name}</p>
          <strong>Status:</strong>
          <p>${invoice.status}</p>
        </div>
        
        <table>
          <tr>
            <th>Description</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
          </tr>
          ${(invoice.items || []).map(item => `
            <tr>
              <td>${item.product?.name || 'Item ' + item.id}</td>
              <td>${item.quantity} ${item.uom?.abbreviation || item.uom?.name || ''}</td>
              <td>Ksh ${formatPrice(item.unit_price || 0)}</td>
              <td>Ksh ${formatPrice((item.unit_price || 0) * item.quantity)}</td>
            </tr>
          `).join('')}
          <tr class="total-row">
            <td colspan="3">TOTAL</td>
            <td>Ksh ${formatPrice(invoice.total)}</td>
          </tr>
          <tr>
            <td colspan="3">Paid</td>
            <td>Ksh ${formatPrice(invoice.paid_amount)}</td>
          </tr>
          <tr class="total-row">
            <td colspan="3">Balance Due</td>
            <td>Ksh ${formatPrice(invoice.balance)}</td>
          </tr>
        </table>
        
        <div class="footer">
          <p>Due: ${formatDate(invoice.due_date)}</p>
          <p>Thank you for your business!</p>
        </div>
      </body>
    </html>
  `
  
  const printWindow = window.open('', '', 'height=600,width=800')
  printWindow.document.write(html)
  printWindow.document.close()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}

async function deleteInvoice(invoice) {
  showConfirmation(
    'Delete Invoice',
    `Are you sure you want to delete invoice ${invoice.invoice_number}? This action cannot be undone.`,
    async () => {
      try {
        await axios.delete(`/invoices/${invoice.id}`)
        showSuccess('Invoice Deleted', 'Invoice has been deleted successfully!')
        await fetchInvoices()
      } catch (err) {
        console.error('Failed to delete invoice', err)
        showError('Delete Failed', err.response?.data?.error || 'Failed to delete invoice')
      }
    },
    'Delete',
    'Cancel'
  )
}

async function reverseInvoice(invoice) {
  if (invoice.status === 'reversed') {
    showInfo('Already Reversed', 'This invoice is already reversed.')
    return
  }

  showConfirmation(
    'Reverse Invoice',
    `Reverse invoice ${invoice.invoice_number}? Items will return to inventory and invoice amounts will be reset.`,
    async () => {
      try {
        await axios.post(`/invoices/${invoice.id}/reverse`)

        showSuccess('Invoice Reversed', 'Invoice reversed successfully.')

        // Send a global admin notification to the top-right alerts center.
        window.dispatchEvent(new CustomEvent('app:add-notification', {
          detail: {
            type: 'success',
            message: `Invoice ${invoice.invoice_number} reversed successfully.`
          }
        }))

        if (viewingInvoice.value && viewingInvoice.value.id === invoice.id) {
          viewingInvoice.value.status = 'reversed'
          viewingInvoice.value.paid_amount = 0
          viewingInvoice.value.balance = 0
        }

        await fetchInvoices()
      } catch (err) {
        console.error('Failed to reverse invoice', err)
        showError('Reverse Failed', err.response?.data?.message || err.response?.data?.error || 'Failed to reverse invoice')
      }
    },
    'Reverse',
    'Cancel'
  )
}

async function fetchReturns() {
  try {
    const res = await axios.get('/api/returns')
    returns.value = res.data.map(ret => ({
      ...ret,
      customer_name: ret.customer?.name || 'Unknown',
      items_count: ret.items?.length || 0,
      sale_number: ret.sale?.id || 'N/A'
    }))
  } catch (err) {
    console.error('Failed to fetch returns', err)
  }
}

onMounted(() => {
  fetchCustomers()
  fetchInvoices()
  fetchReturns()
})
</script>

<style scoped>
.accounts-management {
  padding: 2rem;
  background: #f5f7fa;
  min-height: 100vh;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.page-header p {
  color: #718096;
  font-size: 1rem;
}

.tabs-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.tabs-header {
  display: flex;
  border-bottom: 2px solid #e2e8f0;
  background: #f7fafc;
}

.tab-btn {
  flex: 1;
  padding: 1.25rem 1.5rem;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  color: #718096;
  font-size: 1rem;
  font-weight: 500;
  transition: all 0.3s;
  border-bottom: 3px solid transparent;
}

.tab-btn:hover {
  background: #edf2f7;
  color: #4a5568;
}

.tab-btn.active {
  color: #667eea;
  background: white;
  border-bottom-color: #667eea;
}

.tab-icon {
  font-size: 1.25rem;
}

.tab-content {
  padding: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  font-size: 1.5rem;
  color: #2d3748;
}

.filters-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.search-input, .filter-select, .date-input {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.filter-select, .date-input {
  min-width: 150px;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f7fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.data-table tbody tr:hover {
  background: #f7fafc;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-info strong {
  color: #2d3748;
}

.customer-info small {
  color: #718096;
  font-size: 0.85rem;
}

.amount-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
}

.amount-badge.success {
  background: #c6f6d5;
  color: #22543d;
}

.amount-badge.warning {
  background: #feebc8;
  color: #7c2d12;
}

.status-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.success, .status-badge.paid, .status-badge.approved, .status-badge.completed {
  background: #c6f6d5;
  color: #22543d;
}

.status-badge.warning, .status-badge.sent, .status-badge.pending {
  background: #feebc8;
  color: #7c2d12;
}

.status-badge.danger, .status-badge.overdue, .status-badge.rejected {
  background: #fed7d7;
  color: #742a2a;
}

.status-badge.draft {
  background: #e2e8f0;
  color: #2d3748;
}

.status-badge.reversed {
  background: #e9d8fd;
  color: #553c9a;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.icon-btn {
  padding: 0.5rem;
  background: #edf2f7;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: #4a5568;
  transition: all 0.2s;
}

.icon-btn:hover {
  background: #e2e8f0;
  transform: translateY(-1px);
}

.icon-btn.success {
  background: #c6f6d5;
  color: #22543d;
}

.icon-btn.danger {
  background: #fed7d7;
  color: #742a2a;
}

.primary-btn {
  padding: 0.75rem 1.5rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s;
}

.primary-btn:hover {
  background: #5a67d8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.secondary-btn {
  padding: 0.75rem 1.5rem;
  background: #e2e8f0;
  color: #2d3748;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.secondary-btn:hover {
  background: #cbd5e0;
}

.no-data {
  text-align: center;
  padding: 3rem !important;
  color: #a0aec0;
  font-style: italic;
}

/* Modal */
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
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.scrollable-modal {
  overflow-y: auto;
  max-height: 90vh;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  font-size: 1.25rem;
  color: #2d3748;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.close-btn:hover {
  background: #f7fafc;
}

.modal-body {
  padding: 1.5rem;
  /* Remove overflow here, handled by .scrollable-modal */
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #4a5568;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Custom Alert Modal Styles */
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
  z-index: 2000;
  animation: fadeInOverlay 0.3s ease;
}

@keyframes fadeInOverlay {
  from { opacity: 0; }
  to { opacity: 1; }
}

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
  max-height: 200px;
  overflow-y: auto;
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

/* Invoice View Modal Styles */
.invoice-view-modal {
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 80px rgba(0, 0, 0, 0.35);
  max-width: 680px;
  width: 95%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
  display: flex;
  flex-direction: column;
}

.ivm-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 2rem 2rem 1.5rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
  border-bottom: 1px solid #e2e8f0;
  border-radius: 20px 20px 0 0;
}

.ivm-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #a0aec0;
  display: block;
  margin-bottom: 0.25rem;
}

.ivm-number {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.ivm-date {
  font-size: 0.85rem;
  color: #718096;
  display: block;
  margin-top: 0.25rem;
}

.ivm-header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.ivm-status-badge {
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-paid { background: #c6f6d5; color: #276749; }
.status-pending { background: #fed7d7; color: #9b2335; }
.status-draft { background: #e2e8f0; color: #4a5568; }
.status-sent { background: #bee3f8; color: #2a69ac; }
.status-overdue { background: #fed7d7; color: #9b2335; }
.status-cancelled { background: #e2e8f0; color: #718096; }
.status-reversed { background: #e9d8fd; color: #553c9a; }

.ivm-close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #a0aec0;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 8px;
  transition: all 0.2s;
  line-height: 1;
}

.ivm-close-btn:hover {
  color: #e53e3e;
  background: rgba(229, 62, 62, 0.08);
}

.ivm-body {
  padding: 1.5rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.ivm-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.ivm-detail-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  border: 1px solid #e2e8f0;
}

.ivm-detail-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #a0aec0;
  margin-bottom: 0.4rem;
}

.ivm-detail-value {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2d3748;
}

.type-badge {
  display: inline-block;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.ivm-financials {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  border: 1px solid rgba(102, 126, 234, 0.15);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.ivm-fin-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
  color: #4a5568;
}

.ivm-fin-row.total {
  padding-top: 0.75rem;
  border-top: 1px solid rgba(102, 126, 234, 0.2);
  font-weight: 700;
  font-size: 1rem;
  color: #2d3748;
}

.ivm-fin-value {
  font-weight: 600;
  color: #2d3748;
}

.ivm-fin-value.paid { color: #276749; }
.ivm-fin-value.balance { color: #e53e3e; }

.ivm-items-title {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #718096;
  margin: 0 0 0.75rem;
}

.ivm-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.ivm-items-table thead tr {
  background: #f8fafc;
}

.ivm-items-table th {
  padding: 0.7rem 1rem;
  text-align: left;
  color: #718096;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e2e8f0;
}

.ivm-items-table td {
  padding: 0.8rem 1rem;
  color: #2d3748;
  border-bottom: 1px solid #f7fafc;
}

.ivm-items-table tbody tr:hover {
  background: rgba(102, 126, 234, 0.04);
}

.ivm-notes {
  background: #fffbeb;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  border-left: 4px solid #ed8936;
}

.ivm-notes p {
  margin: 0.5rem 0 0;
  color: #744210;
  font-size: 0.9rem;
  line-height: 1.6;
}

.ivm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.25rem 2rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 0 0 20px 20px;
}

.btn-ivm-print,
.btn-ivm-close {
  padding: 0.7rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-ivm-print {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.btn-ivm-print:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.35);
}

.btn-ivm-close {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-ivm-close:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

@media (max-width: 600px) {
  .invoice-view-modal {
    max-width: 100%;
    border-radius: 16px 16px 0 0;
    max-height: 95vh;
  }

  .ivm-details-grid {
    grid-template-columns: 1fr;
  }

  .ivm-body {
    padding: 1rem 1.25rem;
  }

  .ivm-header {
    padding: 1.25rem;
  }

  .ivm-footer {
    padding: 1rem 1.25rem;
  }
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
