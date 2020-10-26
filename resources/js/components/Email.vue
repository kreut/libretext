<template>
  <div>

    <b-modal
      v-bind:id="id"
      ref="modal"
      v-bind:title="title"
      @ok="submitSendEmail"
      ok-title="Submit"
      type="type"
      extraParams="extraParams"
      size="lg"
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
            class="col-6"
            id="name"
            v-model="sendEmailForm.name"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('name') }"
            @keydown="sendEmailForm.errors.clear('name')"
          >
          </b-form-input>
          <has-error :form="sendEmailForm" field="name"></has-error>

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
            v-model="sendEmailForm.email"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('email') }"
            @keydown="sendEmailForm.errors.clear('email')"
          >
          </b-form-input>
          <has-error :form="sendEmailForm" field="email"></has-error>
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
            class="col-8"
            v-model="sendEmailForm.subject"
            type="text"
            :class="{ 'is-invalid': sendEmailForm.errors.has('subject') }"
            @keydown="sendEmailForm.errors.clear('subject')"
          >
          </b-form-input>
          <has-error :form="sendEmailForm" field="subject"></has-error>
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
          ></b-form-textarea>
          <has-error :form="sendEmailForm" field="text"></has-error>
        </b-form-group>
        <div v-if="sendingEmail" class="float-right">
          <b-spinner small type="grow"></b-spinner>
          Sending Email...
        </div>
      </b-form>
    </b-modal>
  </div>
</template>
<script>

import Form from "vform"


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
    resetSendEmailModal() {
      this.sendEmailForm.name = this.fromUser ? this.fromUser.first_name + ' ' + this.fromUser.last_name : ''
      this.sendEmailForm.email = this.fromUser ? this.fromUser.email : ''
      this.sendEmailForm.text = ''
      this.sendEmailForm.errors.clear()
    },
    openSendEmailModal(toUserId = 0) {
      this.showSendEmailModal = true
      this.resetSendEmailModal()
      this.sendEmailForm.to_user_id = toUserId
      this.sendEmailForm.subject = this.subject
      this.sendEmailForm.type = this.type
      console.log(this.sendEmailForm.extraParams.questionId)
      this.$bvModal.show(this.id)
    },
    setExtraParams(extraParams){
      this.sendEmailForm.extraParams = extraParams
    },
    async submitSendEmail(bvModalEvt) {
      bvModalEvt.preventDefault()
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      this.sendingEmail = true
      try {
        const {data} = await this.sendEmailForm.post('/api/email/send')
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
