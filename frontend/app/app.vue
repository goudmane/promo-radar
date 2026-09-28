<template>
  <div>
    <header class="topbar">
      <NuxtLink class="logo" to="/">promo<span>radar</span><i>●</i></NuxtLink>
      <nav><NuxtLink to="/">Explore</NuxtLink><NuxtLink to="/watches">Watches</NuxtLink><NuxtLink to="/notifications">Inbox</NuxtLink><NuxtLink v-if="user?.is_owner" to="/admin">Sources</NuxtLink></nav>
      <NuxtLink v-if="!user" class="button small" to="/login">Log in</NuxtLink>
      <button v-else class="subtle" @click="logout">Log out</button>
    </header>
    <main><NuxtPage /></main>
    <footer>Always confirm prices and availability with the retailer before buying.</footer>
  </div>
</template>
<script setup lang="ts">
const { user, restore, logout } = useApi()
onMounted(restore)
</script>
