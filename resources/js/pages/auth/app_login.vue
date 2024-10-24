<template>
  <div>
    <a id="initiate-login" href="/api/oidc/initiate-login/app">initiate app login</a>
  </div>
</template>

<script>
export default {
  name: 'AppLogin',
  mounted () {
    document.getElementById('initiate-login').click()
  }
}
</script>

<style scoped>

</style>
