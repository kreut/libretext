<template>
  <div>
    <div class="basic-layout d-flex  justify-content-center m-0 bg-white">
      <div class="text-center">
        <div class="title">
          {{ title }}
        </div>

        <div class="text-dark">
          Libretext Adaptive Learning Assessment System
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex'

  export default {

    metaInfo() {
      return {title: this.$t('home')}
    },

    data: () => ({
      title: window.config.appName
    }),

    computed: mapGetters({
      authenticated: 'auth/check'
    })
  }
</script>

<style lang="scss" scoped>
  .top-right {
    position: absolute;
    right: 10px;
    top: 18px;
  }

  .title {
    font-size: 85px;
  }

  .basic-layout {
    color: #636b6f;
    height: 100vh;
    font-weight: 100;
    position: relative;
    padding-top: 100px;

    .links > a {
      color: #636b6f;
      padding: 0 25px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .1rem;
      text-decoration: none;
      text-transform: uppercase;
    }
  }

</style>
