<template>
  <div class="cart-sidebar" :class="{ show: show }">
    <button class="close-btn" @click="$emit('close')">×</button>
    <h3>Cart</h3>
    <div v-if="cartItems.length === 0">Cart is empty.</div>
    <ul>
      <li v-for="item in cartItems" :key="item.id">
        {{ item.name }} (x{{ item.quantity }}) - Ksh {{ item.price * item.quantity }}
        <button @click="$emit('remove', item.id)">Remove</button>
      </li>
    </ul>
    <hr />
    <p><strong>Total:</strong> Ksh {{ total }}</p>
    <button class="checkout" @click="$emit('checkout')">Checkout</button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCartStore } from '../stores/cartStore';

const cartStore = useCartStore();

const cartItems = computed(() => cartStore.cartItems);
const totalPrice = computed(() => cartStore.totalPrice);
</script>


<style scoped>
.cart-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 300px;
  height: 100%;
  background: #fff;
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
  padding: 20px;
  overflow-y: auto;
  transition: right 0.3s ease;
  z-index: 1000;
}
.cart-sidebar.show {
  right: 0;
}
.close-btn {
  font-size: 20px;
  float: right;
  background: none;
  border: none;
  cursor: pointer;
}
ul {
  list-style: none;
  padding: 0;
}
li {
  margin-bottom: 10px;
}
.checkout {
  margin-top: 20px;
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px;
  width: 100%;
  cursor: pointer;
  border-radius: 5px;
}
</style>
