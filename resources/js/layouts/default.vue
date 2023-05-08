<template>
  <div class="main-layout">
    <a id="skip-link" :href="skipToContent">Skip to content</a>
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
    <div v-else id="default-padding-top" style="padding-top:30px"/>
    <div id="main-content"
         role="main"
         :class="{'container':true, 'mt-4':true}"
         tabindex="-1"
    >
      <child/>
    </div>
    <div v-if="!inIFrame && !isLearningTreesEditor" class="d-flex flex-column"
         style="background: #e5f5fe;margin-top:200px;"
    >
      <footer class="footer" style="border:1px solid #30b3f6">
        <p class="pt-3 pl-3 pr-4">
          The LibreTexts ADAPT platform is supported by the Department of Education Open Textbook Pilot Project and the
          <a href="https://opr.ca.gov/learninglab/">California Education Learning
            Lab</a>.
          Have questions or comments? For more information please <a href="" @click.prevent="contactUs">contact us by
          email</a>.
          For quick navigation, you can use our <a href="" @click.prevent="getSitemapURL()">sitemap</a>. In addition, we
          provide a comprehensive report of the site's
          <a
            href="https://chem.libretexts.org/Courses/Remixer_University/LibreVerse_Accessibility_Conformance_Reports"
            target="_blank"
          >
            accessibility</a> and our <a href="https://chem.libretexts.org/Sandboxes/admin/FERPA_Statement"
                                         target="_blank"
        >FERPA statement</a>. And you can also
          view our <a href="https://libretexts.org/legal/index.html" target="_blank">Terms And Conditions</a> should you use the site.
        </p>
        <div class="d-flex  justify-content-center flex-wrap">
          <a class="ml-5 pt-3 pb-3"
             href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"
             rel="external nofollow" target="_blank"
          > <img alt="Logo for the US Department of Education" :src="asset('assets/img/DOE.png')"></a>
          <a class="ml-5 pt-3 pb-3"
             href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
             rel="external nofollow" target="_blank"
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
    skipToContent: '',
    showEnvironment: window.config.showEnvironment,
    environment: window.config.environment,
    inIFrame: true,
    isLearningTreesEditor: false,
    sitemapURL: ''
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  watch: {
    '$route' (to, from) {
      this.skipToContent = to.name === 'questions.view' ? '#question-to-view' : '#main-content'
      this.isLearningTreesEditor = to.name === 'instructors.learning_trees.editor'
    }
  },
  created () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  mounted () {
    if (!this.inIFrame && !this.isLearningTreesEditor) {
      document.getElementById('main-content').style.minHeight = (window.screen.height - 630) + 'px'
    }
    window.addEventListener('keydown', this.keydownHandler)
    this.$root.$on('bv::modal::shown', (bvEvent, modalId) => {
      console.log('Modal is about to be shown', bvEvent, modalId)
      const originalTitle = document.getElementById(`${modalId}___BV_modal_title_`)
      if (originalTitle) {
        originalTitle.insertAdjacentHTML('afterend', `<h2 id="${modalId}___BV_modal_title_2" class="h5 modal-title">${originalTitle.innerHTML}</h2>`)
        originalTitle.remove()
        document.getElementById(`${modalId}___BV_modal_title_2`).setAttribute('id', `${modalId}___BV_modal_title`)
      }
    })
    this.$root.$on('bv::modal::hidden', (bvEvent, modalId) => {
      console.log('Modal hidden. Fixing body bug')
      document.body.style.paddingRight = '0' // bug with Vue which adds padding to the body aftering opening a Modal
    })
  },
  beforeDestroy () {
    window.removeEventListener('keydown', this.keydownHandler)
  },
  methods: {
    getSitemapURL () {
      let sitemapURL = 'sitemap'
      if (this.user) {
        switch (this.user.role) {
          case (2):
            sitemapURL = 'instructors.sitemap'
            break
          case (3):
            sitemapURL = 'students.sitemap'
            break
        }
      }
      this.$router.push({ name: sitemapURL })
    },
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
