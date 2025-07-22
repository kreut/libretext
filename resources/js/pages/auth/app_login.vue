<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <a v-show="false" id="initiate-login" href="/api/oidc/initiate-login/app">initiate app login</a>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  name: 'AppLogin',
  layout: 'blank',
  components: { Loading },
  data: () => ({
    isLoading: true
  }),
  mounted () {
    document.getElementById('initiate-login').click()
  }
}
</script>
