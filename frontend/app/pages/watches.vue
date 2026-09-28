<template>
  <section class="hero"><h1>Watch your <em>next find.</em></h1><p>Alerts are checked when offers are collected or changed.</p></section>
  <div v-if="!user" class="empty">Log in to create watches. <NuxtLink class="button" to="/login">Log in</NuxtLink></div>
  <div v-else class="layout">
    <section class="panel">
      <h2>New watch</h2>
      <form @submit.prevent="save">
        <label>Name<input v-model="form.name" required placeholder="My next laptop"></label>
        <label>Required phrases, comma separated<input v-model="form.include_terms" required placeholder="ThinkPad, 16 GB"></label>
        <label>Excluded phrases<input v-model="form.exclude_terms" placeholder="used, refurbished"></label>
        <label>Brand<input v-model="form.brand"></label><label>Category<input v-model="form.category"></label>
        <label>Max price MAD<input v-model="form.max_price" type="number" min="0" step=".01"></label>
        <label>Minimum discount %<input v-model="form.min_discount" type="number" min="0" max="100"></label>
        <label>Retailers, comma separated<input v-model="form.retailers"></label>
        <label>Cities, comma separated<input v-model="form.cities"></label>
        <label>Channel<select v-model="form.channel"><option value="">Any</option><option value="online">Online</option><option value="store">Store</option></select></label>
        <p v-if="error" class="error">{{ error }}</p>
        <button class="button">Create watch</button>
      </form>
    </section>
    <section class="stack">
      <article v-for="watch in watches" :key="watch.id" class="panel">
        <div class="row"><h2>{{ watch.name }}</h2><span class="pill">{{ watch.muted ? 'Muted' : 'Active' }}</span></div>
        <p>Includes {{ watch.include_terms.join(', ') }}</p>
        <p class="muted">Excludes {{ (watch.exclude_terms||[]).join(', ') || 'nothing' }} · {{ watch.max_price_minor===null ? 'Any price' : (watch.max_price_minor/100)+' MAD max' }}</p>
        <div class="actions"><button class="outline" @click="toggle(watch)">{{ watch.muted ? 'Unmute' : 'Mute' }}</button><button class="outline" @click="remove(watch)">Delete</button></div>
      </article>
      <div v-if="!watches.length" class="empty">No watches yet.</div>
    </section>
  </div>
</template>
<script setup lang="ts">
const {user,request,restore}=useApi()
const watches=ref<any[]>([]), error=ref('')
const form=reactive({name:'',include_terms:'',exclude_terms:'',brand:'',category:'',max_price:'',min_discount:'',retailers:'',cities:'',channel:''})
const list=(x:string)=>x.split(',').map(s=>s.trim()).filter(Boolean)
async function load(){if(!user.value)await restore();if(user.value)watches.value=(await request<any>('/watches')).data}
onMounted(load)
async function save(){
  error.value=''
  try {
    await request('/watches',{method:'POST',body:{
      name:form.name,include_terms:list(form.include_terms),exclude_terms:list(form.exclude_terms),
      brand:form.brand||null,category:form.category||null,max_price:form.max_price||null,
      min_discount:form.min_discount||null,retailers:list(form.retailers),cities:list(form.cities),
      channels:form.channel?[form.channel]:[]
    }})
    form.name='';form.include_terms='';await load()
  }catch(e:any){error.value=e?.data?.message||'Could not save watch'}
}
async function toggle(w:any){
  const body={...w,max_price:w.max_price_minor===null?null:w.max_price_minor/100,muted:!w.muted}
  await request('/watches/'+w.id,{method:'PUT',body});await load()
}
async function remove(w:any){await request('/watches/'+w.id,{method:'DELETE'});await load()}
</script>
