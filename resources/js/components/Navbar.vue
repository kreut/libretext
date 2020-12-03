<template>
  <div>
    <Email ref="email"
           extraEmailModalText="Please use this form to contact us regarding general questions or issues.  If you have a course specific question, please contact your instructor using your own email client."
           id="contact-us-modal"
           v-bind:fromUser="user"
           title="Contact Us"
           type="contact_us"
    ></Email>

    <b-navbar-brand href="/">
      <img src="/assets/img/libretexts_section_complete_adapt_header.png" class="d-inline-block align-top pl-3">
    </b-navbar-brand>


    <b-nav aria-label="breadcrumb" class="breadcrumb d-flex justify-content-between" style="padding-top:.3em !important;padding-bottom:0 !important; margin-bottom:0 !important;">
      <span style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important; padding-left:16px" v-if="oneBreadcrumb"><a :href="breadcrumbs[0]['href']">{{ breadcrumbs[0]['text']}}</a></span>
      <b-breadcrumb v-if="!oneBreadcrumb" :items="breadcrumbs" style="padding-top:.45em;padding-bottom:0 !important; margin-bottom:0 !important"></b-breadcrumb>
      <b-navbar-nav class="ml-auto mt-0 mb-0">
        <b-row>
          <b-nav-item-dropdown right v-if="user" class="mr-2">
            <!-- Using 'button-content' slot -->
            <template v-slot:button-content>
              <em>Hi, {{ user.first_name }}!</em>
            </template>
            <router-link :to="{ name: 'settings.profile' }" class="dropdown-item pl-3">
              <fa icon="cog" fixed-width/>
              {{ $t('settings') }}
            </router-link>
            <a href="#" class="dropdown-item pl-3" @click.prevent="logout">
              <fa icon="sign-out-alt" fixed-width/>
              {{ $t('logout') }}
            </a>
          </b-nav-item-dropdown>
          <b-navbar-nav v-show="!user">
              <b-nav-item href="/login">
                <router-link :to="{ name: 'login' }" class="nav-link" v-bind:style= "this.$router.history.current.name === 'login' ? 'color:#6C757D' : ''">
                  {{ $t('login') }}
                </router-link>
              </b-nav-item>
            </b-navbar-nav>
          <b-navbar-nav class="ml-2 mr-2">
            <b-nav-item>
          <span v-on:click="openSendEmailModal" class="nav-link" active-class="active">
            Contact Us
          </span>
            </b-nav-item>
          </b-navbar-nav>
          <b-nav-item-dropdown text="Register" class="pr-2" v-show="!user" right>
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
import {mapGetters} from 'vuex'
import LocaleDropdown from './LocaleDropdown'
import Email from './Email'


export default {
  components: {
    LocaleDropdown,
    Email
  },

  data: () => ({
    appName: window.config.appName,
    oneBreadcrumb: false,
    breadcrumbs: [
      {
        text: '',
        href: '#',
        active: true
      }
    ]
  }),
  watch: {
    '$route'(to, from) {
      if (this.user) {
        this.getBreadcrumbs(this.$router.history.current)
      } else {
        this.breadcrumbs = []
      }
    }
  },
  computed: mapGetters({
    user: 'auth/user'
  }),
  methods: {
    async getBreadcrumbs(router) {
      try {
        console.log(router.name)
        const {data} = await axios.post('/api/breadcrumbs', {'name': router.name, 'params': router.params})
        this.breadcrumbs = (data.type === 'success') ? data.breadcrumbs : []
        this.oneBreadcrumb = this.breadcrumbs.length === 1 && ['welcome','instructors.learning_trees.index','instructors.courses.index'].includes(router.name)
      } catch (error) {
        console.log(error.message)
      }
    },
    openSendEmailModal() {
      this.$refs.email.openSendEmailModal()
    },
    async logout() {
      // Log out the user.
      await this.$store.dispatch('auth/logout')

      // Redirect to login.
      this.$router.push({name: 'login'})
    }
  }
}
</script>

<style scoped>
.nav-link {
  padding-top: .25em;
}

</style>
