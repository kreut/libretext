<template>
  <div class="main-layout">
    <b-modal
      id="modal-accessibility"
      ref="modal"
      size="sm"
      title="Accessibility Cookie"
      @hidden="$router.go()"
    >
      {{ accessibilityMessage }}
      <template #modal-footer="{ ok }">
        <b-button size="sm"
                  variant="primary"
                  class="accessible-btn"
                  @click="$router.go()"
        >
          Got it!
        </b-button>
      </template>
    </b-modal>
    <div class="text-center">
      <b-alert :show="showEnvironment && environment === 'production'" variant="danger">
        <span class="font-weight-bold">Production</span>
      </b-alert>
    </div>
    <div v-if="!inIFrame">
      <navbar/>
    </div>
    <div v-else style="padding-top:30px"/>
    <div :class="{'container':true, 'mt-4':true,'expandHeight': ((user === null) && !inIFrame)}">
      <child/>
    </div>
    <div v-if="(user === null) && !inIFrame" class="d-flex flex-column" style="background: #e5f5fe">
      <footer class="footer" style="border:1px solid #30b3f6">
        <p class="pt-3 pl-3 pr-4">
          The LibreTexts Adapt platform is supported by the Department of Education Open Textbook Pilot Project and the
          <a href="https://opr.ca.gov/learninglab/">California Education Learning Lab</a>.
          Unless otherwise noted, LibreTexts content is licensed by CC BY-NC-SA 3.0. Have questions or comments? For
          more information contact us at <a href="mailto:info@libretexts.org.">info@libretexts.org.</a>
        </p>
        <p class="pt-3 pl-3 pr-4">
          To improve the accessibility of this site, we can add an <a class="accessible-link" href=""
                                                                      @click.prevent="addAccessibilityCookie"
        >accessibility
          cookie</a> to your browser. This cookie can also be <a class="accessible-link" href=""
                                                                 @click.prevent="removeAccessibilityCookie"
        >removed</a> at any time.
        </p>

        <div class="d-flex  justify-content-center flex-wrap">
          <a class="ml-5 pt-3 pb-3"
             href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"
             rel="external nofollow" target="_blank"
             title="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"
          > <img alt="DOE Logo.png" :src="asset('assets/img/DOE.png')"></a>
          <a class="ml-5 pt-3 pb-3"
             href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
             rel="external nofollow" target="_blank"
             title="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
          > <img alt="DOE Logo.png" style="height:85px;" :src="asset('assets/img/CELL_LogoColor.png')"></a>
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  name: 'MainLayout',
  components: {
    Navbar
  },
  data: () => ({
    showEnvironment: window.config.showEnvironment,
    environment: window.config.environment,
    inIFrame: true,
    accessibilityMessage: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  mounted () {
    window.addEventListener('keydown', this.keydownHandler)
  },
  beforeDestroy () {
    window.removeEventListener('keydown', this.keydownHandler)
  },
  methods: {
    keydownHandler (e) {
      if (e.keyCode === 27) {
        this.$root.$emit('bv::hide::tooltip')
        console.log('Tooltip closed')
      }
    },
    async addAccessibilityCookie () {
      try {
        const { data } = await axios.patch(`/api/accessibility/set-cookie`)
        this.accessibilityMessage = data.message
        this.$bvModal.show('modal-accessibility')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeAccessibilityCookie () {
      try {
        const { data } = await axios.delete(`/api/accessibility/delete-cookie`)
        this.accessibilityMessage = data.message
        this.$bvModal.show('modal-accessibility')
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
<style scoped>
.expandHeight {
  min-height: 700px
}
</style>
