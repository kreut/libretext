<template>
  <div class="row">
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-complete-reset-password"/>
    <div class="col-lg-8 m-auto">
      <b-card header-html="<h1 class=&quot;h7&quot;>Reset Password</h1>">
        <form @submit.prevent="reset" @keydown="form.onKeydown($event)">
          <alert-success :form="form" :message="status"/>

          <!-- Email -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="email">{{ $t('email') }}</label>
            <div class="col-md-7">
              <input id="email"
                     v-model="form.email"
                     :class="{ 'is-invalid': form.errors.has('email') }"
                     class="form-control"
                     type="email"
                     name="email"
                     readonly
              >
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">Password*</label>
            <div class="col-md-7">
              <input v-model="form.password"
                     required
                     :class="{ 'is-invalid': form.errors.has('password') }"
                     class="form-control"
                     type="password"
                     name="password"
              >
              <has-error :form="form" field="password"/>
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="confirm_password">Confirm Password*</label>
            <div class="col-md-7">
              <input id="confirm_password"
                     v-model="form.password_confirmation"
                     required
                     :class="{ 'is-invalid': form.errors.has('password_confirmation') }"
                     class="form-control"
                     type="password"
                     name="password_confirmation"
              >
              <has-error :form="form" field="password_confirmation"/>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="form-group row">
            <div class="col-md-9 ml-md-auto">
              <v-button :loading="form.busy">
                {{ $t('reset_password') }}
              </v-button>
            </div>
          </div>
        </form>
      </b-card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  middleware: 'guest',
  components: { AllFormErrors },
  metaInfo () {
    return { title: 'Reset Password' }
  },

  data: () => ({
    status: '',
    allFormErrors: [],
    form: new Form({
      token: '',
      email: '',
      password: '',
      password_confirmation: ''
    })
  }),

  created () {
    this.form.email = this.$route.query.email
    this.form.token = this.$route.params.token
  },

  methods: {
    async reset () {
      try {
        const { data } = await this.form.post('/api/password/reset')

        this.status = data.status

        this.form.reset()
      } catch (error) {
        if (error.message.includes('status code 422')) {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-complete-reset-password')
        } else {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>
