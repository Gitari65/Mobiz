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
                      <button @click="deleteInvoice(invoice)" class="icon-btn danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!filteredInvoices.length">
                  <td colspan="9" class="no-data">No invoices found</td>
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
    <div v-if="showCreditLimitModal" class="modal-overlay" @click="showCreditLimitModal = false">
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
    <div v-if="showPaymentModal" class="modal-overlay" @click="showPaymentModal = false">
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
                <select v-model="item.product_id" class="form-control" required>
                  <option value="">Select Product</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">
                    {{ p.name }} - Ksh {{ (p.price || 0).toLocaleString() }}
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
const invoiceForm = ref({
  type: 'sale',
  customer_id: '',
  supplier_id: '',
  due_date: '',
  notes: '',
  items: [{ product_id: '', quantity: 1, unit_price: 0 }]
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
  invoiceForm.value.items.push({ product_id: '', quantity: 1, unit_price: 0 })
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
    items: [{ product_id: '', quantity: 1, unit_price: 0 }]
  }
}
async function saveInvoice() {
  try {
    // Validate required fields before sending
    if (!invoiceForm.value.items || invoiceForm.value.items.length === 0) {
      alert('Please add at least one item to the invoice')
      return
    }

    // Validate invoice type-specific required fields
    if (invoiceForm.value.type === 'sale' && !invoiceForm.value.customer_id) {
      alert('Please select a customer for sale invoices')
      return
    }

    if (invoiceForm.value.type === 'purchase' && !invoiceForm.value.supplier_id) {
      alert('Please select a supplier for purchase invoices')
      return
    }

    // Validate all items have required fields
    const invalidItems = invoiceForm.value.items.filter(item => 
      !item.product_id || !item.quantity || item.quantity <= 0 || !item.unit_price || item.unit_price < 0
    )

    if (invalidItems.length > 0) {
      alert('Please ensure all items have valid product, quantity, and unit price')
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

    const res = await axios.post('/api/invoices', payload)
    
    console.log('Invoice created:', res.data)
    closeInvoiceModal()
    await fetchInvoices()
    alert('Invoice created successfully!')
  } catch (err) {
    console.error('Failed to create invoice:', err)
    
    // Detailed error handling
    const errorMessage = err.response?.data?.message 
      || err.response?.data?.error 
      || (err.response?.data?.details ? Object.values(err.response.data.details).join(', ') : '')
      || err.message
    
    alert(`Failed to create invoice:\n${errorMessage}`)
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
    const res = await axios.get('/api/invoices')
    // Fix: handle paginated response (Laravel returns {data: [...]})
    const list = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
    invoices.value = list.map(inv => ({
      ...inv,
      customer_name: inv.customer?.name || 'Unknown'
    }))
  } catch (err) {
    console.error('Failed to fetch invoices', err)
    invoices.value = []
  }
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
</style>
