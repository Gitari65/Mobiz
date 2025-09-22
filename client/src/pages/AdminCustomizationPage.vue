<template>
  <div class="admin-customization">
    <h2>Product Categories</h2>
    <form @submit.prevent="addCategory">
      <input v-model="newCategory" placeholder="New category name" required />
      <button type="submit">Add Category</button>
    </form>
    <input type="file" @change="handleBulkUpload" accept=".csv" />
    <ul>
      <li v-for="cat in categories" :key="cat.id">
        {{ cat.name }}
        <button @click="deleteCategory(cat.id)">Delete</button>
      </li>
    </ul>
    <h2>Users & Roles</h2>
    <form @submit.prevent="addUser">
      <input v-model="newUser.name" placeholder="Name" required />
      <input v-model="newUser.email" placeholder="Email" required />
      <select v-model="newUser.role_id" required>
        <option v-for="role in roles" :value="role.id" :key="role.id">{{ role.name }}</option>
      </select>
      <button type="submit">Add User</button>
    </form>
    <ul>
      <li v-for="user in users" :key="user.id">
        {{ user.name }} ({{ user.email }}) - {{ user.role?.name || 'Unassigned' }}
      </li>
    </ul>

    <h2>Warehouses</h2>
    <form @submit.prevent="addWarehouse">
      <input v-model="newWarehouse.name" placeholder="Warehouse name" required />
      <input v-model="newWarehouse.type" placeholder="Type (main, breakages, etc.)" />
      <button type="submit">Add Warehouse</button>
    </form>
    <ul>
      <li v-for="wh in warehouses" :key="wh.id">
        {{ wh.name }} ({{ wh.type || 'N/A' }})
        <button @click="deleteWarehouse(wh.id)">Delete</button>
      </li>
    </ul>

    <h2>Payment Methods</h2>
    <form @submit.prevent="addPaymentMethod">
      <input v-model="newPaymentMethod" placeholder="New payment method" required />
      <button type="submit">Add Payment</button>
    </form>
    <ul>
      <li v-for="pm in paymentMethods" :key="pm">
        {{ pm }}
        <button @click="deletePaymentMethod(pm)">Delete</button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const categories = ref([]);
const newCategory = ref('');
const users = ref([]);
const newUser = ref({ name: '', email: '', role_id: '' });
const roles = ref([]);

const warehouses = ref([]);
const newWarehouse = ref({ name: '', type: '' });
const paymentMethods = ref([]);
const newPaymentMethod = ref('');

const fetchCategories = async () => {
  const res = await axios.get('/business-categories');
  categories.value = res.data;
};
const addCategory = async () => {
  await axios.post('/business-categories', { name: newCategory.value });
  newCategory.value = '';
  fetchCategories();
};
const deleteCategory = async (id) => {
  await axios.delete(`/business-categories/${id}`);
  fetchCategories();
};
const handleBulkUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('csv_file', file);
  await axios.post('/business-categories/import-csv', formData);
  fetchCategories();
};
const fetchUsers = async () => {
  const res = await axios.get('/users');
  users.value = res.data;
};
const fetchRoles = async () => {
  const res = await axios.get('/roles');
  roles.value = res.data;
};
const addUser = async () => {
  await axios.post('/register', newUser.value);
  newUser.value = { name: '', email: '', role_id: '' };
  fetchUsers();
};
const fetchWarehouses = async () => {
  const res = await axios.get('/warehouses');
  warehouses.value = res.data;
};
const addWarehouse = async () => {
  await axios.post('/warehouses', newWarehouse.value);
  newWarehouse.value = { name: '', type: '' };
  fetchWarehouses();
};
const deleteWarehouse = async (id) => {
  await axios.delete(`/warehouses/${id}`);
  fetchWarehouses();
};
const fetchPaymentMethods = async () => {
  const res = await axios.get('/expenses/payment-methods');
  paymentMethods.value = res.data;
};
const addPaymentMethod = async () => {
  await axios.post('/expenses/payment-methods', { name: newPaymentMethod.value });
  newPaymentMethod.value = '';
  fetchPaymentMethods();
};
const deletePaymentMethod = async (name) => {
  await axios.delete(`/expenses/payment-methods/${encodeURIComponent(name)}`);
  fetchPaymentMethods();
};
onMounted(() => {
  fetchCategories();
  fetchUsers();
  fetchRoles();
  fetchWarehouses();
  fetchPaymentMethods();
});
</script>

<style scoped>
.admin-customization {
  max-width: 600px;
  margin: 0 auto;
}
.admin-customization h2 {
  margin-top: 2rem;
}
.admin-customization form {
  margin-bottom: 1rem;
}
</style>
