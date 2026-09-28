<template>
  <div class="formpage panel">
    <h1>{{ register ? 'Create account' : 'Welcome back' }}</h1>
    <p class="muted">Save your product watches and get alerts when offers match.</p>
    <form @submit.prevent="submit">
      <label v-if="register">Name<input v-model="name" required maxlength="100"></label>
      <label>Email<input v-model="email" type="email" required autocomplete="email"></label>
      <label>Password<input v-model="password" type="password" required minlength="12" :autocomplete="register?'new-password':'current-password'"></label>
      <p v-if="error" class="error">{{ error }}</p>
      <button class="button" :disabled="busy">{{ busy ? 'Please wait…' : register ? 'Create account' : 'Log in' }}</button>
    </form>
    <button class="subtle" @click="register=!register">{{ register ? 'Already registered? Log in' : 'Need an account? Sign up' }}</button>
  </div>
</template>
<script setup lang="ts">
const {request,accept}=useApi()
const register=ref(false), name=ref(''), email=ref(''), password=ref(''), error=ref(''), busy=ref(false)
async function submit() {
  busy.value=true;error.value=''
  try {
    const result=await request<{token:string,user:any}>(register.value?'/register':'/login',{
      method:'POST',body:{name:name.value,email:email.value,password:password.value}
    })
    accept(result);await navigateTo('/watches')
  } catch(e:any) {error.value=e?.data?.message||'Could not sign in'} finally {busy.value=false}
}
</script>
