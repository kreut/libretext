<template>
  <div class="row">
    <div class="col-lg-8 m-auto">
      <card :title="$t('login')">
        <form @submit.prevent="createCourse" @keydown="form.onKeydown($event)">
          <!-- Email -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
            <div class="col-md-7">
              <input v-model="form.email" :class="{ 'is-invalid': form.errors.has('email') }" class="form-control" type="email" name="email">
              <has-error :form="form" field="email" />
            </div>
          </div>

          <div class="form-group row">
            <div class="col-md-7 offset-md-3 d-flex">
              <!-- Submit Button -->
              <v-button :loading="form.busy">
                {{ $t('login') }}
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
  import LoginWithGithub from '~/components/LoginWithGithub'

  export default {
    middleware: 'auth',

    metaInfo () {
      return { title: this.$t('login') }
    },

    data: () => ({
      form: new Form({
        email: ''
      })
    }),

    methods: {
      async createCourse () {
        // Submit the form.
        const { data } = await this.form.post('/api/courses')
      console.log(data);
      }
    }
  }
</script>
