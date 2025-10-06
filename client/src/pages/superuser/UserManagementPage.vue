<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Super User Dashboard</h1>
            <p class="text-slate-600 mt-1">Manage company registrations and approvals</p>
          </div>
          <div class="flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="font-semibold">{{ companies.length }} Companies</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification -->
    <transition name="slide">
      <div v-if="notification" 
           :class="['fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 text-white',
                    notification.type === 'success' ? 'bg-green-500' : 'bg-red-500']">
        <svg v-if="notification.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ notification.message }}</span>
      </div>
    </transition>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Filters and Search -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
          <!-- Filter Tabs -->
          <div class="flex gap-2 bg-slate-100 p-1 rounded-lg">
            <button @click="filterType = 'pending'" 
                    :class="['px-4 py-2 rounded-md font-medium transition-all',
                             filterType === 'pending' ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pending
              </div>
            </button>
            <button @click="filterType = 'approved'" 
                    :class="['px-4 py-2 rounded-md font-medium transition-all',
                             filterType === 'approved' ? 'bg-white text-green-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Approved
              </div>
            </button>
            <button @click="filterType = 'all'" 
                    :class="['px-4 py-2 rounded-md font-medium transition-all',
                             filterType === 'all' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                All
              </div>
            </button>
          </div>

          <!-- Search -->
          <div class="relative w-full md:w-96">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input v-model="searchTerm" type="text" placeholder="Search companies..."
                   class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredCompanies.length === 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <h3 class="text-xl font-semibold text-slate-900 mb-2">No companies found</h3>
        <p class="text-slate-600">Try adjusting your filters or search criteria</p>
      </div>

      <!-- Companies Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="company in filteredCompanies" :key="company.id"
             class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-all overflow-hidden group">
          
          <!-- Status Badge -->
          <div :class="['h-2', company.approved ? 'bg-green-500' : 'bg-orange-500']"></div>
          
          <div class="p-6">
            <!-- Company Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-xl font-bold text-slate-900 mb-1">{{ company.name }}</h3>
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                  {{ company.category }}
                </span>
              </div>
              <div :class="['p-2 rounded-lg', company.approved ? 'bg-green-100' : 'bg-orange-100']">
                <svg v-if="company.approved" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>

            <!-- Company Details -->
            <div class="space-y-3 mb-4">
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="text-sm truncate">{{ company.email }}</span>
              </div>
              <div class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="text-sm">{{ company.phone }}</span>
              </div>
              <div v-if="company.users && company.users.length > 0" class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-sm">{{ company.users[0].name }}</span>
              </div>
              <div v-if="company.users && company.users.length > 0 && company.users[0].position" class="flex items-center gap-3 text-slate-600">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="text-sm">{{ company.users[0].position }}</span>
              </div>
            </div>

            <!-- Registration Date -->
            <div class="text-xs text-slate-500 mb-4">
              Registered: {{ formatDate(company.created_at) }}
            </div>

            <!-- Action Buttons -->
            <div v-if="!company.approved" class="flex gap-3">
              <button @click="openApprovalModal(company)" :disabled="actionLoading"
                      class="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Approve
              </button>
              <button @click="confirmReject(company)" :disabled="actionLoading"
                      class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Reject
              </button>
            </div>
            
            <div v-else class="bg-green-50 text-green-700 text-center py-2 rounded-lg font-semibold">
              ✓ Approved
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Approval Modal -->
    <transition name="modal">
      <div v-if="showModal && selectedCompany" @click.self="showModal = false"
           class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Approve Company?</h2>
            <p class="text-slate-600">
              You are about to approve <span class="font-semibold">{{ selectedCompany.name }}</span>
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4 mb-6 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-slate-600">Company:</span>
              <span class="font-semibold text-slate-900">{{ selectedCompany.name }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-600">Email:</span>
              <span class="font-semibold text-slate-900">{{ selectedCompany.email }}</span>
            </div>
            <div v-if="selectedCompany.users && selectedCompany.users.length > 0" class="flex justify-between text-sm">
              <span class="text-slate-600">Owner:</span>
              <span class="font-semibold text-slate-900">{{ selectedCompany.users[0].name }}</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button @click="showModal = false" :disabled="actionLoading"
                    class="flex-1 bg-slate-200 hover:bg-slate-300 disabled:opacity-50 text-slate-900 font-semibold py-3 px-4 rounded-lg transition-colors">
              Cancel
            </button>
            <button @click="approveCompany" :disabled="actionLoading"
                    class="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
              <div v-if="actionLoading" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              <template v-else>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Confirm Approval
              </template>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SuperUserManagement',
  
  data() {
    return {
      companies: [],
      loading: false,
      actionLoading: false,
      filterType: 'pending',
      searchTerm: '',
      selectedCompany: null,
      showModal: false,
      notification: null,
      apiBaseUrl: 'http://localhost:8000/api' // Change this to your Laravel API URL
    };
  },

  computed: {
    filteredCompanies() {
      let filtered = this.companies;

      // Apply filter
      if (this.filterType === 'pending') {
        filtered = filtered.filter(c => !c.approved);
      } else if (this.filterType === 'approved') {
        filtered = filtered.filter(c => c.approved);
      }

      // Apply search
      if (this.searchTerm) {
        const search = this.searchTerm.toLowerCase();
        filtered = filtered.filter(c =>
          c.name.toLowerCase().includes(search) ||
          c.email.toLowerCase().includes(search) ||
          c.category.toLowerCase().includes(search)
        );
      }

      return filtered;
    }
  },

  mounted() {
    this.fetchCompanies();
  },

  methods: {
    async fetchCompanies() {
      this.loading = true;
      try {
        // Fetch pending companies
        const response = await axios.get(`${this.apiBaseUrl}/companies/pending`, {
          headers: {
            'Authorization': `Bearer ${this.getAuthToken()}`,
            'Accept': 'application/json'
          }
        });
        
        this.companies = response.data;
      } catch (error) {
        console.error('Error fetching companies:', error);
        this.showNotification('Failed to fetch companies. Please try again.', 'error');
        
        // If you want to handle specific errors
        if (error.response) {
          if (error.response.status === 401) {
            this.showNotification('Unauthorized. Please login again.', 'error');
          } else if (error.response.status === 403) {
            this.showNotification('You do not have permission to view companies.', 'error');
          }
        }
      } finally {
        this.loading = false;
      }
    },

    async approveCompany() {
      if (!this.selectedCompany) return;

      this.actionLoading = true;
      try {
        await axios.put(
          `${this.apiBaseUrl}/companies/${this.selectedCompany.id}/approve`,
          {},
          {
            headers: {
              'Authorization': `Bearer ${this.getAuthToken()}`,
              'Accept': 'application/json'
            }
          }
        );

        // Remove from list or update status
        this.companies = this.companies.filter(c => c.id !== this.selectedCompany.id);
        
        this.showNotification(`${this.selectedCompany.name} has been approved successfully!`, 'success');
        this.showModal = false;
        this.selectedCompany = null;
      } catch (error) {
        console.error('Error approving company:', error);
        this.showNotification('Failed to approve company. Please try again.', 'error');
      } finally {
        this.actionLoading = false;
      }
    },

    confirmReject(company) {
      if (confirm(`Are you sure you want to reject ${company.name}? This action cannot be undone.`)) {
        this.rejectCompany(company);
      }
    },

    async rejectCompany(company) {
      this.actionLoading = true;
      try {
        await axios.delete(
          `${this.apiBaseUrl}/companies/${company.id}/reject`,
          {
            headers: {
              'Authorization': `Bearer ${this.getAuthToken()}`,
              'Accept': 'application/json'
            }
          }
        );

        // Remove from list
        this.companies = this.companies.filter(c => c.id !== company.id);
        
        this.showNotification(`${company.name} has been rejected and removed.`, 'success');
      } catch (error) {
        console.error('Error rejecting company:', error);
        this.showNotification('Failed to reject company. Please try again.', 'error');
      } finally {
        this.actionLoading = false;
      }
    },

    openApprovalModal(company) {
      this.selectedCompany = company;
      this.showModal = true;
    },

    showNotification(message, type = 'success') {
      this.notification = { message, type };
      setTimeout(() => {
        this.notification = null;
      }, 3000);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    getAuthToken() {
      // Retrieve token from localStorage, sessionStorage, or Vuex store
      // Update this based on how you store your auth token
      return localStorage.getItem('auth_token') || '';
    }
  }
};
</script>

<style scoped>
/* Transitions */
.slide-enter-active, .slide-leave-active {
  transition: all 0.3s ease;
}
.slide-enter-from {
  transform: translateX(100%);
  opacity: 0;
}
.slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}
.modal-enter-from .bg-white {
  transform: scale(0.9);
}
.modal-leave-to .bg-white {
  transform: scale(0.9);
}

/* Utility classes */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>