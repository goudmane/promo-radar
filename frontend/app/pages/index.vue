<template>
  <section class="hero"><p class="eyebrow">MOROCCO · ONLINE & IN STORE</p><h1>Find a better <em>deal.</em></h1><p>Search offers across retailers, compare the price, and watch the products you want.</p></section>
  <div class="layout">
    <aside class="panel">
      <h2>Refine results</h2>
      <form @submit.prevent="apply">
        <label>Keywords<input v-model="form.search" placeholder="Product, brand, model"></label>
        <label>Channel<select v-model="form.channel"><option value="">All channels</option><option value="online">Online</option><option value="store">In store</option><option value="both">Both</option></select></label>
        <label>City<select v-model="form.city"><option value="">All cities</option><option v-for="city in facets?.cities" :key="city">{{ city }}</option></select></label>
        <label>Retailer<select v-model="form.retailer"><option value="">All retailers</option><option v-for="name in facets?.retailers" :key="name">{{ name }}</option></select></label>
        <label>Brand<select v-model="form.brand"><option value="">All brands</option><option v-for="name in facets?.brands" :key="name">{{ name }}</option></select></label>
        <label>Category<select v-model="form.category"><option value="">All categories</option><option v-for="name in facets?.categories" :key="name">{{ name }}</option></select></label>
        <div class="two"><label>Min MAD<input v-model="form.min_price" type="number" min="0"></label><label>Max MAD<input v-model="form.max_price" type="number" min="0"></label></div>
        <label>Minimum discount %<input v-model="form.min_discount" type="number" min="0" max="100"></label>
        <label>Updated within<select v-model="form.fresh_hours"><option value="">Any time</option><option value="24">24 hours</option><option value="168">7 days</option><option value="720">30 days</option></select></label>
        <label>Sort<select v-model="form.sort"><option value="newest">Newest</option><option value="price_asc">Lowest price</option><option value="price_desc">Highest price</option><option value="discount">Best discount</option></select></label>
        <button class="button" type="submit">Apply filters</button>
      </form>
    </aside>
    <section>
      <div class="sectionhead"><h2>{{ offers?.total ?? 0 }} offers</h2><span v-if="pending">Loading…</span></div>
      <p v-if="error" class="error">Offers could not be loaded.</p>
      <div v-else-if="!offers?.data?.length" class="empty">No matching offers yet. Sources need to be configured before the catalog fills up.</div>
      <div v-else class="cards"><OfferCard v-for="offer in offers.data" :key="offer.id" :offer="offer" /></div>
      <div class="pager"><button class="outline" :disabled="page<=1" @click="page--">Previous</button><span>Page {{ page }}</span><button class="outline" :disabled="!offers || page>=offers.last_page" @click="page++">Next</button></div>
    </section>
  </div>
</template>
<script setup lang="ts">
const { request } = useApi()
const form = reactive<Record<string,string>>({search:'',channel:'',city:'',retailer:'',brand:'',category:'',min_price:'',max_price:'',min_discount:'',fresh_hours:'',sort:'newest'})
const filters = ref<Record<string,string>>({})
const page = ref(1)
const query = computed(() => ({...filters.value,page:page.value}))
const {data: offers, pending, error, refresh} = await useAsyncData('offers', () => request<any>('/offers',{query:query.value}),{watch:[query]})
const {data: facets} = await useAsyncData('facets', () => request<any>('/filters'))
function apply() { filters.value=Object.fromEntries(Object.entries(form).filter(([,v])=>v!=='')); page.value=1; refresh() }
</script>
