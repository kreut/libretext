<template>
  <card :title="$t('your_info')">
    <form @submit.prevent="update" @keydown="form.onKeydown($event)">
      <!-- Name -->
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right">{{ $t('name') }}</label>
        <div class="col-md-3">
          <input v-model="form.first_name" :class="{ 'is-invalid': form.errors.has('first_name') }"
                 class="form-control" type="text" name="first_name" placeholder="First"
          >
          <has-error :form="form" field="first_name" />
        </div>
        <div class="col-md-4">
          <input v-model="form.last_name" :class="{ 'is-invalid': form.errors.has('last_name') }"
                 class="form-control" type="text" name="last_name" placeholder="Last"
          >
          <has-error :form="form" field="last_name" />
        </div>
      </div>

      <!-- Email -->
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
        <div class="col-md-7">
          <input v-model="form.email" :class="{ 'is-invalid': form.errors.has('email') }" class="form-control" type="email" name="email">
          <has-error :form="form" field="email" />
        </div>
      </div>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right">Time zone</label>
        <div class="col-md-7" @change="removeTimeZoneError()">
          <b-form-select v-model="form.time_zone"
                         :options="timeZones"
                         :class="{ 'is-invalid': form.errors.has('time_zone') }"
          />
          <has-error :form="form" field="time_zone" />
        </div>
      </div>
      <!-- Submit Button -->

      <div class="float-right">
        <b-button variant="primary">
          {{ $t('update') }}
        </b-button>
      </div>
    </form>
  </card>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'

export default {
  scrollToTop: false,

  metaInfo () {
    return { title: this.$t('settings') }
  },

  data: () => ({
    form: new Form({
      first_name: '',
      last_name: '',
      email: '',
      time_zone: null
    }),
    timeZones: [
      { value: null, text: 'Please select a time zone' }
    ],
    currentTimeZone: ''
  }),

  computed: mapGetters({
    user: 'auth/user'
  }),

  created () {
    // Fill the form with user data.
    this.form.keys().forEach(key => {
      this.form[key] = this.user[key]
    })
  },
  mounted () {
    let timeZones = getTimeZones()
    populateTimeZoneSelect(timeZones, this)
    this.form.time_zone = this.user.time_zone
  },
  methods: {
    removeTimeZoneError () {
      this.form.errors.clear('time_zone')
    },
    async update () {
      try {
        const { data } = await this.form.patch('/api/settings/profile')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$store.dispatch('auth/updateUser', { user: data.user })
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    }
  }
}
</script>
