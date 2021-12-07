<template>
  <div>
    <div class="text-center">
      <div class="title font-italic text-primary" style="margin-top:100px">
        {{ title }}
      </div>
      <h5 class="text-primary pb-5">
        The Libretexts Adaptive Learning Assessment System
      </h5>
      <b-button size="lg" variant="info" @click="$router.push({name:'commons'})">
        The Commons
      </b-button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {

  metaInfo () {
    return { title: this.$t('home') }
  },

  data: () => ({
    backgroundUrl: '',
    title: window.config.appName
  }),
  computed: mapGetters({
    authenticated: 'auth/check'
  }),
  mounted () {
    this.resizeHandler()
    window.addEventListener('resize', this.resizeHandler)
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.resizeHandler)
  },
  methods: {
    resizeHandler () {
      this.zoomGreaterThan(1.5)
        ? document.getElementsByClassName('basic-layout')[0].style.height = 'auto'
        : document.getElementsByClassName('basic-layout')[0].style.height = '100vh'
    }
  }
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
