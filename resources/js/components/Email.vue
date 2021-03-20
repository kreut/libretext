<template>
  <div>
    <b-modal
      :id="id"
      ref="modal"
      :title="title"
      ok-title="Submit"
      type="type"
      extra-params="extraParams"
      size="lg"
      @ok="submitSendEmail"
    >
      <p>{{ extraEmailModalText }}</p>
      <b-form ref="form">
        <b-form-group
          id="name"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Name"
          label-for="name"
        >
          <b-form-input
            id="name"
            v-model="sendEmailForm.name"
            class="col-6"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('name') }"
            @keydown="sendEmailForm.errors.clear('name')"
          />
          <has-error :form="sendEmailForm" field="name" />
        </b-form-group>
        <b-form-group
          id="email"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Email"
          label-for="email"
        >
          <b-form-input
            id="name"
            v-model="sendEmailForm.email"
            class="col-6"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('email') }"
            @keydown="sendEmailForm.errors.clear('email')"
          />
          <has-error :form="sendEmailForm" field="email" />
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
            v-model="sendEmailForm.subject"
            class="col-8"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('subject') }"
            @keydown="sendEmailForm.errors.clear('subject')"
          />
          <has-error :form="sendEmailForm" field="subject" />
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
            v-model="sendEmailForm.text"
            placeholder="Enter something..."
            rows="6"
            max-rows="6"
            :class="{ 'is-invalid': sendEmailForm.errors.has('text') }"
            @keydown="sendEmailForm.errors.clear('text')"
          />
          <has-error :form="sendEmailForm" field="text" />
        </b-form-group>
        <div v-if="sendingEmail" class="float-right">
          <b-spinner small type="grow" />
          Sending Email...
        </div>
      </b-form>
    </b-modal>
  </div>
</template>
<script>

import Form from 'vform'

export default {
  props: ['fromUser', 'title', 'subject', 'id', 'extraEmailModalText', 'type'],
  data: () => ({
    showSendEmailModal: false,
    sendEmailForm: new Form({
      name: '',
      email: '',
      subject: '',
      text: ''
    }),
    sendingEmail: false
  }),
  methods: {
    resetSendEmailModal () {
      this.sendEmailForm.name = this.fromUser ? this.fromUser.first_name + ' ' + this.fromUser.last_name : ''
      this.sendEmailForm.email = this.fromUser ? this.fromUser.email : ''
      this.sendEmailForm.text = ''
      this.sendEmailForm.errors.clear()
    },
    openSendEmailModal (toUserId = 0) {
      this.showSendEmailModal = true
      this.resetSendEmailModal()
      this.sendEmailForm.to_user_id = toUserId
      this.sendEmailForm.subject = this.subject
      this.sendEmailForm.type = this.type
      this.$bvModal.show(this.id)
    },
    setExtraParams (extraParams) {
      this.sendEmailForm.extraParams = extraParams
    },
    async submitSendEmail (bvModalEvt) {
      bvModalEvt.preventDefault()
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
        }
      }
      this.sendingEmail = false
    }
  }
}
</script>
