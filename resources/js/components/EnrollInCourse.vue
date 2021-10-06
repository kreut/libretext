<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-enroll-in-course'"/>
    <b-modal
      id="modal-enroll-in-course"
      ref="modal"
      ok-title="Submit"
      :ok-only="inIFrame"
      :no-close-on-esc="true"
      @ok="submitEnrollInCourse"
    >
      <template v-if="inIFrame" #modal-header>
        Enroll In Course
      </template>
      <template v-if="!inIFrame" #modal-title>
        Enroll In Course
      </template>
      <p>Please provide the course access code given to you by your instructor.</p>
      <RequiredText :plural="false"/>
      <b-form ref="form" @submit="submitEnrollInCourse">
        <b-form-group
          id="access_code"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="access_code"
        >
          <template slot="label">
            Access Code<Asterisk />
          </template>
          <b-form-input
            id="access_code"
            v-model="form.access_code"
            type="text"
            :class="{ 'is-invalid': form.errors.has('access_code') }"
            @keydown="form.errors.clear('access_code')"
          />
          <has-error :form="form" field="access_code"/>
        </b-form-group>
        <div v-show="isLms" class="form-group row">
          <label class="col-md-3 col-form-label text-md-right">Time zone</label>
          <div class="col-md-7" @change="form.errors.clear('time_zone')">
            <b-form-select v-model="form.time_zone"
                           :options="timeZones"
                           :class="{ 'is-invalid': form.errors.has('time_zone') }"
            />
            <has-error :form="form" field="time_zone"/>
          </div>
        </div>
      </b-form>
    </b-modal>
  </div>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'
import AllFormErrors from './AllFormErrors'

export default {
  components: {
    AllFormErrors
  },
  props: {
    getEnrolledInCourses: {
      type: Function,
      default: function () {
      }
    },
    isLms: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    allFormErrors: [],
    inIFrame: false,
    form: new Form({
      access_code: '',
      time_zone: '',
      is_lms: false
    }),
    timeZones: [
      { value: null, text: 'Please select a time zone' }
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    // never completed the registration in the iframe
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
    // don't know the user's timezone yet since they were auto enrolled
    let timeZones = getTimeZones()
    populateTimeZoneSelect(timeZones, this)
    this.form.time_zone = null
  },
  methods: {
    submitEnrollInCourse (bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.inIFrame ? this.enrollInCourseViaIFrame() : this.enrollInCourse()
    },
    resetAll () {
      this.getEnrolledInCourses()
      this.form.access_code = ''
      this.form.errors.clear()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide('modal-enroll-in-course')
      })
    },
    async enrollInCourse () {
      try {
        const { data } = await this.form.post('/api/enrollments')
        if (data.validated) {
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            this.resetAll()
          }
        } else {
          if (data.type === 'error') {
            this.$noty.error(data.message)// no access
            this.resetAll()
          }
        }
        console.log(data)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-enroll-in-course')
        }
      }
    },
    async enrollInCourseViaIFrame () {
      this.form.is_lms = this.isLms
      try {
        const { data } = await this.form.post('/api/enrollments')
        if (data.validated) {
          location.reload()
        }
        // they never finished the registration page
        if (parseInt(this.user.role) === 0) {
          alert('You did not complete the previous registration page.')
          window.location = '/finish-sso-registration'
          return false
        }
        if (data.type === 'error') {
          this.$noty.error(data.message)
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

<style scoped>

</style>
