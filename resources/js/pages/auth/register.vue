<template>
  <div class="row">
    <div class="col-lg-8 m-auto">
      <card v-if="mustVerifyEmail" :title="$t('register')">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card v-else :title="$t('register')">
        <form @submit.prevent="register" @keydown="form.onKeydown($event)">
          <!-- Name -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('name') }}</label>
            <div class="col-md-3">
              <input v-model="form.first_name" :class="{ 'is-invalid': form.errors.has('first_name') }"
                     class="form-control" type="text" name="first_name" placeholder="First">
              <has-error :form="form" field="first_name"/>
            </div>
            <div class="col-md-4">
              <input v-model="form.last_name" :class="{ 'is-invalid': form.errors.has('last_name') }"
                     class="form-control" type="text" name="last_name" placeholder="Last">
              <has-error :form="form" field="last_name"/>
            </div>
          </div>

          <!-- Email -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
            <div class="col-md-7">
              <input v-model="form.email" :class="{ 'is-invalid': form.errors.has('email') }" class="form-control"
                     type="email" name="email">
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('password') }}</label>
            <div class="col-md-7">
              <input v-model="form.password" :class="{ 'is-invalid': form.errors.has('password') }" class="form-control"
                     type="password" name="password">
              <has-error :form="form" field="password"/>
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('confirm_password') }}</label>
            <div class="col-md-7">
              <input v-model="form.password_confirmation"
                     :class="{ 'is-invalid': form.errors.has('password_confirmation') }" class="form-control"
                     type="password" name="password_confirmation">
              <has-error :form="form" field="password_confirmation"/>
            </div>
          </div>

          <!-- Access Code -->
          <div class="form-group row" v-if="isInstructor">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('access_code') }}</label>
            <div class="col-md-7">
              <input v-model="form.access_code" :class="{ 'is-invalid': form.errors.has('access_code') }"
                     class="form-control" type="text" name="access_code">
              <has-error :form="form" field="access_code"/>
            </div>
          </div>

          <div class="form-group row">
            <div class="col-md-7 offset-md-3 d-flex">
              <!-- Submit Button -->
              <v-button :loading="form.busy">
                {{ $t('register') }}
              </v-button>

              <!-- GitHub Register Button -->
              <login-with-github/>
            </div>
          </div>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
  import Form from 'vform'
  import LoginWithGithub from '~/components/LoginWithGithub'

  export default {
    middleware: 'guest',

    components: {
      LoginWithGithub
    },

    metaInfo() {
      return {title: this.$t('register')}
    },
    mounted() {
      this.isInstructor = this.$route.path.includes('instructor')
      this.form.registration_type = this.isInstructor ? 'instructor' : 'student';
    },
    data: () => ({
      form: new Form({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        access_code: '',
        registration_type: ''
      }),
      mustVerifyEmail: false,
      isInstructor: ''
    }),

    methods: {
      async register() {

        // Register the user.
        const {data} = await this.form.post('/api/register')
          // Must verify email fist.
          if (data.status) {
            this.mustVerifyEmail = true
          } else {
            // Log in the user.
            const {data: {token}} = await this.form.post('/api/login')

            // Save the token.
            this.$store.dispatch('auth/saveToken', {token})

            // Update the user.
            await this.$store.dispatch('auth/updateUser', {user: data})

            // Redirect home.
            this.$router.push({name: 'home'})
          }
      }
    }
  }
</script>
