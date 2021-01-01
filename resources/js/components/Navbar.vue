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
      <img src="/assets/img/libretexts_section_complete_adapt_header.png" class="d-inline-block align-top pl-3">
    </b-navbar-brand>

    <b-nav aria-label="breadcrumb" class="breadcrumb d-flex justify-content-between" style="padding-top:.3em !important;padding-bottom:0 !important; margin-bottom:0 !important;">
      <span v-if="oneBreadcrumb" style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important; padding-left:16px"><a :href="breadcrumbs[0]['href']">{{ breadcrumbs[0]['text'] }}</a></span>
      <b-breadcrumb v-if="!oneBreadcrumb" :items="breadcrumbs" style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important" />
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
              <router-link :to="{ name: 'login' }" class="nav-link" :style="this.$router.history.current.name === 'login' ? 'color:#6C757D' : ''">
                {{ $t('login') }}
              </router-link>
            </b-nav-item>
          </b-navbar-nav>
          <b-navbar-nav v-if="isMe && (user !== null)" class="ml-2 mr-2 mb-1">
            <b-nav-item>
              <span class="nav-link" active-class="active">
                <router-link :to="{ name: 'login.as'}">
                  Login As
                </router-link>
              </span>
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

export default {
  components: {
    Email
  },

  data: () => ({
    appName: window.config.appName,
    oneBreadcrumb: false,
    isLearningTreesEditor: false,
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
      this.isLearningTreesEditor = (to.name === 'instructors.learning_trees.editor') ? 'color:#E9ECEF !important;' : ''
      if (this.user) {
        this.getBreadcrumbs(this.$router.history.current)
      } else {
        this.breadcrumbs = []
      }
    }
  },
  methods: {
    async getBreadcrumbs (router) {
      try {
        console.log(router.name)
        const { data } = await axios.post('/api/breadcrumbs', { 'name': router.name, 'params': router.params })
        this.breadcrumbs = (data.type === 'success') ? data.breadcrumbs : []
        this.oneBreadcrumb = this.breadcrumbs.length === 1 && ['welcome', 'instructors.learning_trees.index', 'instructors.courses.index'].includes(router.name)
      } catch (error) {
        console.log(error.message)
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
