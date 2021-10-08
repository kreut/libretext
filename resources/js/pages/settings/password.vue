<template>
  <card title="Reset Password">
    <form @submit.prevent="update" @keydown="form.onKeydown($event)">
      <!-- Password -->
      <RequiredText/>
      <div class="form-group row">
        <label class="col-md-4 col-form-label text-md-right" for="password">New Password
          <Asterisk/>
        </label>
        <div class="col-md-7">
          <input id="password"
                 v-model="form.password"
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
        <label class="col-md-4 col-form-label text-md-right" for="confirm_password">Confirm Password
          <Asterisk/>
        </label>
        <div class="col-md-7">
          <input id="confirm_password"
                 v-model="form.password_confirmation"
                 :class="{ 'is-invalid': form.errors.has('password_confirmation') }"
                 class="form-control"
                 type="password"
                 name="password_confirmation"
          >
          <has-error :form="form" field="password_confirmation"/>
        </div>
      </div>

      <!-- Submit Button -->
      <hr>
      <div class="float-right">
        <b-button variant="primary" @click="update" size="sm">
          {{ $t('update') }}
        </b-button>
      </div>
    </form>
  </card>
</template>

<script>
import Form from 'vform'

export default {
  scrollToTop: false,

  metaInfo () {
    return { title: this.$t('settings') }
  },

  data: () => ({
    form: new Form({
      password: '',
      password_confirmation: ''
    })
  }),

  methods: {
    async update () {
      try {
        const { data } = await this.form.patch('/api/settings/password')
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>
