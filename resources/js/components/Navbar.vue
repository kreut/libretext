<template>
  <div>
    <Email id="contact-us-modal"
           ref="email"
           extra-email-modal-text="Please use this form to contact us regarding general questions or issues.  If you have a course specific question, please contact your instructor using your own email client."
           :from-user="user"
           title="Contact Us"
           type="contact_us"
    />

    <b-navbar-brand href="/">
      <img src="/assets/img/libretexts_section_complete_adapt_header.png" class="d-inline-block align-top pl-3"
           @load="logoLoaded = true"
      >
    </b-navbar-brand>
    <div v-if="logoLoaded" class="float-right p-2">
      <toggle-button
        v-if="showToggleStudentView && (user !== null)"
        class="mt-2"
        :width="140"
        :value="isInstructorView"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#28a745', unchecked: '#6c757d'}"
        :labels="{checked: 'Instructor View', unchecked: 'Student View'}"
        @change="toggleStudentView()"
      />
      <span v-if="isMe && (user !== null)">
        <router-link :to="{ name: 'login.as'}">
          <b-button size="sm" variant="outline-primary">Login As</b-button>
        </router-link>
      </span>
    </div>
    <b-nav v-if="logoLoaded" aria-label="breadcrumb" class="breadcrumb d-flex justify-content-between"
           style="padding-top:.3em !important;padding-bottom:0 !important; margin-bottom:0 !important;"
    >
      <span v-if="oneBreadcrumb && (user !== null)"
            style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important; padding-left:16px"
      ><a :href="breadcrumbs && breadcrumbs[0]['href']">{{ breadcrumbs[0]['text'] }}</a></span>
      <b-breadcrumb v-if="!oneBreadcrumb" :items="breadcrumbs"
                    style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important"
      />
      <b-navbar-nav class="ml-auto mt-0 mb-0">
        <b-row>
          <b-nav-item-dropdown v-if="user && !isLearningTreesEditor" right class="mr-2">
            <!-- Using 'button-content' slot -->
            <template v-slot:button-content>
              <em>Hi, {{ user.first_name }}!</em>
            </template>
            <router-link :to="{ name: 'settings.profile' }" class="dropdown-item pl-3">
              <fa icon="cog" fixed-width />
              {{ $t('settings') }}
            </router-link>
            <a href="#" class="dropdown-item pl-3" @click.prevent="logout">
              <fa icon="sign-out-alt" fixed-width />
              {{ $t('logout') }}
            </a>
          </b-nav-item-dropdown>
          <b-navbar-nav v-show="!user">
            <b-nav-item href="/login">
              <router-link :to="{ name: 'login' }" class="nav-link"
                           :style="this.$router.history.current.name === 'login' ? 'color:#6C757D' : ''"
              >
                {{ $t('login') }}
              </router-link>
            </b-nav-item>
          </b-navbar-nav>

          <b-navbar-nav class="ml-2 mr-2 mb-1">
            <b-nav-item>
              <span class="nav-link" active-class="active" @click="openSendEmailModal">
                <span :style="isLearningTreesEditor">Contact Us</span>
              </span>
            </b-nav-item>
          </b-navbar-nav>
          <b-nav-item-dropdown v-show="!user" text="Register" class="pr-2" right>
            <b-dropdown-item href="#">
              <router-link :to="{ path: '/register/student' }" class="dropdown-item pl-3">
                Student
              </router-link>
            </b-dropdown-item>
            <b-dropdown-item href="#">
              <router-link :to="{ path: '/register/instructor' }" class="dropdown-item pl-3">
                Instructor
              </router-link>
            </b-dropdown-item>
            <b-dropdown-item href="#">
              <router-link :to="{ path: '/register/grader' }" class="dropdown-item pl-3">
                Grader
              </router-link>
            </b-dropdown-item>
          </b-nav-item-dropdown>
        </b-row>
      </b-navbar-nav>
    </b-nav>
  </div>
</template>

<script>

import axios from 'axios'
import { mapGetters } from 'vuex'
import Email from './Email'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  components: {
    Email,
    ToggleButton
  },

  data: () => ({
    showToggleStudentView: false,
    originalRole: 0,
    isInstructorView: false,
    canToggleStudentView: false,
    courseId: 0,
    assignmentId: 0,
    appName: window.config.appName,
    oneBreadcrumb: false,
    logoLoaded: false,
    isLearningTreesEditor: false,
    breadcrumbsLoaded: false,
    breadcrumbs: [
      {
        text: '',
        href: '#',
        active: true
      }
    ]
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe,
    currentRouteName () {
      return this.$route.name
    }
  },
  watch: {
    '$route' (to, from) {
      if (to.params.courseId) {
        this.courseId = to.params.courseId
      }
      if (to.params.assignmentId) {
        this.assignmentId = to.params.assignmentId
      }
      this.canToggleStudentView = Boolean(this.assignmentId + this.courseId)

      this.isLearningTreesEditor = (to.name === 'instructors.learning_trees.editor') ? 'color:#E9ECEF !important;' : ''
      if (this.user) {
        this.getBreadcrumbs(this.$router.history.current)
      } else {
        this.breadcrumbs = []
      }
      this.breadcrumbsLoaded = true
      this.getSession()
      this.isInstructorView = this.user !== null && this.user.role === 2
    }
  },
  methods: {
    async getSession () {
      console.log(this.user)
      if (this.user !== null) {
        try {
          const { data } = await axios.get('/api/user/get-session')
          console.log(data)
          this.originalRole = data.original_role
        } catch (error) {
          this.$noty(error.message)
        }
        this.showToggleStudentView = parseInt(this.originalRole) === 2
      }
    },
    async toggleStudentView () {
      if (!this.canToggleStudentView) {
        let message = 'Please visit a page within a course to toggle the view.'
        this.$noty.info(message)
        return false
      }
      try {
        const { data } = await axios.post('/api/user/toggle-student-view',
          {
            course_id: this.courseId,
            assignment_id: this.assignmentId,
            route_name: this.$router.history.current.name
          })
        console.log(data)
        if (data.type === 'success') {
          // Save the token.
          this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })
          this.isInstructorView = !this.isInstructorView
          if (data.new_route_name !== this.$router.history.current.name) {
            await this.$store.dispatch('auth/fetchUser')
            await this.getBreadcrumbs(this.$router)
            await this.$router.push({ name: data.new_route_name })
          } else {
            this.$router.go()
          }

          // Redirect to the correct home page
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        this.$noty(error.message)
      }
    },
    async getBreadcrumbs (router) {
      try {
        console.log(router.name)
        console.log({ 'name': router.name, 'params': router.params })
        const { data } = await axios.post('/api/breadcrumbs', { 'name': router.name, 'params': router.params })
        this.breadcrumbs = (data.type === 'success') ? data.breadcrumbs : []
        this.oneBreadcrumb = this.breadcrumbs.length === 1 && ['welcome', 'instructors.learning_trees.index', 'instructors.courses.index', 'login.as'].includes(router.name)
      } catch (error) {
        this.$noty(error.message)
      }
    },
    openSendEmailModal () {
      this.$refs.email.openSendEmailModal()
    },
    async logout () {
      // Log out the user.
      const { data } = await axios.get('/api/sso/is-sso-user')

      await this.$store.dispatch('auth/logout')
      if (data.is_sso_user) {
        window.location = 'https://sso.libretexts.org/cas/logout'
      } else {
        // Redirect to login.
        this.$router.push({ name: 'login' })
      }
    }
  }
}
</script>

<style scoped>
.nav-link {
  padding-top: .25em;
}

</style>
