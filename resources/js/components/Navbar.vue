<template>
  <div>
    <b-modal
      id="modal-contact-us"
      ref="modal"
      title="Contact Us"
      @ok="submitContactUs"
      ok-title="Submit"
      size="lg"
    >
      <b-form ref="form">
        <b-form-group
          id="name"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Name"
          label-for="name"

        >
          <b-form-input
            class="col-6"
            id="name"
            v-model="contactUsForm.name"
            type="text"
            :class="{ 'is-invalid': contactUsForm.errors.has('name') }"
            @keydown="contactUsForm.errors.clear('name')"
          >
          </b-form-input>
          <has-error :form="contactUsForm" field="name"></has-error>

        </b-form-group>
        <b-form-group
          id="email"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Email"
          label-for="email"
        >
          <b-form-input
            class="col-6"
            id="name"
            v-model="contactUsForm.email"
            type="text"
            :class="{ 'is-invalid': contactUsForm.errors.has('email') }"
            @keydown="contactUsForm.errors.clear('email')"
          >
          </b-form-input>
          <has-error :form="contactUsForm" field="email"></has-error>
        </b-form-group>
        <b-form-group
          id="subject"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Subject"
          label-for="subject"
        >
          <b-form-input
            id="subject"
            class="col-6"
            v-model="contactUsForm.subject"
            type="text"
            :class="{ 'is-invalid': contactUsForm.errors.has('subject') }"
            @keydown="contactUsForm.errors.clear('subject')"
          >
          </b-form-input>
          <has-error :form="contactUsForm" field="subject"></has-error>
        </b-form-group>
        <b-form-group
          id="message"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Message"
          label-for="message"
        >
          <b-form-textarea
            id="text"
            v-model="contactUsForm.text"
            placeholder="Enter something..."
            rows="6"
            max-rows="6"
            :class="{ 'is-invalid': contactUsForm.errors.has('text') }"
            @keydown="contactUsForm.errors.clear('text')"
          ></b-form-textarea>
          <has-error :form="contactUsForm" field="text"></has-error>
        </b-form-group>
        <div v-if="sendingEmail" class="float-right">
          <b-spinner small type="grow"></b-spinner>
          Sending Email...
        </div>
      </b-form>
    </b-modal>

    <b-navbar-brand href="/">
      <img src="/assets/img/libretexts_section_complete_adapt_header.png" class="d-inline-block align-top pl-3">
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
            <router-link :to="{ name: (user.role === 3) ? 'students.courses.index' : 'instructors.courses.index'}"
                         class="nav-link">
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
          </b-navbar-nav>
          <b-navbar-nav>
            <b-nav-item>
          <span v-on:click="openContactUsModal" class="nav-link" active-class="active">
            Contact Us
          </span>
            </b-nav-item>
          </b-navbar-nav>
        </b-navbar-nav>

      </b-collapse>
    </b-navbar>


  </div>

</template>

<script>

import {mapGetters} from 'vuex'
import LocaleDropdown from './LocaleDropdown'
import Form from "vform";

export default {
  components: {
    LocaleDropdown
  },

  data: () => ({
    appName: window.config.appName,
    showContactUsModal: false,
    contactUsForm: new Form({
      name: '',
      email: '',
      subject: '',
      text: ''
    }),
    sendingEmail: false
  }),

  computed: mapGetters({
    user: 'auth/user'
  }),

  methods: {
    resetContactUsModal() {
      this.contactUsForm.name = this.user ? this.user.first_name + ' ' + this.user.last_name : ''
      this.contactUsForm.email = this.user ? this.user.email : ''
      this.contactUsForm.subject = ''
      this.contactUsForm.text = ''
      this.contactUsForm.errors.clear()
    },
    openContactUsModal() {
      this.showContactUsModal = true
      this.resetContactUsModal()
      this.$bvModal.show('modal-contact-us')
    },
    async submitContactUs(bvModalEvt) {
      bvModalEvt.preventDefault()
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      try {
        this.sendingEmail = true
        console.log(this.contactUsForm)
        const {data} = await this.contactUsForm.post('/api/contact-us')
        if (data.type === 'success') {
          this.$bvModal.hide('modal-contact-us')
        }
        this.$noty[data.type](data.message)

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.sendingEmail = false


    },
    resetModalForms() {

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

.bg-info {
  background-color: #b4b4b4 !important;
}
</style>
