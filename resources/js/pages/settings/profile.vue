<template>
  <card title="Your Info">
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-profile'"/>
    <form @submit.prevent="update" @keydown="form.onKeydown($event)">
      <!-- Name -->
      <RequiredText/>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="first_name">First Name*
        </label>
        <div class="col-md-7">
          <input id="first_name"
                 v-model="form.first_name"
                 :class="{ 'is-invalid': form.errors.has('first_name') }"
                 class="form-control"
                 type="text"
                 name="first_name"
                 placeholder="First"
          >
          <has-error :form="form" field="first_name"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="last_name">Last Name*
        </label>
        <div class="col-md-7">
          <input id="last_name"
                 v-model="form.last_name"
                 :class="{ 'is-invalid': form.errors.has('last_name') }"
                 class="form-control"
                 type="text"
                 name="last_name"
                 placeholder="Last"
          >
          <has-error :form="form" field="last_name"/>
        </div>
      </div>
      <div v-if="user.role === 3" class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="student_id">Student ID*
        </label>
        <div class="col-md-7">
          <input id="student_id"
                 v-model="form.student_id"
                 :class="{ 'is-invalid': form.errors.has('student_id') }"
                 class="form-control"
                 type="text"
                 name="student_id"
          >
          <has-error :form="form" field="student_id"/>
        </div>
      </div>
      <!-- Email -->
      <!-- For now I'm not letting them change their email because if they do a course through Canvas and change the email
      it will screw up the grade pass back -->
      <div v-show="false" class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="email">Email*
        </label>
        <div class="col-md-7">
          <input id="email"
                 v-model="form.email"
                 :class="{ 'is-invalid': form.errors.has('email') }"
                 class="form-control"
                 type="email"
                 name="email"
          >
          <has-error :form="form" field="email"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-md-3 col-form-label text-md-right" for="time_zone">Time zone*
        </label>
        <div class="col-md-7" @change="removeTimeZoneError()">
          <b-form-select id="time_zone"
                         v-model="form.time_zone"
                         :options="timeZones"
                         :class="{ 'is-invalid': form.errors.has('time_zone') }"
          />
          <has-error :form="form" field="time_zone"/>
        </div>
      </div>
      <!-- Submit Button -->

      <div class="float-right">
        <b-button variant="primary" size="sm" @click="update">
          Update
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
import AllFormErrors from '~/components/AllFormErrors'

export default {
  scrollToTop: false,
  components: { AllFormErrors },
  metaInfo () {
    return { title: 'Settings - Profile'}
  },

  data: () => ({
    allFormErrors: [],
    form: new Form({
      first_name: '',
      last_name: '',
      email: '',
      student_id: '',
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
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-profile')
        }
      }
    }
  }
}
</script>
