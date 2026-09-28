<template>
  <div class="detail panel" v-if="offer">
    <NuxtLink to="/" class="back">← Back to offers</NuxtLink>
    <div class="badge">{{ offer.channel }}</div><h1>{{ offer.title }}</h1>
    <p>{{ offer.description }}</p><p class="muted">{{ offer.retailer }} · {{ offer.city || 'Online' }} · {{ offer.product?.brand }}</p>
    <p class="price">{{ (offer.price_minor/100).toFixed(2) }} {{ offer.currency }} <s v-if="offer.original_price_minor">{{ (offer.original_price_minor/100).toFixed(2) }}</s></p>
    <p v-if="offer.valid_until">Valid until {{ new Date(offer.valid_until).toLocaleDateString() }}</p>
    <p>Last seen {{ new Date(offer.seen_at).toLocaleString() }}</p>
    <a class="button" :href="offer.url" target="_blank" rel="noopener noreferrer">View on retailer site ↗</a>
  </div>
</template>
<script setup lang="ts">
const {request}=useApi(); const route=useRoute()
const {data:offer}=await useAsyncData('offer-'+route.params.id,()=>request<any>('/offers/'+route.params.id))
</script>
