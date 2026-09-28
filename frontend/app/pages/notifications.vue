<template>
  <section class="hero"><h1>Your <em>inbox.</em></h1><p>One alert per watch, offer, and meaningful change.</p></section>
  <div v-if="!user" class="empty"><NuxtLink class="button" to="/login">Log in</NuxtLink></div>
  <template v-else>
    <div class="panel row"><span>Email delivery</span><select style="max-width:220px" v-model="mode" @change="saveMode"><option value="off">Off</option><option value="immediate">Immediate</option><option value="digest">Daily digest</option></select></div>
    <div class="stack" style="margin-top:20px">
      <article v-for="a in alerts" :key="a.id" class="panel">
        <div class="row"><h2>{{ a.offer?.title }}</h2><span class="pill">{{ a.read_at ? 'Read' : 'New' }}</span></div>
        <p>{{ (a.offer?.price_minor/100).toFixed(2) }} {{ a.offer?.currency }} · {{ a.watch?.name }}</p>
        <div class="actions"><a v-if="a.offer?.url" class="button" :href="a.offer.url" target="_blank" rel="noopener noreferrer">See offer ↗</a><button v-if="!a.read_at" class="outline" @click="read(a)">Mark read</button></div>
      </article>
      <div v-if="!alerts.length" class="empty">No matching offers yet.</div>
    </div>
  </template>
</template>
<script setup lang="ts">
const {user,request,restore}=useApi()
const alerts=ref<any[]>([]), mode=ref('off')
async function load(){if(!user.value)await restore();if(user.value){mode.value=user.value.email_mode;alerts.value=(await request<any>('/notifications')).data}}
onMounted(load)
async function saveMode(){user.value=await request<any>('/preferences',{method:'PUT',body:{email_mode:mode.value}})}
async function read(a:any){await request('/notifications/'+a.id+'/read',{method:'POST'});a.read_at=new Date().toISOString()}
</script>
