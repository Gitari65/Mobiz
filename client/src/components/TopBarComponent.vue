<template>

  <div class="top-bar">
    <div class="logo">POS System</div>
    <div class="search-bar">
      <input type="text" placeholder="Search..." v-model="searchQuery" />
    </div>
    <div class="actions">
      <button @click="toggleCartSidebar">🛒 Cart ({{ cartItems.length }})</button>
      <button @click="$emit('toggleNotifications')">🔔 Notifications</button>
      <button @click="$emit('toggleProfile')">👤 Profile</button>
    </div>
    <div class="user-info">
      <span>{{ user.name }}</span>
      <img :src="user.avatar" alt="User Avatar" class="avatar" />
    </div>
    </div>


</template>
<script>
export default {
  data() {
    return {
      searchQuery: '',
      cartItems: [],
      user: {
        name: 'John Doe',
        avatar: 'path/to/avatar.jpg'
      }
    };
  },
  methods: {
    toggleCartSidebar() {
      // Logic to toggle cart sidebar
    }
  }
};
</script>
<style scoped>
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px;
  background-color: #333;
  color: white;
}
.logo {
  font-size: 1.5em;
  font-weight: bold;
}
.search-bar {
  flex-grow: 1;
  margin: 0 20px;
}
.search-bar input {
  width: 100%;
  padding: 8px;
  border-radius: 4px;
  border: none;
}
.actions {
  display: flex;
  gap: 10px;
}
.actions button {
  background: #3490dc;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}
.actions button:hover {
  background: #2779bd;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

</style>