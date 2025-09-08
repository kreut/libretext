<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="modalFormErrorsId"/>
    <b-modal
      :id="id"
      ref="modal"
      type="type"
      extra-params="extraParams"
      :title="title"
      size="lg"

    >
      <p>{{ extraEmailModalText }}</p>
      <b-form ref="form">
        <RequiredText v-show="type !== 'contact_grader'"/>
        <b-form-group
          v-show="showRequestInstructorAccessCode"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Subject"
          label-for="subject"
        >
          <template v-slot:label>
            Subject*
          </template>
          <div v-if="showSubjectOptions">
            <b-form-select id="subject"
                           v-model="sendEmailForm.subject"
                           required
                           :options="subjectOptions"
                           style="width:280px"
                           @change="checkIfRequestInstructorAccessCode($event)"
            />
          </div>
          <div v-if="!showSubjectOptions">
            <b-form-input
              id="subject"
              v-model="sendEmailForm.subject"
              required
              class="col-8"
              type="text"
              :class="{ 'is-invalid': sendEmailForm.errors.has('subject') }"
              @keydown="sendEmailForm.errors.clear('subject')"
            />
            <has-error :form="sendEmailForm" field="subject"/>
          </div>
        </b-form-group>
        <div v-show="false">
          <b-form-input
            id="subject"
            v-model="sendEmailForm.subject"
            required
            class="col-8"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('subject') }"
            @keydown="sendEmailForm.errors.clear('subject')"
          />
          <has-error :form="sendEmailForm" field="subject"/>
        </div>
        <b-form-group
          v-if="sendEmailForm.subject !== 'Email Change' && type !== 'contact_grader'"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Name*"
          label-for="email_from_name"
        >
          <b-form-input
            id="email_from_name"
            v-model="sendEmailForm.name"
            required
            class="col-6"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('name') }"
            @keydown="sendEmailForm.errors.clear('name')"
          />
          <has-error :form="sendEmailForm" field="name"/>
        </b-form-group>
        <b-form-group
          v-if="sendEmailForm.subject !== 'Email Change' && type !== 'contact_grader'"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Email*"
          label-for="email_in_contact_us_form"
        >
          <b-form-input
            id="email_in_contact_us_form"
            v-model="sendEmailForm.email"
            required
            class="col-6"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('email') }"
            @keydown="sendEmailForm.errors.clear('email')"
          />
          <has-error :form="sendEmailForm" field="email"/>
        </b-form-group>
        <b-form-group
          v-if="showSchool"
          label-cols-sm="3"
          label-cols-lg="2"
          label="School*"
          label-for="school"
        >
          <b-form-input
            id="school"
            v-model="sendEmailForm.school"
            required
            class="col-6"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('school') }"
            @keydown="sendEmailForm.errors.clear('school')"
          />
          <has-error :form="sendEmailForm" field="school"/>
        </b-form-group>
        <div v-if="sendEmailForm.subject === 'Email Change'">
          <p>
            If you are a student and need your emailed changed, please contact your instructor directly. They can update
            your email by going to Course Properties->Students.
          </p>
        </div>

        <b-form-group
          v-if="sendEmailForm.subject !== 'Email Change'"
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="message"
        >
          <template #label>
            <span class="ml-1">Message<span v-show="type !== 'contact_grader'">*</span></span>
          </template>
          <b-form-textarea
            id="message"
            v-model="sendEmailForm.text"
            required
            placeholder="Enter something..."
            rows="6"
            max-rows="6"
            :class="{ 'is-invalid': sendEmailForm.errors.has('text') }"
            @keydown="sendEmailForm.errors.clear('text')"
          />
          <has-error :form="sendEmailForm" field="text"/>
        </b-form-group>
        <div v-if="sendingEmail" class="float-right">
          <b-spinner small type="grow"/>
          Sending Email...
        </div>
      </b-form>
      <template #modal-footer="{  ok }">
        <b-button size="sm"
                  variant="primary"
                  :disabled="sendEmailForm.subject === 'Email Change'"
                  @click="submitSendEmail()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
  </div>
</template>
<script>

import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import _ from 'lodash'
import { fixInvalid } from '../helpers/accessibility/FixInvalid'

export default {
  components: { AllFormErrors },
  props: {
    fromUser: {
      type: Object,
      default: function () {
        return {}
      }
    },
    title: {
      type: String,
      default: ''
    },
    subject: {
      type: String,
      default: ''
    },
    id: {
      type: String,
      default: ''
    },
    extraEmailModalText: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: ''
    }
  },
  data: () => ({
    showRequestInstructorAccessCode: true,
    showSchool: false,
    subjectOptions: [
      { value: 'General Inquiry', text: 'General Inquiry' },

      { value: 'Technical Issue', text: 'Technical Issue' },
      { value: 'Email Change', text: 'Email Change' },
      { value: 'Request Instructor Access Code', text: 'Request Instructor Access Code' },
      { value: 'Integrating ADAPT with LMS', text: 'Integrating ADAPT with LMS' },
      { value: 'Other', text: 'Other' }
    ],
    showSubjectOptions: false,
    modalFormErrorsId: '',
    allFormErrors: [],
    showSendEmailModal: false,
    sendEmailForm: new Form({
      name: '',
      email: '',
      subject: '',
      text: '',
      school: ''
    }),
    sendingEmail: false
  }),
  created () {
    this.modalFormErrorsId = 'modal-form-errors-' + this.id
    if (this.type === 'contact_us') {
      this.showSubjectOptions = true
    }
    if (this.subject === 'Request Instructor Access Code') {
      this.sendEmailForm.subject = 'Request Instructor Access Code'
      this.showSchool = true
    }
  },
  methods: {
    checkIfRequestInstructorAccessCode (option) {
      this.showSchool = option === 'Request Instructor Access Code'
    },
    resetSendEmailModal () {
      this.sendEmailForm.name = !_.isEmpty(this.fromUser) && this.fromUser.email !== 'anonymous' ? this.fromUser.first_name + ' ' + this.fromUser.last_name : ''
      this.sendEmailForm.email = !_.isEmpty({}) && this.fromUser.email !== 'anonymous' ? this.fromUser.email : ''
      this.sendEmailForm.text = ''
      this.sendEmailForm.school = ''
      this.showSchool = false
      this.sendEmailForm.errors.clear()
    },
    openSendEmailModal (toUserId = 0) {
      this.showSendEmailModal = true
      this.resetSendEmailModal()
      this.sendEmailForm.to_user_id = toUserId
      this.sendEmailForm.subject = this.subject
      if (this.subject === 'Request Instructor Access Code') {
        this.showSchool = true
        this.showRequestInstructorAccessCode = false
      }
      if (this.type === 'contact_grader') {
        this.showRequestInstructorAccessCode = false
      }
      this.sendEmailForm.type = this.type
      this.$bvModal.show(this.id)
    },
    setExtraParams (extraParams) {
      this.sendEmailForm.extraParams = extraParams
    },
    async submitSendEmail () {
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      this.sendingEmail = true
      try {
        const { data } = await this.sendEmailForm.post('/api/email/send')
        console.log(data)
        if (data.type === 'success') {
          this.$bvModal.hide(this.id)
        }
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.sendEmailForm.errors.flatten()
          this.$bvModal.show(this.modalFormErrorsId)
        }
      }
      this.sendingEmail = false
    }
  }
}
</script>
