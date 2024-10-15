<template>
  <div>
    <div v-show="false" id="support-widget-container"
         style="position: fixed; z-index: 9999; display: inline-flex; bottom: 0; right: 5px;"
    />
    <Email id="contact-us-modal" ref="email"
           extra-email-modal-text="Please use this form to contact us regarding general questions or issues.  If you have a course specific question, please contact your instructor using your own email client."
           :from-user="user" title="Contact Us" type="contact_us" subject="General Inquiry"
    />
    <div v-if="showNavBar" id="navbar">
      <b-navbar toggleable="lg">
        <LibreOne size="sm" class="m-1"/>

        <b-navbar-brand>
          <a :href="getLogoHref()"><img src="https://adapt-promo.libretexts.org/wp-content/uploads/2024/06/ADAPT-v10-FINAL.png"
                                        :alt="user !== null && [2, 3, 4, 5].includes(user.role)
                                          ? 'ADAPT logo with redirect to My Courses' : 'ADAPT logo with redirect to main page'"
                                        @load="logoLoaded = true"
          >
          </a>
        </b-navbar-brand>

        <b-navbar-toggle target="nav-collapse" right/>

        <b-collapse id="nav-collapse" is-nav>
          <b-navbar-nav class="ml-auto">
            <b-nav-item v-show="!isAnonymousUser && !(user && (user.fake_student || user.testing_student))"
                        @click="contactUsWidget()"
            >
              <span class="" :class="{ 'hidden-nav-link': isLearningTreesEditor }">Support</span>
            </b-nav-item>
            <b-nav-item v-if="user && (user.fake_student || user.testing_student || user.formative_student)"
                        @click.prevent="logout"
            >
              <span v-if="!user.formative_student">Logout</span>
              <span v-if="user.formative_student">End Session</span>
            </b-nav-item>
            <b-nav-item v-show="!user" @click="$router.push({ name: 'login' })">
              <span :style="this.$router.history.current.name === 'login' ? 'color:#6C757D' : ''">Log In</span>
            </b-nav-item>
            <b-nav-item-dropdown v-show="!user" text="Register" right>
              <b-dropdown-item @click="$router.push({ path: '/register/student' })">
                <span class="">Student</span>
              </b-dropdown-item>
              <b-dropdown-item @click="$router.push({ path: '/register/instructor' })">
                <span class="">Instructor</span>
              </b-dropdown-item>
              <b-dropdown-item @click="$router.push({ path: '/register/grader' })">
                <span class="">Grader</span>
              </b-dropdown-item>
              <b-dropdown-item @click="$router.push({ path: '/register/question-editor' })">
                <span class="">Non-Instructor Editor</span>
              </b-dropdown-item>
              <b-dropdown-item @click="$router.push({ path: '/register/tester' })">
                <span class="">Tester</span>
              </b-dropdown-item>
            </b-nav-item-dropdown>
          </b-navbar-nav>
        </b-collapse>
        <div v-if="logoLoaded
          && user
          && ((![2,3,5].includes(user.role) && user.logged_in_as_user) || (user.role === 3 && user.is_instructor_logged_in_as_student && !user.fake_student))"
        >
          <b-button size="sm" variant="outline-danger" @click="exitLoginAs">
            Exit Login As
          </b-button>
        </div>
        <div v-if="logoLoaded && showToggleStudentView || (user && user.role === 5)" class="float-right">
          <toggle-button
            v-if="showToggleStudentView && (user !== null) && toggleInstructorStudentViewRouteNames.includes($route.name)"
            tabindex="0" class="mt-2" :width="140" :value="isInstructorView" :sync="true" :font-size="14" :margin="4"
            :color="toggleColors" :labels="{ checked: 'Instructor View', unchecked: 'Student View' }"
            :aria-label="isInstructorView ? 'Instructor view shown' : 'Student view shown'"
            @change="toggleStudentView()"
          />
          <span v-if="user && (!user.logged_in_as_user && user.id === 7665 && !isMe)">
            <router-link :to="{ name: 'loginAsSingle' }">
              <b-button size="sm" variant="outline-primary">Login As</b-button>
            </router-link>
          </span>
          <span v-if="user && user.logged_in_as_user">
            <b-button size="sm" variant="outline-danger" @click="exitLoginAs">Exit Login As</b-button>
          </span>
          <span
            v-if="isMe && (user !== null) && (!user.logged_in_as_user) && !user.fake_student && ('instructors.learning_trees.editor' !== $route.name)"
          >
            <router-link :to="{ name: 'login.as' }">
              <b-button size="sm" variant="outline-primary">Control Panel</b-button>
            </router-link>
          </span>
          <span v-if="user && [2, 4, 5].includes(user.role)">
            <b-dropdown v-show="'instructors.learning_trees.editor' !== $route.name" id="dropdown-right" right
                        text="Dashboard" variant="primary" class="m-2" size="sm"
            >
              <b-dropdown-item v-for="location in dashboards" :key="location.text" href="#" :class="{
                'border-bottom': location.text === 'My Assignment Templates',
                'pb-2': location.text === 'My Assignment Templates',
                'pt-2': location.text === 'Search Questions'
              }" @click="loadRoutePath(location.routePath)"
              >
                <span class="hover-underline">{{ location.text }}</span>
              </b-dropdown-item>
            </b-dropdown>
          </span>
        </div>
      </b-navbar>
    </div>

    <b-nav v-if="logoLoaded" role="navigation" aria-label="breadcrumb" class="breadcrumb d-flex justify-content-between"
           style="padding-top:.3em !important;padding-bottom:0 !important; margin-bottom:0 !important;"
    >
      <span v-if="(user === null) || (oneBreadcrumb && (user !== null))"
            style="padding-top:.3em;padding-bottom:0 !important; margin-bottom:0 !important; padding-left:16px"
      ><a
        v-if="breadcrumbs[0] && breadcrumbs[0]['text']" :href="breadcrumbs && breadcrumbs[0]['href']"
      >
        {{ breadcrumbs[0]['text'] }}
      </a></span>
      <b-breadcrumb v-if="!oneBreadcrumb && breadcrumbs[0] && breadcrumbs[0]['text'] && !(user && user.testing_student)"
                    :items="breadcrumbs"
                    style="padding-bottom:0 !important; margin-bottom:0 !important"
                    :style="breadcrumbs.length === 1 && breadcrumbs[0].active ?
      'padding-top:.3em;padding-bottom:.3em !important; margin-bottom:0 !important' : 'padding-top:.3em;padding-bottom:0 !important; margin-bottom:0 !important'"
      />
      <b-navbar-nav class="ml-auto mt-0 mb-0 d-flex flex-row">
        <b-nav-item-dropdown
          v-if="user && !user.fake_student && !user.testing_student && !isLearningTreesEditor && !user.formative_student"
          right class="mr-2"
        >
          <!-- Using 'button-content' slot -->
          <template v-slot:button-content>
            <span class="hover-underline">Hi, {{ user.first_name }}!</span>
          </template>
          <b-dropdown-item v-if="!isAnonymousUser && !user.formative_student"
                           @click="$router.push({ name: 'settings.profile' })"
          >
            <fa icon="cog" fixed-width/>
            <span class="hover-underline pl-3">{{ $t('settings') }}</span>
          </b-dropdown-item>
          <b-dropdown-item @click.prevent="logout">
            <fa icon="sign-out-alt" fixed-width/>
            <span class="hover-underline pl-3">{{ $t('logout') }}</span>
          </b-dropdown-item>
        </b-nav-item-dropdown>
      </b-navbar-nav>
    </b-nav>
  </div>
</template>

<script>

import axios from 'axios'
import { mapGetters } from 'vuex'
import Email from './Email'
import { logout } from '~/helpers/Logout'
import { ToggleButton } from 'vue-js-toggle-button'
import { toggleInstructorStudentViewRouteNames } from '~/helpers/StudentInstructorViewToggles'
import LibreOne from './LibreOne.vue'

export default {
  components: {
    LibreOne,
    Email,
    ToggleButton
  },
  data: () => ({
    toggleInstructorStudentViewRouteNames: toggleInstructorStudentViewRouteNames,
    toggleColors: window.config.toggleColors,
    isAnonymousUser: false,
    showNavBar: true,
    isLearningTreeView: true,
    isInstructorsMyCoursesView: true,
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
    },
    dashboards () {
      if (!this.user) {
        return []
      }
      let dashboards = [{ routePath: '/instructors/courses', text: 'My Courses' },
        { routePath: '/question-editor/my-questions', text: 'My Questions' }]
      if (this.user.id === 6314) {
        dashboards.unshift({ routePath: '/instructors/nursing-analytics', text: 'Analytics' })
      }
      if (this.user.role === 5) {
        dashboards.push({ routePath: '/all-questions/get', text: 'Non-instructor Editor Questions' })
      }
      if (this.user.role === 2) {
        let instructorDashboards = [
          { routePath: '/instructors/learning-trees', text: 'My Learning Trees' },
          { routePath: '/instructors/assignment-templates', text: 'My Assignment Templates' },
          { routePath: '/all-questions/get', text: 'Search Questions' },
          { routePath: '/all-learning-trees/get', text: 'Browse Learning Trees' },
          { routePath: '/open-courses/public', text: 'Public Courses' },
          { routePath: '/open-courses/commons', text: 'Commons' },
          { routePath: '/instructors/frameworks', text: 'Frameworks' }]
        dashboards = [...dashboards, ...instructorDashboards]
      }
      if (!this.isMe) {
        dashboards = dashboards.filter(dashboard => dashboard.routeName !== 'question.editor')
      }
      return dashboards
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
      this.isInstructorsMyCoursesView = to.name === 'instructors.courses.index'
      this.isLearningTreesEditor = (to.name === 'instructors.learning_trees.editor') ? 'color:#E9ECEF !important;' : ''
      this.showNavBar = !['init_lms_assignment', 'anonymous-users-entry'].includes(this.$router.history.current.name)
      if (this.showNavBar) {
        this.getBreadcrumbs(this.$router.history.current)
        this.breadcrumbsLoaded = true
      }
      this.showToggleStudentView = this.user !== null && (this.user.role === 2 || this.user.fake_student)
      this.isInstructorView = this.user !== null && this.user.role === 2
      this.isAnonymousUser = this.user !== null && this.user.email === 'anonymous'
    }
  },
  created () {
    this.logout = logout
    const widgetScript = document.createElement('script')
    widgetScript.setAttribute(
      'src',
      'https://cdn.libretexts.net/libretexts-support-widget.min.js'
    )
    document.head.appendChild(widgetScript)
  },
  methods: {
    contactUsWidget () {
      document.getElementById('supportButton').click()
    },
    async exitLoginAs () {
      try {
        const { data } = await axios.post('/api/user/exit-login-as')
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        } else {
          // Save the token.
          await this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })
          // Fetch the user.
          await this.$store.dispatch('auth/fetchUser')
          if (this.$route.name !== 'instructors.courses.index') {
            await this.$router.push({ name: 'instructors.courses.index' })
          } else {
            location.reload()
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    loadRoutePath (routePath) {
      // need a reload since Search Questions is the same page as the Question Bank except for a parameter change
      window.location = routePath
    },
    getLogoHref () {
      let href = '/'
      if (this.user !== null) {
        if ([3, 4].includes(this.user.role)) {
          href = '/students/courses'
        }
        if ([2, 5].includes(this.user.role)) {
          href = '/instructors/courses'
        }
      }
      return href
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
          await this.$store.dispatch('auth/saveToken', {
            token: data.token,
            remember: false
          })
          await this.$store.dispatch('auth/fetchUser')
          this.isInstructorView = !this.isInstructorView
          if (data.new_route_name !== this.$router.history.current.name) {
            await this.getBreadcrumbs(this.$router)
            await this.$router.push({ name: data.new_route_name, params: { assignmentId: this.assignmentId } })
          } else {
            this.$router.go()
          }

          // Redirect to the correct home page
        } else {
          this.$noty.error(data.message)// no access
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getBreadcrumbs (router) {
      try {
        console.log(router.name)
        console.log({ 'name': router.name, 'params': router.params })
        this.breadcrumbs = [
          {
            text: '',
            href: '#',
            active: true
          }
        ]
        if (this.user && !localStorage.launchInNewWindow) {
          const { data } = await axios.post('/api/breadcrumbs', { 'name': router.name, 'params': router.params })
          if (data.type === 'success') {
            this.breadcrumbs = data.breadcrumbs
          }
        }
        this.oneBreadcrumb = this.breadcrumbs.length === 1 &&
          [
            'commons',
            'welcome',
            'refresh.question.requests',
            'manual.grade.passbacks',
            'testers.students.results'
          ].includes(router.name)
      } catch (error) {
        if (!error.message.includes('status code 401')) {
          this.$noty.error(error.message)
        }
      }
    },
    openSendEmailModal () {
      this.$refs.email.openSendEmailModal()
    }
  }
}
</script>

<style scoped>
.nav-link {
  padding-top: .25em;
}
</style>
