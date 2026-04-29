<template>
  <div class="admin-customization">
    <!-- Alert Messages -->
    <div v-if="alert.show" :class="['alert', alert.type]">
      {{ alert.message }}
    </div>

    <div class="tabs">
      <button v-for="tab in tabs" :key="tab" :class="['tab-btn', { active: currentTab === tab }]" @click="currentTab = tab">
        {{ tab }}
      </button>
    </div>
    <div class="tab-content">
      <div v-if="currentTab === 'Product Categories'">
        <h2>Product Categories <span class="count">({{ categories.length }})</span></h2>
        <form class="custom-form" @submit.prevent="addCategory">
          <div class="form-row">
            <div class="form-group">
              <label>Category Name *</label>
              <input v-model="newCategory.name" placeholder="New category name" required />
            </div>
            <div class="form-group">
              <label>Parent Category</label>
              <select v-model="newCategory.parent_id">
                <option :value="null">-- No Parent (Root Category) --</option>
                <option v-for="cat in categories.filter(c => !c.parent_id)" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="newCategory.description" placeholder="Optional description" rows="2"></textarea>
          </div>
          <button type="submit" class="primary-btn" :disabled="loading.categories">
            <span v-if="loading.categories" class="spinner"></span>
            <span v-else>Add Category</span>
          </button>
        </form>
        <div class="csv-upload">
          <label>Bulk Upload (CSV):</label>
          <input class="file-input" type="file" @change="handleBulkUpload" accept=".csv" :disabled="loading.categories" />
          <a :href="sampleCsvUrl" download="sample_categories.csv" class="sample-link">Download Sample CSV</a>
          <small>CSV Format: Category Name, Parent Category Name (optional)</small>
        </div>
        <div v-if="loading.categories" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading categories...</p>
        </div>
        <div v-else-if="categories.length === 0" class="empty-state">
          <i class="fas fa-folder"></i>
          <p>No categories found</p>
          <small>Add your first category above</small>
        </div>
        <div v-else class="category-tree">
          <div v-for="cat in rootCategories" :key="cat.id" class="category-item">
            <div class="category-row">
              <div class="category-content">
                <span v-if="cat.children && cat.children.length > 0" 
                      class="expand-btn"
                      @click="toggleCategoryExpand(cat.id)"
                      :class="{ expanded: expandedCategories.includes(cat.id) }">
                  <i class="fas fa-chevron-right"></i>
                </span>
                <span v-else class="expand-placeholder"></span>
                <span class="category-name">{{ cat.name }}</span>
                <span v-if="cat.description" class="category-desc">{{ cat.description }}</span>
              </div>
              <div class="category-actions">
                <button class="edit-btn" @click="editCategory(cat)" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="delete-btn" @click="deleteCategory(cat.id)" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <!-- Subcategories -->
            <div v-if="expandedCategories.includes(cat.id) && cat.children && cat.children.length > 0" 
                 class="subcategories">
              <div v-for="subcat in cat.children" :key="subcat.id" class="category-item sub-category">
                <div class="category-row">
                  <div class="category-content">
                    <span class="subcategory-marker">├─</span>
                    <span class="category-name">{{ subcat.name }}</span>
                    <span v-if="subcat.description" class="category-desc">{{ subcat.description }}</span>
                  </div>
                  <div class="category-actions">
                    <button class="edit-btn" @click="editCategory(subcat)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn" @click="deleteCategory(subcat.id)" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="currentTab === 'Warehouses'">
        <h2>Warehouses <span class="count">({{ warehouses.length }})</span></h2>
        <form class="custom-form" @submit.prevent="editingWarehouse ? updateWarehouse() : addWarehouse()">
          <input v-model="newWarehouse.name" placeholder="Warehouse name" required />
          <input v-model="newWarehouse.type" placeholder="Type (main, breakages, etc.)" />
          <button type="submit" class="primary-btn" :disabled="loading.warehouses">
            <span v-if="loading.warehouses" class="spinner"></span>
            <span v-else>{{ editingWarehouse ? 'Update' : 'Add' }}</span>
          </button>
          <button v-if="editingWarehouse" type="button" class="cancel-btn" @click="cancelEdit">Cancel</button>
        </form>
        <div v-if="loading.warehouses" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading warehouses...</p>
        </div>
        <div v-else-if="warehouses.length === 0" class="empty-state">
          <i class="fas fa-warehouse"></i>
          <p>No warehouses found</p>
          <small>Add your first warehouse above</small>
        </div>
        <div v-else class="table-wrapper">
          <table class="warehouse-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="wh in warehouses" :key="wh.id">
                <td class="name-cell">{{ wh.name }}</td>
                <td><span class="type-badge">{{ wh.type || 'General' }}</span></td>
                <td class="date-cell">{{ formatDate(wh.created_at) }}</td>
                <td class="actions-cell">
                  <button class="edit-btn" @click="editWarehouse(wh)" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="delete-btn" @click="deleteWarehouse(wh.id)" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="currentTab === 'Payment Methods'">
        <div class="tab-header">
          <div>
            <h2>Payment Methods <span class="count">({{ paymentMethods.filter(pm => pm.is_enabled).length }} / {{ paymentMethods.length }} enabled)</span></h2>
            <p class="info-text">Select which payment methods your business accepts</p>
          </div>
          <button 
            v-if="hasPaymentChanges" 
            @click="savePaymentChanges" 
            class="save-btn"
            :disabled="loading.paymentMethods"
          >
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>
        
        <div v-if="loading.paymentMethods" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading payment methods...</p>
        </div>
        <div v-else class="payment-grid">
          <div 
            v-for="pm in paymentMethods" 
            :key="pm.id" 
            class="payment-card"
            :class="{ 
              enabled: pm.is_enabled,
              changed: hasChanged(pm.id),
              locked: !pm.allowed_by_subscription
            }"
            @click="togglePaymentMethodLocal(pm.id)"
          >
            <div v-if="hasChanged(pm.id)" class="change-indicator">
              <i class="fas fa-circle"></i>
            </div>
            <div class="payment-icon">
              <i :class="getPaymentIcon(pm.name)"></i>
            </div>
            <div class="payment-info">
              <div class="payment-meta">
                <h3 class="payment-name">{{ pm.name }}</h3>
                <span v-if="!pm.allowed_by_subscription" class="locked-badge">Requires {{ formatRequiredFeature(pm.required_feature) }}</span>
              </div>
              <p class="payment-desc">{{ pm.description }}</p>
              <p v-if="!pm.allowed_by_subscription" class="payment-lock-reason">{{ pm.locked_reason }}</p>
            </div>
            <div class="payment-toggle">
              <div class="toggle-switch" :class="{ active: pm.is_enabled, disabled: !pm.allowed_by_subscription }">
                <div class="toggle-slider"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="currentTab === 'Tax Configuration'">
        <div class="tab-header">
          <div>
            <h2>Tax Configuration <span class="count">({{ taxConfigs.length }} tax types)</span></h2>
            <p class="info-text">Manage tax rates and settings for your business</p>
          </div>
        </div>

        <form class="custom-form tax-form" @submit.prevent="editingTaxConfig ? updateTaxConfig() : addTaxConfig()">
          <div class="form-row">
            <div class="form-group">
              <label>Tax Name *</label>
              <input v-model="newTaxConfig.name" placeholder="e.g., VAT, Sales Tax, Excise" required />
            </div>
            <div class="form-group">
              <label>Tax Type *</label>
              <select v-model="newTaxConfig.tax_type" required>
                <option value="VAT">VAT</option>
                <option value="Excise">Excise</option>
                <option value="Withholding">Withholding</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Rate (%) *</label>
              <input v-model.number="newTaxConfig.rate" type="number" step="0.01" min="0" max="100" placeholder="0.00" required />
            </div>
            <div class="form-group">
              <label>Calculation Method *</label>
              <select v-model="newTaxConfig.is_inclusive" required>
                <option :value="false">Exclusive (add to price)</option>
                <option :value="true">Inclusive (already in price)</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input v-model="newTaxConfig.description" placeholder="e.g., Kenya standard VAT rate" />
          </div>
          <div class="form-group checkbox-group">
            <label>
              <input v-model="newTaxConfig.is_active" type="checkbox" />
              Active
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="primary-btn" :disabled="loading.taxConfigs">
              <span v-if="loading.taxConfigs" class="spinner"></span>
              <span v-else>{{ editingTaxConfig ? 'Update Tax' : 'Add Tax' }}</span>
            </button>
            <button v-if="editingTaxConfig" type="button" class="cancel-btn" @click="cancelTaxEdit">Cancel</button>
          </div>
        </form>

        <div v-if="loading.taxConfigs" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading tax configurations...</p>
        </div>
        <div v-else-if="taxConfigs.length === 0" class="empty-state">
          <i class="fas fa-percentage"></i>
          <p>No tax configurations found</p>
          <small>Add your first tax configuration above</small>
        </div>
        <div v-else class="tax-cards">
          <div v-for="tax in taxConfigs" :key="tax.id" class="tax-card" :class="{ default: tax.is_default }">
            <div class="tax-card-header">
              <div class="tax-icon">
                <i class="fas fa-percentage"></i>
              </div>
              <div class="tax-info">
                <h3>{{ tax.name }}</h3>
                <p class="tax-rate">{{ tax.rate }}% {{ tax.is_inclusive ? 'Inclusive' : 'Exclusive' }}</p>
              </div>
              <div v-if="tax.is_default" class="default-badge">Default</div>
            </div>
            <div class="tax-card-actions">
              <button class="set-default-btn" @click="setDefaultTax(tax.id)" :disabled="tax.is_default">
                <i class="fas fa-star"></i>
                {{ tax.is_default ? 'Default' : 'Set as Default' }}
              </button>
              <button class="edit-btn" @click="editTaxConfig(tax)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="delete-btn" @click="deleteTaxConfig(tax.id)" :disabled="tax.is_default">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="currentTab === 'Printer Settings'">
        <div class="tab-header">
          <div>
            <h2>Receipt Printer Settings</h2>
            <p class="info-text">Configure how receipts are printed</p>
          </div>
          <button
            v-if="hasPrinterChanges"
            @click="savePrinterSettings"
            class="save-btn"
            :disabled="loading.printerSettings"
          >
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>

        <div v-if="loading.printerSettings" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading printer settings...</p>
        </div>
        <div v-else class="printer-settings-form">
          <div class="form-section">
            <h3><i class="fas fa-image"></i> Receipt Logo</h3>
            <p class="section-info">Upload a business logo to print on receipts and invoices</p>

            <div class="logo-uploader">
              <div v-if="printerSettings.receipt_logo_url" class="logo-preview-wrap">
                <img :src="printerSettings.receipt_logo_url" alt="Receipt Logo" class="logo-preview" />
              </div>
              <div v-else class="logo-placeholder">
                <i class="fas fa-image"></i>
                <span>No logo uploaded</span>
              </div>

              <div class="logo-actions">
                <input
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                  @change="uploadPrinterLogo"
                  :disabled="logoUploading"
                />
                <button
                  type="button"
                  class="delete-btn"
                  @click="removePrinterLogo"
                  :disabled="logoUploading || !printerSettings.receipt_logo_path"
                >
                  <i :class="logoUploading ? 'fas fa-spinner fa-spin' : 'fas fa-trash'"></i>
                  Remove Logo
                </button>
              </div>
            </div>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-align-left"></i> Receipt Header</h3>
            <p class="section-info">This text appears at the top of every receipt</p>
            <textarea
              v-model="printerSettings.header_message"
              placeholder="Business Name&#10;Address Line 1&#10;Address Line 2&#10;Phone Number"
              rows="4"
              @input="checkPrinterChanges"
            ></textarea>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-align-right"></i> Receipt Footer</h3>
            <p class="section-info">This text appears at the bottom of every receipt</p>
            <textarea
              v-model="printerSettings.footer_message"
              placeholder="Thank you for your business!&#10;Return policy: 7 days with receipt&#10;Visit us again!"
              rows="4"
              @input="checkPrinterChanges"
            ></textarea>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-cog"></i> Display Options</h3>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_logo" @change="checkPrinterChanges" />
                <span>Show business logo on receipt</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_taxes" @change="checkPrinterChanges" />
                <span>Show tax breakdown on receipt</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_discounts" @change="checkPrinterChanges" />
                <span>Show discounts on receipt</span>
              </label>
            </div>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-print"></i> Print Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Paper Size</label>
                <select v-model="printerSettings.paper_size" @change="checkPrinterChanges">
                  <option value="58mm">58mm (Thermal)</option>
                  <option value="80mm">80mm (Thermal)</option>
                  <option value="A4">A4 (Standard)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Text Alignment</label>
                <select v-model="printerSettings.alignment" @change="checkPrinterChanges">
                  <option value="left">Left</option>
                  <option value="center">Center</option>
                  <option value="right">Right</option>
                </select>
              </div>
            </div>
          </div>

          <div class="preview-section">
            <h3><i class="fas fa-eye"></i> Receipt Preview</h3>
            <div class="receipt-preview">
              <div class="receipt-header">
                <img
                  v-if="printerSettings.show_logo && printerSettings.receipt_logo_url"
                  :src="printerSettings.receipt_logo_url"
                  alt="Business Logo"
                  class="receipt-preview-logo"
                />
                <div v-if="printerSettings.header_message" v-html="formatPreviewText(printerSettings.header_message)"></div>
                <div v-else class="preview-placeholder">Header will appear here</div>
              </div>
              <div class="receipt-divider">═══════════════════════</div>
              <div class="receipt-body">
                <p><strong>Receipt #:</strong> 12345</p>
                <p><strong>Date:</strong> {{ new Date().toLocaleDateString() }}</p>
                <div class="receipt-divider-thin">───────────────────────</div>
                <p>Sample Item 1 - Ksh 500.00</p>
                <p>Sample Item 2 - Ksh 300.00</p>
                <div class="receipt-divider-thin">───────────────────────</div>
                <p><strong>Subtotal:</strong> Ksh 800.00</p>
                <p v-if="printerSettings.show_discounts"><strong>Discount:</strong> - Ksh 50.00</p>
                <p v-if="printerSettings.show_taxes"><strong>VAT (16%):</strong> + Ksh 120.00</p>
                <p><strong>Total:</strong> Ksh 870.00</p>
              </div>
              <div class="receipt-divider">═══════════════════════</div>
              <div class="receipt-footer">
                <div v-if="printerSettings.footer_message" v-html="formatPreviewText(printerSettings.footer_message)"></div>
                <div v-else class="preview-placeholder">Footer will appear here</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="currentTab === 'Invoice Look'">
        <div class="tab-header">
          <div>
            <h2>Invoice Look & Feel</h2>
            <p class="info-text">Customize how invoices appear when printed</p>
          </div>
          <button
            v-if="hasPrinterChanges"
            @click="savePrinterSettings"
            class="save-btn"
            :disabled="loading.printerSettings"
          >
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>

        <div class="printer-settings-form">
          <div class="form-section">
            <h3><i class="fas fa-file-invoice"></i> Invoice Header</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Invoice Title</label>
                <input
                  v-model="printerSettings.invoice_title"
                  placeholder="INVOICE"
                  @input="checkPrinterChanges"
                />
              </div>
              <div class="form-group">
                <label>Invoice Subtitle</label>
                <input
                  v-model="printerSettings.invoice_subtitle"
                  placeholder="Thank you for your business"
                  @input="checkPrinterChanges"
                />
              </div>
            </div>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.invoice_show_logo" @change="checkPrinterChanges" />
                <span>Show company logo on printed invoice</span>
              </label>
            </div>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-align-left"></i> Invoice Footer Note</h3>
            <textarea
              v-model="printerSettings.invoice_footer_note"
              rows="3"
              placeholder="Payment due as per agreed terms"
              @input="checkPrinterChanges"
            ></textarea>
          </div>

          <div class="preview-section">
            <h3><i class="fas fa-eye"></i> Invoice Preview</h3>
            <div class="invoice-preview-card">
              <img
                v-if="printerSettings.invoice_show_logo && printerSettings.receipt_logo_url"
                :src="printerSettings.receipt_logo_url"
                alt="Invoice Logo"
                class="invoice-preview-logo"
              />
              <h4>{{ printerSettings.invoice_title || 'INVOICE' }}</h4>
              <p class="invoice-subtitle" v-if="printerSettings.invoice_subtitle">{{ printerSettings.invoice_subtitle }}</p>
              <div class="receipt-divider-thin">────────────────────────────────</div>
              <p><strong>Invoice No:</strong> INV-2026-0001</p>
              <p><strong>Date:</strong> {{ new Date().toLocaleDateString() }}</p>
              <p><strong>Customer:</strong> Sample Customer</p>
              <div class="receipt-divider-thin">────────────────────────────────</div>
              <p><strong>Total:</strong> Ksh 8,720.00</p>
              <p class="invoice-note" v-if="printerSettings.invoice_footer_note">{{ printerSettings.invoice_footer_note }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- UOMs Tab -->
      <div v-else-if="currentTab === 'UOMs'">
        <div class="tab-header">
          <div>
            <h2>Units of Measure <span class="count">({{ uoms.length }} total)</span></h2>
            <p class="info-text">Create and manage custom UOMs for your business</p>
          </div>
        </div>

        <form class="custom-form" @submit.prevent="editingUOM ? updateUOM() : addUOM()">
          <div class="form-row">
            <div class="form-group">
              <label>UOM Name *</label>
              <input v-model="newUOM.name" placeholder="e.g., Millilitre, Kilogram" required />
            </div>
            <div class="form-group">
              <label>Abbreviation *</label>
              <input v-model="newUOM.abbreviation" placeholder="e.g., ml, kg" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Type</label>
              <select v-model="newUOM.type">
                <option value="">Select Type</option>
                <option value="volume">Volume</option>
                <option value="weight">Weight</option>
                <option value="length">Length</option>
                <option value="area">Area</option>
                <option value="count">Count</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Description</label>
              <input v-model="newUOM.description" placeholder="Optional description" />
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="primary-btn" :disabled="loading.uoms">
              <span v-if="loading.uoms" class="spinner"></span>
              <span v-else>{{ editingUOM ? 'Update UOM' : 'Add UOM' }}</span>
            </button>
            <button v-if="editingUOM" type="button" class="cancel-btn" @click="cancelUOMEdit">Cancel</button>
          </div>
        </form>

        <div v-if="loading.uoms" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading UOMs...</p>
        </div>
        <div v-else-if="uoms.length === 0" class="empty-state">
          <i class="fas fa-ruler"></i>
          <p>No UOMs found</p>
          <small>Add your first UOM above</small>
        </div>
        <div v-else class="table-wrapper">
          <div class="uom-filters">
            <button 
              v-for="type in uomTypes" 
              :key="type" 
              :class="['filter-btn', { active: activeUOMFilter === type }]"
              @click="activeUOMFilter = activeUOMFilter === type ? null : type"
            >
              {{ type || 'All' }}
            </button>
          </div>
          <table class="uom-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Abbreviation</th>
                <th>Type</th>
                <th>System</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="uom in filteredUOMs" :key="uom.id" :class="{ 'system-uom': uom.is_system }">
                <td>{{ uom.name }}</td>
                <td><span class="abbreviation-badge">{{ uom.abbreviation }}</span></td>
                <td><span v-if="uom.type" class="type-badge" :class="uom.type">{{ uom.type }}</span></td>
                <td><span v-if="uom.is_system" class="system-badge">✓ System</span></td>
                <td class="description-cell">{{ uom.description || '—' }}</td>
                <td class="actions-cell">
                  <button 
                    v-if="!uom.is_system" 
                    class="edit-btn" 
                    @click="editUOM(uom)" 
                    title="Edit"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    v-if="!uom.is_system" 
                    class="delete-btn" 
                    @click="deleteUOM(uom.id)" 
                    title="Delete"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                  <span v-else class="locked-badge" title="System UOM cannot be edited">
                    <i class="fas fa-lock"></i>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="currentTab === 'Pricing Groups'">
        <div class="tab-header">
          <div>
            <h2>Pricing Groups <span class="count">({{ priceGroups.length }} total)</span></h2>
            <p class="info-text">Each company starts with Retail Default, Stockist, and Wholesale pricing groups. You can edit them or toggle them on and off here.</p>
          </div>
        </div>

        <div class="pricing-groups-help">
          <div class="help-card">
            <h3><i class="fas fa-users"></i> Customer Assignment</h3>
            <p>Assign a pricing group to each customer from the customer management form.</p>
          </div>
          <div class="help-card">
            <h3><i class="fas fa-tags"></i> Product Pricing</h3>
            <p>Set different prices for each pricing group inside the product add/edit form.</p>
          </div>
        </div>

        <form class="price-group-form" @submit.prevent="editingPriceGroup ? updatePriceGroup() : addPriceGroup()">
          <div class="form-row">
            <div class="form-group">
              <label>Group Name *</label>
              <input v-model="newPriceGroup.name" placeholder="e.g., Retail Default" required />
            </div>
            <div class="form-group">
              <label>Code *</label>
              <input v-model="newPriceGroup.code" placeholder="e.g., RETAIL_DEFAULT" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Discount %</label>
              <input v-model.number="newPriceGroup.discount_percentage" type="number" min="0" max="100" step="0.01" placeholder="0.00" required />
            </div>
            <div class="form-group">
              <label>Description</label>
              <input v-model="newPriceGroup.description" placeholder="Who this group is meant for" />
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="primary-btn" :disabled="loading.priceGroups">
              <span v-if="loading.priceGroups" class="spinner"></span>
              <span v-else>{{ editingPriceGroup ? 'Update Group' : 'Add Group' }}</span>
            </button>
            <button v-if="editingPriceGroup" type="button" class="cancel-btn" @click="cancelPriceGroupEdit">Cancel</button>
          </div>
        </form>

        <div v-if="loading.priceGroups" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading pricing groups...</p>
        </div>
        <div v-else-if="priceGroups.length === 0" class="empty-state">
          <i class="fas fa-layer-group"></i>
          <p>No pricing groups found</p>
          <small>Default groups will appear automatically for this company</small>
        </div>
        <div v-else class="price-group-grid">
          <div
            v-for="group in priceGroups"
            :key="group.id"
            class="price-group-card"
            :class="{ enabled: group.is_enabled !== false, system: group.is_system }"
          >
            <div class="price-group-top">
              <div class="price-group-icon">
                <i class="fas fa-tags"></i>
              </div>
              <div class="price-group-info">
                <h3 class="price-group-name">{{ group.name }}</h3>
                <p class="group-description">{{ group.description || 'No description provided' }}</p>
                <p class="group-code">{{ group.code }}</p>
              </div>
              <div class="payment-toggle">
                <label class="pricing-toggle" :title="isRetailDefaultGroup(group) ? 'Retail Default must stay enabled' : (group.is_enabled ? 'Disable pricing group' : 'Enable pricing group')">
                  <input
                    type="checkbox"
                    :checked="group.is_enabled !== false"
                    :disabled="loading.priceGroups || isRetailDefaultGroup(group)"
                    @change="togglePriceGroup(group)"
                    :aria-label="`${group.name} pricing group toggle`"
                  />
                  <span class="pricing-toggle-switch" :class="{ active: group.is_enabled !== false }">
                    <span class="pricing-toggle-slider"></span>
                  </span>
                </label>
              </div>
            </div>

            <div class="price-group-meta">
              <span class="discount-badge">{{ Number(group.discount_percentage || 0).toFixed(2) }}% off</span>
              <span class="status-badge" :class="group.is_enabled ? 'enabled' : 'disabled'">
                {{ isRetailDefaultGroup(group) ? 'Always On' : (group.is_enabled ? 'Enabled' : 'Disabled') }}
              </span>
              <span v-if="group.is_system" class="system-badge">Default</span>
              <span v-else class="custom-badge">Custom</span>
            </div>

            <div class="price-group-actions">
              <span class="toggle-text">{{ isRetailDefaultGroup(group) ? 'Always On' : (group.is_enabled ? 'On' : 'Off') }}</span>
              <button class="edit-btn" @click="editPriceGroup(group)" title="Edit pricing group">
                <i class="fas fa-edit"></i>
              </button>
              <button class="delete-btn" @click="deletePriceGroup(group.id)" :disabled="group.is_system" title="Delete pricing group">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const tabs = ['Product Categories', 'Warehouses', 'Payment Methods', 'Tax Configuration', 'Printer Settings', 'Invoice Look', 'UOMs', 'Pricing Groups'];
const currentTab = ref(tabs[0]);

const categories = ref([]);
const newCategory = ref({ name: '', parent_id: null, description: '' });
const expandedCategories = ref([]);
const warehouses = ref([]);
const newWarehouse = ref({ name: '', type: '' });
const paymentMethods = ref([]);
const originalPaymentStates = ref({});
const hasPaymentChanges = ref(false);
const editingWarehouse = ref(null);
const currentUser = ref(null);
const priceGroups = ref([]);
const newPriceGroup = ref({
  name: '',
  code: '',
  description: '',
  discount_percentage: 0
});
const editingPriceGroup = ref(null);

// Computed root categories
const rootCategories = computed(() => {
  return categories.value.filter(c => !c.parent_id);
});

// Tax Configuration state
const taxConfigs = ref([]);
const newTaxConfig = ref({ 
  name: '', 
  tax_type: 'VAT',
  rate: 0, 
  is_inclusive: false,
  is_active: true,
  description: ''
});
const editingTaxConfig = ref(null);

// Printer Settings state
const printerSettings = ref({
  header_message: '',
  footer_message: '',
  show_logo: true,
  receipt_logo_path: null,
  receipt_logo_url: null,
  show_taxes: true,
  show_discounts: true,
  paper_size: '58mm',
  alignment: 'center',
  invoice_title: 'INVOICE',
  invoice_subtitle: '',
  invoice_footer_note: '',
  invoice_show_logo: true,
});
const originalPrinterSettings = ref({});
const hasPrinterChanges = ref(false);
const logoUploading = ref(false);

// UOMs state
const uoms = ref([]);
const newUOM = ref({ 
  name: '', 
  abbreviation: '', 
  type: '',
  description: ''
});
const editingUOM = ref(null);
const activeUOMFilter = ref(null);
const uomTypes = ['volume', 'weight', 'length', 'area', 'count', 'other'];

const loading = ref({
  categories: false,
  warehouses: false,
  paymentMethods: false,
  taxConfigs: false,
  printerSettings: false,
  uoms: false,
  priceGroups: false
});

const alert = ref({
  show: false,
  type: 'success',
  message: ''
});

const sampleCsvUrl = '/sample_categories.csv';

const showAlert = (message, type = 'success') => {
  alert.value = { show: true, message, type };
  setTimeout(() => {
    alert.value.show = false;
  }, 3000);
};

const fetchCategories = async () => {
  loading.value.categories = true;
  try {
    const res = await axios.get('/product-categories');
    categories.value = res.data;
  } catch (error) {
    showAlert('Failed to load categories', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const toggleCategoryExpand = (id) => {
  if (expandedCategories.value.includes(id)) {
    expandedCategories.value = expandedCategories.value.filter(c => c !== id);
  } else {
    expandedCategories.value.push(id);
  }
};

const editCategory = (category) => {
  newCategory.value = {
    name: category.name,
    parent_id: category.parent_id,
    description: category.description,
    id: category.id
  };
  // Scroll to form
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const addCategory = async () => {
  if (!newCategory.value.name.trim()) {
    showAlert('Category name is required', 'error');
    return;
  }

  loading.value.categories = true;
  try {
    const payload = {
      name: newCategory.value.name,
      parent_id: newCategory.value.parent_id,
      description: newCategory.value.description
    };

    if (newCategory.value.id) {
      // Update existing category
      await axios.put(`/product-categories/${newCategory.value.id}`, payload);
      showAlert('Category updated successfully');
    } else {
      // Create new category
      await axios.post('/product-categories', payload);
      showAlert('Category added successfully');
    }
    
    newCategory.value = { name: '', parent_id: null, description: '' };
    await fetchCategories();
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to save category', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const deleteCategory = async (id) => {
  if (!confirm('Are you sure you want to delete this category and all its subcategories?')) return;
  loading.value.categories = true;
  try {
    await axios.delete(`/product-categories/${id}`);
    await fetchCategories();
    showAlert('Category deleted successfully');
  } catch (error) {
    showAlert('Failed to delete category', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const handleBulkUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  loading.value.categories = true;
  try {
    const formData = new FormData();
    formData.append('csv_file', file);
    await axios.post('/product-categories/bulk-upload', formData);
    await fetchCategories();
    showAlert('Categories imported successfully');
  } catch (error) {
    showAlert('Failed to import categories', 'error');
  } finally {
    loading.value.categories = false;
    e.target.value = '';
  }
};

const fetchWarehouses = async () => {
  loading.value.warehouses = true;
  try {
    const res = await axios.get('/warehouses');
    // Handle both direct array and wrapped response
    warehouses.value = res.data.warehouses || res.data;
  } catch (error) {
    showAlert('Failed to load warehouses', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const fetchCurrentUser = async () => {
  try {
    const res = await axios.get('/user');
    // Handle the user response - it could be wrapped or direct
    const userData = res.data.data || res.data;
    currentUser.value = userData;
    console.log('Current user:', currentUser.value);
  } catch (error) {
    console.error('Failed to fetch user:', error);
    // Fallback: try to get company_id from localStorage
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        currentUser.value = JSON.parse(storedUser);
      } catch (e) {
        console.error('Failed to parse stored user:', e);
      }
    }
  }
};

const addWarehouse = async () => {
  if (!currentUser.value) {
    showAlert('User not loaded. Please refresh the page.', 'error');
    return;
  }

  if (!currentUser.value.company_id) {
    showAlert(`Company ID not found. User company_id: ${currentUser.value.company_id}`, 'error');
    return;
  }
  
  loading.value.warehouses = true;
  try {
    console.log('Creating warehouse with company_id:', currentUser.value.company_id);
    await axios.post('/warehouses', {
      name: newWarehouse.value.name,
      type: newWarehouse.value.type,
      company_id: currentUser.value.company_id
    });
    newWarehouse.value = { name: '', type: '' };
    await fetchWarehouses();
    showAlert('Warehouse added successfully');
  } catch (error) {
    console.error('Warehouse creation error:', error);
    showAlert(error.response?.data?.message || 'Failed to add warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const editWarehouse = (warehouse) => {
  editingWarehouse.value = warehouse.id;
  newWarehouse.value = { name: warehouse.name, type: warehouse.type };
};

const updateWarehouse = async () => {
  if (!confirm('Are you sure you want to update this warehouse?')) return;
  loading.value.warehouses = true;
  try {
    await axios.put(`/warehouses/${editingWarehouse.value}`, newWarehouse.value);
    newWarehouse.value = { name: '', type: '' };
    editingWarehouse.value = null;
    await fetchWarehouses();
    showAlert('Warehouse updated successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to update warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const cancelEdit = () => {
  editingWarehouse.value = null;
  newWarehouse.value = { name: '', type: '' };
};

const deleteWarehouse = async (id) => {
  if (!confirm('Are you sure you want to delete this warehouse? This action cannot be undone.')) return;
  loading.value.warehouses = true;
  try {
    await axios.delete(`/warehouses/${id}`);
    await fetchWarehouses();
    showAlert('Warehouse deleted successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to delete warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const fetchPaymentMethods = async () => {
  loading.value.paymentMethods = true;
  try {
    const res = await axios.get('/payment-methods');
    paymentMethods.value = Array.isArray(res.data) ? res.data : [];
    // Store original states
    originalPaymentStates.value = {};
    paymentMethods.value.forEach(pm => {
      originalPaymentStates.value[pm.id] = pm.is_enabled;
    });
    hasPaymentChanges.value = false;
  } catch (error) {
    console.error('Payment methods error:', error);
    showAlert('Failed to load payment methods', 'error');
    paymentMethods.value = [];
  } finally {
    loading.value.paymentMethods = false;
  }
};

const resetPriceGroupForm = () => {
  newPriceGroup.value = {
    name: '',
    code: '',
    description: '',
    discount_percentage: 0
  }
  editingPriceGroup.value = null
}

const fetchPriceGroups = async () => {
  loading.value.priceGroups = true
  try {
    const res = await axios.get('/price-groups', {
      params: { include_disabled: 1 }
    })
    priceGroups.value = Array.isArray(res.data)
      ? res.data.map((group) => ({
        ...group,
        is_enabled: group.is_enabled === undefined || group.is_enabled === null
          ? true
          : Boolean(group.is_enabled)
      }))
      : []
  } catch (error) {
    console.error('Price groups error:', error)
    showAlert(error.response?.data?.message || 'Failed to load pricing groups', 'error')
    priceGroups.value = []
  } finally {
    loading.value.priceGroups = false
  }
}

const addPriceGroup = async () => {
  loading.value.priceGroups = true
  try {
    await axios.post('/price-groups', newPriceGroup.value)
    await fetchPriceGroups()
    resetPriceGroupForm()
    showAlert('Pricing group added successfully')
  } catch (error) {
    const validationMessage = error.response?.data?.errors
      ? Object.values(error.response.data.errors).flat().join(', ')
      : null
    showAlert(validationMessage || error.response?.data?.message || 'Failed to add pricing group', 'error')
  } finally {
    loading.value.priceGroups = false
  }
}

const editPriceGroup = (group) => {
  editingPriceGroup.value = group.id
  newPriceGroup.value = {
    name: group.name,
    code: group.code,
    description: group.description || '',
    discount_percentage: Number(group.discount_percentage || 0)
  }
}

const updatePriceGroup = async () => {
  if (!editingPriceGroup.value) return

  loading.value.priceGroups = true
  try {
    await axios.put(`/price-groups/${editingPriceGroup.value}`, newPriceGroup.value)
    await fetchPriceGroups()
    resetPriceGroupForm()
    showAlert('Pricing group updated successfully')
  } catch (error) {
    const validationMessage = error.response?.data?.errors
      ? Object.values(error.response.data.errors).flat().join(', ')
      : null
    showAlert(validationMessage || error.response?.data?.message || 'Failed to update pricing group', 'error')
  } finally {
    loading.value.priceGroups = false
  }
}

const cancelPriceGroupEdit = () => {
  resetPriceGroupForm()
}

const deletePriceGroup = async (id) => {
  if (!confirm('Are you sure you want to delete this pricing group?')) return

  loading.value.priceGroups = true
  try {
    await axios.delete(`/price-groups/${id}`)
    await fetchPriceGroups()
    showAlert('Pricing group deleted successfully')
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to delete pricing group', 'error')
  } finally {
    loading.value.priceGroups = false
  }
}

const togglePriceGroup = async (group) => {
  if (isRetailDefaultGroup(group)) {
    showAlert('Retail Default pricing group must always remain enabled', 'warning')
    return
  }

  loading.value.priceGroups = true
  try {
    const res = await axios.post(`/price-groups/${group.id}/toggle`)
    const updatedGroup = res.data?.data

    if (updatedGroup?.id) {
      const idx = priceGroups.value.findIndex((item) => Number(item.id) === Number(updatedGroup.id))
      if (idx !== -1) {
        priceGroups.value[idx] = {
          ...priceGroups.value[idx],
          ...updatedGroup,
          is_enabled: updatedGroup.is_enabled === undefined || updatedGroup.is_enabled === null
            ? true
            : Boolean(updatedGroup.is_enabled)
        }
      }
    } else {
      await fetchPriceGroups()
    }

    showAlert(res.data?.message || 'Pricing group status updated successfully')
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to update pricing group status', 'error')
  } finally {
    loading.value.priceGroups = false
  }
}

const normalizePricingGroupIdentifier = (value) => {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

const isRetailDefaultGroup = (group) => {
  if (!group) return false

  const code = normalizePricingGroupIdentifier(group.code)
  const name = normalizePricingGroupIdentifier(group.name)

  return code === 'retail_default' || name === 'retail_default' || code === 'retail' || name === 'retail'
}

const togglePaymentMethodLocal = (id) => {
  const method = paymentMethods.value.find(pm => pm.id === id);
  if (method) {
    if (!method.allowed_by_subscription) {
      showAlert(`${method.name} is not available in your current subscription.`, 'warning');
      return;
    }
    method.is_enabled = !method.is_enabled;
    checkForChanges();
  }
};

const hasChanged = (id) => {
  const method = paymentMethods.value.find(pm => pm.id === id);
  return method && originalPaymentStates.value[id] !== method.is_enabled;
};

const checkForChanges = () => {
  hasPaymentChanges.value = paymentMethods.value.some(pm => 
    originalPaymentStates.value[pm.id] !== pm.is_enabled
  );
};

const savePaymentChanges = async () => {
  loading.value.paymentMethods = true;
  try {
    const changedMethods = paymentMethods.value.filter(pm => 
      pm.allowed_by_subscription && originalPaymentStates.value[pm.id] !== pm.is_enabled
    );

    let savedCount = 0;
    for (const method of changedMethods) {
      try {
        await axios.post(`/payment-methods/${method.id}/set-enabled`, {
          is_enabled: !!method.is_enabled
        });
        originalPaymentStates.value[method.id] = method.is_enabled;
        savedCount++;
      } catch (error) {
        console.error(`Failed to toggle ${method.name}:`, error);
        // Revert this specific method on error
        method.is_enabled = originalPaymentStates.value[method.id];
      }
    }

    // Re-read from backend so local vars always match server truth.
    await fetchPaymentMethods();
    
    if (savedCount === changedMethods.length) {
      showAlert(`Successfully updated ${savedCount} payment method${savedCount !== 1 ? 's' : ''}`);
    } else {
      showAlert(`Updated ${savedCount} of ${changedMethods.length} payment methods`, 'warning');
    }
  } catch (error) {
    showAlert('Failed to save changes', 'error');
  } finally {
    loading.value.paymentMethods = false;
  }
};

const formatRequiredFeature = (feature) => {
  const labels = {
    mpesa: 'M-Pesa Integration'
  };

  return labels[feature] || feature || 'subscription access';
};

const getPaymentIcon = (name) => {
  const icons = {
    'Cash': 'fas fa-money-bill-wave',
    'M-Pesa': 'fas fa-mobile-alt',
    'Bank Transfer': 'fas fa-university',
    'Credit Card': 'fas fa-credit-card',
    'Debit Card': 'far fa-credit-card',
    'Cheque': 'fas fa-money-check',
    'Credit/Invoice': 'fas fa-file-invoice-dollar',
    'PayPal': 'fab fa-paypal'
  }
  return icons[name] || 'fas fa-money-bill'
}

const fetchTaxConfigs = async () => {
  loading.value.taxConfigs = true;
  try {
    const res = await axios.get('/api/tax-configurations');
    taxConfigs.value = Array.isArray(res.data) ? res.data : [];
    
    // If currently editing a tax, check if it still exists
    if (editingTaxConfig.value && !taxConfigs.value.some(t => t.id === editingTaxConfig.value)) {
      showAlert('The tax configuration you were editing was deleted.', 'error');
      editingTaxConfig.value = null;
      newTaxConfig.value = { 
        name: '', 
        tax_type: 'VAT',
        rate: 0, 
        is_inclusive: false,
        is_active: true,
        description: ''
      };
    }
  } catch (error) {
    console.error('Tax configs error:', error);
    showAlert('Failed to load tax configurations', 'error');
    taxConfigs.value = [];
  } finally {
    loading.value.taxConfigs = false;
  }
};

const addTaxConfig = async () => {
  loading.value.taxConfigs = true;
  try {
    await axios.post('/api/tax-configurations', newTaxConfig.value);
    newTaxConfig.value = { 
      name: '', 
      tax_type: 'VAT',
      rate: 0, 
      is_inclusive: false,
      is_active: true,
      description: ''
    };
    await fetchTaxConfigs();
    showAlert('Tax configuration added successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to add tax configuration', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const editTaxConfig = (tax) => {
  editingTaxConfig.value = tax.id;
  newTaxConfig.value = { 
    name: tax.name,
    tax_type: tax.tax_type || 'VAT',
    rate: tax.rate, 
    is_inclusive: tax.is_inclusive,
    is_active: tax.is_active !== false,
    description: tax.description || ''
  };
};

const updateTaxConfig = async () => {
  if (!confirm('Are you sure you want to update this tax configuration?')) return;
  loading.value.taxConfigs = true;
  try {
    await axios.put(`/api/tax-configurations/${editingTaxConfig.value}`, newTaxConfig.value);
    newTaxConfig.value = { 
      name: '', 
      tax_type: 'VAT',
      rate: 0, 
      is_inclusive: false,
      is_active: true,
      description: ''
    };
    editingTaxConfig.value = null;
    await fetchTaxConfigs();
    showAlert('Tax configuration updated successfully');
  } catch (error) {
    if (error.response?.status === 404) {
      showAlert('This tax configuration was deleted. Refreshing list...', 'error');
      editingTaxConfig.value = null;
      await fetchTaxConfigs();
    } else {
      showAlert(error.response?.data?.message || 'Failed to update tax configuration', 'error');
    }
  } finally {
    loading.value.taxConfigs = false;
  }
};

const cancelTaxEdit = () => {
  editingTaxConfig.value = null;
  newTaxConfig.value = { 
    name: '', 
    tax_type: 'VAT',
    rate: 0, 
    is_inclusive: false,
    is_active: true,
    description: ''
  };
};

const deleteTaxConfig = async (id) => {
  if (!confirm('Are you sure you want to delete this tax configuration?')) return;
  loading.value.taxConfigs = true;
  try {
    await axios.delete(`/api/tax-configurations/${id}`);
    await fetchTaxConfigs();
    showAlert('Tax configuration deleted successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to delete tax configuration', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const setDefaultTax = async (id) => {
  loading.value.taxConfigs = true;
  try {
    await axios.post(`/api/tax-configurations/${id}/set-default`);
    await fetchTaxConfigs();
    showAlert('Default tax configuration updated');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to set default tax', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const fetchPrinterSettings = async () => {
  loading.value.printerSettings = true;
  try {
    const res = await axios.get('/api/printer-settings');
    // Initialize with defaults if no settings exist
    printerSettings.value = {
      header_message: res.data.header_message || '',
      footer_message: res.data.footer_message || '',
      show_logo: res.data.show_logo !== undefined ? res.data.show_logo : true,
      receipt_logo_path: res.data.receipt_logo_path || null,
      receipt_logo_url: res.data.receipt_logo_url || null,
      show_taxes: res.data.show_taxes !== undefined ? res.data.show_taxes : true,
      show_discounts: res.data.show_discounts !== undefined ? res.data.show_discounts : true,
      paper_size: res.data.paper_size || '58mm',
      alignment: res.data.alignment || 'center',
      invoice_title: res.data.invoice_title || 'INVOICE',
      invoice_subtitle: res.data.invoice_subtitle || '',
      invoice_footer_note: res.data.invoice_footer_note || '',
      invoice_show_logo: res.data.invoice_show_logo !== undefined ? res.data.invoice_show_logo : true,
    };
    originalPrinterSettings.value = JSON.parse(JSON.stringify(printerSettings.value));
    hasPrinterChanges.value = false;
  } catch (error) {
    console.error('Printer settings error:', error);
    // If settings don't exist, keep defaults
    if (error.response?.status === 404) {
      showAlert('No printer settings found. Using defaults. Save to create configuration.', 'info');
    } else {
      showAlert('Failed to load printer settings', 'error');
    }
  } finally {
    loading.value.printerSettings = false;
  }
};

const checkPrinterChanges = () => {
  hasPrinterChanges.value = JSON.stringify(printerSettings.value) !== JSON.stringify(originalPrinterSettings.value);
};

const savePrinterSettings = async () => {
  loading.value.printerSettings = true;
  try {
    // Use PUT instead of POST to update existing settings
    await axios.put('/api/printer-settings', printerSettings.value);
    originalPrinterSettings.value = JSON.parse(JSON.stringify(printerSettings.value));
    hasPrinterChanges.value = false;
    showAlert('Printer settings saved successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to save printer settings', 'error');
  } finally {
    loading.value.printerSettings = false;
  }
};

const uploadPrinterLogo = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  logoUploading.value = true;
  try {
    const formData = new FormData();
    formData.append('logo', file);
    const res = await axios.post('/api/printer-settings/logo', formData);

    printerSettings.value.receipt_logo_path = res.data.receipt_logo_path || null;
    printerSettings.value.receipt_logo_url = res.data.receipt_logo_url || null;
    originalPrinterSettings.value.receipt_logo_path = printerSettings.value.receipt_logo_path;
    originalPrinterSettings.value.receipt_logo_url = printerSettings.value.receipt_logo_url;
    checkPrinterChanges();
    showAlert('Printer logo uploaded successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to upload logo', 'error');
  } finally {
    logoUploading.value = false;
    event.target.value = '';
  }
};

const removePrinterLogo = async () => {
  if (!printerSettings.value.receipt_logo_path) return;
  logoUploading.value = true;
  try {
    await axios.delete('/api/printer-settings/logo');
    printerSettings.value.receipt_logo_path = null;
    printerSettings.value.receipt_logo_url = null;
    originalPrinterSettings.value.receipt_logo_path = null;
    originalPrinterSettings.value.receipt_logo_url = null;
    checkPrinterChanges();
    showAlert('Printer logo removed successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to remove logo', 'error');
  } finally {
    logoUploading.value = false;
  }
};

const formatPreviewText = (text) => {
  return text.split('\n').map(line => `<p>${line || '&nbsp;'}</p>`).join('');
};

// UOMs computed property
const filteredUOMs = computed(() => {
  if (!activeUOMFilter.value) return uoms.value;
  return uoms.value.filter(uom => uom.type === activeUOMFilter.value);
});

// UOMs methods
const fetchUOMs = async () => {
  loading.value.uoms = true;
  try {
    const res = await axios.get('/uoms');
    uoms.value = res.data;
  } catch (error) {
    console.error('UOMs error:', error);
    showAlert('Failed to load UOMs', 'error');
  } finally {
    loading.value.uoms = false;
  }
};

const addUOM = async () => {
  if (!newUOM.value.name || !newUOM.value.abbreviation) {
    showAlert('Name and Abbreviation are required', 'error');
    return;
  }

  loading.value.uoms = true;
  try {
    await axios.post('/uoms', {
      name: newUOM.value.name,
      abbreviation: newUOM.value.abbreviation,
      type: newUOM.value.type || 'other',
      description: newUOM.value.description,
      is_system: false
    });
    newUOM.value = { name: '', abbreviation: '', type: '', description: '' };
    await fetchUOMs();
    showAlert('UOM added successfully');
  } catch (error) {
    const errMsg = error.response?.data?.message || error.message;
    showAlert(`Failed to add UOM: ${errMsg}`, 'error');
  } finally {
    loading.value.uoms = false;
  }
};

const editUOM = (uom) => {
  editingUOM.value = uom.id;
  newUOM.value = {
    name: uom.name,
    abbreviation: uom.abbreviation,
    type: uom.type || '',
    description: uom.description
  };
};

const updateUOM = async () => {
  if (!newUOM.value.name || !newUOM.value.abbreviation) {
    showAlert('Name and Abbreviation are required', 'error');
    return;
  }

  loading.value.uoms = true;
  try {
    await axios.put(`/uoms/${editingUOM.value}`, {
      name: newUOM.value.name,
      abbreviation: newUOM.value.abbreviation,
      type: newUOM.value.type || 'other',
      description: newUOM.value.description
    });
    newUOM.value = { name: '', abbreviation: '', type: '', description: '' };
    editingUOM.value = null;
    await fetchUOMs();
    showAlert('UOM updated successfully');
  } catch (error) {
    const errMsg = error.response?.data?.message || error.message;
    showAlert(`Failed to update UOM: ${errMsg}`, 'error');
  } finally {
    loading.value.uoms = false;
  }
};

const cancelUOMEdit = () => {
  editingUOM.value = null;
  newUOM.value = { name: '', abbreviation: '', type: '', description: '' };
};

const deleteUOM = async (id) => {
  if (!confirm('Are you sure you want to delete this UOM? This action cannot be undone.')) return;
  
  loading.value.uoms = true;
  try {
    await axios.delete(`/uoms/${id}`);
    await fetchUOMs();
    showAlert('UOM deleted successfully');
  } catch (error) {
    const errMsg = error.response?.data?.message || error.message;
    showAlert(`Failed to delete UOM: ${errMsg}`, 'error');
  } finally {
    loading.value.uoms = false;
  }
};

onMounted(() => {
  fetchCurrentUser();
  fetchCategories();
  fetchWarehouses();
  fetchPaymentMethods();
  fetchTaxConfigs();
  fetchPrinterSettings();
  fetchUOMs();
  fetchPriceGroups();
});
</script>

<style scoped>
.admin-customization {
  padding: 2rem 0;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-weight: 500;
  animation: slideDown 0.3s ease;
}

.alert.success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}

.alert.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}
.tab-btn {
  background: #f3f4f6;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px 8px 0 0;
  font-weight: 600;
  cursor: pointer;
  color: #374151;
  transition: background 0.2s, color 0.2s;
}
.tab-btn.active {
  background: #fff;
  color: #3b82f6;
  border-bottom: 2px solid #3b82f6;
}
.tab-content {
  background: #fff;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.07);
  padding: 2rem 1.5rem 1.5rem 1.5rem;
}
.custom-form {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.custom-form input,
.custom-form select {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  background: #f9fafb;
  transition: border 0.2s;
}
.custom-form input:focus,
.custom-form select:focus {
  border-color: #3b82f6;
  outline: none;
}
.primary-btn {
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.primary-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
.primary-btn:hover {
  background: #2563eb;
}
.file-input {
  margin-bottom: 1rem;
}
.sample-link {
  display: inline-block;
  margin-left: 1rem;
  color: #3b82f6;
  text-decoration: underline;
  font-size: 0.95rem;
}
.item-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.item-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f1f1;
}
.item-list li:last-child {
  border-bottom: none;
}
.delete-btn {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.15s;
}
.delete-btn:hover {
  background: #fee2e2;
}
.csv-upload {
  margin-bottom: 1rem;
}
.count {
  color: #6b7280;
  font-size: 0.9rem;
  font-weight: 400;
}
.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
.spinner.large {
  width: 40px;
  height: 40px;
  border-width: 4px;
  border-color: #3b82f6;
  border-top-color: transparent;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #6b7280;
}
.loading-container p {
  margin-top: 1rem;
  font-size: 0.95rem;
}
.type-badge {
  display: inline-block;
  background: #e0e7ff;
  color: #4f46e5;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 0.5rem;
}
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  font-size: 1.1rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #6b7280;
}

.empty-state small {
  font-size: 0.9rem;
  color: #9ca3af;
}

.warehouse-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.warehouse-name {
  font-weight: 500;
  color: #1f2937;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.edit-btn {
  background: #dbeafe;
  border: none;
  color: #2563eb;
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0.4rem 0.6rem;
  border-radius: 4px;
  transition: background 0.15s;
}

.edit-btn:hover {
  background: #bfdbfe;
}

.cancel-btn {
  background: #e5e7eb;
  color: #374151;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.cancel-btn:hover {
  background: #d1d5db;
}

.table-wrapper {
  overflow-x: auto;
  margin-top: 1.5rem;
}

.warehouse-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.warehouse-table thead {
  background: #f3f4f6;
}

.warehouse-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.warehouse-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #1f2937;
}

.warehouse-table tbody tr:hover {
  background: #f9fafb;
}

.warehouse-table tbody tr:last-child td {
  border-bottom: none;
}

.name-cell {
  font-weight: 500;
}

.date-cell {
  font-size: 0.9rem;
  color: #6b7280;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.info-text {
  color: #6b7280;
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

.payment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.payment-card {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.payment-card.locked {
  opacity: 0.72;
  border-style: dashed;
}

.payment-card.locked:hover {
  transform: none;
}

.payment-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.payment-card.enabled {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-color: #10b981;
}

.payment-card.enabled::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 60px 60px 0;
  border-color: transparent #10b981 transparent transparent;
}

.payment-card.enabled::after {
  content: '✓';
  position: absolute;
  top: 8px;
  right: 8px;
  color: white;
  font-size: 18px;
  font-weight: bold;
  z-index: 1;
}

.payment-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #3b82f6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.payment-card.enabled .payment-icon {
  background: #10b981;
  color: white;
}

.payment-card:hover .payment-icon {
  transform: scale(1.1);
}

.payment-info {
  flex: 1;
}

.payment-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.payment-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.locked-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: #fff7ed;
  color: #c2410c;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}

.payment-desc {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

.payment-lock-reason {
  margin-top: 0.35rem;
  color: #b45309;
  font-size: 0.82rem;
}

.payment-toggle {
  display: flex;
  justify-content: flex-end;
}

.toggle-switch {
  width: 52px;
  height: 28px;
  background: #d1d5db;
  border-radius: 14px;
  position: relative;
  transition: background 0.3s ease;
}

.toggle-switch.active {
  background: #10b981;
}

.toggle-switch.disabled {
  background: #cbd5e1;
}

.toggle-slider {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  background: white;
  border-radius: 50%;
  transition: transform 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-switch.active .toggle-slider {
  transform: translateX(24px);
}

/* Tax & Printer Settings Styles */
.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
}

.tab-header h2 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.75rem;
  font-weight: 700;
}

.tab-header > div:first-child {
  flex: 1;
}

.save-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.875rem 1.75rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  white-space: nowrap;
  height: fit-content;
}

.save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.save-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
  opacity: 0.6;
}

.save-btn i {
  font-size: 1rem;
}

/* Tax Form Styles */
.tax-form {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto auto;
  gap: 0.75rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #bae6fd;
  margin-bottom: 2rem;
}

.tax-form input,
.tax-form select {
  padding: 0.75rem 1rem;
  border: 1px solid #e0e7ff;
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
  transition: all 0.3s ease;
}

.tax-form input:focus,
.tax-form select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Tax Cards Grid */
.tax-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.tax-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  padding: 1.75rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.tax-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
}

.tax-card.default::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.tax-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
  border-color: #3b82f6;
}

.tax-card.default {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #fbbf24;
}

.tax-card.default:hover {
  border-color: #f59e0b;
}

.tax-card-header {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 2px solid #f3f4f6;
}

.tax-card.default .tax-card-header {
  border-bottom-color: #fde68a;
}

.tax-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
  transition: all 0.3s ease;
}

.tax-card:hover .tax-icon {
  transform: scale(1.1) rotate(5deg);
}

.tax-card.default .tax-icon {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.tax-info {
  flex: 1;
}

.tax-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  letter-spacing: -0.01em;
}

.tax-rate {
  margin: 0;
  font-size: 1rem;
  color: #6b7280;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tax-rate::before {
  content: '📊';
  font-size: 1.1rem;
}

.default-badge {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white;
  padding: 0.375rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.default-badge::before {
  content: '⭐';
}

.tax-card-actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 1.25rem;
}

.set-default-btn {
  flex: 1;
  background: white;
  border: 2px solid #e5e7eb;
  color: #374151;
  padding: 0.625rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.set-default-btn i {
  color: #9ca3af;
  transition: color 0.3s ease;
}

.set-default-btn:not(:disabled):hover {
  background: #fffbeb;
  border-color: #fbbf24;
  color: #f59e0b;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.set-default-btn:not(:disabled):hover i {
  color: #f59e0b;
}

.set-default-btn:disabled {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
  border-color: #e5e7eb;
}

.set-default-btn:disabled i {
  color: #d1d5db;
}

.pricing-groups-help {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.help-card {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem 1.25rem;
}

.help-card h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.help-card p {
  margin: 0;
  color: #6b7280;
  line-height: 1.5;
}

.preset-groups-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
}

.preset-groups-panel h3 {
  margin: 0 0 0.25rem 0;
  color: #1e3a8a;
}

.preset-groups-panel p {
  margin: 0;
  color: #475569;
}

.price-group-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.price-group-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.price-group-card {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.price-group-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.price-group-card.enabled {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-color: #10b981;
}

.price-group-card.enabled::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 60px 60px 0;
  border-color: transparent #10b981 transparent transparent;
}

.price-group-card.enabled::after {
  content: '✓';
  position: absolute;
  top: 8px;
  right: 8px;
  color: white;
  font-size: 18px;
  font-weight: bold;
  z-index: 1;
}

.price-group-top {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.price-group-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #3b82f6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.price-group-card.enabled .price-group-icon {
  background: #10b981;
  color: white;
}

.price-group-card:hover .price-group-icon {
  transform: scale(1.1);
}

.price-group-info {
  flex: 1;
}

.price-group-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.group-description {
  margin: 0.45rem 0;
  color: #6b7280;
  line-height: 1.4;
  font-size: 0.875rem;
}

.group-code {
  margin: 0;
  color: #64748b;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.price-group-meta {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.discount-badge {
  background: #dbeafe;
  color: #1d4ed8;
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
  font-weight: 700;
  white-space: nowrap;
  font-size: 0.78rem;
}

.status-badge {
  border-radius: 999px;
  padding: 0.3rem 0.7rem;
  font-weight: 700;
  font-size: 0.78rem;
}

.status-badge.enabled {
  background: rgba(16, 185, 129, 0.16);
  color: #047857;
}

.status-badge.disabled {
  background: rgba(148, 163, 184, 0.18);
  color: #475569;
}

.custom-badge,
.system-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
}

.custom-badge {
  background: #dcfce7;
  color: #166534;
}

.system-badge {
  background: #ede9fe;
  color: #6d28d9;
}

.price-group-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: space-between;
  align-items: center;
}

.secondary-btn.small {
  padding: 0.55rem 0.85rem;
  font-size: 0.82rem;
}

.pricing-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  cursor: pointer;
  user-select: none;
}

.pricing-toggle input {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
  pointer-events: none;
}

.pricing-toggle-switch {
  position: relative;
  width: 52px;
  height: 28px;
  border-radius: 999px;
  background: #d1d5db;
  transition: background 0.3s ease;
}

.pricing-toggle-switch.active {
  background: #10b981;
}

.pricing-toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: white;
  transition: transform 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.pricing-toggle-switch.active .pricing-toggle-slider {
  transform: translateX(24px);
}

.pricing-toggle input:focus-visible + .pricing-toggle-switch {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}

.pricing-toggle input:disabled + .pricing-toggle-switch {
  opacity: 0.55;
  cursor: not-allowed;
}

.pricing-toggle input:disabled ~ .toggle-text {
  opacity: 0.7;
}

.toggle-text {
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #334155;
  min-width: 72px;
}

.tax-card-actions .edit-btn,
.tax-card-actions .delete-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 2px solid;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.tax-card-actions .edit-btn {
  background: #dbeafe;
  border-color: #93c5fd;
  color: #2563eb;
}

.tax-card-actions .edit-btn:hover {
  background: #2563eb;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.tax-card-actions .delete-btn {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.tax-card-actions .delete-btn:hover:not(:disabled) {
  background: #dc2626;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.tax-card-actions .delete-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: #f3f4f6;
  border-color: #e5e7eb;
  color: #9ca3af;
}

/* Printer Settings Styles */
.printer-settings-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  border: 2px solid #e5e7eb;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.form-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
}

.form-section:hover {
  border-color: #3b82f6;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.1);
}

.form-section h3 {
  margin: 0 0 0.75rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-section h3 i {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-radius: 10px;
  color: #2563eb;
  font-size: 1.1rem;
}

.section-info {
  color: #6b7280;
  font-size: 0.95rem;
  margin: 0 0 1.25rem 0;
  line-height: 1.6;
}

.form-section textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 0.95rem;
  font-family: 'Courier New', monospace;
  resize: vertical;
  background: #f9fafb;
  transition: all 0.3s ease;
  line-height: 1.6;
}

.form-section textarea:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  font-size: 1rem;
  color: #374151;
  font-weight: 500;
  padding: 1rem;
  border-radius: 10px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  transition: all 0.3s ease;
}

.checkbox-label:hover {
  background: white;
  border-color: #3b82f6;
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.checkbox-label input[type="checkbox"] {
  width: 22px;
  height: 22px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.checkbox-label input[type="checkbox"]:checked + span {
  color: #2563eb;
  font-weight: 600;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label::before {
  content: '•';
  color: #3b82f6;
  font-size: 1.5rem;
}

.form-group select {
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #f9fafb;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #374151;
}

.form-group select:hover {
  border-color: #cbd5e0;
}

.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.preview-section {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  padding: 2rem;
  border-radius: 16px;
  border: 2px dashed #3b82f6;
  position: relative;
  margin-top: 1rem;
}

.preview-section::before {
  content: '👁️ LIVE PREVIEW';
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: white;
  padding: 0.25rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.preview-section h3 {
  margin: 1rem 0 1.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.preview-section h3 i {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-radius: 10px;
  color: #2563eb;
}

.receipt-preview {
  background: white;
  max-width: 420px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 12px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.7;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
}

.receipt-preview::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 8px;
  background: repeating-linear-gradient(
    45deg,
    #3b82f6,
    #3b82f6 10px,
    #8b5cf6 10px,
    #8b5cf6 20px
  );
  border-radius: 12px 12px 0 0;
}

.receipt-header,
.receipt-footer {
  text-align: center;
  padding: 0.5rem 0;
}

.logo-uploader {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.logo-preview-wrap {
  width: 120px;
  height: 120px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  overflow: hidden;
}

.logo-preview {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.logo-placeholder {
  width: 120px;
  height: 120px;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.logo-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.receipt-preview-logo {
  max-width: 90px;
  max-height: 90px;
  object-fit: contain;
  margin: 0 auto 0.75rem auto;
  display: block;
}

.invoice-preview-card {
  background: white;
  max-width: 500px;
  margin: 0 auto;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.invoice-preview-logo {
  max-width: 110px;
  max-height: 80px;
  object-fit: contain;
  margin-bottom: 0.75rem;
}

.invoice-preview-card h4 {
  margin: 0;
  font-size: 1.25rem;
  color: #111827;
}

.invoice-subtitle {
  margin: 0.3rem 0 0.75rem 0;
  color: #6b7280;
}

.invoice-note {
  margin-top: 1rem;
  font-style: italic;
  color: #475569;
}

/* UOM Styles */
.uom-filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s ease;
  text-transform: capitalize;
}

.filter-btn:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.filter-btn.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.uom-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.uom-table thead {
  background: #f3f4f6;
}

.uom-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  border-bottom: 2px solid #e5e7eb;
}

.uom-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.uom-table tbody tr:hover {
  background: #f9fafb;
}

.uom-table tbody tr.system-uom {
  background: #fafafa;
}

.abbreviation-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f0f0f0;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.type-badge.volume {
  background: #dbeafe;
  color: #1e40af;
}

.type-badge.weight {
  background: #fce7f3;
  color: #9d174d;
}

.type-badge.length {
  background: #dcfce7;
  color: #166534;
}

.type-badge.area {
  background: #fed7aa;
  color: #92400e;
}

.type-badge.count {
  background: #f3e8ff;
  color: #6b21a8;
}

.type-badge.other {
  background: #f3f4f6;
  color: #374151;
}

.system-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #d1d5db;
  color: #374151;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.locked-badge {
  color: #9ca3af;
  cursor: not-allowed;
}

.description-cell {
  color: #6b7280;
  font-size: 0.875rem;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.receipt-header p,
.receipt-footer p {
  margin: 3px 0;
  color: #374151;
}

.preview-placeholder {
  color: #9ca3af;
  font-style: italic;
  padding: 1.5rem 0;
  text-align: center;
  background: #f9fafb;
  border-radius: 8px;
  border: 2px dashed #d1d5db;
}

.receipt-divider {
  text-align: center;
  margin: 1rem 0;
  color: #4b5563;
  font-size: 11px;
  letter-spacing: 1px;
}

.receipt-divider-thin {
  text-align: center;
  margin: 0.75rem 0;
  color: #9ca3af;
  font-size: 11px;
}

.receipt-body {
  margin: 1rem 0;
}

.receipt-body p {
  margin: 8px 0;
  color: #1f2937;
}

.receipt-body p strong {
  color: #111827;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .tax-form {
    grid-template-columns: 1fr 1fr;
  }

  .tax-form .cancel-btn {
    grid-column: 1 / -1;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .save-btn {
    width: 100%;
    justify-content: center;
  }

  .tax-form {
    grid-template-columns: 1fr;
  }

  .tax-cards {
    grid-template-columns: 1fr;
  }

  .tax-card-actions {
    flex-wrap: wrap;
  }

  .set-default-btn {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .receipt-preview {
    max-width: 100%;
    font-size: 12px;
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .tab-header h2 {
    font-size: 1.5rem;
  }

  .tax-card-actions {
    gap: 0.5rem;
  }

  .tax-card-actions .edit-btn,
  .tax-card-actions .delete-btn {
    width: 40px;
    height: 40px;
    font-size: 0.9rem;
  }

  .form-section {
    padding: 1.5rem 1rem;
  }

  .checkbox-label {
    padding: 0.75rem;
    font-size: 0.95rem;
  }
}

/* Tax Configuration Form Styles */
.tax-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.95rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.checkbox-group {
  flex-direction: row;
  align-items: center;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  font-weight: 500;
  cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.form-actions button {
  flex: 1;
  max-width: 200px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .tax-form {
    padding: 1.5rem;
    gap: 1rem;
  }
}

/* Category Tree Styles */
.category-tree {
  margin-top: 2rem;
}

.category-item {
  margin-bottom: 0.5rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.category-item:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.category-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  gap: 1rem;
}

.category-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.expand-btn {
  cursor: pointer;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 6px;
  color: #667eea;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.expand-btn:hover {
  background: #667eea;
  color: white;
}

.expand-btn.expanded i {
  transform: rotate(90deg);
}

.expand-placeholder {
  width: 24px;
  flex-shrink: 0;
}

.category-name {
  font-weight: 600;
  color: #1f2937;
}

.category-desc {
  color: #6b7280;
  font-size: 0.875rem;
  margin-left: auto;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.subcategories {
  background: #f9fafb;
  padding: 0.5rem 0;
}

.sub-category {
  margin: 0.5rem 1rem;
  border-color: #d1d5db;
  background: white;
}

.subcategory-marker {
  color: #9ca3af;
  font-size: 0.875rem;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
}

.empty-state i {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.csv-upload {
  background: #f0f9ff;
  border: 1px dashed #0284c7;
  padding: 1.5rem;
  border-radius: 8px;
  margin: 1.5rem 0;
}

.csv-upload label {
  display: block;
  font-weight: 600;
  color: #1e40af;
  margin-bottom: 0.75rem;
}

.csv-upload small {
  display: block;
  color: #0c4a6e;
  margin-top: 0.5rem;
  font-size: 0.85rem;
}

.file-input {
  padding: 0.75rem;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
}

.sample-link {
  display: inline-block;
  margin-top: 0.75rem;
  color: #0284c7;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s ease;
}

.sample-link:hover {
  color: #0369a1;
  text-decoration: underline;
}
</style>
