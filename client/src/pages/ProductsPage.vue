<template>
  <div class="pos-container">
    <!-- Alert System -->
    <div v-if="alert.show" class="alert-container" :class="alert.type">
      <div class="alert-content">
        <i :class="getAlertIcon()"></i>
        <span>{{ alert.message }}</span>
      </div>
      <button class="alert-close" @click="hideAlert">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-cube"></i>
            Products Management
          </h1>
          <p class="page-subtitle">Manage your inventory with ease</p>
        </div>
        <div class="header-actions">
          <div class="search-container">
            <div class="search-input-wrapper">
              <i class="fas fa-search search-icon"></i>
              <input 
                type="text" 
                class="search-input" 
                v-model="searchQuery"
                @input="handleSearch"
                placeholder="Search products..." 
              />
              <button 
                v-if="searchQuery" 
                class="clear-search-btn" 
                @click="clearSearch"
                title="Clear search"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div v-if="isSearching" class="search-spinner">
              <div class="mini-spinner"></div>
            </div>
          </div>
          <div class="stats-mini">
            <div class="stat-item">
              <span class="stat-number">{{ filteredProducts.length }}</span>
              <span class="stat-label">{{ searchQuery ? 'Found' : 'Total' }}</span>
            </div>
          </div>
          <button class="btn-add-product" @click="showAddModal = true">
            <i class="fas fa-plus"></i>
            <span>Add Product</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading products...</p>
      </div>
    </div>

    <!-- Products Grid -->
    <div v-else class="products-section">
      <!-- Search Results Info -->
      <div v-if="searchQuery" class="search-results-info">
        <div class="results-summary">
          <i class="fas fa-search"></i>
          <span>
            {{ filteredProducts.length }} result{{ filteredProducts.length !== 1 ? 's' : '' }} 
            for "<strong>{{ searchQuery }}</strong>"
          </span>
        </div>
        <button class="clear-all-btn" @click="clearSearch">
          <i class="fas fa-times"></i>
          Clear Search
        </button>
      </div>

      <div class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card">
          <div class="product-card-inner">
            <!-- Product Image Placeholder -->
            <div class="product-image">
              <i class="fas fa-box-open"></i>
            </div>
            
            <!-- Product Info -->
            <div class="product-info">
              <h3 class="product-name" v-html="highlightSearchTerm(product.name)"></h3>
              <div class="product-details">
                <div class="price-tag">
                  <span class="currency">Ksh</span>
                  <span class="amount">{{ formatPrice(product.price) }}</span>
                </div>
                <div class="stock-info" :class="getStockClass(product.stock_quantity)">
                  <i class="fas fa-boxes"></i>
                  <span>{{ product.stock_quantity }} in stock</span>
                </div>
              </div>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
              <button class="action-btn edit-btn" @click="editProduct(product)" title="Edit Product">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn delete-btn" @click="confirmDelete(product)" title="Delete Product">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>

            <!-- Stock Status Badge -->
            <div class="stock-badge" :class="getStockClass(product.stock_quantity)">
              {{ getStockStatus(product.stock_quantity) }}
            </div>
          </div>
        </div>

        <!-- No Search Results -->
        <div v-if="searchQuery && filteredProducts.length === 0" class="no-results-state">
          <i class="fas fa-search-minus"></i>
          <h3>No Products Found</h3>
          <p>No products match your search for "<strong>{{ searchQuery }}</strong>"</p>
          <div class="no-results-actions">
            <button class="btn-secondary" @click="clearSearch">
              <i class="fas fa-arrow-left"></i>
              Back to All Products
            </button>
            <button class="btn-add-product" @click="showAddModal = true">
              <i class="fas fa-plus"></i>
              Add New Product
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!searchQuery && !loading && products.length === 0" class="empty-state">
          <i class="fas fa-inbox"></i>
          <h3>No Products Yet</h3>
          <p>Start by adding your first product to the inventory</p>
          <button class="btn-add-product" @click="showAddModal = true">
            <i class="fas fa-plus"></i>
            <span>Add Your First Product</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Multistep Add Products Modal -->
    <div v-if="showAddModal && !editingProduct" class="modal-overlay" @click="closeModal">
      <div class="multistep-modal" @click.stop>
        <!-- Progress Indicator -->
        <div class="step-progress">
          <div class="progress-header">
            <h2 class="modal-title">
              <i class="fas fa-plus-circle"></i>
              Add Products
            </h2>
            <button type="button" class="close-btn" @click="closeModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="progress-bar">
            <div 
              v-for="step in steps" 
              :key="step.id"
              class="progress-step"
              :class="{ 
                'active': currentStep === step.id, 
                'completed': currentStep > step.id 
              }"
            >
              <div class="step-circle">
                <i v-if="currentStep > step.id" class="fas fa-check"></i>
                <span v-else>{{ step.id }}</span>
              </div>
              <span class="step-label">{{ step.label }}</span>
            </div>
          </div>
        </div>

        <!-- Step Content -->
        <div class="step-content">
          <!-- Step 1: Choose Method -->
          <div v-if="currentStep === 1" class="step-panel">
            <div class="step-header">
              <h3>How would you like to add products?</h3>
              <p>Choose your preferred method for adding products to inventory</p>
            </div>
            
            <div class="method-options">
              <div 
                class="method-card"
                :class="{ 'selected': selectedMethod === 'single' }"
                @click="selectedMethod = 'single'"
              >
                <div class="method-icon">
                  <i class="fas fa-plus"></i>
                </div>
                <h4>Single Product</h4>
                <p>Add one product at a time with detailed information</p>
                <div class="method-features">
                  <span><i class="fas fa-check"></i> Detailed form</span>
                  <span><i class="fas fa-check"></i> Upload image</span>
                  <span><i class="fas fa-check"></i> Categories</span>
                </div>
              </div>

              <div 
                class="method-card"
                :class="{ 'selected': selectedMethod === 'bulk' }"
                @click="selectedMethod = 'bulk'"
              >
                <div class="method-icon">
                  <i class="fas fa-layer-group"></i>
                </div>
                <h4>Bulk Products</h4>
                <p>Add multiple products quickly with essential details</p>
                <div class="method-features">
                  <span><i class="fas fa-check"></i> Quick entry</span>
                  <span><i class="fas fa-check"></i> Duplicate products</span>
                  <span><i class="fas fa-check"></i> Batch processing</span>
                </div>
              </div>

              <div 
                class="method-card"
                :class="{ 'selected': selectedMethod === 'import' }"
                @click="selectedMethod = 'import'"
              >
                <div class="method-icon">
                  <i class="fas fa-file-csv"></i>
                </div>
                <h4>Import CSV</h4>
                <p>Upload a CSV file with product data</p>
                <div class="method-features">
                  <span><i class="fas fa-check"></i> Bulk import</span>
                  <span><i class="fas fa-check"></i> CSV template</span>
                  <span><i class="fas fa-check"></i> Data validation</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Product Entry -->
          <div v-if="currentStep === 2" class="step-panel">
            <!-- Single Product Form -->
            <div v-if="selectedMethod === 'single'" class="single-product-form">
              <div class="step-header">
                <h3>Product Details</h3>
                <p>Enter detailed information for your product</p>
              </div>

              <form @submit.prevent="addSingleProduct" class="product-form">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-tag"></i>
                      Product Name *
                    </label>
                    <input 
                      type="text" 
                      class="form-input" 
                      v-model="singleProductForm.name" 
                      placeholder="Enter product name"
                      required 
                    />
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-barcode"></i>
                      SKU/Barcode
                    </label>
                    <input 
                      type="text" 
                      class="form-input" 
                      v-model="singleProductForm.sku" 
                      placeholder="Auto-generated if empty"
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-list"></i>
                      Category
                    </label>
                    <select class="form-input" v-model="singleProductForm.category">
                      <option value="">Select Category</option>
                      <option value="fertilizers">Fertilizers</option>
                      <option value="pesticides">Pesticides & Herbicides</option>
                      <option value="seeds">Seeds & Seedlings</option>
                      <option value="animal-feed">Animal Feed</option>
                      <option value="veterinary-medicine">Veterinary Medicine</option>
                      <option value="farm-tools">Farm Tools & Equipment</option>
                      <option value="irrigation">Irrigation Supplies</option>
                      <option value="dairy-supplies">Dairy Supplies</option>
                      <option value="poultry-supplies">Poultry Supplies</option>
                      <option value="livestock-care">Livestock Care</option>
                      <option value="crop-protection">Crop Protection</option>
                      <option value="soil-amendments">Soil Amendments</option>
                      <option value="greenhouse-supplies">Greenhouse Supplies</option>
                      <option value="other">Other</option>
                      <option value="other">Other</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-industry"></i>
                      Brand
                    </label>
                    <input 
                      type="text" 
                      class="form-input" 
                      v-model="singleProductForm.brand" 
                      placeholder="Product brand"
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-money-bill-wave"></i>
                      Cost Price (Ksh) *
                    </label>
                    <input 
                      type="number" 
                      class="form-input" 
                      v-model="singleProductForm.cost_price" 
                      placeholder="0.00" 
                      step="0.01"
                      required 
                    />
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-receipt"></i>
                      Selling Price (Ksh) *
                    </label>
                    <input 
                      type="number" 
                      class="form-input" 
                      v-model="singleProductForm.price" 
                      placeholder="0.00" 
                      step="0.01"
                      required 
                    />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-boxes"></i>
                      Initial Stock *
                    </label>
                    <input 
                      type="number" 
                      class="form-input" 
                      v-model="singleProductForm.stock_quantity" 
                      placeholder="0" 
                      min="0"
                      required 
                    />
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-exclamation-triangle"></i>
                      Low Stock Alert
                    </label>
                    <input 
                      type="number" 
                      class="form-input" 
                      v-model="singleProductForm.low_stock_threshold" 
                      placeholder="5" 
                      min="0"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Description
                  </label>
                  <textarea 
                    class="form-input" 
                    v-model="singleProductForm.description" 
                    placeholder="Product description (optional)"
                    rows="3"
                  ></textarea>
                </div>
              </form>
            </div>

            <!-- Bulk Products Form -->
            <div v-if="selectedMethod === 'bulk'" class="bulk-products-form">
              <div class="step-header">
                <h3>Bulk Product Entry</h3>
                <p>Add multiple products quickly. You can add more products by clicking "Add Another Product"</p>
              </div>

              <div class="bulk-products-container">
                <div 
                  v-for="(product, index) in bulkProducts" 
                  :key="index"
                  class="bulk-product-item"
                >
                  <div class="product-item-header">
                    <h4>Product {{ index + 1 }}</h4>
                    <button 
                      v-if="bulkProducts.length > 1"
                      type="button" 
                      class="remove-product-btn"
                      @click="removeBulkProduct(index)"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>

                  <div class="bulk-form-row">
                    <div class="form-group">
                      <label class="form-label">Product Name *</label>
                      <input 
                        type="text" 
                        class="form-input" 
                        v-model="product.name" 
                        placeholder="Enter product name"
                        required 
                      />
                    </div>

                    <div class="form-group">
                      <label class="form-label">Category</label>
                      <select class="form-input" v-model="product.category">
                        <option value="">Select Category</option>
                        <option value="fertilizers">Fertilizers</option>
                        <option value="pesticides">Pesticides & Herbicides</option>
                        <option value="seeds">Seeds & Seedlings</option>
                        <option value="animal-feed">Animal Feed</option>
                        <option value="veterinary-medicine">Veterinary Medicine</option>
                        <option value="farm-tools">Farm Tools & Equipment</option>
                        <option value="irrigation">Irrigation Supplies</option>
                        <option value="dairy-supplies">Dairy Supplies</option>
                        <option value="poultry-supplies">Poultry Supplies</option>
                        <option value="livestock-care">Livestock Care</option>
                        <option value="crop-protection">Crop Protection</option>
                        <option value="soil-amendments">Soil Amendments</option>
                        <option value="greenhouse-supplies">Greenhouse Supplies</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                  </div>

                  <div class="bulk-form-row">
                    <div class="form-group">
                      <label class="form-label">Cost Price (Ksh) *</label>
                      <input 
                        type="number" 
                        class="form-input" 
                        v-model="product.cost_price" 
                        placeholder="0.00" 
                        step="0.01"
                        required 
                      />
                    </div>

                    <div class="form-group">
                      <label class="form-label">Selling Price (Ksh) *</label>
                      <input 
                        type="number" 
                        class="form-input" 
                        v-model="product.price" 
                        placeholder="0.00" 
                        step="0.01"
                        required 
                      />
                    </div>

                    <div class="form-group">
                      <label class="form-label">Stock *</label>
                      <input 
                        type="number" 
                        class="form-input" 
                        v-model="product.stock_quantity" 
                        placeholder="0" 
                        min="0"
                        required 
                      />
                    </div>
                  </div>
                </div>

                <button 
                  type="button" 
                  class="add-another-btn"
                  @click="addAnotherProduct"
                >
                  <i class="fas fa-plus"></i>
                  Add Another Product
                </button>
              </div>
            </div>

            <!-- CSV Import Form -->
            <div v-if="selectedMethod === 'import'" class="csv-import-form">
              <div class="step-header">
                <h3>Import Products from CSV</h3>
                <p>Upload a CSV file with your product data</p>
              </div>

              <div class="csv-upload-area">
                <div class="upload-zone" :class="{ 'dragover': isDragOver }" 
                     @dragover.prevent="isDragOver = true"
                     @dragleave.prevent="isDragOver = false"
                     @drop.prevent="handleFileDrop">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <h4>Drag & Drop CSV File</h4>
                  <p>or click to browse</p>
                  <input 
                    type="file" 
                    ref="csvFileInput"
                    accept=".csv"
                    @change="handleFileSelect"
                    class="file-input"
                  />
                  <button type="button" class="browse-btn" @click="$refs.csvFileInput.click()">
                    Browse Files
                  </button>
                </div>

                <div v-if="csvFile" class="file-preview">
                  <div class="file-info">
                    <i class="fas fa-file-csv"></i>
                    <span>{{ csvFile.name }}</span>
                    <button type="button" @click="csvFile = null" class="remove-file-btn">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>

                <div class="csv-template">
                  <h5>CSV Template Format</h5>
                  <div class="template-example">
                    <code>name,category,cost_price,price,stock_quantity,description</code>
                    <code>DAP Fertilizer 50kg,fertilizers,2800.00,3500.00,50,Di-ammonium phosphate fertilizer for crop growth</code>
                    <code>Roundup Herbicide 1L,pesticides,1200.00,1800.00,30,Glyphosate-based weed killer</code>
                    <code>Maize Hybrid Seeds,seeds,800.00,1200.00,100,High-yield drought resistant maize variety</code>
                  </div>
                  <button type="button" class="download-template-btn">
                    <i class="fas fa-download"></i>
                    Download Template
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Review & Confirm -->
          <div v-if="currentStep === 3" class="step-panel">
            <div class="step-header">
              <h3>Review & Confirm</h3>
              <p>Review your products before adding them to inventory</p>
            </div>

            <div class="review-container">
              <div class="review-summary">
                <div class="summary-stats">
                  <div class="stat-item">
                    <span class="stat-number">{{ getProductsToReview().length }}</span>
                    <span class="stat-label">Products</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-number">{{ getTotalValue() }}</span>
                    <span class="stat-label">Total Value</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-number">{{ getTotalStock() }}</span>
                    <span class="stat-label">Total Stock</span>
                  </div>
                </div>
              </div>

              <div class="products-review">
                <div 
                  v-for="(product, index) in getProductsToReview()" 
                  :key="index"
                  class="review-product-card"
                >
                  <div class="product-info">
                    <h4>{{ product.name }}</h4>
                    <div class="product-details">
                      <span class="category">{{ product.category || 'No Category' }}</span>
                      <span class="price">Ksh {{ formatPrice(product.price) }}</span>
                      <span class="stock">Stock: {{ product.stock_quantity }}</span>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    class="edit-product-btn"
                    @click="editProductInReview(index)"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Footer -->
        <div class="modal-footer">
          <div class="footer-left">
            <button 
              v-if="currentStep > 1" 
              type="button" 
              class="btn-secondary"
              @click="previousStep"
            >
              <i class="fas fa-arrow-left"></i>
              Previous
            </button>
          </div>

          <div class="footer-right">
            <button type="button" class="btn-secondary" @click="closeModal">
              <i class="fas fa-times"></i>
              Cancel
            </button>

            <button 
              v-if="currentStep < 3" 
              type="button" 
              class="btn-primary"
              @click="nextStep"
              :disabled="!canProceedToNextStep()"
            >
              Next
              <i class="fas fa-arrow-right"></i>
            </button>

            <button 
              v-if="currentStep === 3" 
              type="button" 
              class="btn-primary"
              @click="saveAllProducts"
              :disabled="saving"
            >
              <div v-if="saving" class="btn-loading">
                <div class="btn-spinner"></div>
                Saving...
              </div>
              <div v-else>
                <i class="fas fa-check"></i>
                Add {{ getProductsToReview().length }} Product{{ getProductsToReview().length !== 1 ? 's' : '' }}
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Single Product Edit Modal -->
    <div v-if="editingProduct" class="modal-overlay" @click="closeModal">
      <div class="modern-modal" @click.stop>
        <form @submit.prevent="saveProduct">
          <!-- Modal Header -->
          <div class="modal-header">
            <div class="modal-title-section">
              <div class="modal-icon">
                <i class="fas fa-edit"></i>
              </div>
              <div>
                <h2 class="modal-title">Edit Product</h2>
                <p class="modal-subtitle">Update product information</p>
              </div>
            </div>
            <button type="button" class="close-btn" @click="closeModal">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-tag"></i>
                Product Name
              </label>
              <input 
                type="text" 
                class="form-input" 
                v-model="form.name" 
                placeholder="Enter product name" 
                required 
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-money-bill-wave"></i>
                  Price (Ksh)
                </label>
                <input 
                  type="number" 
                  class="form-input" 
                  v-model="form.price" 
                  placeholder="0.00" 
                  step="0.01"
                  required 
                />
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fas fa-boxes"></i>
                  Stock Quantity
                </label>
                <input 
                  type="number" 
                  class="form-input" 
                  v-model="form.stock_quantity" 
                  placeholder="0" 
                  min="0"
                  required 
                />
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeModal">
              <i class="fas fa-times"></i>
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <div v-if="saving" class="btn-loading">
                <div class="btn-spinner"></div>
                Saving...
              </div>
              <div v-else>
                <i class="fas fa-save"></i>
                Update Product
              </div>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingProduct" class="modal-overlay" @click="deletingProduct = null">
      <div class="modern-modal delete-modal" @click.stop>
        <div class="modal-header delete-header">
          <div class="modal-title-section">
            <div class="modal-icon delete-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
              <h2 class="modal-title">Confirm Deletion</h2>
              <p class="modal-subtitle">This action cannot be undone</p>
            </div>
          </div>
        </div>

        <div class="modal-body">
          <p class="delete-message">
            Are you sure you want to delete <strong>{{ deletingProduct.name }}</strong>?
          </p>
        </div>

        <div class="modal-footer">
          <button class="btn-secondary" @click="deletingProduct = null">
            <i class="fas fa-times"></i>
            Cancel
          </button>
          <button class="btn-danger" @click="deleteProduct(deletingProduct.id)">
            <i class="fas fa-trash"></i>
            Yes, Delete
          </button>
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
      products: [],
      loading: false,
      saving: false,
      isSearching: false,
      showAddModal: false,
      editingProduct: null,
      deletingProduct: null,
      searchQuery: '',
      searchTimeout: null,
      alert: {
        show: false,
        message: '',
        type: 'success' // success, error, warning, info
      },
      form: {
        name: '',
        price: '',
        stock_quantity: ''
      },
      // Multistep form data
      currentStep: 1,
      selectedMethod: 'single',
      steps: [
        { id: 1, label: 'Method' },
        { id: 2, label: 'Products' },
        { id: 3, label: 'Review' }
      ],
      // Single product form
      singleProductForm: {
        name: '',
        sku: '',
        category: '',
        brand: '',
        description: '',
        cost_price: '',
        price: '',
        stock_quantity: '',
        low_stock_threshold: 5
      },
      // Bulk products array
      bulkProducts: [
        {
          name: '',
          category: '',
          cost_price: '',
          price: '',
          stock_quantity: ''
        }
      ],
      // CSV import
      csvFile: null,
      isDragOver: false,
      csvData: []
    }
  },
  computed: {
    filteredProducts() {
      if (!this.searchQuery.trim()) {
        return this.products
      }
      
      const query = this.searchQuery.toLowerCase().trim()
      return this.products.filter(product => 
        product.name.toLowerCase().includes(query) ||
        product.price.toString().includes(query) ||
        product.stock_quantity.toString().includes(query)
      )
    }
  },
  mounted() {
    this.fetchProducts()
  },
  methods: {
    async fetchProducts() {
      this.loading = true
      try {
        const res = await axios.get('http://localhost:8000/products')
        this.products = res.data
        if (this.products.length === 0) {
          this.showAlert('No products found. Add your first product to get started!', 'info')
        }
      } catch (err) {
        this.showAlert('Error fetching products. Please try again.', 'error')
        console.error('Fetch products error:', err)
      } finally {
        this.loading = false
      }
    },
    
    // Search functionality
    handleSearch() {
      this.isSearching = true
      
      // Clear previous timeout
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout)
      }
      
      // Debounce search to avoid too many operations
      this.searchTimeout = setTimeout(() => {
        this.isSearching = false
        
        if (this.searchQuery.trim() && this.filteredProducts.length === 0) {
          this.showAlert(`No results found for "${this.searchQuery}"`, 'warning')
        }
      }, 300)
    },
    
    clearSearch() {
      this.searchQuery = ''
      this.isSearching = false
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout)
      }
      this.hideAlert()
    },
    
    highlightSearchTerm(text) {
      if (!this.searchQuery.trim()) return text
      
      const regex = new RegExp(`(${this.searchQuery})`, 'gi')
      return text.replace(regex, '<mark class="search-highlight">$1</mark>')
    },
    
    // Alert system
    showAlert(message, type = 'success') {
      this.alert = {
        show: true,
        message,
        type
      }
      
      // Auto-hide after 5 seconds for success/info alerts
      if (type === 'success' || type === 'info') {
        setTimeout(() => {
          this.hideAlert()
        }, 5000)
      }
    },
    
    hideAlert() {
      this.alert.show = false
    },
    
    getAlertIcon() {
      switch (this.alert.type) {
        case 'success': return 'fas fa-check-circle'
        case 'error': return 'fas fa-exclamation-circle'
        case 'warning': return 'fas fa-exclamation-triangle'
        case 'info': return 'fas fa-info-circle'
        default: return 'fas fa-info-circle'
      }
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingProduct = null
      this.form = { name: '', price: '', stock_quantity: '' }
    },
    
    editProduct(product) {
      this.editingProduct = product
      this.form = { ...product }
    },
    
    async saveProduct() {
      this.saving = true
      try {
        if (this.editingProduct) {
          await axios.put(`http://localhost:8000/products/${this.editingProduct.id}`, this.form)
          this.showAlert('Product updated successfully!', 'success')
        } else {
          await axios.post(`http://localhost:8000/products`, this.form)
          this.showAlert('Product added successfully!', 'success')
        }
        await this.fetchProducts()
        this.closeModal()
      } catch (err) {
        this.showAlert('Failed to save product. Please try again.', 'error')
        console.error('Save product error:', err)
      } finally {
        this.saving = false
      }
    },
    
    confirmDelete(product) {
      this.deletingProduct = product
    },
    
    async deleteProduct(id) {
      try {
        await axios.delete(`http://localhost:8000/products/${id}`)
        this.showAlert('Product deleted successfully!', 'success')
        await this.fetchProducts()
        this.deletingProduct = null
      } catch (err) {
        this.showAlert('Failed to delete product. Please try again.', 'error')
        console.error('Delete product error:', err)
      }
    },
    
    formatPrice(price) {
      return new Intl.NumberFormat().format(price)
    },
    
    getStockStatus(quantity) {
      if (quantity === 0) return 'Out of Stock'
      if (quantity < 10) return 'Low Stock'
      return 'In Stock'
    },
    
    getStockClass(quantity) {
      if (quantity === 0) return 'out-of-stock'
      if (quantity < 10) return 'low-stock'
      return 'in-stock'
    },

    // Multistep form methods
    nextStep() {
      if (this.canProceedToNextStep()) {
        this.currentStep++
      }
    },

    previousStep() {
      if (this.currentStep > 1) {
        this.currentStep--
      }
    },

    canProceedToNextStep() {
      switch (this.currentStep) {
        case 1:
          return this.selectedMethod !== ''
        case 2:
          if (this.selectedMethod === 'single') {
            return this.singleProductForm.name && this.singleProductForm.price && this.singleProductForm.stock_quantity
          } else if (this.selectedMethod === 'bulk') {
            return this.bulkProducts.every(product => 
              product.name && product.price && product.stock_quantity
            )
          } else if (this.selectedMethod === 'import') {
            return this.csvFile !== null
          }
          return false
        default:
          return true
      }
    },

    // Bulk products methods
    addAnotherProduct() {
      this.bulkProducts.push({
        name: '',
        category: '',
        cost_price: '',
        price: '',
        stock_quantity: ''
      })
    },

    removeBulkProduct(index) {
      if (this.bulkProducts.length > 1) {
        this.bulkProducts.splice(index, 1)
      }
    },

    // CSV import methods
    handleFileDrop(event) {
      this.isDragOver = false
      const files = event.dataTransfer.files
      if (files.length > 0) {
        this.handleFile(files[0])
      }
    },

    handleFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        this.handleFile(file)
      }
    },

    handleFile(file) {
      if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
        this.csvFile = file
        this.parseCSV(file)
      } else {
        this.showAlert('Please select a valid CSV file', 'error')
      }
    },

    parseCSV(file) {
      const reader = new FileReader()
      reader.onload = (e) => {
        const csv = e.target.result
        const lines = csv.split('\n')
        const headers = lines[0].split(',').map(h => h.trim())
        
        this.csvData = []
        for (let i = 1; i < lines.length; i++) {
          if (lines[i].trim()) {
            const values = lines[i].split(',').map(v => v.trim())
            const product = {}
            headers.forEach((header, index) => {
              product[header] = values[index] || ''
            })
            this.csvData.push(product)
          }
        }
        
        if (this.csvData.length > 0) {
          this.showAlert(`Successfully parsed ${this.csvData.length} products from CSV`, 'success')
        }
      }
      reader.readAsText(file)
    },

    // Review methods
    getProductsToReview() {
      if (this.selectedMethod === 'single') {
        return [this.singleProductForm]
      } else if (this.selectedMethod === 'bulk') {
        return this.bulkProducts.filter(p => p.name && p.price && p.stock_quantity)
      } else if (this.selectedMethod === 'import') {
        return this.csvData
      }
      return []
    },

    getTotalValue() {
      const products = this.getProductsToReview()
      const total = products.reduce((sum, product) => {
        return sum + (parseFloat(product.price || 0) * parseInt(product.stock_quantity || 0))
      }, 0)
      return `Ksh ${this.formatPrice(total)}`
    },

    getTotalStock() {
      const products = this.getProductsToReview()
      return products.reduce((sum, product) => sum + parseInt(product.stock_quantity || 0), 0)
    },

    editProductInReview(index) {
      this.currentStep = 2
      // Additional logic can be added here to focus on specific product
    },

    // Save all products
    async saveAllProducts() {
      this.saving = true
      const products = this.getProductsToReview()
      
      try {
        const promises = products.map(product => {
          // Clean up the product data
          const cleanProduct = {
            name: product.name,
            price: parseFloat(product.price),
            stock_quantity: parseInt(product.stock_quantity),
            category: product.category || null,
            brand: product.brand || null,
            description: product.description || null,
            cost_price: parseFloat(product.cost_price) || null,
            sku: product.sku || null,
            low_stock_threshold: parseInt(product.low_stock_threshold) || 5
          }
          
          return axios.post('http://localhost:8000/products', cleanProduct)
        })

        await Promise.all(promises)
        
        this.showAlert(`Successfully added ${products.length} product${products.length !== 1 ? 's' : ''}!`, 'success')
        await this.fetchProducts()
        this.closeModal()
      } catch (err) {
        this.showAlert('Failed to save some products. Please try again.', 'error')
        console.error('Save products error:', err)
      } finally {
        this.saving = false
      }
    },

    // Reset multistep form
    resetMultistepForm() {
      this.currentStep = 1
      this.selectedMethod = 'single'
      this.singleProductForm = {
        name: '',
        sku: '',
        category: '',
        brand: '',
        description: '',
        cost_price: '',
        price: '',
        stock_quantity: '',
        low_stock_threshold: 5
      }
      this.bulkProducts = [{
        name: '',
        category: '',
        cost_price: '',
        price: '',
        stock_quantity: ''
      }]
      this.csvFile = null
      this.csvData = []
    },

    // Override closeModal to reset multistep form
    closeModal() {
      this.showAddModal = false
      this.editingProduct = null
      this.form = { name: '', price: '', stock_quantity: '' }
      this.resetMultistepForm()
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.pos-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Header Section */
.page-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: #667eea;
}

.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

/* Search Container */
.search-container {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 300px;
  padding: 0.875rem 1rem 0.875rem 2.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.search-icon {
  position: absolute;
  left: 1rem;
  color: #a0aec0;
  z-index: 1;
  pointer-events: none;
}

.clear-search-btn {
  position: absolute;
  right: 0.5rem;
  width: 28px;
  height: 28px;
  border: none;
  background: rgba(160, 174, 192, 0.1);
  border-radius: 50%;
  cursor: pointer;
  color: #a0aec0;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.clear-search-btn:hover {
  background: rgba(160, 174, 192, 0.2);
  color: #4a5568;
}

.search-spinner {
  display: flex;
  align-items: center;
}

.mini-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(102, 126, 234, 0.2);
  border-left: 2px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Alert System */
.alert-container {
  position: fixed;
  top: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2000;
  max-width: 500px;
  width: 90%;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  animation: alertSlideIn 0.3s ease;
}

@keyframes alertSlideIn {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.alert-container.success {
  background: linear-gradient(135deg, rgba(72, 187, 120, 0.95), rgba(56, 161, 105, 0.95));
  color: white;
  border: 1px solid rgba(72, 187, 120, 0.3);
}

.alert-container.error {
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.95), rgba(197, 48, 48, 0.95));
  color: white;
  border: 1px solid rgba(229, 62, 62, 0.3);
}

.alert-container.warning {
  background: linear-gradient(135deg, rgba(237, 137, 54, 0.95), rgba(221, 107, 32, 0.95));
  color: white;
  border: 1px solid rgba(237, 137, 54, 0.3);
}

.alert-container.info {
  background: linear-gradient(135deg, rgba(66, 153, 225, 0.95), rgba(49, 130, 206, 0.95));
  color: white;
  border: 1px solid rgba(66, 153, 225, 0.3);
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.alert-content i {
  font-size: 1.1rem;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.alert-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Search Results */
.search-results-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding: 1rem 1.5rem;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 12px;
  border: 1px solid rgba(102, 126, 234, 0.2);
}

.results-summary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a5568;
  font-weight: 500;
}

.results-summary i {
  color: #667eea;
}

.clear-all-btn {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
}

.clear-all-btn:hover {
  background: rgba(102, 126, 234, 0.2);
  transform: translateY(-1px);
}

/* Search Highlighting */
.search-highlight {
  background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
  color: white;
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
  font-weight: 600;
}

/* No Results State */
.no-results-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 2rem;
  background: rgba(102, 126, 234, 0.05);
  border-radius: 16px;
  border: 2px dashed rgba(102, 126, 234, 0.2);
}

.no-results-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  color: #a0aec0;
}

.no-results-state h3 {
  font-size: 1.5rem;
  margin: 0 0 1rem 0;
  color: #4a5568;
}

.no-results-state p {
  margin: 0 0 2rem 0;
  color: #718096;
}

.no-results-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.stats-mini {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 12px;
  padding: 1rem 1.5rem;
  color: white;
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
}

.stat-label {
  font-size: 0.85rem;
  opacity: 0.9;
}

.btn-add-product {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border: none;
  border-radius: 12px;
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
}

.btn-add-product:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(72, 187, 120, 0.4);
}

/* Loading State */
.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.loading-spinner {
  text-align: center;
  color: white;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-left: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Products Grid */
.products-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.product-card {
  position: relative;
  transition: all 0.3s ease;
}

.product-card-inner {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  height: 100%;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.product-card:hover .product-card-inner {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product-image {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.product-info {
  margin-bottom: 1.5rem;
}

.product-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1rem 0;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.price-tag {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.currency {
  font-size: 0.9rem;
  color: #718096;
  font-weight: 500;
}

.amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.stock-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  width: fit-content;
}

.stock-info.in-stock {
  background: rgba(72, 187, 120, 0.1);
  color: #38a169;
}

.stock-info.low-stock {
  background: rgba(237, 137, 54, 0.1);
  color: #dd6b20;
}

.stock-info.out-of-stock {
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
}

.product-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}

.edit-btn {
  background: rgba(49, 130, 206, 0.1);
  color: #3182ce;
}

.edit-btn:hover {
  background: #3182ce;
  color: white;
  transform: translateY(-2px);
}

.delete-btn {
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
}

.delete-btn:hover {
  background: #e53e3e;
  color: white;
  transform: translateY(-2px);
}

.stock-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stock-badge.in-stock {
  background: #48bb78;
  color: white;
}

.stock-badge.low-stock {
  background: #ed8936;
  color: white;
}

.stock-badge.out-of-stock {
  background: #e53e3e;
  color: white;
}

/* Empty State */
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin: 0 0 1rem 0;
  color: #4a5568;
}

.empty-state p {
  margin: 0 0 2rem 0;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modern-modal {
  background: white;
  border-radius: 20px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  padding: 2rem 2rem 1rem;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.modal-title-section {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.modal-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.delete-header .modal-icon.delete-icon {
  background: linear-gradient(135deg, #e53e3e, #c53030);
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.modal-subtitle {
  color: #718096;
  margin: 0.25rem 0 0 0;
  font-size: 0.9rem;
}

.close-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
  cursor: pointer;
  color: #718096;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: rgba(0, 0, 0, 0.1);
  color: #2d3748;
}

.modal-body {
  padding: 0 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 0.5rem;
}

.form-label i {
  color: #667eea;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-footer {
  padding: 1rem 2rem 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-secondary, .btn-primary, .btn-danger {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-danger {
  background: linear-gradient(135deg, #e53e3e, #c53030);
  color: white;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-left: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.delete-message {
  font-size: 1.1rem;
  color: #4a5568;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
  .pos-container {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
    gap: 1.5rem;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 1rem;
  }
  
  .search-input {
    width: 100%;
  }
  
  .search-results-info {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .no-results-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .products-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .alert-container {
    width: 95%;
    padding: 0.875rem 1rem;
  }
}

@media (max-width: 480px) {
  .search-input {
    padding: 0.75rem 1rem 0.75rem 2.25rem;
  }
  
  .page-title {
    font-size: 1.75rem;
  }
  
  .header-actions {
    gap: 0.75rem;
  }
}

/* Multistep Modal Styles */
.multistep-modal {
  background: white;
  border-radius: 20px;
  max-width: 900px;
  width: 95%;
  max-height: 95vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease;
  display: flex;
  flex-direction: column;
}

/* Progress Indicator */
.step-progress {
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.progress-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
}

.progress-bar::before {
  content: '';
  position: absolute;
  top: 20px;
  left: 20px;
  right: 20px;
  height: 2px;
  background: #e2e8f0;
  z-index: 1;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  z-index: 2;
  position: relative;
}

.step-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #a0aec0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  transition: all 0.3s ease;
}

.progress-step.active .step-circle {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  transform: scale(1.1);
}

.progress-step.completed .step-circle {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
}

.step-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #4a5568;
  transition: color 0.3s ease;
}

.progress-step.active .step-label {
  color: #667eea;
  font-weight: 600;
}

.progress-step.completed .step-label {
  color: #48bb78;
}

/* Step Content */
.step-content {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.step-panel {
  max-width: 100%;
}

.step-header {
  text-align: center;
  margin-bottom: 2rem;
}

.step-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.step-header p {
  color: #718096;
  margin: 0;
}

/* Method Selection */
.method-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.method-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.method-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.method-card:hover::before,
.method-card.selected::before {
  opacity: 1;
}

.method-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.method-card.selected {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.method-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  position: relative;
  z-index: 1;
}

.method-card h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
  position: relative;
  z-index: 1;
}

.method-card p {
  color: #718096;
  margin: 0 0 1rem 0;
  position: relative;
  z-index: 1;
}

.method-features {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  position: relative;
  z-index: 1;
}

.method-features span {
  font-size: 0.875rem;
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.method-features i {
  color: #48bb78;
  font-size: 0.75rem;
}

/* Single Product Form */
.product-form {
  max-width: 600px;
  margin: 0 auto;
}

/* Bulk Products Form */
.bulk-products-container {
  max-width: 800px;
  margin: 0 auto;
}

.bulk-product-item {
  background: #f7fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border: 1px solid #e2e8f0;
}

.product-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.product-item-header h4 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

.remove-product-btn {
  background: #fed7d7;
  color: #e53e3e;
  border: none;
  border-radius: 8px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.remove-product-btn:hover {
  background: #feb2b2;
  transform: scale(1.05);
}

.bulk-form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.add-another-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 12px;
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin: 0 auto;
}

.add-another-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* CSV Import Form */
.csv-upload-area {
  max-width: 600px;
  margin: 0 auto;
}

.upload-zone {
  border: 2px dashed #cbd5e0;
  border-radius: 16px;
  padding: 3rem 2rem;
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
}

.upload-zone.dragover {
  border-color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.upload-zone i {
  font-size: 3rem;
  color: #a0aec0;
  margin-bottom: 1rem;
}

.upload-zone h4 {
  font-size: 1.25rem;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.upload-zone p {
  color: #718096;
  margin: 0 0 1.5rem 0;
}

.file-input {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.browse-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.browse-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.file-preview {
  margin: 1rem 0;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.file-info i {
  color: #48bb78;
  font-size: 1.25rem;
}

.remove-file-btn {
  background: none;
  border: none;
  color: #e53e3e;
  cursor: pointer;
  margin-left: auto;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background 0.3s ease;
}

.remove-file-btn:hover {
  background: #fed7d7;
}

.csv-template {
  margin-top: 2rem;
  padding: 1.5rem;
  background: #f7fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.csv-template h5 {
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1rem 0;
}

.template-example {
  background: #2d3748;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.template-example code {
  display: block;
  color: #e2e8f0;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.download-template-btn {
  background: #4a5568;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.download-template-btn:hover {
  background: #2d3748;
}

/* Review Section */
.review-container {
  max-width: 800px;
  margin: 0 auto;
}

.review-summary {
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
  font-weight: 500;
}

.products-review {
  display: grid;
  gap: 1rem;
}

.review-product-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
}

.review-product-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.review-product-card h4 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.product-details {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.product-details span {
  font-size: 0.875rem;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
}

.category {
  background: #e6fffa;
  color: #00a3a0;
}

.price {
  background: #f0fff4;
  color: #2f855a;
}

.stock {
  background: #fef5e7;
  color: #d69e2e;
}

.edit-product-btn {
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.edit-product-btn:hover {
  background: #764ba2;
  transform: scale(1.05);
}

/* Footer Navigation */
.modal-footer {
  background: #f7fafc;
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #e2e8f0;
}

.footer-left,
.footer-right {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Responsive Multistep Modal */
@media (max-width: 768px) {
  .multistep-modal {
    max-width: 95%;
    max-height: 95vh;
  }
  
  .step-progress {
    padding: 1.5rem 1rem;
  }
  
  .progress-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .step-content {
    padding: 1.5rem 1rem;
  }
  
  .method-options {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .method-card {
    padding: 1.5rem;
  }
  
  .bulk-form-row {
    grid-template-columns: 1fr;
  }
  
  .summary-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 1rem;
  }
  
  .footer-left,
  .footer-right {
    width: 100%;
    justify-content: center;
  }
}
</style>