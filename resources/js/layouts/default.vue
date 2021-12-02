<template>
  <div class="main-layout">
    <a id="skip-link" href="#main-content">Skip to content</a>
    <Email id="contact-us-general-inquiry-modal"
           ref="email"
           title="Contact Us"
           type="contact_us"
           subject="General Inquiry"
    />
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
      <child id="main-content"/>
    </div>
    <div v-if="(user === null) && !inIFrame" class="d-flex flex-column" style="background: #e5f5fe">
      <footer class="footer" style="border:1px solid #30b3f6">
        <p class="pt-3 pl-3 pr-4">
          The LibreTexts ADAPT platform is supported by the Department of Education Open Textbook Pilot Project and the
          <a href="https://opr.ca.gov/learninglab/" title="California Learning Lab">California Education Learning
            Lab</a>.
          Unless otherwise noted, LibreTexts content is licensed by CC BY-NC-SA 3.0. Have questions or comments? For
          more information please <a href="" @click.prevent="contactUs">contact us</a>.
        </p>
        <div class="d-flex  justify-content-center flex-wrap">
          <a class="ml-5 pt-3 pb-3"
             href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"
             rel="external nofollow" target="_blank"
             title="Press release for the US Department of Education awarding a grant to UC Davis to develop free open textbooks"
          > <img alt="Logo for the US Department of Education" :src="asset('assets/img/DOE.png')"></a>
          <a class="ml-5 pt-3 pb-3"
             href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
             rel="external nofollow" target="_blank"
             title="Press release for the LibreText Project receiving a $1 million Innovation Grant from the California Learning Lab"
          > <img alt="Logo for the California Learning Lab" style="height:85px;"
                 :src="asset('assets/img/CELL_LogoColor.png')"
          ></a>
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'
import { mapGetters } from 'vuex'
import Email from '~/components/Email'

export default {
  name: 'MainLayout',
  components: {
    Navbar,
    Email
  },
  data: () => ({
    showEnvironment: window.config.showEnvironment,
    environment: window.config.environment,
    inIFrame: true
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
    this.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
      console.log('Modal is about to be shown', bvEvent, modalId)
      const originalTitle = document.getElementById(`${modalId}___BV_modal_title_`)
      originalTitle.insertAdjacentHTML('afterend', `<h2 id="${modalId}___BV_modal_title_2" class="h5 modal-title">${originalTitle.innerHTML}</h2>`)
      originalTitle.remove()
      document.getElementById(`${modalId}___BV_modal_title_2`).setAttribute('id', `${modalId}___BV_modal_title`)
    })
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
    contactUs () {
      this.$bvModal.show('contact-us-general-inquiry-modal')
    }
  }
}
</script>
<style scoped>
.expandHeight {
  min-height: 700px
}
</style>

