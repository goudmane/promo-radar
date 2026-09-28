<template>
  <section class="hero"><h1>Collection <em>sources.</em></h1><p>Configure trusted feeds or extraction profiles, then inspect each run.</p></section>
  <div v-if="!user?.is_owner" class="empty">Owner access required.</div>
  <div v-else class="layout">
    <div class="stack">
      <div class="panel"><h2>Add source</h2>
        <form @submit.prevent="save">
          <label>Name<input v-model="form.name" required></label>
          <label>Retailer<input v-model="form.retailer" required></label>
          <label>Driver<select v-model="form.driver"><option value="json_feed">JSON feed</option><option value="browser">Browser extraction</option></select></label>
          <label>HTTPS URL<input v-model="form.url" type="url" required></label>
          <label>Allowed host<input v-model="form.allowed_host" placeholder="shop.example.ma" required></label>
          <label>Interval, minutes<input v-model.number="form.interval_minutes" type="number" min="10" required></label>
          <label>Mapping JSON (optional)<textarea v-model="mapping" placeholder='{"item_selector":".product","selectors":{"title":".name","price":".price","url":"a@href"},"max_pages":2}'></textarea></label>
          <label><input class="check" v-model="form.enabled" type="checkbox">Enable after saving</label>
          <p v-if="error" class="error">{{ error }}</p><button class="button">Save source</button>
        </form>
      </div>
      <div class="panel"><h2>Import export rows</h2><p class="muted">Paste JSON: source_id and rows array. Useful for Ultimate Web Scraper exports after mapping columns.</p>
        <textarea v-model="importJson" placeholder='{"source_id":1,"rows":[...]}'></textarea>
        <button class="button" @click="upload">Import rows</button>
      </div>
    </div>
    <div class="stack">
      <article v-for="source in sources" :key="source.id" class="panel">
        <div class="row"><h2>{{ source.name }}</h2><span class="pill">{{ source.enabled ? 'Enabled' : 'Paused' }}</span></div>
        <p class="muted">{{ source.driver }} · {{ source.retailer }} · {{ source.interval_minutes }} min</p>
        <p v-if="source.last_error" class="error">{{ source.last_error }}</p>
        <p>Last run: {{ source.last_run_at || 'Never' }}</p>
        <div class="actions"><button class="outline" @click="run(source)">Queue run</button><button class="outline" @click="toggle(source)">{{ source.enabled?'Pause':'Enable' }}</button></div>
      </article>
      <div v-if="!sources.length" class="empty">No sources configured yet.</div>
    </div>
  </div>
</template>
<script setup lang="ts">
const {user,request,restore}=useApi()
const sources=ref<any[]>([]),error=ref(''), mapping=ref(''),importJson=ref('')
const form=reactive({name:'',retailer:'',driver:'json_feed',url:'',allowed_host:'',interval_minutes:360,enabled:false})
async function load(){if(!user.value)await restore();if(user.value?.is_owner)sources.value=(await request<any>('/admin/sources')).data}
onMounted(load)
async function save(){
  error.value=''
  try {await request('/admin/sources',{method:'POST',body:{...form,mapping:mapping.value?JSON.parse(mapping.value):{}}});await load()}
  catch(e:any){error.value=e?.data?.message||e.message||'Source could not be saved'}
}
async function run(s:any){await request('/admin/sources/'+s.id+'/run',{method:'POST'});await load()}
async function toggle(s:any){await request('/admin/sources/'+s.id,{method:'PUT',body:{...s,enabled:!s.enabled}});await load()}
async function upload(){try{const result=await request<any>('/admin/imports',{method:'POST',body:JSON.parse(importJson.value)});error.value='Imported '+result.changed+' changed offers'}catch(e:any){error.value=e?.data?.message||e.message}}
</script>
