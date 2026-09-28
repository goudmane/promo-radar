<template>
  <article class="card">
    <div class="cardtop"><span class="badge">{{ offer.channel }}</span><span>{{ offer.city || 'Online' }}</span></div>
    <div class="placeholder" v-if="!offer.image_url">◎</div>
    <img v-else class="picture" :src="offer.image_url" :alt="offer.title" loading="lazy">
    <p class="muted">{{ offer.retailer }} · {{ offer.product?.brand || offer.product?.category || 'Offer' }}</p>
    <h3>{{ offer.title }}</h3>
    <div class="grow"></div>
    <div class="price">{{ (offer.price_minor/100).toFixed(2) }} {{ offer.currency }} <s v-if="offer.original_price_minor">{{ (offer.original_price_minor/100).toFixed(2) }}</s></div>
    <div class="actions"><NuxtLink class="outline" :to="'/offers/'+offer.id">Details</NuxtLink><a class="button" :href="offer.url" rel="noopener noreferrer" target="_blank">Retailer ↗</a></div>
  </article>
</template>
<script setup lang="ts">
defineProps<{offer:any}>()
</script>
