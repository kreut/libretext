<template>
  <div>
    <div v-if="hasAccess">
      <PageTitle title="Instructor Access Codes"/>
      <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :is-full-page="true"
                 :width="128"
                 :height="128"
                 color="#007BFF"
                 background="#FFFFFF"
        />
        <div v-show="!isLoading">
          <div>
            <b-card header="default" header-html="Create Instructor Access Codes" class="mb-3">
              <p>Create instructor access codes on the fly. These codes will be valid for 48 hours.</p>
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                label="Number of access codes"
              >
                <b-form-select v-model="numberOfInstructorAccessCodesForm.number_of_instructor_access_codes"
                               title="Number Of Instructor Access Codes"
                               :options="numberOfInstructorAccessCodesOptions"
                               :class="{ 'is-invalid': numberOfInstructorAccessCodesForm.errors.has('number_of_instructor_access_codes') }"
                               style="width: 60px"
                />
                <has-error :form="numberOfInstructorAccessCodesForm" field="number_of_instructor_access_codes'"/>
                <b-button variant="primary" size="sm" @click="createInstructorAccessCodes">
                  Create Codes
                </b-button>
              </b-form-group>
              <span v-if="instructorAccessCodes.length">
                The instructor access codes are valid for 48 hours.  Please copy them before leaving this page as they will
                only be shown once.
              </span>
              <ul v-for="instructorAccessCode in instructorAccessCodes" :key="instructorAccessCode">
                <li>
                  <span :id="instructorAccessCode">{{ instructorAccessCode }}</span> <a
                  href=""
                  class="pr-1"
                  aria-label="Copy Instructor Access Code"
                  @click.prevent="doCopy(instructorAccessCode)"
                >
                  <font-awesome-icon
                    :icon="copyIcon"
                  />
                </a>
                </li>
              </ul>
            </b-card>
            <b-card header="default" header-html="Email Instructor Access Codes" class="mb-5">
              <p>
                Enter a comma separated list of emails. Instructors will be sent an access code which they can use to
                register. Access codes are valid for 48 hours.
              </p>
              <b-form-group
              >
                <b-form-textarea
                  id="description"
                  v-model="instructorAccessCodeEmails"
                  rows="8"
                  type="text"
                />
                <div class="float-right pt-2">
                  <span v-if="numberOfEmailsToProcess >0"> Processing {{
                      emailsProcessed
                    }} of {{ numberOfEmailsToProcess }} emails</span>
                  <b-button size="sm" variant="primary" @click="emailInstructorAccessCodes">
                    Email Instructor Access Codes
                  </b-button>
                </div>
              </b-form-group>
              <div class="pb-6" style="height:200px">
                <div v-if="sentMessage.length">
                  <b-alert :show="true" variant="success">
                <span v-if="sent.length === numberOfEmailsToProcess" class="font-weight-bold">
                  All emails were sent successfully.
                </span>
                    <span v-if="sent.length < numberOfEmailsToProcess" class="font-weight-bold">
                  The following emails were sent successfully: {{ sentMessage }}
                </span>
                  </b-alert>
                </div>
                <div v-if="notSentMessage.length">
                  <b-alert :show="true" variant="danger">
                <span class="font-weight-bold">
                  We were unable to send the following emails: {{ notSentMessage }}
                </span>
                  </b-alert>
                </div>
              </div>
            </b-card>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { doCopy } from '~/helpers/Copy'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  components: {
    Loading,
    FontAwesomeIcon
  },
  data: () => ({
    sent: [],
    sentMessage: '',
    notSentMessage: '',
    numberOfEmailsToProcess: 0,
    emailsProcessed: 0,
    instructorAccessCodeEmails: '',
    copyIcon: faCopy,
    numberOfInstructorAccessCodesForm: new Form({
      number_of_instructor_access_codes: 1
    }),
    emailInstructorAccessCodesForm: new Form({
      email: ''
    }),
    numberOfInstructorAccessCodesOptions: [],
    isLoading: true,
    hasAccess: false,
    instructorAccessCodes: []
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe
  },
  mounted () {
    this.doCopy = doCopy
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
    for (let i = 1; i <= 10; i++) {
      this.numberOfInstructorAccessCodesOptions.push({ text: i, value: i })
    }
    this.isLoading = false
  },
  methods: {
    async createInstructorAccessCodes () {
      try {
        const { data } = await this.numberOfInstructorAccessCodesForm.post('/api/instructor-access-code')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.instructorAccessCodes = data.instructor_access_codes
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async emailInstructorAccessCodes () {
      let instructorAccessCodeEmails = this.instructorAccessCodeEmails.split(',')
      this.sent = []
      this.sentMessage = ''
      let notSent = []
      this.notSentMessage = ''
      this.numberOfEmailsToProcess = instructorAccessCodeEmails.length
      if (!instructorAccessCodeEmails.length) {
        this.$noty.error('Please enter at least one email.')
        return false
      }
      for (let i = 0; i < instructorAccessCodeEmails.length; i++) {
        this.emailsProcessed = i + 1
        this.emailInstructorAccessCodesForm.email = instructorAccessCodeEmails[i]
        console.log(this.emailInstructorAccessCodesForm.email)
        try {
          const { data } = await this.emailInstructorAccessCodesForm.post('/api/instructor-access-code/email')
          data.type === 'success'
            ? this.sent.push(this.emailInstructorAccessCodesForm.email)
            : notSent.push(this.emailInstructorAccessCodesForm.email)
        } catch (error) {
          let message = !error.message.includes('status code 422')
            ? error.message
            : this.emailInstructorAccessCodesForm.errors.get('email')
          notSent.push(`${this.emailInstructorAccessCodesForm.email} (${message})`)
        }
      }
      this.sentMessage = this.sent.join(', ')
      this.notSentMessage = notSent.join(', ')
      this.emailInstructorAccessCodesForm = new Form({ emails: '' })
    }
  }
}
</script>
