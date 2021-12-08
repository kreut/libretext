<template>
  <div class="row">
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-reset-password"/>
    <div class="col-lg-8 m-auto">
      <card :title="$t('reset_password')">
        <form @submit.prevent="send" @keydown="form.onKeydown($event)">
          <alert-success :form="form" :message="status"/>

          <!-- Email -->
          <RequiredText :plural="false"/>
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="email">Email*
            </label>
            <div class="col-md-7">
              <input id="email"
                     v-model="form.email"
                     required
                     :class="{ 'is-invalid': form.errors.has('email') }"
                     class="form-control"
                     type="email"
                     name="email"
              >
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="form-group row">
            <div class="col-md-9 ml-md-auto">
              <v-button
                :loading="form.busy"
              >
                Send Password Reset Link
              </v-button>
            </div>
          </div>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  components: {
    AllFormErrors
  },
  middleware: 'guest',

  metaInfo () {
    return { title: 'Reset Password' }
  },

  data: () => ({
    status: '',
    form: new Form({
      email: ''
    }),
    allFormErrors: []
  }),
  methods: {
    async send () {
      this.form.busy = true
      try {
        const { data } = await this.form.post('/api/password/email')

        this.status = data.status

        this.form.reset()
      } catch (error) {
        if (error.message.includes('status code 422')) {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-reset-password')
        }
      }
    }
  }
}
</script>
