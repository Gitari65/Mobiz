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
              <span class="stat-number">{{ totalFilteredProducts }}</span>
              <span class="stat-label">{{ searchQuery || (filters.category || filters.sortBy !== 'newest' || filters.priceRange.min !== 0 || filters.priceRange.max !== null) ? 'Found' : 'Total' }}</span>
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
            {{ totalFilteredProducts }} result{{ totalFilteredProducts !== 1 ? 's' : '' }} 
            for "<strong>{{ searchQuery }}</strong>"
          </span>
        </div>
        <button class="clear-all-btn" @click="clearSearch">
          <i class="fas fa-times"></i>
          Clear Search
        </button>
      </div>

      <!-- View Controls & Filters -->
      <div class="view-and-filters-section">
        <!-- View Mode Toggle -->
        <div class="view-mode-toggle">
          <button 
            class="view-btn" 
            :class="{ active: viewMode === 'grid' }"
            @click="viewMode = 'grid'"
            title="Grid View"
          >
            <i class="fas fa-th"></i>
            <span>Grid</span>
          </button>
          <button 
            class="view-btn" 
            :class="{ active: viewMode === 'list' }"
            @click="viewMode = 'list'"
            title="List View"
          >
            <i class="fas fa-list"></i>
            <span>List</span>
          </button>
        </div>

        <!-- Filters -->
        <div class="filters-container">
          <!-- Category Filter -->
          <div class="filter-group">
            <label class="filter-label">Category</label>
            <select v-model="filters.category" class="filter-select">
              <option value="">All Categories</option>
              <option v-for="category in uniqueCategories" :key="category" :value="category">
                {{ category }}
              </option>
            </select>
          </div>

          <!-- Sort By -->
          <div class="filter-group">
            <label class="filter-label">Sort By</label>
            <select v-model="filters.sortBy" class="filter-select">
              <option value="newest">Newest</option>
              <option value="oldest">Oldest</option>
              <option value="a-z">A - Z</option>
              <option value="z-a">Z - A</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
            </select>
          </div>

          <!-- Price Range Filter -->
          <div class="filter-group price-range-group">
            <label class="filter-label">Price Range</label>
            <div class="price-inputs">
              <div class="price-input">
                <input 
                  type="number" 
                  v-model.number="filters.priceRange.min" 
                  placeholder="Min (Ksh)"
                  min="0"
                  class="price-field"
                />
              </div>
              <span class="price-separator">-</span>
              <div class="price-input">
                <input 
                  type="number" 
                  v-model.number="filters.priceRange.max" 
                  :placeholder="`Max (Ksh) - ${maxProductPrice.toFixed(0)}`"
                  min="0"
                  class="price-field"
                />
              </div>
            </div>
          </div>

          <!-- Reset Filters Button -->
          <button class="filter-reset-btn" @click="resetFilters" title="Reset all filters">
            <i class="fas fa-redo"></i>
            Reset
          </button>
        </div>

        <!-- Results Info -->
        <div class="results-info-bar">
          <span class="results-count">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, totalFilteredProducts) }} of {{ totalFilteredProducts }} products
          </span>
        </div>
      </div>

      <div :class="['products-container', viewMode]">
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="products-grid">
          <div v-for="product in filteredProducts" :key="product.id" class="product-card">
            <div class="product-card-inner">
              <!-- Product Image Placeholder -->
              <div class="product-image">
                <i class="fas fa-box-open"></i>
              </div>
              
              <!-- Product Info -->
              <div class="product-info">
                <h3 class="product-name" v-html="highlightSearchTerm(product.name)"></h3>
                <div v-if="product.category" class="product-category">
                  <i class="fas fa-tag"></i>
                  <span>{{ product.category }}</span>
                </div>
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
                <button class="action-btn empties-btn" @click="showEmptiesModal(product)" title="Manage Empties">
                  <i class="fas fa-recycle"></i>
                </button>
                <button class="action-btn transfer-btn" @click="showTransferModal(product)" title="Transfer Stock">
                  <i class="fas fa-exchange-alt"></i>
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

          <!-- No Results States (Grid) -->
          <div v-if="filteredProducts.length === 0 && products.length === 0 && !searchQuery" class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Products Yet</h3>
            <p>Start by adding your first product to the inventory</p>
            <button class="btn-add-product" @click="showAddModal = true">
              <i class="fas fa-plus"></i>
              <span>Add Your First Product</span>
            </button>
          </div>

          <div v-else-if="filteredProducts.length === 0" class="no-results-state">
            <i class="fas fa-search-minus"></i>
            <h3>No Products Found</h3>
            <p v-if="searchQuery">No products match your search for "<strong>{{ searchQuery }}</strong>"</p>
            <p v-else>Try adjusting your filters</p>
            <div class="no-results-actions">
              <button class="btn-secondary" @click="clearSearch">
                <i class="fas fa-arrow-left"></i>
                Back to All Products
              </button>
              <button class="btn-primary" @click="resetFilters" v-if="filters.category || filters.sortBy !== 'newest'">
                <i class="fas fa-redo"></i>
                Reset Filters
              </button>
            </div>
          </div>
        </div>

        <!-- List View -->
        <div v-else class="products-list">
          <div class="list-header">
            <div class="list-col-name">Product Name</div>
            <div class="list-col-category">Category</div>
            <div class="list-col-price">Price (Ksh)</div>
            <div class="list-col-stock">Stock</div>
            <div class="list-col-status">Status</div>
            <div class="list-col-actions">Actions</div>
          </div>

          <div v-for="product in filteredProducts" :key="product.id" class="list-row">
            <div class="list-col-name">
              <div class="product-name-cell">
                <i class="fas fa-cube"></i>
                <span v-html="highlightSearchTerm(product.name)"></span>
              </div>
            </div>
            <div class="list-col-category">
              <span v-if="product.category" class="category-badge">{{ product.category }}</span>
              <span v-else class="text-muted">-</span>
            </div>
            <div class="list-col-price">
              <span class="price-value">{{ formatPrice(product.price) }}</span>
            </div>
            <div class="list-col-stock">
              <span :class="['stock-value', getStockClass(product.stock_quantity)]">
                {{ product.stock_quantity }}
              </span>
            </div>
            <div class="list-col-status">
              <span :class="['status-badge', getStockClass(product.stock_quantity)]">
                {{ getStockStatus(product.stock_quantity) }}
              </span>
            </div>
            <div class="list-col-actions">
              <button class="action-btn-small edit-btn" @click="editProduct(product)" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn-small empties-btn" @click="showEmptiesModal(product)" title="Empties">
                <i class="fas fa-recycle"></i>
              </button>
              <button class="action-btn-small transfer-btn" @click="showTransferModal(product)" title="Transfer">
                <i class="fas fa-exchange-alt"></i>
              </button>
              <button class="action-btn-small delete-btn" @click="confirmDelete(product)" title="Delete">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>

          <!-- No Results (List) -->
          <div v-if="filteredProducts.length === 0" class="no-results-state-list">
            <i class="fas fa-search-minus"></i>
            <h3>No Products Found</h3>
            <p v-if="searchQuery">No products match your search for "<strong>{{ searchQuery }}</strong>"</p>
            <p v-else>Try adjusting your filters</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalFilteredProducts > 0" class="pagination-container">
          <div class="pagination-info">
            <span class="total-products">Total: {{ totalFilteredProducts }} products</span>
            <div class="items-per-page">
              <label>Items per page:</label>
              <select v-model.number="itemsPerPage" class="items-select">
                <option value="6">6</option>
                <option value="12">12</option>
                <option value="24">24</option>
                <option value="48">48</option>
              </select>
            </div>
          </div>

          <div class="pagination-controls">
            <button 
              class="pagination-btn"
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
            >
              <i class="fas fa-chevron-left"></i>
              Previous
            </button>

            <div class="page-numbers">
              <button 
                v-for="page in getPaginationRange()"
                :key="page"
                :class="['page-number', { active: currentPage === page }]"
                @click="currentPage = typeof page === 'number' ? page : null"
                :disabled="typeof page !== 'number'"
                v-show="page !== '...'"
              >
                {{ page }}
              </button>
            </div>

            <button 
              class="pagination-btn"
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage === totalPages"
            >
              Next
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>

          <div class="pagination-status">
            <span>Page {{ currentPage }} of {{ totalPages }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Multistep Add Products Modal -->
    <div v-if="showAddModal && !editingProduct" class="modal-overlay">
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
                    <div class="input-with-action">
                      <select class="form-input" v-model="singleProductForm.category">
                        <option value="">Select Category</option>
                        <option v-for="category in categories.filter(c => !c.parent_id)" :key="category.id" :value="category.id">
                          {{ category.name }}
                        </option>
                      </select>
                      <button 
                        type="button" 
                        class="quick-add-btn" 
                        @click="showQuickAddCategoryModal = true"
                        title="Create new category"
                      >
                        <i class="fas fa-plus"></i>
                      </button>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-sitemap"></i>
                      Subcategory
                    </label>
                    <div class="input-with-action">
                      <select class="form-input" v-model="singleProductForm.subcategory" :disabled="!singleProductForm.category">
                        <option value="">Select Subcategory</option>
                        <option v-for="subcat in (singleFormSubcategories || [])" :key="subcat.id" :value="subcat.id">
                          {{ subcat.name }}
                        </option>
                      </select>
                      <button 
                        type="button" 
                        class="quick-add-btn" 
                        @click="selectedCategoryForSubcategory = singleProductForm.category; showQuickAddSubcategoryModal = true"
                        :disabled="!singleProductForm.category"
                        title="Create new subcategory"
                      >
                        <i class="fas fa-plus"></i>
                      </button>
                    </div>
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
                      <i class="fas fa-warehouse"></i>
                      Warehouse *
                    </label>
                    <select class="form-input" v-model="singleProductForm.warehouse_id" required>
                      <option value="">Select Warehouse</option>
                      <option 
                        v-for="warehouse in warehouses" 
                        :key="warehouse.id" 
                        :value="warehouse.id"
                      >
                        {{ warehouse.name }}
                        <template v-if="!warehouse.company_id"> (System Default)</template>
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-scale-balanced"></i>
                      Unit of Measurement
                    </label>
                    <select class="form-input" v-model="singleProductForm.uom_id">
                      <option value="">Select UOM</option>
                      <option 
                        v-for="uom in uoms" 
                        :key="uom.id" 
                        :value="uom.id"
                      >
                        {{ uom.name }} ({{ uom.abbreviation }})
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-download"></i>
                      Purchase UOM (Bulk Buying)
                    </label>
                    <select class="form-input" v-model="singleProductForm.purchase_uom_id">
                      <option value="">Select Purchase UOM</option>
                      <option 
                        v-for="uom in uoms" 
                        :key="uom.id" 
                        :value="uom.id"
                      >
                        {{ uom.name }} ({{ uom.abbreviation }})
                      </option>
                    </select>
                    <small class="form-hint">e.g., 50L for bulk purchase</small>
                  </div>

                  <div class="form-group">
                    <label class="form-label">
                      <i class="fas fa-arrow-circle-up"></i>
                      Sale UOMs (Retail Units - Multiple Selection)
                    </label>
                    <UOMSelector 
                      v-model="singleProductForm.sale_uom_ids"
                      :uoms="getFilteredSaleUoms(singleProductForm.purchase_uom_id)"
                    />
                    <small class="form-hint">Select multiple UOMs (e.g., 250ml, 500ml, 1L). First one will be default.</small>
                  </div>

                  <div class="form-group" v-if="singleProductForm.sale_uom_ids && singleProductForm.sale_uom_ids.length > 0">
                    <label class="form-label">
                      <i class="fas fa-exchange-alt"></i>
                      Conversion Ratio
                    </label>
                    <input 
                      type="number" 
                      class="form-input" 
                      v-model="singleProductForm.conversion_ratio" 
                      placeholder="How many sale units = 1 purchase unit" 
                      step="0.01"
                      min="0.01"
                    />
                    <small class="form-hint">
                      <template v-if="singleProductForm.conversion_ratio">
                        1 {{ getPurchaseUomAbbrv() }} = {{ singleProductForm.conversion_ratio }} {{ getSaleUomAbbrv() }}
                      </template>
                    </small>
                  </div>

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
                      @input="autoPopulateSingleProductUomPrices"
                      placeholder="0.00" 
                      step="0.01"
                      required 
                    />
                  </div>

                  <div class="form-section" v-if="singleProductForm.sale_uom_ids && singleProductForm.sale_uom_ids.length > 0">
                    <h3 class="section-title"><i class="fas fa-tags"></i> UOM Selling Prices</h3>
                    <div class="uom-pricing-table">
                      <div class="pricing-header">
                        <div class="col-uom">UOM</div>
                        <div class="col-price">Selling Price (Ksh)</div>
                        <div class="col-margin">Margin %</div>
                      </div>

                      <div v-for="uomId in singleProductForm.sale_uom_ids" :key="uomId" class="pricing-row">
                        <div class="col-uom">
                          <span class="uom-badge">{{ getUomLabel(uomId) }}</span>
                        </div>
                        <div class="col-price">
                          <input
                            type="number"
                            v-model.number="singleProductForm.uom_prices[uomId]"
                            @input="onSingleUomPriceInput(uomId)"
                            class="form-input-small"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                          />
                        </div>
                        <div class="col-margin">
                          <span class="margin-placeholder">
                            {{ calculateSingleProductUomMargin(uomId) }}
                          </span>
                        </div>
                      </div>
                    </div>
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

                <!-- Empties/Returnables Section -->
                <div class="empties-section">
                  <h3 class="empties-header">
                    <i class="fas fa-recycle"></i>
                    Returnables/Empties (Optional)
                  </h3>
                  <p class="empties-subtitle">Link returnable containers that come with this product</p>
                  
                  <div v-if="singleProductForm.empties.length > 0" class="linked-empties">
                    <div v-for="(empty, idx) in singleProductForm.empties" :key="idx" class="empty-item">
                      <div class="empty-info">
                        <strong>{{ getProductNameById(empty.empty_product_id) }}</strong>
                        <span class="empty-details">Qty: {{ empty.quantity }} | Deposit: Ksh {{ empty.deposit_amount }}</span>
                      </div>
                      <button type="button" @click="removeEmptyFromForm(idx)" class="remove-empty-btn">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>

                  <div class="add-empty-form">
                    <div class="empty-inputs">
                      <select v-model="tempEmpty.empty_product_id" class="form-input">
                        <option value="">Select returnable product</option>
                        <option v-for="product in products.filter(p => p.id !== editingProduct?.id)" :key="product.id" :value="product.id">
                          {{ product.name }}
                        </option>
                      </select>
                      <input 
                        type="number" 
                        v-model.number="tempEmpty.quantity" 
                        class="form-input" 
                        placeholder="Qty"
                        min="1"
                      />
                      <input 
                        type="number" 
                        v-model.number="tempEmpty.deposit_amount" 
                        class="form-input" 
                        placeholder="Deposit (Ksh)"
                        min="0"
                        step="0.01"
                      />
                      <button 
                        type="button" 
                        @click="addEmptyToForm" 
                        class="add-empty-btn"
                        :disabled="!tempEmpty.empty_product_id"
                      >
                        <i class="fas fa-plus"></i> Add
                      </button>
                      <button 
                        type="button" 
                        @click="showQuickAddReturnableModal = true" 
                        class="add-empty-btn"
                        title="Create new returnable/empty"
                      >
                        <i class="fas fa-plus-circle"></i> New
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Price Group Pricing Section -->
                <div v-if="priceGroups.length > 0" class="pricing-section">
                  <h3 class="pricing-header">
                    <i class="fas fa-tag"></i>
                    Price Group Pricing
                  </h3>
                  <p class="pricing-subtitle">Set custom prices for different customer tiers (optional). Disabled groups are shown as read-only.</p>
                  
                  <div class="price-group-inputs">
                    <div v-for="group in (priceGroups || [])" :key="group.id" class="price-group-input" :class="{ disabled: group.is_enabled === false }">
                      <label class="form-label">
                        {{ group.name }}
                        <span class="discount-label" v-if="group.discount_percentage > 0">
                          ({{ group.discount_percentage }}% off base)
                        </span>
                        <span class="group-status group-status-disabled" v-if="group.is_enabled === false">Disabled</span>
                      </label>
                      <div class="price-input-wrapper">
                        <span class="currency">Ksh</span>
                        <input 
                          type="number" 
                          class="form-input price-input" 
                          v-model="singleProductForm.prices[group.id]" 
                          :placeholder="`Price for ${group.name}`"
                          step="0.01"
                          min="0"
                          :disabled="group.is_enabled === false"
                        />
                      </div>
                    </div>
                  </div>
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
                      <div class="input-with-action">
                        <select class="form-input" v-model="product.category">
                          <option value="">Select Category</option>
                          <option v-for="category in categories.filter(c => !c.parent_id)" :key="category.id" :value="category.id">
                            {{ category.name }}
                          </option>
                        </select>
                        <button 
                          type="button" 
                          class="quick-add-btn" 
                          @click="showQuickAddCategoryModal = true"
                          title="Create new category"
                        >
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label class="form-label">Subcategory</label>
                      <div class="input-with-action">
                        <select class="form-input" v-model="product.subcategory" :disabled="!product.category">
                          <option value="">Select Subcategory</option>
                          <option v-for="subcat in ((bulkFormSubcategories && bulkFormSubcategories[bulkProducts.indexOf(product)]) || [])" :key="subcat.id" :value="subcat.id">
                            {{ subcat.name }}
                          </option>
                        </select>
                        <button 
                          type="button" 
                          class="quick-add-btn" 
                          @click="selectedCategoryForSubcategory = product.category; showQuickAddSubcategoryModal = true"
                          :disabled="!product.category"
                          title="Create new subcategory"
                        >
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label class="form-label">Warehouse *</label>
                      <select class="form-input" v-model="product.warehouse_id" required>
                        <option value="">Select Warehouse</option>
                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                          {{ warehouse.name }}
                        </option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label class="form-label">Unit of Measurement</label>
                      <select class="form-input" v-model="product.uom_id">
                        <option value="">Select UOM</option>
                        <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                          {{ uom.name }} ({{ uom.abbreviation }})
                        </option>
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

                  <!-- Price Group Pricing Section for Bulk Products -->
                  <div v-if="priceGroups.length > 0" class="pricing-section-bulk">
                    <h4 class="pricing-header-bulk">
                      <i class="fas fa-tag"></i>
                      Price Group Pricing
                    </h4>
                    <p class="pricing-subtitle-bulk">Set custom prices for different customer tiers (optional). Disabled groups are shown as read-only.</p>
                    
                    <div class="price-group-inputs-bulk">
                      <div v-for="group in (priceGroups || [])" :key="group.id" class="price-group-input-bulk" :class="{ disabled: group.is_enabled === false }">
                        <label class="form-label-bulk">
                          {{ group.name }}
                          <span class="discount-label" v-if="group.discount_percentage > 0">
                            ({{ group.discount_percentage }}% off)
                          </span>
                          <span class="group-status group-status-disabled" v-if="group.is_enabled === false">Disabled</span>
                        </label>
                        <div class="price-input-wrapper-bulk">
                          <span class="currency">Ksh</span>
                          <input 
                            type="number" 
                            class="form-input price-input" 
                            v-model="product.prices[group.id]" 
                            :placeholder="`Price for ${group.name}`"
                            step="0.01"
                            min="0"
                            :disabled="group.is_enabled === false"
                          />
                        </div>
                      </div>
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
                  <h5>Excel Template with Dropdowns</h5>
                  <div class="template-example">
                    <code>name,category,warehouse,cost_price,price,stock_quantity,description</code>
                    <code v-if="categories.length > 0 && warehouses.length > 0">
                      Sample Product,{{ categories[0]?.name || 'Category' }},{{ warehouses[0]?.name || 'Warehouse' }},2800.00,3500.00,50,Product description
                    </code>
                  </div>
                  <button type="button" class="download-template-btn" @click="downloadCSVTemplate">
                    <i class="fas fa-download"></i>
                    Download Excel Template (with dropdowns)
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
                      <span class="category" v-if="product.category">
                        {{ getCategoryName(product.category) || 'No Category' }}
                      </span>
                      <span class="category" v-else>No Category</span>
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
                <span>Saving {{ getProductsToReview().length }} product{{ getProductsToReview().length !== 1 ? 's' : '' }}...</span>
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
    <div v-if="editingProduct" class="modal-overlay">
      <div class="edit-modal-container" @click.stop>
        <form @submit.prevent="saveProduct" class="edit-product-form">
          <!-- Modal Header with Close Button -->
          <div class="edit-modal-header">
            <div class="header-content">
              <div class="header-title">
                <i class="fas fa-edit"></i>
                <div>
                  <h2>Edit Product</h2>
                  <p>Update {{ editingProduct.name }}</p>
                </div>
              </div>
            </div>
            <button type="button" class="modal-close-btn" @click="editingProduct = null" title="Close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Scrollable Content -->
          <div class="edit-modal-body">
            <!-- Basic Information -->
            <div class="form-section">
              <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Product Name *</label>
                  <input type="text" class="form-input" v-model="form.name" placeholder="Enter product name" required/>
                </div>
                <div class="form-group">
                  <label class="form-label">SKU/Barcode</label>
                  <input type="text" class="form-input" v-model="form.sku" placeholder="Auto-generated if empty"/>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <div class="input-with-action">
                    <select class="form-input" v-model="form.category">
                      <option value="">Select Category</option>
                      <option v-for="category in categories.filter(c => !c.parent_id)" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                    <button 
                      type="button" 
                      class="quick-add-btn" 
                      @click="showQuickAddCategoryModal = true"
                      title="Create new category"
                    >
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Subcategory</label>
                  <div class="input-with-action">
                    <select class="form-input" v-model="form.subcategory" :disabled="!form.category">
                      <option value="">Select Subcategory</option>
                      <option v-for="subcat in (editFormSubcategories || [])" :key="subcat.id" :value="subcat.id">
                        {{ subcat.name }}
                      </option>
                    </select>
                    <button 
                      type="button" 
                      class="quick-add-btn" 
                      @click="selectedCategoryForSubcategory = form.category; showQuickAddSubcategoryModal = true"
                      :disabled="!form.category"
                      title="Create new subcategory"
                    >
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Brand</label>
                  <input type="text" class="form-input" v-model="form.brand" placeholder="Product brand"/>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" v-model="form.description" placeholder="Product description (optional)" rows="3"></textarea>
              </div>
            </div>

            <!-- Warehouse & UoM -->
            <div class="form-section">
              <h3 class="section-title"><i class="fas fa-warehouse"></i> Warehouse & Units</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Warehouse *</label>
                  <select class="form-input" v-model="form.warehouse_id" required>
                    <option value="">Select Warehouse</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                      {{ warehouse.name }}
                      <template v-if="!warehouse.company_id"> (System Default)</template>
                    </option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Default UOM</label>
                  <select class="form-input" v-model="form.uom_id">
                    <option value="">Select UOM</option>
                    <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                      {{ uom.name }} ({{ uom.abbreviation }})
                    </option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Purchase UOM (How you buy)</label>
                  <select class="form-input" v-model="form.purchase_uom_id">
                    <option value="">Select Purchase UOM</option>
                    <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                      {{ uom.name }} ({{ uom.abbreviation }})
                    </option>
                  </select>
                  <small class="form-hint">e.g., 50L for bulk purchase</small>
                </div>
                <div class="form-group">
                  <label class="form-label">Sale UOMs (How you sell - Multiple Selection)</label>
                  <UOMSelector 
                    v-model="form.sale_uom_ids"
                    :uoms="getFilteredSaleUoms(form.purchase_uom_id)"
                  />
                  <small class="form-hint">Select multiple UOMs (e.g., 250ml, 500ml, 1L). First is default.</small>
                </div>
              </div>

              <div class="form-group" v-if="form.sale_uom_ids && form.sale_uom_ids.length > 0">
                <label class="form-label">Conversion Ratio</label>
                <input type="number" class="form-input" v-model="form.conversion_ratio" placeholder="How many sale units = 1 purchase unit" step="0.01" min="0.01"/>
                <small class="form-hint">Helps system calculate inventory correctly</small>
              </div>
            </div>

            <!-- Pricing -->
            <div class="form-section">
              <h3 class="section-title"><i class="fas fa-money-bill-wave"></i> Pricing</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Cost Price (Ksh) *</label>
                  <input type="number" class="form-input" v-model="form.cost_price" placeholder="0.00" step="0.01" min="0" required/>
                  <small class="form-hint">What you paid for the product</small>
                </div>
                <div class="form-group">
                  <label class="form-label">Selling Price (Ksh) *</label>
                  <input type="number" class="form-input" v-model="form.price" placeholder="0.00" step="0.01" min="0" required/>
                  <small class="form-hint">What customers pay</small>
                </div>
              </div>

              <div class="profit-display" v-if="form.cost_price && form.price && form.cost_price < form.price">
                <i class="fas fa-chart-line"></i>
                <span>Base Profit Margin: <strong>{{ ((((form.price - form.cost_price) / form.cost_price) * 100).toFixed(1)) }}%</strong></span>
              </div>
            </div>

            <!-- UOM-Specific Pricing -->
            <div class="form-section" v-if="form.sale_uom_ids && form.sale_uom_ids.length > 0">
              <h3 class="section-title">
                <i class="fas fa-tags"></i>
                Prices by Unit of Measure
              </h3>
              <p class="section-description">
                Set different selling prices for each UOM. Margin calculated from purchase cost.
              </p>

              <div class="uom-pricing-table">
                <div class="pricing-header">
                  <div class="col-uom">UOM</div>
                  <div class="col-price">Selling Price (Ksh)</div>
                  <div class="col-margin">Margin %</div>
                </div>

                <div v-for="uomId in form.sale_uom_ids" :key="uomId" class="pricing-row">
                  <div class="col-uom">
                    <span class="uom-badge">{{ getUomLabel(uomId) }}</span>
                  </div>
                  <div class="col-price">
                    <input 
                      type="number" 
                      v-model.number="form.uom_prices[uomId]"
                      @input="onEditUomPriceInput(uomId); $forceUpdate()"
                      class="form-input-small" 
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                    />
                  </div>
                  <div class="col-margin">
                    <span v-if="calculateUomMargin(uomId) !== null" class="margin-value" :class="getMarginClass(calculateUomMargin(uomId))">
                      {{ calculateUomMargin(uomId).toFixed(1) }}%
                    </span>
                    <span v-else class="margin-placeholder">-</span>
                  </div>
                </div>
              </div>

              <div class="pricing-helper">
                <label class="markup-label">
                  <i class="fas fa-percentage"></i>
                  Quick Set: Apply Markup to All UOMs
                </label>
                <div class="markup-controls">
                  <input 
                    type="number" 
                    v-model.number="markupPercentage"
                    class="form-input-small"
                    placeholder="50"
                    min="0"
                    step="5"
                  />
                  <span class="markup-symbol">%</span>
                  <button 
                    type="button"
                    class="btn-apply-markup"
                    @click="applyMarkupToAllUoms"
                  >
                    <i class="fas fa-sync-alt"></i>
                    Apply
                  </button>
                </div>
                <small class="form-hint">
                  Automatically calculates price = cost × (1 + markup%) for each UOM
                </small>
                <small class="form-hint">
                  Tip: Enter a price for one UOM and other UOM prices auto-convert from saved UOM conversions.
                </small>
              </div>
            </div>

            <!-- Stock Management -->
            <div class="form-section">
              <h3 class="section-title"><i class="fas fa-boxes"></i> Stock Management</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Stock Quantity *</label>
                  <input type="number" class="form-input" v-model="form.stock_quantity" placeholder="0" min="0" required/>
                </div>
                <div class="form-group">
                  <label class="form-label">Low Stock Alert (Units)</label>
                  <input type="number" class="form-input" v-model="form.low_stock_threshold" placeholder="5" min="0"/>
                  <small class="form-hint">You'll be notified when stock falls below this</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="edit-modal-footer">
            <button type="button" class="btn-secondary" @click="editingProduct = null">
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
    <div v-if="deletingProduct" class="modal-overlay">
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

    <!-- Stock Transfer Modal -->
    <div v-if="transferringProduct" class="modal-overlay">
      <div class="modern-modal transfer-modal" @click.stop>
        <form @submit.prevent="transferStock">
          <!-- Modal Header -->
          <div class="modal-header">
            <div class="modal-title-section">
              <div class="modal-icon">
                <i class="fas fa-exchange-alt"></i>
              </div>
              <div>
                <h2 class="modal-title">Transfer Stock</h2>
                <p class="modal-subtitle">{{ transferringProduct.name }}</p>
              </div>
            </div>
            <button type="button" class="close-btn" @click="closeTransferModal">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-warehouse"></i>
                From Warehouse
              </label>
              <input 
                type="text" 
                class="form-input" 
                :value="getWarehouseName(transferringProduct.warehouse_id)"
                disabled
              />
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-arrows-alt-h"></i>
                Transfer Type *
              </label>
              <select class="form-input" v-model="transferForm.destination_type" required>
                <option value="">Select Transfer Type</option>
                <option value="warehouse">Transfer to Another Warehouse</option>
                <option value="supplier_return">Return to Supplier</option>
                <option value="write_off">Write Off (Damage/Expiry)</option>
                <option value="adjustment_out">Adjustment Out</option>
              </select>
            </div>

            <div v-if="transferForm.destination_type === 'warehouse'" class="form-group">
              <label class="form-label">
                <i class="fas fa-warehouse"></i>
                To Warehouse *
              </label>
              <select class="form-input" v-model="transferForm.to_warehouse_id" required>
                <option value="">Select Destination Warehouse</option>
                <option 
                  v-for="warehouse in availableWarehouses" 
                  :key="warehouse.id" 
                  :value="warehouse.id"
                >
                  {{ warehouse.name }}
                  <template v-if="!warehouse.company_id"> (System Default)</template>
                </option>
              </select>
            </div>

            <div v-if="transferForm.destination_type === 'supplier_return'" class="form-group">
              <label class="form-label">
                <i class="fas fa-truck"></i>
                Supplier Name
              </label>
              <input 
                type="text" 
                class="form-input" 
                v-model="transferForm.external_target"
                placeholder="Enter supplier name"
              />
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-boxes"></i>
                Quantity *
              </label>
              <input 
                type="number" 
                class="form-input" 
                v-model="transferForm.quantity"
                :max="transferringProduct.stock_quantity"
                min="1"
                placeholder="Enter quantity to transfer"
                required
              />
              <small class="form-hint">Available: {{ transferringProduct.stock_quantity }}</small>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-info-circle"></i>
                Reason
              </label>
              <input 
                type="text" 
                class="form-input" 
                v-model="transferForm.reason"
                placeholder="Enter reason for transfer (optional)"
              />
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-hashtag"></i>
                Reference Number
              </label>
              <input 
                type="text" 
                class="form-input" 
                v-model="transferForm.reference"
                placeholder="Enter reference number (optional)"
              />
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-sticky-note"></i>
                Notes
              </label>
              <textarea 
                class="form-input" 
                v-model="transferForm.note"
                placeholder="Additional notes (optional)"
                rows="3"
              ></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeTransferModal">
              <i class="fas fa-times"></i>
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <div v-if="saving" class="btn-loading">
                <div class="btn-spinner"></div>
                <span>Transferring...</span>
              </div>
              <div v-else>
                <i class="fas fa-exchange-alt"></i>
                <span>Transfer Stock</span>
              </div>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Empties Management Modal -->
  <EmptiesModal
    :isOpen="showEmptiesModalFlag"
    :product="selectedProductForEmpties"
    @close="closeEmptiesModal"
    @success="handleEmptiesSuccess"
    @error="handleEmptiesError"
  />

  <!-- Quick Add Category Modal -->
  <div v-if="showQuickAddCategoryModal" class="modal-overlay">
    <div class="modal-content quick-add-modal" @click.stop>
      <div class="modal-header">
        <h3><i class="fas fa-tag"></i> Create New Category</h3>
        <button class="close-btn" @click="showQuickAddCategoryModal = false">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <form @submit.prevent="createQuickCategory" class="quick-add-form">
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="quickAddCategory.name"
            placeholder="Enter category name"
            required
            autofocus
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Description (Optional)</label>
          <textarea 
            class="form-input" 
            v-model="quickAddCategory.description"
            placeholder="Enter description"
            rows="3"
          ></textarea>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showQuickAddCategoryModal = false">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="quickAddLoading">
            <div v-if="quickAddLoading" class="btn-loading">
              <div class="btn-spinner"></div>
              Creating...
            </div>
            <div v-else>
              <i class="fas fa-plus"></i> Create Category
            </div>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Quick Add Subcategory Modal -->
  <div v-if="showQuickAddSubcategoryModal" class="modal-overlay">
    <div class="modal-content quick-add-modal" @click.stop>
      <div class="modal-header">
        <h3><i class="fas fa-sitemap"></i> Create New Subcategory</h3>
        <button class="close-btn" @click="showQuickAddSubcategoryModal = false">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <form @submit.prevent="createQuickSubcategory" class="quick-add-form">
        <div class="form-group">
          <label class="form-label">Parent Category</label>
          <p class="info-text">
            <i class="fas fa-info-circle"></i>
            {{ getCategoryName(selectedCategoryForSubcategory) || 'Select a parent category from the dropdown' }}
          </p>
        </div>
        
        <div class="form-group">
          <label class="form-label">Subcategory Name *</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="quickAddSubcategory.name"
            placeholder="Enter subcategory name"
            required
            autofocus
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Description (Optional)</label>
          <textarea 
            class="form-input" 
            v-model="quickAddSubcategory.description"
            placeholder="Enter description"
            rows="3"
          ></textarea>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showQuickAddSubcategoryModal = false">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="quickAddLoading || !selectedCategoryForSubcategory">
            <div v-if="quickAddLoading" class="btn-loading">
              <div class="btn-spinner"></div>
              Creating...
            </div>
            <div v-else>
              <i class="fas fa-plus"></i> Create Subcategory
            </div>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Quick Add Returnable Modal -->
  <div v-if="showQuickAddReturnableModal" class="modal-overlay">
    <div class="modal-content quick-add-modal" @click.stop>
      <div class="modal-header">
        <h3><i class="fas fa-recycle"></i> Create New Returnable/Empty</h3>
        <button class="close-btn" @click="showQuickAddReturnableModal = false">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <form @submit.prevent="createQuickReturnable" class="quick-add-form">
        <div class="form-group">
          <label class="form-label">Returnable Name *</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="quickAddReturnable.name"
            placeholder="e.g., Glass Bottle, Plastic Container"
            required
            autofocus
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">SKU (Optional)</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="quickAddReturnable.sku"
            placeholder="Stock Keeping Unit"
          />
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Warehouse *</label>
            <select class="form-input" v-model="quickAddReturnable.warehouse_id" required>
              <option value="">Select Warehouse</option>
              <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.name }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">UOM (Optional)</label>
            <select class="form-input" v-model="quickAddReturnable.uom_id">
              <option value="">Select UOM</option>
              <option v-for="uom in uoms" :key="uom.id" :value="uom.id">
                {{ uom.name }} ({{ uom.abbreviation }})
              </option>
            </select>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Description (Optional)</label>
          <textarea 
            class="form-input" 
            v-model="quickAddReturnable.description"
            placeholder="Enter description"
            rows="2"
          ></textarea>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showQuickAddReturnableModal = false">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="quickAddLoading">
            <div v-if="quickAddLoading" class="btn-loading">
              <div class="btn-spinner"></div>
              Creating...
            </div>
            <div v-else>
              <i class="fas fa-plus"></i> Create Returnable
            </div>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import * as XLSX from 'xlsx'
import ExcelJS from 'exceljs'
import EmptiesModal from '../../components/EmptiesModal.vue'
import UOMSelector from '../../components/UOMSelector.vue'

export default {
  components: {
    EmptiesModal,
    UOMSelector
  },
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
      markupPercentage: 50,  // Default 50% markup for UOM pricing helper
      alert: {
        show: false,
        message: '',
        type: 'success' // success, error, warning, info
      },
      form: {
        name: '',
        sku: '',
        category: '',
        subcategory: '',
        brand: '',
        description: '',
        price: '',
        cost_price: '',
        stock_quantity: '',
        warehouse_id: '',
        uom_id: '',
        purchase_uom_id: '',
        sale_uom_ids: [],
        conversion_ratio: '',
        low_stock_threshold: '',
        uom_prices: {}  // { uom_id: price } for UOM-specific pricing
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
        subcategory: '',
        brand: '',
        description: '',
        cost_price: '',
        price: '',
        stock_quantity: '',
        low_stock_threshold: 5,
        warehouse_id: '',
        uom_id: '',
        purchase_uom_id: '',
        sale_uom_ids: [],
        conversion_ratio: 1,
        uom_prices: {},
        prices: {}, // Price group pricing
        empties: [] // Array of {empty_product_id, quantity, deposit_amount}
      },
      // Bulk products array
      bulkProducts: [
        {
          name: '',
          category: '',
          subcategory: '',
          warehouse_id: '',
          uom_id: '',
          purchase_uom_id: '',
          sale_uom_id: '',
          conversion_ratio: 1,
          cost_price: '',
          price: '',
          stock_quantity: '',
          prices: {},
          empties: []
        }
      ],
      // CSV import
      csvFile: null,
      isDragOver: false,
      csvData: [],
      // Warehouses
      warehouses: [],
      // UOMs
      uoms: [],
      // Categories
      categories: [],
      // Price Groups
      priceGroups: [],
      // Stock Transfer
      transferringProduct: null,
      transferForm: {
        destination_type: '',
        to_warehouse_id: '',
        quantity: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      },
      // Empties Management
      showEmptiesModalFlag: false,
      selectedProductForEmpties: null,
      // Temp empty for adding to new product
      tempEmpty: {
        empty_product_id: '',
        quantity: 1,
        deposit_amount: 0
      },
      // Quick add modals
      showQuickAddCategoryModal: false,
      showQuickAddSubcategoryModal: false,
      showQuickAddReturnableModal: false,
      quickAddCategory: {
        name: '',
        description: ''
      },
      quickAddSubcategory: {
        name: '',
        parent_id: '',
        description: ''
      },
      quickAddReturnable: {
        name: '',
        description: '',
        sku: '',
        warehouse_id: '',
        uom_id: ''
      },
      quickAddLoading: false,
      selectedCategoryForSubcategory: '', // Track which category context for subcategory creation
      uomConversionCache: {},
      isAutoPopulatingUomPrices: false,
      // View and Display Options
      viewMode: 'grid', // 'grid' or 'list'
      currentPage: 1,
      itemsPerPage: 12,
      // Filters
      filters: {
        priceRange: { min: 0, max: null },
        sortBy: 'newest', // 'newest', 'oldest', 'a-z', 'z-a', 'price-low', 'price-high'
        category: '' // empty means all categories
      }
    }
  },
  computed: {
    filteredAndSortedProducts() {
      let result = this.products

      // Apply search filter
      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase().trim()
        result = result.filter(product => 
          product.name.toLowerCase().includes(query) ||
          product.price.toString().includes(query) ||
          product.stock_quantity.toString().includes(query)
        )
      }

      // Apply category filter
      if (this.filters.category) {
        result = result.filter(product => product.category === this.filters.category)
      }

      // Apply price range filter
      if (this.filters.priceRange.min !== null || this.filters.priceRange.max !== null) {
        result = result.filter(product => {
          const price = parseFloat(product.price) || 0
          const minOk = this.filters.priceRange.min === null || price >= this.filters.priceRange.min
          const maxOk = this.filters.priceRange.max === null || price <= this.filters.priceRange.max
          return minOk && maxOk
        })
      }

      // Apply sorting
      const sortMap = {
        'newest': (a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0),
        'oldest': (a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0),
        'a-z': (a, b) => a.name.localeCompare(b.name),
        'z-a': (a, b) => b.name.localeCompare(a.name),
        'price-low': (a, b) => (a.price || 0) - (b.price || 0),
        'price-high': (a, b) => (b.price || 0) - (a.price || 0)
      }

      if (sortMap[this.filters.sortBy]) {
        result.sort(sortMap[this.filters.sortBy])
      }

      return result
    },

    filteredProducts() {
      const products = this.filteredAndSortedProducts
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return products.slice(start, end)
    },

    totalFilteredProducts() {
      return this.filteredAndSortedProducts.length
    },

    totalPages() {
      return Math.ceil(this.totalFilteredProducts / this.itemsPerPage)
    },

    uniqueCategories() {
      const categorySet = new Set()
      this.products.forEach(p => {
        if (p.category) categorySet.add(p.category)
      })
      return Array.from(categorySet).sort()
    },
    availableWarehouses() {
      // Exclude the current product's warehouse from the destination options
      if (!this.transferringProduct) return this.warehouses
      return this.warehouses.filter(w => w.id !== this.transferringProduct.warehouse_id)
    },

    maxProductPrice() {
      if (this.products.length === 0) return 0
      return Math.max(...this.products.map(p => parseFloat(p.price) || 0))
    },

    // Filter sale UOMs based on selected purchase UOM type
    availableSaleUoms() {
      if (!this.form.purchase_uom_id) {
        // No purchase UOM selected, show all UOMs grouped by type
        return this.uoms
      }
      
      // Find the purchase UOM
      const purchaseUom = this.uoms.find(u => u.id === this.form.purchase_uom_id)
      if (!purchaseUom || !purchaseUom.type) {
        // No type info, show all
        return this.uoms
      }
      
      // Filter to only same type UOMs as purchase UOM
      return this.uoms.filter(u => u.type === purchaseUom.type)
    },
    // Get subcategories for single product form based on selected category
    singleFormSubcategories() {
      if (!this.singleProductForm.category) return []
      const selectedCategory = this.categories?.find(c => c.id === this.singleProductForm.category)
      return selectedCategory?.children || []
    },
    // Get subcategories for bulk product form
    bulkFormSubcategories() {
      const subcats = {}
      if (!this.bulkProducts) return subcats
      
      this.bulkProducts.forEach((product, index) => {
        if (product?.category) {
          const selectedCategory = this.categories?.find(c => c.id === product.category)
          subcats[index] = selectedCategory?.children || []
        } else {
          subcats[index] = []
        }
      })
      return subcats
    },
    // Get subcategories for edit form based on selected category
    editFormSubcategories() {
      if (!this.form.category) return []
      const selectedCategory = this.categories?.find(c => c.id === this.form.category)
      return selectedCategory?.children || []
    }
  },
  mounted() {
    this.fetchProducts()
    this.fetchWarehouses()
    this.fetchUoms()
    this.fetchCategories()
    this.fetchPriceGroups()
  },
  watch: {
    priceGroups() {
      // Initialize prices object when price groups are loaded
      this.initializePricesObject()
    },
    'singleProductForm.sale_uom_ids': {
      handler() {
        this.autoPopulateSingleProductUomPrices()
      },
      deep: true
    },
    'singleProductForm.price'() {
      this.autoPopulateSingleProductUomPrices()
    },
    'form.sale_uom_ids': {
      handler() {
        this.autoPopulateEditUomPrices()
      },
      deep: true
    },
    'form.price'() {
      this.autoPopulateEditUomPrices()
    },
    filters: {
      handler() {
        this.currentPage = 1
      },
      deep: true
    }
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

    async fetchWarehouses() {
      try {
        const res = await axios.get('http://localhost:8000/warehouses')
        this.warehouses = res.data
      } catch (err) {
        console.error('Error fetching warehouses:', err)
      }
    },

    async fetchUoms() {
      try {
        const res = await axios.get('http://localhost:8000/uoms')
        this.uoms = res.data
      } catch (err) {
        console.error('Error fetching UOMs:', err)
      }
    },

    async fetchCategories() {
      try {
        const res = await axios.get('http://localhost:8000/product-categories')
        this.categories = res.data
      } catch (err) {
        console.error('Error fetching categories:', err)
      }
    },

    // Helper: Get category name from ID
    getCategoryName(categoryId) {
      if (!categoryId) return null
      const category = this.categories.find(c => c.id === categoryId)
      return category ? category.name : null
    },

    // Helper: Get subcategory name from ID
    getSubcategoryName(subcategoryId) {
      if (!subcategoryId) return null
      for (const category of this.categories) {
        if (category.children) {
          const subcat = category.children.find(s => s.id === subcategoryId)
          if (subcat) return subcat.name
        }
      }
      return null
    },

    // Helper: Prepare product data for API (convert IDs to names)
    prepareProductForApi(product) {
      const saleUomIds = (product.sale_uom_ids || [])
        .map(id => parseInt(id, 10))
        .filter(Boolean)

      const uomPrices = Object.entries(product.uom_prices || {}).reduce((acc, [uomId, price]) => {
        if (price === null || price === '' || Number.isNaN(Number(price))) {
          return acc
        }
        acc[String(parseInt(uomId, 10))] = parseFloat(price)
        return acc
      }, {})

      // Process price group pricing
      const prices = Object.entries(product.prices || {}).reduce((acc, [groupId, price]) => {
        if (price === null || price === '' || Number.isNaN(Number(price))) {
          return acc
        }
        acc[String(parseInt(groupId, 10))] = parseFloat(price)
        return acc
      }, {})

      return {
        ...product,
        category: product.category ? this.getCategoryName(product.category) : null,
        subcategory: product.subcategory ? this.getSubcategoryName(product.subcategory) : null,
        uom_id: product.uom_id ? parseInt(product.uom_id, 10) : null,
        purchase_uom_id: product.purchase_uom_id ? parseInt(product.purchase_uom_id, 10) : null,
        sale_uom_ids: saleUomIds,
        conversion_ratio: parseFloat(product.conversion_ratio) || 1,
        uom_prices: uomPrices,
        prices: prices,
        empties: product.empties || []
      }
    },

    addSingleProduct() {
      // Validate required fields
      if (!this.singleProductForm.name.trim()) {
        this.showAlert('Product name is required', 'error')
        return
      }
      if (!this.singleProductForm.cost_price) {
        this.showAlert('Cost price is required', 'error')
        return
      }
      if (!this.singleProductForm.price) {
        this.showAlert('Selling price is required', 'error')
        return
      }
      if (!this.singleProductForm.stock_quantity && this.singleProductForm.stock_quantity !== 0) {
        this.showAlert('Initial stock is required', 'error')
        return
      }
      if (!this.singleProductForm.warehouse_id) {
        this.showAlert('Warehouse is required', 'error')
        return
      }
      
      // Move to review step to preview before saving
      this.currentStep = 3
    },

    async fetchPriceGroups() {
      try {
        const res = await axios.get('/price-groups')
        this.priceGroups = res.data || []
      } catch (err) {
        console.error('Error fetching price groups:', err)
        this.priceGroups = []
      }
    },

    initializePricesObject() {
      // Initialize prices object for single product form with all price groups
      if (!this.singleProductForm.prices) {
        this.singleProductForm.prices = {}
      }
      if (this.priceGroups && this.priceGroups.length > 0) {
        this.priceGroups.forEach(group => {
          if (!(group.id in this.singleProductForm.prices)) {
            this.singleProductForm.prices[group.id] = ''
          }
        })
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

    resetFilters() {
      this.filters = {
        priceRange: { min: 0, max: null },
        sortBy: 'newest',
        category: ''
      }
      this.currentPage = 1
    },

    getPaginationRange() {
      const maxVisible = 5
      const pages = []
      let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2))
      let endPage = Math.min(this.totalPages, startPage + maxVisible - 1)

      if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1)
      }

      if (startPage > 1) {
        pages.push(1)
        if (startPage > 2) pages.push('...')
      }

      for (let i = startPage; i <= endPage; i++) {
        pages.push(i)
      }

      if (endPage < this.totalPages) {
        if (endPage < this.totalPages - 1) pages.push('...')
        pages.push(this.totalPages)
      }

      return pages
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
    
    // UoM Helper methods
    getPurchaseUomAbbrv() {
      if (this.singleProductForm.purchase_uom_id) {
        const uom = this.uoms.find(u => u.id === this.singleProductForm.purchase_uom_id)
        return uom?.abbreviation || 'unit'
      }
      return 'unit'
    },
    
    getSaleUomAbbrv() {
      const firstSaleUomId = this.singleProductForm.sale_uom_ids?.[0]
      if (firstSaleUomId) {
        const uom = this.uoms.find(u => u.id === firstSaleUomId)
        return uom?.abbreviation || 'unit'
      }
      return 'unit'
    },

    async getConversionFactor(fromUomId, toUomId) {
      const from = Number(fromUomId)
      const to = Number(toUomId)

      if (!from || !to) {
        return null
      }
      if (from === to) {
        return 1
      }

      const cacheKey = `${from}_${to}`
      if (Object.prototype.hasOwnProperty.call(this.uomConversionCache, cacheKey)) {
        return this.uomConversionCache[cacheKey]
      }

      try {
        const res = await axios.get('/uoms/conversion-factor', {
          params: {
            from_uom_id: from,
            to_uom_id: to
          }
        })

        const factor = Number(res.data?.factor)
        const normalizedFactor = Number.isFinite(factor) && factor > 0 ? factor : null
        this.uomConversionCache[cacheKey] = normalizedFactor
        return normalizedFactor
      } catch (error) {
        this.uomConversionCache[cacheKey] = null
        console.warn('Failed to fetch UOM conversion factor', { from, to, error })
        return null
      }
    },

    async autoPopulateSingleProductUomPrices(force = false) {
      if (this.isAutoPopulatingUomPrices) return

      const saleUomIds = (this.singleProductForm.sale_uom_ids || []).map(id => Number(id)).filter(Boolean)
      const baseUomId = saleUomIds[0]
      const basePrice = Number(this.singleProductForm.price)

      if (!baseUomId || !Number.isFinite(basePrice) || basePrice <= 0) {
        return
      }

      this.isAutoPopulatingUomPrices = true
      try {
        const nextUomPrices = { ...(this.singleProductForm.uom_prices || {}) }
        nextUomPrices[baseUomId] = Number(basePrice.toFixed(2))

        for (const targetUomId of saleUomIds) {
          const hasUserValue = nextUomPrices[targetUomId] !== undefined && nextUomPrices[targetUomId] !== null && nextUomPrices[targetUomId] !== ''
          if (!force && hasUserValue) {
            continue
          }

          const factor = await this.getConversionFactor(baseUomId, targetUomId)
          if (!factor) {
            continue
          }

          nextUomPrices[targetUomId] = Number((basePrice * factor).toFixed(2))
        }

        this.singleProductForm.uom_prices = nextUomPrices
      } finally {
        this.isAutoPopulatingUomPrices = false
      }
    },

    async autoPopulateEditUomPrices(force = false) {
      if (this.isAutoPopulatingUomPrices) return

      const saleUomIds = (this.form.sale_uom_ids || []).map(id => Number(id)).filter(Boolean)
      const baseUomId = saleUomIds[0]
      const basePrice = Number(this.form.price)

      if (!baseUomId || !Number.isFinite(basePrice) || basePrice <= 0) {
        return
      }

      this.isAutoPopulatingUomPrices = true
      try {
        const nextUomPrices = { ...(this.form.uom_prices || {}) }
        nextUomPrices[baseUomId] = Number(basePrice.toFixed(2))

        for (const targetUomId of saleUomIds) {
          const hasUserValue = nextUomPrices[targetUomId] !== undefined && nextUomPrices[targetUomId] !== null && nextUomPrices[targetUomId] !== ''
          if (!force && hasUserValue) {
            continue
          }

          const factor = await this.getConversionFactor(baseUomId, targetUomId)
          if (!factor) {
            continue
          }

          nextUomPrices[targetUomId] = Number((basePrice * factor).toFixed(2))
        }

        this.form.uom_prices = nextUomPrices
      } finally {
        this.isAutoPopulatingUomPrices = false
      }
    },

    async onSingleUomPriceInput(sourceUomId) {
      const saleUomIds = (this.singleProductForm.sale_uom_ids || []).map(id => Number(id)).filter(Boolean)
      const sourceId = Number(sourceUomId)
      const sourcePrice = Number(this.singleProductForm.uom_prices?.[sourceId])
      if (!sourceId || !Number.isFinite(sourcePrice) || sourcePrice <= 0 || saleUomIds.length === 0) {
        return
      }

      this.isAutoPopulatingUomPrices = true
      try {
        const nextUomPrices = { ...(this.singleProductForm.uom_prices || {}) }

        for (const targetUomId of saleUomIds) {
          const factor = await this.getConversionFactor(sourceId, targetUomId)
          if (!factor) {
            continue
          }
          nextUomPrices[targetUomId] = Number((sourcePrice * factor).toFixed(2))
        }

        this.singleProductForm.uom_prices = nextUomPrices

        const defaultUomId = saleUomIds[0]
        if (nextUomPrices[defaultUomId] !== undefined) {
          this.singleProductForm.price = Number(nextUomPrices[defaultUomId])
        }
      } finally {
        this.isAutoPopulatingUomPrices = false
      }
    },

    async onEditUomPriceInput(sourceUomId) {
      const saleUomIds = (this.form.sale_uom_ids || []).map(id => Number(id)).filter(Boolean)
      const sourceId = Number(sourceUomId)
      const sourcePrice = Number(this.form.uom_prices?.[sourceId])
      if (!sourceId || !Number.isFinite(sourcePrice) || sourcePrice <= 0 || saleUomIds.length === 0) {
        return
      }

      this.isAutoPopulatingUomPrices = true
      try {
        const nextUomPrices = { ...(this.form.uom_prices || {}) }

        for (const targetUomId of saleUomIds) {
          const factor = await this.getConversionFactor(sourceId, targetUomId)
          if (!factor) {
            continue
          }
          nextUomPrices[targetUomId] = Number((sourcePrice * factor).toFixed(2))
        }

        this.form.uom_prices = nextUomPrices

        const defaultUomId = saleUomIds[0]
        if (nextUomPrices[defaultUomId] !== undefined) {
          this.form.price = Number(nextUomPrices[defaultUomId])
        }
      } finally {
        this.isAutoPopulatingUomPrices = false
      }
    },

    calculateSingleProductUomMargin(uomId) {
      const uomPrice = this.singleProductForm.uom_prices[uomId]
      const costPrice = parseFloat(this.singleProductForm.cost_price) || 0
      const conversionRatio = parseFloat(this.singleProductForm.conversion_ratio) || 1

      if (!uomPrice || !costPrice || !conversionRatio) {
        return '-'
      }

      const costPerUom = costPrice / conversionRatio
      if (!costPerUom) {
        return '-'
      }

      const margin = ((uomPrice - costPerUom) / costPerUom) * 100
      return `${margin.toFixed(1)}%`
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingProduct = null
      this.form = { 
        name: '', 
        sku: '',
        category: '',
        subcategory: '',
        brand: '',
        description: '',
        price: '', 
        cost_price: '',
        stock_quantity: '',
        warehouse_id: '',
        uom_id: '',
        purchase_uom_id: '',
        sale_uom_ids: [],
        conversion_ratio: '',
        low_stock_threshold: '',
        uom_prices: {}
      }
    },
    
    editProduct(product) {
      this.editingProduct = product
      this.form = {
        ...product,
        uom_id: product.uom_id ? parseInt(product.uom_id, 10) : '',
        purchase_uom_id: product.purchase_uom_id ? parseInt(product.purchase_uom_id, 10) : '',
        sale_uom_ids: product.sale_uom_ids || product.saleUoms?.map(uom => uom.id) || [],
        uom_prices: { ...(product.uom_prices || {}) }
      }
      
      // Convert category name to ID if it exists
      if (product.category) {
        const category = this.categories.find(c => c.name === product.category)
        if (category) {
          this.form.category = category.id
        }
      }
      
      // Convert subcategory name to ID if it exists
      if (product.subcategory) {
        for (const category of this.categories) {
          if (category.children) {
            const subcat = category.children.find(s => s.name === product.subcategory)
            if (subcat) {
              this.form.subcategory = subcat.id
              break
            }
          }
        }
      }
      
      if (!this.form.uom_prices) this.form.uom_prices = {}
    },
    
    async saveProduct() {
      if (this.saving) return
      
      this.saving = true
      this.showAlert('Saving product...', 'info')
      
      try {
        // Prepare product data with category name conversion
        const productData = this.prepareProductForApi(this.form)
        
        if (this.editingProduct) {
          await axios.put(`http://localhost:8000/products/${this.editingProduct.id}`, productData)
          this.showAlert('✓ Product updated successfully!', 'success')
        } else {
          await axios.post(`/products`, productData)
          this.showAlert('✓ Product added successfully!', 'success')
        }
        
        await this.fetchProducts()
        
        // Close modal after brief delay to show success
        setTimeout(() => {
          this.closeModal()
        }, 500)
        
      } catch (err) {
        this.showAlert(
          `✗ Failed to save product: ${err.response?.data?.error || err.message || 'Unknown error'}`, 
          'error'
        )
        console.error('Save product error:', err)
      } finally {
        this.saving = false
      }
    },
    
    confirmDelete(product) {
      this.deletingProduct = product
    },
    
    async deleteProduct(id) {
      this.showAlert('Deleting product...', 'info')
      
      try {
        await axios.delete(`http://localhost:8000/products/${id}`)
        this.showAlert('✓ Product deleted successfully!', 'success')
        await this.fetchProducts()
        this.deletingProduct = null
      } catch (err) {
        this.showAlert(
          `✗ Failed to delete product: ${err.response?.data?.error || err.message || 'Unknown error'}`, 
          'error'
        )
        console.error('Delete product error:', err)
        this.deletingProduct = null
      }
    },

    // Stock Transfer Methods
    showTransferModal(product) {
      this.transferringProduct = product
      this.transferForm = {
        destination_type: '',
        to_warehouse_id: '',
        quantity: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      }
    },

    closeTransferModal() {
      this.transferringProduct = null
      this.transferForm = {
        destination_type: '',
        to_warehouse_id: '',
        quantity: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      }
    },

    // Empties Management Methods
    showEmptiesModal(product) {
      this.selectedProductForEmpties = product
      this.showEmptiesModalFlag = true
    },

    closeEmptiesModal() {
      this.showEmptiesModalFlag = false
      this.selectedProductForEmpties = null
    },

    handleEmptiesSuccess(message) {
      this.showAlert(message || 'Operation successful', 'success')
    },

    handleEmptiesError(message) {
      this.showAlert(message || 'Operation failed', 'error')
    },

    // Empties form management
    addEmptyToForm() {
      if (!this.tempEmpty.empty_product_id) return
      
      this.singleProductForm.empties.push({
        empty_product_id: this.tempEmpty.empty_product_id,
        quantity: this.tempEmpty.quantity || 1,
        deposit_amount: this.tempEmpty.deposit_amount || 0
      })
      
      // Reset temp form
      this.tempEmpty = {
        empty_product_id: '',
        quantity: 1,
        deposit_amount: 0
      }
    },

    removeEmptyFromForm(index) {
      this.singleProductForm.empties.splice(index, 1)
    },

    getProductNameById(productId) {
      const product = this.products.find(p => p.id === productId)
      return product ? product.name : 'Unknown'
    },

    async transferStock() {
      if (this.saving) return
      
      this.saving = true
      this.showAlert('Processing stock transfer...', 'info')
      
      try {
        const payload = {
          product_id: this.transferringProduct.id,
          from_warehouse_id: this.transferringProduct.warehouse_id,
          quantity: parseInt(this.transferForm.quantity),
          destination_type: this.transferForm.destination_type,
          reason: this.transferForm.reason,
          reference: this.transferForm.reference,
          note: this.transferForm.note
        }

        // Add to_warehouse_id only for warehouse transfers
        if (this.transferForm.destination_type === 'warehouse') {
          payload.to_warehouse_id = parseInt(this.transferForm.to_warehouse_id)
        }

        // Add external_target for supplier returns
        if (this.transferForm.destination_type === 'supplier_return') {
          payload.external_target = this.transferForm.external_target
        }

        const response = await axios.post('/products/transfer', payload)
        
        this.showAlert(
          `✓ ${response.data.message || 'Stock transferred successfully!'}`, 
          'success'
        )
        
        await this.fetchProducts()
        
        // Close modal after brief delay
        setTimeout(() => {
          this.closeTransferModal()
        }, 500)
        
      } catch (err) {
        this.showAlert(
          `✗ ${err.response?.data?.error || err.response?.data?.message || 'Failed to transfer stock'}`, 
          'error'
        )
        console.error('Transfer stock error:', err)
      } finally {
        this.saving = false
      }
    },

    getWarehouseName(warehouseId) {
      const warehouse = this.warehouses.find(w => w.id === warehouseId)
      return warehouse ? warehouse.name : 'Unknown Warehouse'
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
            return this.singleProductForm.name && this.singleProductForm.price && this.singleProductForm.stock_quantity && this.singleProductForm.warehouse_id
          } else if (this.selectedMethod === 'bulk') {
            return this.bulkProducts.every(product => 
              product.name && product.price && product.stock_quantity && product.warehouse_id
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
        warehouse_id: '',
        uom_id: '',
        cost_price: '',
        price: '',
        stock_quantity: '',
        prices: {}
      })
    },

    removeBulkProduct(index) {
      if (this.bulkProducts.length > 1) {
        this.bulkProducts.splice(index, 1)
      }
    },

    // CSV import methods
    async downloadCSVTemplate() {
      try {
        // Create workbook using ExcelJS
        const workbook = new ExcelJS.Workbook()
        const worksheet = workbook.addWorksheet('Products')
        
        // Add headers
        const headers = ['name', 'category', 'subcategory', 'warehouse', 'cost_price', 'price', 'stock_quantity', 'low_stock_threshold', 'description']
        worksheet.addRow(headers)
        
        // Style header row
        worksheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } }
        worksheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4472C4' } }
        
        // Add sample rows
        const sampleRows = []
        if (this.categories.length > 0 && this.warehouses.length > 0) {
          sampleRows.push([
            'Sample Product 1',
            this.categories[0]?.name || 'Category1',
            'Dairy',
            this.warehouses[0]?.name || 'Warehouse1',
            '2800.00',
            '3500.00',
            '50',
            '10',
            'Product description 1'
          ])
          sampleRows.push([
            'Sample Product 2',
            this.categories[Math.min(1, this.categories.length - 1)]?.name || 'Category2',
            'Meat',
            this.warehouses[Math.min(1, this.warehouses.length - 1)]?.name || 'Warehouse2',
            '1200.00',
            '1800.00',
            '30',
            '5',
            'Product description 2'
          ])
          sampleRows.push([
            'Sample Product 3',
            this.categories[Math.min(2, this.categories.length - 1)]?.name || 'Category3',
            'Grains',
            this.warehouses[0]?.name || 'Warehouse1',
            '800.00',
            '1200.00',
            '100',
            '20',
            'Product description 3'
          ])
        } else {
          sampleRows.push(['Sample Product', 'Category Name', 'Subcategory', 'Warehouse Name', '1000.00', '1500.00', '50', '10', 'Product description'])
        }
        
        sampleRows.forEach(row => worksheet.addRow(row))
        
        // Set column widths
        worksheet.columns = [
          { header: 'name', width: 20 },
          { header: 'category', width: 15 },
          { header: 'subcategory', width: 15 },
          { header: 'warehouse', width: 15 },
          { header: 'cost_price', width: 12 },
          { header: 'price', width: 12 },
          { header: 'stock_quantity', width: 15 },
          { header: 'low_stock_threshold', width: 18 },
          { header: 'description', width: 30 }
        ]
        
        // Add data validation for category column (B2:B1000)
        const categoryList = this.categories.map(c => c.name).join(',')
        worksheet.dataValidations.add('B2:B1000', {
          type: 'list',
          allowBlank: false,
          showInputMessage: true,
          promptTitle: 'Category Selection',
          prompt: 'Please select a category from the list',
          showErrorMessage: true,
          errorTitle: 'Invalid Category',
          error: 'You must select a valid category from the dropdown list',
          formulae: [`"${categoryList}"`]
        })
        
        // Add data validation for warehouse column (D2:D1000) - updated from C to D due to new subcategory column
        const warehouseList = this.warehouses.map(w => w.name).join(',')
        worksheet.dataValidations.add('D2:D1000', {
          type: 'list',
          allowBlank: false,
          showInputMessage: true,
          promptTitle: 'Warehouse Selection',
          prompt: 'Please select a warehouse from the list',
          showErrorMessage: true,
          errorTitle: 'Invalid Warehouse',
          error: 'You must select a valid warehouse from the dropdown list',
          formulae: [`"${warehouseList}"`]
        })
        
        // Write to buffer and download
        const buffer = await workbook.xlsx.writeBuffer()
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `product_import_template_${new Date().toISOString().split('T')[0]}.xlsx`
        link.click()
        window.URL.revokeObjectURL(url)
        
        this.showAlert('Excel template with dropdowns downloaded successfully!', 'success')
      } catch (error) {
        console.error('Error generating Excel template:', error)
        this.showAlert('Error generating Excel template. Please try again.', 'error')
      }
    },

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
      const isCSV = file.type === 'text/csv' || file.name.endsWith('.csv')
      const isExcel = file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.type.includes('spreadsheet')
      
      if (isCSV) {
        this.csvFile = file
        this.parseCSV(file)
      } else if (isExcel) {
        this.csvFile = file
        this.parseExcel(file)
      } else {
        this.showAlert('Please select a valid CSV or Excel file', 'error')
      }
    },

    parseExcel(file) {
      const reader = new FileReader()
      reader.onload = async (e) => {
        try {
          const workbook = new ExcelJS.Workbook()
          await workbook.xlsx.load(e.target.result)
          const worksheet = workbook.worksheets[0]
          
          this.csvData = []
          worksheet.eachRow((row, rowNumber) => {
            if (rowNumber === 1) return // Skip header row
            
            const product = {}
            const headers = ['name', 'category', 'warehouse', 'cost_price', 'price', 'stock_quantity', 'low_stock_threshold', 'description']
            row.values.forEach((value, colIndex) => {
              if (colIndex > 0 && headers[colIndex - 1]) {
                product[headers[colIndex - 1]] = value || ''
              }
            })
            
            if (Object.keys(product).length > 0 && product.name) {
              this.csvData.push(product)
            }
          })
          
          this.showAlert(`Successfully loaded ${this.csvData.length} products from Excel file`, 'success')
        } catch (error) {
          console.error('Error parsing Excel file:', error)
          this.showAlert('Error parsing Excel file. Please ensure it has the correct format.', 'error')
        }
      }
      reader.readAsArrayBuffer(file)
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
      if (this.saving) return
      
      this.saving = true
      const products = this.getProductsToReview()
      
      // Show initial progress alert
      this.showAlert(`Saving ${products.length} product${products.length !== 1 ? 's' : ''}...`, 'info')
      
      try {
        if (this.selectedMethod === 'single') {
          // Single product creation
          const product = products[0]
          
          // Find warehouse ID by name if warehouse is provided
          let warehouseId = product.warehouse_id || null
          if (!warehouseId && product.warehouse) {
            const warehouse = this.warehouses.find(w => 
              w.name.toLowerCase() === product.warehouse.toLowerCase()
            )
            warehouseId = warehouse?.id || null
          }
          
          // Clean up the product data
          const cleanProduct = this.prepareProductForApi({
            name: product.name,
            price: parseFloat(product.price),
            stock_quantity: parseInt(product.stock_quantity),
            category: product.category || null,
            subcategory: product.subcategory || null,
            brand: product.brand || null,
            description: product.description || null,
            cost_price: parseFloat(product.cost_price) || null,
            sku: product.sku || null,
            low_stock_threshold: parseInt(product.low_stock_threshold) || 5,
            warehouse_id: warehouseId ? parseInt(warehouseId) : null,
            uom_id: product.uom_id ? parseInt(product.uom_id) : null,
            purchase_uom_id: product.purchase_uom_id ? parseInt(product.purchase_uom_id) : null,
            sale_uom_ids: product.sale_uom_ids || [],
            conversion_ratio: parseFloat(product.conversion_ratio) || 1,
            uom_prices: product.uom_prices || {}
          })
          
          // Create single product
          const response = await axios.post('/products', cleanProduct)
          const createdProduct = response.data?.product || response.data
          
          // Link empties if provided
          if (product.empties && product.empties.length > 0 && createdProduct?.id) {
            for (const empty of product.empties) {
              await axios.post(`/products/${createdProduct.id}/empties`, {
                empty_product_id: empty.empty_product_id,
                quantity: empty.quantity,
                deposit_amount: empty.deposit_amount
              }).catch(err => {
                console.error('Failed to link empty:', err)
              })
            }
          }
          
        } else if (this.selectedMethod === 'bulk') {
          // Bulk product creation - use bulk endpoint
          const cleanProducts = products.map(product => this.prepareProductForApi({
            name: product.name,
            price: parseFloat(product.price),
            stock_quantity: parseInt(product.stock_quantity),
            category: product.category || null,
            subcategory: product.subcategory || null,
            brand: product.brand || null,
            description: product.description || null,
            cost_price: parseFloat(product.cost_price) || null,
            sku: product.sku || null,
            low_stock_threshold: parseInt(product.low_stock_threshold) || 5,
            warehouse_id: product.warehouse_id ? parseInt(product.warehouse_id) : null,
            uom_id: product.uom_id ? parseInt(product.uom_id) : null,
            purchase_uom_id: product.purchase_uom_id ? parseInt(product.purchase_uom_id) : null,
            sale_uom_ids: product.sale_uom_ids || [],
            conversion_ratio: parseFloat(product.conversion_ratio) || 1,
            uom_prices: product.uom_prices || {}
          }))
          
          await axios.post('/products/bulk', {
            products: cleanProducts
          })
          
        } else if (this.selectedMethod === 'import') {
          // CSV/Excel import - use csv-upload endpoint
          const cleanProducts = products.map(product => this.prepareProductForApi({
            name: product.name,
            price: parseFloat(product.price) || 0,
            stock_quantity: parseInt(product.stock_quantity) || 0,
            category: product.category || null,
            subcategory: product.subcategory || null,
            brand: product.brand || null,
            description: product.description || null,
            cost_price: parseFloat(product.cost_price) || null,
            sku: product.sku || null,
            low_stock_threshold: parseInt(product.low_stock_threshold) || 5,
            warehouse_id: product.warehouse_id ? parseInt(product.warehouse_id) : null,
            uom_id: product.uom_id ? parseInt(product.uom_id) : null,
            purchase_uom_id: product.purchase_uom_id ? parseInt(product.purchase_uom_id) : null,
            sale_uom_ids: product.sale_uom_ids || [],
            conversion_ratio: parseFloat(product.conversion_ratio) || 1,
            uom_prices: product.uom_prices || {}
          }))
          
          await axios.post('/products/csv-upload', {
            products: cleanProducts
          })
        }
        
        // Show success alert with details
        this.showAlert(
          `✓ Successfully added ${products.length} product${products.length !== 1 ? 's' : ''} to inventory!`, 
          'success'
        )
        
        // Refresh product list
        await this.fetchProducts()
        
        // Close modal after brief delay to show success
        setTimeout(() => {
          this.closeModal()
        }, 500)
        
      } catch (err) {
        const errorMsg = err.response?.data?.error || err.response?.data?.message || err.message || 'Unknown error'
        const validationErrors = err.response?.data?.details || err.response?.data?.errors
        
        let fullMsg = `✗ Failed to save products: ${errorMsg}`
        
        if (validationErrors && typeof validationErrors === 'object') {
          const errorList = Object.entries(validationErrors)
            .map(([field, errors]) => `${field}: ${Array.isArray(errors) ? errors.join(', ') : errors}`)
            .slice(0, 3)
            .join('\n')
          if (errorList) {
            fullMsg += `\n\nValidation errors:\n${errorList}`
          }
        }
        
        this.showAlert(fullMsg, 'error')
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
        low_stock_threshold: 5,
        warehouse_id: '',
        uom_id: '',
        purchase_uom_id: '',
        sale_uom_ids: [],
        conversion_ratio: 1,
        uom_prices: {},
        empties: []
      }
      this.tempEmpty = {
        empty_product_id: '',
        quantity: 1,
        deposit_amount: 0
      }
      this.bulkProducts = [{
        name: '',
        category: '',
        warehouse_id: '',
        uom_id: '',
        purchase_uom_id: '',
        sale_uom_ids: [],
        conversion_ratio: 1,
        cost_price: '',
        price: '',
        stock_quantity: '',
        uom_prices: {}
      }]
      this.csvFile = null
      this.csvData = []
    },

    // UOM Pricing Methods
    getUomLabel(uomId) {
      const uom = this.uoms.find(u => u.id === uomId)
      return uom ? `${uom.name} (${uom.abbreviation})` : 'Unknown UOM'
    },

    // Get filtered UOMs based on purchase UOM type
    getFilteredSaleUoms(purchaseUomId) {
      if (!purchaseUomId) {
        return this.uoms
      }
      
      const purchaseUom = this.uoms.find(u => u.id === purchaseUomId)
      if (!purchaseUom || !purchaseUom.type) {
        return this.uoms
      }
      
      // Return only UOMs of the same type as purchase UOM
      return this.uoms.filter(u => u.type === purchaseUom.type)
    },

    calculateUomMargin(uomId) {
      const uomPrice = this.form.uom_prices[uomId]
      const costPrice = parseFloat(this.form.cost_price) || 0
      const conversionRatio = parseFloat(this.form.conversion_ratio) || 1

      if (!uomPrice || !costPrice || costPrice === 0) {
        return null
      }

      // Cost per UOM unit = cost_price / conversion_ratio
      const costPerUom = costPrice / conversionRatio
      
      // Margin = (selling_price - cost_per_uom) / cost_per_uom * 100
      const margin = ((uomPrice - costPerUom) / costPerUom) * 100
      return margin
    },

    getMarginClass(margin) {
      if (margin < 0) return 'margin-negative'
      if (margin < 20) return 'margin-low'
      if (margin < 50) return 'margin-medium'
      return 'margin-high'
    },

    applyMarkupToAllUoms() {
      const costPrice = parseFloat(this.form.cost_price) || 0
      const conversionRatio = parseFloat(this.form.conversion_ratio) || 1
      const markup = parseFloat(this.markupPercentage) || 0

      if (!costPrice || !conversionRatio) {
        this.showAlert('Please enter cost price and conversion ratio first', 'warning')
        return
      }

      const costPerUom = costPrice / conversionRatio
      const priceWithMarkup = costPerUom * (1 + markup / 100)

      // Apply to all UOMs
      this.form.sale_uom_ids.forEach(uomId => {
        this.form.uom_prices[uomId] = parseFloat(priceWithMarkup.toFixed(2))
      })

      this.showAlert(`Applied ${markup}% markup to all UOMs`, 'success')
    },

    // Quick Add Methods for Categories, Subcategories, and Returnables
    async createQuickCategory() {
      if (!this.quickAddCategory.name.trim()) {
        this.showAlert('Category name is required', 'error')
        return
      }

      this.quickAddLoading = true
      try {
        const res = await axios.post('/product-categories', {
          name: this.quickAddCategory.name,
          description: this.quickAddCategory.description || null,
          parent_id: null // Root category
        })
        
        const newCategory = res.data.category
        
        // Add new category to list with children array
        newCategory.children = newCategory.children || []
        this.categories.push(newCategory)
        
        // Set it as selected in the form
        if (this.editingProduct) {
          this.form.category = newCategory.id
          this.form.subcategory = '' // Clear subcategory when category changes
        } else if (this.showAddModal) {
          this.singleProductForm.category = newCategory.id
          this.singleProductForm.subcategory = ''
        }
        
        this.showAlert(`✓ Category "${this.quickAddCategory.name}" created successfully!`, 'success')
        this.showQuickAddCategoryModal = false
        this.quickAddCategory = { name: '', description: '' }
      } catch (err) {
        this.showAlert(
          `Failed to create category: ${err.response?.data?.error || err.message}`,
          'error'
        )
      } finally {
        this.quickAddLoading = false
      }
    },

    async createQuickSubcategory() {
      if (!this.quickAddSubcategory.name.trim()) {
        this.showAlert('Subcategory name is required', 'error')
        return
      }

      if (!this.selectedCategoryForSubcategory) {
        this.showAlert('Please select a parent category first', 'error')
        return
      }

      this.quickAddLoading = true
      try {
        const res = await axios.post('/product-categories', {
          name: this.quickAddSubcategory.name,
          description: this.quickAddSubcategory.description || null,
          parent_id: this.selectedCategoryForSubcategory
        })
        
        const newSubcategory = res.data.category
        
        // Find parent category and add subcategory to its children
        const parentCategory = this.categories.find(c => c.id === this.selectedCategoryForSubcategory)
        if (parentCategory) {
          if (!parentCategory.children) {
            parentCategory.children = []
          }
          parentCategory.children.push(newSubcategory)
        }
        
        // Set it as selected in the form
        if (this.editingProduct) {
          this.form.subcategory = newSubcategory.id
        } else if (this.showAddModal) {
          this.singleProductForm.subcategory = newSubcategory.id
        }
        
        this.showAlert(`✓ Subcategory "${this.quickAddSubcategory.name}" created successfully!`, 'success')
        this.showQuickAddSubcategoryModal = false
        this.quickAddSubcategory = { name: '', parent_id: '', description: '' }
      } catch (err) {
        this.showAlert(
          `Failed to create subcategory: ${err.response?.data?.error || err.message}`,
          'error'
        )
      } finally {
        this.quickAddLoading = false
      }
    },

    async createQuickReturnable() {
      if (!this.quickAddReturnable.name.trim()) {
        this.showAlert('Returnable name is required', 'error')
        return
      }

      if (!this.quickAddReturnable.warehouse_id) {
        this.showAlert('Please select a warehouse', 'error')
        return
      }

      this.quickAddLoading = true
      try {
        const res = await axios.post('/products', {
          name: this.quickAddReturnable.name,
          description: this.quickAddReturnable.description || null,
          sku: this.quickAddReturnable.sku || null,
          price: 0,
          cost_price: 0,
          stock_quantity: 0,
          warehouse_id: this.quickAddReturnable.warehouse_id,
          uom_id: this.quickAddReturnable.uom_id || null,
          category: 'Returnables',
          brand: 'System'
        })
        
        // Add to products list
        this.products.push(res.data.product || res.data)
        
        // Add to empties in current form if editing
        if (this.editingProduct || this.showAddModal) {
          const newEmpty = {
            empty_product_id: res.data.product?.id || res.data.id,
            quantity: 1,
            deposit_amount: 0
          }
          
          if (this.editingProduct) {
            this.form.empties.push(newEmpty)
          } else if (this.singleProductForm.empties) {
            this.singleProductForm.empties.push(newEmpty)
          }
        }
        
        this.showAlert(`✓ Returnable "${this.quickAddReturnable.name}" created successfully!`, 'success')
        this.showQuickAddReturnableModal = false
        this.quickAddReturnable = {
          name: '',
          description: '',
          sku: '',
          warehouse_id: '',
          uom_id: ''
        }
      } catch (err) {
        this.showAlert(
          `Failed to create returnable: ${err.response?.data?.error || err.message}`,
          'error'
        )
      } finally {
        this.quickAddLoading = false
      }
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
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.stat-number {
  display: block;
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  letter-spacing: -0.5px;
}

.stat-label {
  font-size: 0.85rem;
  opacity: 0.95;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
  letter-spacing: 0.5px;
  margin-top: 0.25rem;
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

/* View and Filters Section */
.view-and-filters-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 15px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: flex-end;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

/* View Mode Toggle */
.view-mode-toggle {
  display: flex;
  gap: 0.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.25rem;
  background: #f9fafb;
}

.view-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: none;
  background: transparent;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.view-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.view-btn.active {
  background: #667eea;
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.view-btn i {
  font-size: 1.1rem;
}

/* Filters Container */
.filters-container {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: flex-end;
  flex: 1;
  min-width: 300px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 180px;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  color: #374151;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-select:hover {
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Price Range Filter */
.price-range-group {
  min-width: 280px;
}

.price-inputs {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.price-input {
  flex: 1;
}

.price-field {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s ease;
}

.price-field:hover {
  border-color: #667eea;
}

.price-field:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.price-separator {
  color: #d1d5db;
  font-weight: bold;
}

/* Reset Filters Button */
.filter-reset-btn {
  padding: 0.75rem 1.25rem;
  background: #f3f4f6;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.filter-reset-btn:hover {
  background: #e5e7eb;
  color: #374151;
  border-color: #d1d5db;
}

/* Results Info Bar */
.results-info-bar {
  display: flex;
  justify-content: flex-end;
  width: 100%;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.results-count {
  font-size: 0.9rem;
  color: #6b7280;
  font-weight: 500;
}

/* Products Container */
.products-container {
  transition: all 0.3s ease;
}

.products-container.grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 2rem;
  grid-template-rows: auto auto auto;
}

.products-container.list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  grid-column: 1 / -1;
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

.empties-btn {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.empties-btn:hover {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
}

.transfer-btn {
  background: rgba(128, 90, 213, 0.1);
  color: #805ad5;
}

.transfer-btn:hover {
  background: #805ad5;
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

/* List View */
.products-list {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  grid-column: 1 / -1;
}

.list-header {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 0.8fr 1fr 1.2fr;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-bottom: 2px solid #e5e7eb;
  font-weight: 700;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.list-row {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 0.8fr 1fr 1.2fr;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  align-items: center;
  transition: all 0.2s ease;
  background: white;
}

.list-row:hover {
  background: #f9fafb;
  box-shadow: inset 0 0 0 1px rgba(102, 126, 234, 0.1);
}

.list-col-name {
  display: flex;
  align-items: center;
  min-width: 0;
}

.product-name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
  font-weight: 600;
  color: #374151;
}

.product-name-cell i {
  color: #9ca3af;
  flex-shrink: 0;
}

.product-name-cell span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.list-col-category {
  text-align: center;
}

.category-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}

.text-muted {
  color: #9ca3af;
}

.list-col-price {
  text-align: center;
}

.price-value {
  font-weight: 700;
  color: #667eea;
  font-size: 1rem;
}

.list-col-stock {
  text-align: center;
}

.stock-value {
  font-weight: 600;
  display: inline-block;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.stock-value.in-stock {
  background: #dcfce7;
  color: #166534;
}

.stock-value.low-stock {
  background: #fef08a;
  color: #92400e;
}

.stock-value.out-of-stock {
  background: #fee2e2;
  color: #991b1b;
}

.list-col-status {
  text-align: center;
}

.status-badge {
  display: inline-block;
  padding: 0.35rem 0.7rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.status-badge.in-stock {
  background: #dcfce7;
  color: #166534;
}

.status-badge.low-stock {
  background: #fef08a;
  color: #92400e;
}

.status-badge.out-of-stock {
  background: #fee2e2;
  color: #991b1b;
}

.list-col-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn-small {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e5e7eb;
  background: white;
  color: #6b7280;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.action-btn-small:hover {
  border-color: #667eea;
  background: #f9fafb;
  color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
}

.action-btn-small.edit-btn:hover {
  color: #3b82f6;
  border-color: #3b82f6;
}

.action-btn-small.delete-btn:hover {
  color: #ef4444;
  border-color: #ef4444;
}

.action-btn-small.empties-btn:hover {
  color: #10b981;
  border-color: #10b981;
}

.action-btn-small.transfer-btn:hover {
  color: #8b5cf6;
  border-color: #8b5cf6;
}

.no-results-state-list {
  text-align: center;
  padding: 3rem 2rem;
  color: #6b7280;
}

.no-results-state-list i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.no-results-state-list h3 {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
  color: #4a5568;
}

.no-results-state-list p {
  margin: 0;
  color: #9ca3af;
}

/* Pagination */
.pagination-container {
  grid-column: 1 / -1;
  margin-top: 2rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.pagination-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.total-products {
  font-weight: 600;
  color: #374151;
}

.items-per-page {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.items-per-page label {
  font-weight: 600;
  color: #6b7280;
  font-size: 0.9rem;
}

.items-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.items-select:hover {
  border-color: #667eea;
}

.items-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.pagination-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.pagination-btn {
  padding: 0.75rem 1.25rem;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #667eea;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.pagination-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  transform: translateY(-2px);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.page-number {
  min-width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  color: #6b7280;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.page-number:hover:not(:disabled) {
  border-color: #667eea;
  color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.page-number.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.page-number:disabled {
  cursor: not-allowed;
}

.pagination-status {
  text-align: center;
  color: #6b7280;
  font-weight: 600;
  font-size: 0.9rem;
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

.form-hint {
  display: block;
  font-size: 0.85rem;
  color: #718096;
  margin-top: 0.25rem;
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

/* Enhanced Edit Modal Styles */
.edit-modal-container {
  background: white;
  border-radius: 20px;
  max-width: 600px;
  width: 90%;
  height: 90vh;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease;
  display: flex;
  flex-direction: column;
}

.edit-product-form {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
  min-height: 0;
}

.edit-modal-header {
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #e2e8f0;
  flex-shrink: 0;
}

.edit-modal-header .header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.edit-modal-header .header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.edit-modal-header .header-title i {
  font-size: 1.5rem;
  color: #667eea;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 10px;
}

.edit-modal-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.edit-modal-header p {
  color: #718096;
  margin: 0.25rem 0 0 0;
  font-size: 0.9rem;
}

.modal-close-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
  cursor: pointer;
  color: #718096;
  transition: all 0.3s ease;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close-btn:hover {
  background: rgba(0, 0, 0, 0.1);
  color: #2d3748;
}

.edit-modal-body {
  overflow-y: auto;
  overflow-x: hidden;
  padding: 2rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  -webkit-overflow-scrolling: touch;
  scroll-behavior: smooth;
  min-height: 0;
}

.edit-modal-body::-webkit-scrollbar {
  width: 10px;
}

.edit-modal-body::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
  margin: 5px 0;
}

.edit-modal-body::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 10px;
  transition: background 0.2s ease;
}

.edit-modal-body::-webkit-scrollbar-thumb:hover {
  background: #667eea;
}

/* Fallback for Firefox */
* {
  scrollbar-color: #cbd5e0 #f1f5f9;
  scrollbar-width: thin;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0.5rem 0 0 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-title i {
  color: #667eea;
  font-size: 1.1rem;
}

.form-hint {
  display: block;
  font-size: 0.85rem;
  color: #718096;
  margin-top: 0.25rem;
}

.profit-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: rgba(72, 187, 120, 0.1);
  border-left: 4px solid #48bb78;
  border-radius: 8px;
  color: #22543d;
  font-weight: 600;
}

.profit-display i {
  color: #48bb78;
}

/* UOM Pricing Section */
.section-description {
  color: #718096;
  font-size: 0.9rem;
  margin: 0.5rem 0 1rem 0;
}

.uom-pricing-table {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: white;
}

.pricing-header {
  display: grid;
  grid-template-columns: 2fr 2fr 1.5fr;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-bottom: 2px solid #e2e8f0;
  font-weight: 700;
  color: #2d3748;
  font-size: 0.9rem;
}

.pricing-row {
  display: grid;
  grid-template-columns: 2fr 2fr 1.5fr;
  gap: 1rem;
  padding: 1rem;
  align-items: center;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.2s ease;
}

.pricing-row:hover {
  background: #f7fafc;
}

.pricing-row:last-child {
  border-bottom: none;
}

.col-uom {
  display: flex;
  align-items: center;
}

.uom-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
}

.col-price {
  display: flex;
}

.form-input-small {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #2d3748;
  transition: all 0.2s ease;
}

.form-input-small:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.col-margin {
  display: flex;
  align-items: center;
  justify-content: center;
}

.margin-value {
  font-weight: 700;
  font-size: 0.95rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
}

.margin-placeholder {
  color: #cbd5e0;
  font-size: 0.9rem;
}

.margin-high {
  color: #22543d;
  background: rgba(72, 187, 120, 0.1);
}

.margin-medium {
  color: #4c5282;
  background: rgba(102, 126, 234, 0.1);
}

.margin-low {
  color: #7c2d12;
  background: rgba(245, 158, 11, 0.1);
}

.margin-negative {
  color: #742a2a;
  background: rgba(245, 101, 101, 0.1);
}

/* Pricing Helper */
.pricing-helper {
  margin-top: 1.5rem;
  padding: 1.25rem;
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.markup-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: #2d3748;
}

.markup-label i {
  color: #667eea;
}

.markup-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.markup-controls .form-input-small {
  max-width: 100px;
}

.markup-symbol {
  color: #718096;
  font-weight: 600;
  font-size: 1rem;
}

.btn-apply-markup {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.btn-apply-markup:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-apply-markup:active {
  transform: translateY(0);
}

.btn-apply-markup i {
  font-size: 0.8rem;
}

.edit-modal-footer {
  padding: 1.5rem 2rem;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  border-top: 2px solid #e2e8f0;
  flex-shrink: 0;
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

  /* View and Filters Responsive */
  .view-and-filters-section {
    flex-direction: column;
    gap: 1rem;
  }

  .view-mode-toggle {
    width: 100%;
  }

  .filters-container {
    flex-direction: column;
    width: 100%;
  }

  .filter-group {
    width: 100%;
    min-width: unset;
  }

  .price-range-group {
    min-width: unset;
  }

  .filter-reset-btn {
    width: 100%;
    justify-content: center;
  }

  .results-info-bar {
    justify-content: center;
    padding-top: 0.5rem;
  }

  /* List View Responsive */
  .list-header,
  .list-row {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .list-header {
    display: none;
  }

  .list-row {
    padding: 1rem;
  }

  .list-col-name,
  .list-col-category,
  .list-col-price,
  .list-col-stock,
  .list-col-status,
  .list-col-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .list-col-name::before {
    content: 'Product: ';
    font-weight: 600;
    color: #6b7280;
  }

  .list-col-category::before {
    content: 'Category: ';
    font-weight: 600;
    color: #6b7280;
  }

  .list-col-price::before {
    content: 'Price: ';
    font-weight: 600;
    color: #6b7280;
  }

  .list-col-stock::before {
    content: 'Stock: ';
    font-weight: 600;
    color: #6b7280;
  }

  .list-col-status::before {
    content: 'Status: ';
    font-weight: 600;
    color: #6b7280;
  }

  .list-col-actions::before {
    content: 'Actions: ';
    font-weight: 600;
    color: #6b7280;
  }

  /* Pagination Responsive */
  .pagination-container {
    gap: 1rem;
  }

  .pagination-info {
    justify-content: center;
    flex-direction: column;
    gap: 0.75rem;
  }

  .items-per-page {
    justify-content: center;
    width: 100%;
  }

  .pagination-controls {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .pagination-btn {
    padding: 0.6rem 0.9rem;
    font-size: 0.85rem;
  }

  .page-number {
    min-width: 35px;
    height: 35px;
    font-size: 0.85rem;
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
  opacity: 0.95;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
  letter-spacing: 0.5px;
  margin-top: 0.25rem;
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

/* Transfer Modal Styles */
.transfer-modal {
  max-width: 600px;
}

.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.85rem;
  color: #718096;
  font-style: italic;
}

/* Empties/Returnables Section */
.empties-section {
  margin-top: 1.5rem;
  padding: 1.25rem;
  border: 1px dashed #cbd5e0;
  border-radius: 12px;
  background: #f8fafc;
}

.empties-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.05rem;
  font-weight: 700;
}

.empties-header i {
  color: #10b981;
}

.empties-subtitle {
  margin: 0 0 0.75rem 0;
  color: #718096;
  font-size: 0.9rem;
}

.linked-empties {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.empty-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.empty-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  color: #2d3748;
}

.empty-details {
  color: #718096;
  font-size: 0.9rem;
}

.remove-empty-btn {
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 8px;
  padding: 0.45rem 0.65rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.remove-empty-btn:hover {
  background: #fecaca;
}

.add-empty-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.empty-inputs {
  display: grid;
  grid-template-columns: 2fr 0.8fr 0.8fr auto;
  gap: 0.75rem;
  align-items: center;
}

.add-empty-btn {
  background: #10b981;
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.add-empty-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.add-empty-btn:hover:not(:disabled) {
  background: #0ea371;
  transform: translateY(-1px);
}

@media (max-width: 900px) {
  .empty-inputs {
    grid-template-columns: 1fr;
  }
}

/* Price Group Pricing Section */
.pricing-section {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #e2e8f0;
}

.pricing-header {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
}

.pricing-header i {
  color: #667eea;
}

.pricing-subtitle {
  color: #718096;
  font-size: 0.9rem;
  margin: 0 0 1.5rem 0;
}

.price-group-inputs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.price-group-input {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  transition: all 0.3s ease;
}

.price-group-input:hover {
  border-color: #cbd5e0;
  background: #edf2f7;
}

.price-group-input.disabled {
  opacity: 0.75;
  background: #fff7ed;
  border-color: #fed7aa;
}

.price-group-input .form-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  color: #2d3748;
  font-weight: 600;
}

.discount-label {
  font-size: 0.8rem;
  color: #718096;
  font-weight: 500;
  background: #e6fffa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.group-status {
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 999px;
  padding: 0.2rem 0.5rem;
  line-height: 1;
}

.group-status-disabled {
  color: #9a3412;
  background: #ffedd5;
}

.price-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.price-input-wrapper .currency {
  position: absolute;
  left: 12px;
  color: #a0aec0;
  font-weight: 600;
  font-size: 0.9rem;
  pointer-events: none;
}

.price-input {
  padding-left: 2.5rem !important;
}

/* Bulk Product Pricing Section */
.pricing-section-bulk {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #cbd5e0;
}

.pricing-header-bulk {
  font-size: 0.95rem;
  font-weight: 700;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
}

.pricing-header-bulk i {
  color: #667eea;
  font-size: 0.85rem;
}

.pricing-subtitle-bulk {
  color: #718096;
  font-size: 0.85rem;
  margin: 0 0 1rem 0;
}

.price-group-inputs-bulk {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.price-group-input-bulk {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
  transition: all 0.3s ease;
}

.price-group-input-bulk:hover {
  border-color: #cbd5e0;
  background: #edf2f7;
}

.price-group-input-bulk.disabled {
  opacity: 0.75;
  background: #fff7ed;
  border-color: #fed7aa;
}

.form-label-bulk {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  color: #2d3748;
  font-weight: 600;
  font-size: 0.9rem;
}

.price-input-wrapper-bulk {
  position: relative;
  display: flex;
  align-items: center;
}

.price-input-wrapper-bulk .currency {
  position: absolute;
  left: 10px;
  color: #a0aec0;
  font-weight: 600;
  font-size: 0.85rem;
  pointer-events: none;
}

/* UoM and Conversion Styles */
.form-hint {
  display: block;
  margin-top: 0.25rem;
  color: #666;
  font-size: 0.85rem;
  font-style: italic;
}

@media (max-width: 768px) {
  .price-group-inputs {
    grid-template-columns: 1fr;
  }

  .price-group-inputs-bulk {
    grid-template-columns: 1fr;
  }
}

/* Input with Action Button */
.input-with-action {
  display: flex;
  gap: 0.5rem;
  align-items: stretch;
}

.input-with-action .form-input {
  flex: 1;
}

.quick-add-btn {
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: #f7fafc;
  color: #667eea;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 50px;
}

.quick-add-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.quick-add-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Quick Add Modal */
.quick-add-modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease cubic-bezier(0.34, 1.56, 0.64, 1);
  width: 90%;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.quick-add-modal .modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #e2e8f0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.quick-add-modal .modal-header h3 {
  margin: 0;
  font-size: 1.3rem;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
}

.quick-add-modal .close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 8px;
  font-size: 1.5rem;
  color: white;
  cursor: pointer;
  padding: 0.5rem 0.75rem;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.quick-add-modal .close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.quick-add-form {
  padding: 2rem;
}

.quick-add-form .form-group {
  margin-bottom: 1.5rem;
}

.info-text {
  background: #edf2f7;
  border-left: 4px solid #667eea;
  padding: 0.75rem 1rem;
  border-radius: 5px;
  font-size: 0.9rem;
  color: #4a5568;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.modal-actions button {
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-actions .btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.modal-actions .btn-secondary:hover {
  background: #cbd5e0;
}

.modal-actions .btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.modal-actions .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>