<template>
  <div>

    <b-navbar-brand href="/">
      <img src="https://adapt.libretexts.org/assets/img/libretexts_section_complete_adapt_header.png"  class="d-inline-block align-top pl-3">
    </b-navbar-brand>

  <b-navbar toggleable="lg" type="dark" variant="info">

    <!--<b-navbar-brand href="#">
      <router-link :to="{ name: user ? 'home' : 'welcome' }" class="navbar-brand">
        {{ appName }}
      </router-link>
    </b-navbar-brand>-->
    <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

    <b-collapse id="nav-collapse" is-nav>
      <b-navbar-nav v-if="user">
        <b-nav-item href="#" v-if="user">
          <router-link :to="{ name: (user.role === 3) ? 'students.courses.index' : 'instructors.courses.index'}" class="nav-link">
            My Courses
          </router-link>
        </b-nav-item>

      </b-navbar-nav>

      <!-- Right aligned nav items -->
      <b-navbar-nav class="ml-auto">

        <b-nav-item-dropdown right v-if="user">
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
        <b-navbar-nav v-if="!user">

          <b-navbar-nav>
          <b-nav-item href="/login">
            <router-link :to="{ name: 'login' }" class="nav-link" active-class="active">
              {{ $t('login') }}
            </router-link>
          </b-nav-item>
          </b-navbar-nav>

          <b-nav-item-dropdown text="Register" right>
            <b-dropdown-item href="#"> <router-link  :to="{ path: '/register/student' }" class="dropdown-item pl-3">
             Student
            </router-link>
            </b-dropdown-item>
            <b-dropdown-item href="#"> <router-link  :to="{ path: '/register/instructor' }" class="dropdown-item pl-3">
              Instructor
            </router-link>
            </b-dropdown-item>
          </b-nav-item-dropdown>

        </b-navbar-nav>
      </b-navbar-nav>
    </b-collapse>
  </b-navbar>

  </div>

</template>

<script>
  import {mapGetters} from 'vuex'
  import LocaleDropdown from './LocaleDropdown'

  export default {
    components: {
      LocaleDropdown
    },

    data: () => ({
      appName: window.config.appName
    }),

    computed: mapGetters({
      user: 'auth/user'
    }),

    methods: {
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
  .bg-info {
    background-color: #b4b4b4 !important;
  }
</style>
