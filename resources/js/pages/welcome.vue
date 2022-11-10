<template>
  <div>
    <div class="text-center">
      <h1 class="title text-primary" style="margin-top:100px">
        {{ title }}
      </h1>
      <div class="h5 homepage-subtitle pb-5">
        The Libretexts Adaptive Learning Assessment System
      </div>
      <b-button size="lg" variant="info" @click="$router.push({path:'/open-courses/commons'})">
        Commons
      </b-button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {

  metaInfo () {
    return { title: 'Home' }
  },

  data: () => ({
    backgroundUrl: '',
    title: window.config.appName
  }),
  computed: mapGetters({
    authenticated: 'auth/check',
    user: 'auth/user'
  }),
  mounted () {
    if (this.authenticated && this.user.is_tester_student) {
      this.$router.push({ name: 'cannot.view.as.testing.student' })
    }
    this.resizeHandler()
    window.addEventListener('resize', this.resizeHandler)
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.resizeHandler)
  },
  methods: {
    resizeHandler () {
      let basicLayout = document.getElementsByClassName('basic-layout')
      if (basicLayout.length) {
        this.zoomGreaterThan(1.5)
          ? basicLayout[0].style.height = 'auto'
          : basicLayout[0].style.height = '100vh'
      }
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
