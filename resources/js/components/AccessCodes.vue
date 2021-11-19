<template>
  <div>
    <b-card header="default" :header-html="getCardTitle()" class="mb-3">
      <p>Create {{ accessCodeType }} access codes on the fly. These codes will be valid for 48 hours.</p>
      <b-form-group
        label-cols-sm="4"
        label-cols-lg="3"
        label="Number of access codes"
      >
        <b-form-select v-model="numberOfAccessCodesForm.number_of_access_codes"
                       :title="getFormTitle()"
                       :options="numberOfAccessCodesOptions"
                       :class="{ 'is-invalid': numberOfAccessCodesForm.errors.has('number_of_access_codes') }"
                       style="width: 60px"
        />
        <has-error :form="numberOfAccessCodesForm" field="number_of_access_codes'"/>
        <b-button variant="primary" size="sm" @click="createAccessCodes">
          Create Access Codes
        </b-button>
      </b-form-group>
      <span v-if="accessCodes.length">
                The {{ accessCodeType }} access codes are valid for 48 hours.  Please copy them before leaving this page as they will
                only be shown once.
              </span>
      <ul v-for="accessCode in accessCodes" :key="accessCode">
        <li>
          <span :id="accessCode">{{ accessCode }}</span> <a
          href=""
          class="pr-1"
          aria-label="Copy Access Code"
          @click.prevent="doCopy(accessCode)"
        >
          <font-awesome-icon
            :icon="copyIcon"
          />
        </a>
        </li>
      </ul>
    </b-card>
    <b-card header="default" :header-html="getEmailTitle()" class="mb-5">
      <p>
        Enter a comma separated list of emails. {{ getCapitalizedType() }}s will be sent an access code which they can use to
        register. Access codes are valid for 48 hours.
      </p>
      <b-form-group
      >
        <b-form-textarea
          id="description"
          v-model="accessCodeEmails"
          rows="8"
          type="text"
        />
        <div class="float-right pt-2">
                  <span v-if="numberOfEmailsToProcess >0"> Processing {{
                      emailsProcessed
                    }} of {{ numberOfEmailsToProcess }} emails</span>
          <b-button size="sm" variant="primary" @click="emailAccessCodes">
            {{ getEmailAccessCodesButton() }}
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
</template>

<script>
import { doCopy } from '~/helpers/Copy'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import Form from 'vform'
import _ from 'lodash'

export default {
  name: 'AccessCodes',
  components: {
    FontAwesomeIcon
  },
  props: {
    accessCodeType: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    fields: [
      'name',
      'email',
      'actions'
    ],
    sent: [],
    sentMessage: '',
    notSentMessage: '',
    numberOfEmailsToProcess: 0,
    emailsProcessed: 0,
    accessCodeEmails: '',
    copyIcon: faCopy,
    numberOfAccessCodesForm: new Form({
      number_of_access_codes: 1
    }),
    emailAccessCodesForm: new Form({
      email: ''
    }),
    numberOfAccessCodesOptions: [],
    accessCodes: []
  }),
  mounted () {
    if (!['instructor', 'question editor'].includes(this.accessCodeType)) {
      this.$noty.error(`${this.accessCodeType} is not a valid access code type`)
      return false
    }
    this.doCopy = doCopy
    for (let i = 1; i <= 10; i++) {
      this.numberOfAccessCodesOptions.push({ text: i, value: i })
    }
    this.emailAccessCodesForm.type = this.numberOfAccessCodesForm.type = this.accessCodeType
  },
  methods: {
    getCapitalizedType () {
      return _.startCase(this.accessCodeType)
    },
    getFormTitle () {
      return 'Create ' + _.startCase(this.accessCodeType) + ' Access Codes'
    },
    getCardTitle () {
      return 'Create ' + _.startCase(this.accessCodeType) + ' Access Codes'
    },
    getEmailTitle () {
      return 'Email ' + _.startCase(this.accessCodeType) + ' Access Codes'
    },
    getEmailAccessCodesButton () {
      return 'Email ' + _.startCase(this.accessCodeType) + ' Access Codes'
    },
    async createAccessCodes () {
      try {
        const { data } = await this.numberOfAccessCodesForm.post('/api/access-code')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.accessCodes = data.access_codes
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async emailAccessCodes () {
      let accessCodeEmails = this.accessCodeEmails.split(',')
      this.sent = []
      this.sentMessage = ''
      let notSent = []
      this.notSentMessage = ''
      this.numberOfEmailsToProcess = accessCodeEmails.length
      if (!accessCodeEmails.length) {
        this.$noty.error('Please enter at least one email.')
        return false
      }
      for (let i = 0; i < accessCodeEmails.length; i++) {
        this.emailsProcessed = i + 1
        this.emailAccessCodesForm.email = accessCodeEmails[i]
        console.log(this.emailAccessCodesForm.email)
        try {
          const { data } = await this.emailAccessCodesForm.post('/api/access-code/email')
          data.type === 'success'
            ? this.sent.push(this.emailAccessCodesForm.email)
            : notSent.push(`${this.emailAccessCodesForm.email} (${data.message})`)
        } catch (error) {
          let message = !error.message.includes('status code 422')
            ? error.message
            : this.emailAccessCodesForm.errors.get('email')
          notSent.push(`${this.emailAccessCodesForm.email} (${message})`)
        }
      }
      this.sentMessage = this.sent.join(', ')
      this.notSentMessage = notSent.join(', ')
      this.emailInstructorAccessCodesForm = new Form({ emails: '' })
    }
  }
}
</script>
