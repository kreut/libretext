<template>
  <div>
    <div class="pb-3">
    <div class="basic-layout d-flex  justify-content-center bg" :style="{backgroundImage:'url(/assets/img/hatice-yardim-5LvC-QX0OOc-unsplash.jpg)'}">
      <div class="text-center">
        <div class="title font-italic text-white">
          {{ title }}
        </div>
        <h5 class="text-white">
          The Libretext Adaptive Learning Assessment System
        </h5>
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
  .bg {
    //background-image: url("~@/assets/img/hatice-yardim-5LvC-QX0OOc-unsplash.jpg");
    opacity: 0.6;
    height: 100%;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
  }
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
