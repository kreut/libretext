<template>
  <div class="main-layout">
    <a id="skip-link" :href="skipToContent">Skip to content</a>
    <Email id="contact-us-general-inquiry-modal" ref="email" title="Contact Us" type="contact_us"
           subject="General Inquiry"
    />
    <div class="text-center">
      <b-alert :show="showEnvironment && environment === 'production'" variant="danger">
        <span class="font-weight-bold">Production</span>
      </b-alert>
      <b-alert v-if="false" variant="info">
        ADAPT will be unavailable on Saturday, March 15, from to 7 to 9 am PST due to a scheduled maintenance.
      </b-alert>
    </div>
    <div v-if="!inIFrame">
      <navbar :linked-accounts="linkedAccounts"/>
    </div>
    <div v-else id="default-padding-top" style="padding-top:30px"/>
    <div id="main-content" class="page-container" role="main" :class="{ 'container': true, 'mt-4': true }" tabindex="-1">
      <child/>
    </div>
    <div v-if="!inIFrame && !isLearningTreesEditor" id="footer-div" class="d-flex flex-column"
         style="margin-top:50px;"
    >
      <footer v-if="showFooter" id="site-footer"
              class="footer bg-black text-white mt-3"
      >
        <div>
          <b-navbar toggleable="sm" type="dark" variant="dark">
            <b-navbar-nav>
              <b-nav-item href="https://libretexts.org/terms-of-service/" target="_blank">
                Terms of Use
              </b-nav-item>
              <b-nav-item href="https://chem.libretexts.org/Sandboxes/admin/FERPA_Statement" target="_blank">
                FERPA
                Statement
              </b-nav-item>
              <b-nav-item
                href="https://chem.libretexts.org/Courses/Remixer_University/LibreVerse_Accessibility_Conformance_Reports"
                target="_blank"
              >
                Accessibility Report
              </b-nav-item>
              <b-nav-item @click="contactUsWidget();">
                Contact Us
              </b-nav-item>
              <b-nav-item href="#" @click.prevent="getSitemapURL()">
                Sitemap
              </b-nav-item>
            </b-navbar-nav>
          </b-navbar>
        </div>
        <b-container fluid class="p-4">
          <b-row>
            <b-col sm="12" md="5" lg="4">
              <!-- <b-img src="/assets/img/libretexts_footer_logo_bw.svg"></b-img> -->
              <a class="mr-3 mb-3 d-inline-block"
                 href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
                 rel="external nofollow" target="_blank"
              > <img alt="Logo for the California Learning Lab"
                     style="height:85px;" src="https://cdn.libretexts.net/Images/learning-lab-logo-footer@2x.png"
              ></a>
              <a class="d-inline-block"
                 href="https://libretexts.org/"
                 rel="external" target="_blank"
              > <img alt="Logo for the LibreTexts"
                     style="max-width:200px;" src="https://cdn.libretexts.net/Images/libretexts_footer_logo_bw.svg"
              ></a>
            </b-col>
            <b-col sm="12" md="7" lg="8">
              <p>
                The LibreTexts ADAPT platform is supported by the Department of Education Open Textbook Pilot Project
                and the
                <a href="https://opr.ca.gov/learninglab/">California Education Learning Lab</a>. Have questions or
                comments? For more information please <a href="" @click.prevent="contactUsWidget()">contact us by
                email</a>.
              </p>
              <p>
                For quick navigation, you can use our <a href="" @click.prevent="getSitemapURL()">sitemap</a>. In
                addition, we
                provide a comprehensive report of the site's
                <a
                  href="https://chem.libretexts.org/Courses/Remixer_University/LibreVerse_Accessibility_Conformance_Reports"
                  target="_blank"
                >
                  accessibility</a> and our <a href="https://chem.libretexts.org/Sandboxes/admin/FERPA_Statement"
                                               target="_blank"
              >FERPA statement</a>.
              </p>
            </b-col>
            <!-- <b-col cols="5" class="text-right">
              <a class="ml-5 pt-3 pb-3"
                href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
                rel="external nofollow" target="_blank"> <img alt="Logo for the California Learning Lab"
                  style="height:85px;" :src="asset('assets/img/CELL_LogoColor.png')"></a>
            </b-col> -->
          </b-row>
        </b-container>
        <!-- <p class="pt-3 pl-3 pr-4">
          The LibreTexts ADAPT platform is supported by the Department of Education Open Textbook Pilot Project and the
          <a href="https://opr.ca.gov/learninglab/">California Education Learning
            Lab</a>.
          Have questions or comments? For more information please <a href="" @click.prevent="contactUs">contact us by
            email</a>.
          For quick navigation, you can use our <a href="" @click.prevent="getSitemapURL()">sitemap</a>. In addition, we
          provide a comprehensive report of the site's
          <a href="https://chem.libretexts.org/Courses/Remixer_University/LibreVerse_Accessibility_Conformance_Reports"
            target="_blank">
            accessibility</a> and our <a href="https://chem.libretexts.org/Sandboxes/admin/FERPA_Statement"
            target="_blank">FERPA statement</a>. And you can also
          view our <a href="https://libretexts.org/legal/index.html" target="_blank">Terms And Conditions</a> should you
          use the site.
        </p>
        <div class="d-flex  justify-content-center flex-wrap">
          <a class="ml-5 pt-3 pb-3"
            href="https://www.ed.gov/news/press-releases/us-department-education-awards-49-million-grant-university-california-davis-develop-free-open-textbooks-program"
            rel="external nofollow" target="_blank"> <img alt="Logo for the US Department of Education"
              :src="asset('assets/img/DOE.png')"></a>
          <a class="ml-5 pt-3 pb-3"
            href="https://blog.libretexts.org/2020/03/21/libretext-project-announces-1-million-california/"
            rel="external nofollow" target="_blank"> <img alt="Logo for the California Learning Lab"
              style="height:85px;" :src="asset('assets/img/CELL_LogoColor.png')"></a>
        </div> -->
      </footer>
    </div>
  </div>
</template>

<script>
import Navbar from '~/components/Navbar'
import { mapGetters } from 'vuex'
import Email from '~/components/Email'
import Child from '../components/Child.vue'

export default {
  name: 'MainLayout',
  components: {
    Child,
    Navbar,
    Email
  },
  data: () => ({
    linkedAccounts: [],
    intervalId: null,
    showFooter: true,
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
      if (to.name === 'questions.view') {
        this.showFooter = false
        this.intervalId = setInterval(this.checkQuestionViewDisplay, 50)

        document.getElementById('footer-div').style.marginTop = '0px'
      }
      if (to.name === 'welcome' && this.user){
        if ([3, 4].includes(this.user.role)) {
          window.location = '/students/courses'
        }
        if ([2, 5].includes(this.user.role)) {
          window.location = '/instructors/courses'
        }
      }
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
    this.$nextTick(() => {
      this.linkedAccounts = this.user && this.user.linked_accounts ? JSON.parse(this.user.linked_accounts) : []
      if (this.linkedAccounts.length) {
        this.linkedAccounts = this.linkedAccounts.sort((a, b) => {
          return a.id === this.user.id ? -1 : b.id === this.user.id ? 1 : 0
        })
      }
    })

    if (!this.inIFrame && !this.isLearningTreesEditor) {
      document.getElementById('main-content').style.minHeight = (window.screen.height - 430) + 'px'
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
    if (this.intervalId) {
      clearInterval(this.intervalId)
    }
  },
  methods: {
    checkQuestionViewDisplay () {
      const questionViewDisplay = document.getElementById('questions-loaded')
      if (questionViewDisplay) {
        this.showFooter = true
        clearInterval(this.intervalId)
      }
    },
    contactUsWidget () {
      document.getElementById('supportButton').click()
    },
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
