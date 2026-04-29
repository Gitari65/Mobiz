<template>
  <div class="uom-selector-component">
    <!-- Selected UOMs Display -->
    <div class="selected-uoms-display">
      <div v-if="selectedUoms && selectedUoms.length > 0" class="selected-tags">
        <div v-for="(uomId, index) in selectedUoms" :key="uomId" class="uom-tag">
          <span class="uom-name">{{ getUomLabel(uomId) }}</span>
          <button 
            type="button" 
            @click="removeUom(index)" 
            class="remove-btn"
            :title="`Remove ${getUomLabel(uomId)}`"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div v-else class="empty-state">
        <i class="fas fa-layer-group"></i>
        <span>No UOMs selected</span>
      </div>
    </div>

    <!-- Dropdown Trigger Button -->
    <button 
      type="button"
      class="dropdown-trigger"
      @click="isOpen = !isOpen"
      :class="{ 'is-open': isOpen }"
    >
      <i class="fas fa-plus"></i>
      <span>Add UOM</span>
      <i class="fas fa-chevron-down"></i>
    </button>

    <!-- Dropdown Menu with Checklist -->
    <div v-if="isOpen" class="dropdown-menu" @click.stop>
      <div class="dropdown-header">
        <span class="header-title">
          <i class="fas fa-check-square"></i>
          Select Sale UOMs
        </span>
        <button 
          type="button"
          class="close-dropdown"
          @click="isOpen = false"
          title="Close"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="dropdown-search">
        <i class="fas fa-search"></i>
        <input 
          v-model="searchQuery"
          type="text"
          class="search-input"
          placeholder="Search UOMs..."
          @keydown.escape="isOpen = false"
        />
      </div>

      <div class="uom-list">
        <label 
          v-for="uom in filteredUoms"
          :key="uom.id"
          class="uom-checkbox-item"
        >
          <input 
            type="checkbox"
            :checked="isSelected(uom.id)"
            @change="toggleUom(uom.id)"
            class="checkbox-input"
          />
          <span class="checkbox-custom"></span>
          <div class="uom-info">
            <span class="uom-name">{{ uom.name }}</span>
            <span class="uom-abbr">{{ uom.abbreviation }}</span>
          </div>
        </label>

        <!-- Empty State -->
        <div v-if="filteredUoms.length === 0" class="empty-search">
          <i class="fas fa-search-minus"></i>
          <span>No UOMs match "{{ searchQuery }}"</span>
        </div>
      </div>

      <div class="dropdown-footer">
        <small class="hint">
          <i class="fas fa-info-circle"></i>
          First selected UOM will be the default
        </small>
        <button 
          type="button"
          class="btn-done"
          @click="isOpen = false"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UOMSelector',
  props: {
    modelValue: {
      type: Array,
      default: () => [],
    },
    uoms: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      isOpen: false,
      searchQuery: '',
    }
  },
  computed: {
    selectedUoms: {
      get() {
        return this.modelValue || []
      },
      set(value) {
        this.$emit('update:modelValue', value)
      },
    },
    filteredUoms() {
      return this.uoms.filter(uom => {
        const query = this.searchQuery.toLowerCase()
        return (
          !this.isSelected(uom.id) && (
            uom.name.toLowerCase().includes(query) ||
            uom.abbreviation.toLowerCase().includes(query)
          )
        )
      })
    },
  },
  methods: {
    isSelected(uomId) {
      return this.selectedUoms.includes(uomId)
    },
    toggleUom(uomId) {
      if (this.isSelected(uomId)) {
        this.removeUom(this.selectedUoms.indexOf(uomId))
      } else {
        this.selectedUoms.push(uomId)
        this.$emit('update:modelValue', [...this.selectedUoms])
      }
    },
    removeUom(index) {
      this.selectedUoms.splice(index, 1)
      this.$emit('update:modelValue', [...this.selectedUoms])
    },
    getUomLabel(uomId) {
      const uom = this.uoms.find(u => u.id === uomId)
      return uom ? `${uom.name} (${uom.abbreviation})` : 'Unknown UOM'
    },
  },
  watch: {
    isOpen(newVal) {
      if (!newVal) {
        this.searchQuery = ''
      }
    },
  },
}
</script>

<style scoped lang="scss">
.uom-selector-component {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

// Selected UOMs Display
.selected-uoms-display {
  display: flex;
  align-items: center;
  min-height: 2.5rem;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.uom-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  animation: slideIn 0.2s ease;

  .uom-name {
    margin-right: 0.25rem;
  }

  .remove-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.75rem;

    &:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: scale(1.1);
    }

    i {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
}

.empty-state {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #a0aec0;
  font-size: 0.9rem;

  i {
    font-size: 1rem;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-4px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

// Dropdown Trigger
.dropdown-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  color: #667eea;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;

  i:last-child {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
  }

  &:hover {
    border-color: #667eea;
    background: #f7fafc;
  }

  &.is-open {
    border-color: #667eea;
    background: #f0f4ff;
    color: #667eea;

    i:last-child {
      transform: rotate(180deg);
    }
  }
}

// Dropdown Menu
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.5rem;
  background: white;
  border: 2px solid #667eea;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  max-height: 400px;
  overflow: hidden;
  animation: dropdownOpen 0.2s ease;
}

@keyframes dropdownOpen {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);

  .header-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #2d3748;
    font-weight: 700;
    font-size: 0.95rem;

    i {
      color: #667eea;
    }
  }

  .close-dropdown {
    background: rgba(0, 0, 0, 0.05);
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #718096;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      background: rgba(0, 0, 0, 0.1);
      color: #2d3748;
    }
  }
}

.dropdown-search {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: white;

  i {
    color: #a0aec0;
    font-size: 0.9rem;
  }

  .search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.9rem;
    color: #2d3748;

    &::placeholder {
      color: #cbd5e0;
    }
  }
}

// UOM List
.uom-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f5f9;
  }

  &::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;

    &:hover {
      background: #a0aec0;
    }
  }
}

.uom-checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 1px solid #f1f5f9;
  user-select: none;

  &:hover {
    background: #f7fafc;
  }

  &:last-child {
    border-bottom: none;
  }

  .checkbox-input {
    display: none;
  }

  .checkbox-custom {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e0;
    border-radius: 4px;
    background: white;
    transition: all 0.2s ease;
    flex-shrink: 0;

    &::after {
      content: '✓';
      color: white;
      font-size: 0.75rem;
      font-weight: bold;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
  }

  .checkbox-input:checked ~ .checkbox-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;

    &::after {
      opacity: 1;
    }
  }

  .uom-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;

    .uom-name {
      color: #2d3748;
      font-weight: 600;
      font-size: 0.95rem;
    }

    .uom-abbr {
      color: #a0aec0;
      font-size: 0.8rem;
    }
  }
}

.empty-search {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem 1rem;
  color: #a0aec0;
  text-align: center;

  i {
    font-size: 2rem;
    opacity: 0.5;
  }
}

// Dropdown Footer
.dropdown-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
  background: #f7fafc;

  .hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #718096;
    font-size: 0.85rem;

    i {
      color: #667eea;
    }
  }

  .btn-done {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1.25rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    &:active {
      transform: translateY(0);
    }
  }
}

// Responsive
@media (max-width: 480px) {
  .dropdown-menu {
    max-height: 300px;
  }

  .uom-checkbox-item {
    padding: 0.5rem 0.75rem;
  }

  .dropdown-footer {
    flex-direction: column;
    gap: 0.75rem;

    .btn-done {
      width: 100%;
    }
  }
}
</style>
