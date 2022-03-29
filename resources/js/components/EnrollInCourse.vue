<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-enroll-in-course'"/>
    <b-modal
      id="modal-enroll-in-course"
      ref="modal"
    >
      <template v-if="inIFrame" #modal-header>
        Enroll In Course
      </template>
      <template v-if="!inIFrame" #modal-title>
        Enroll In Course
      </template>
      <p>
        Please complete the form below.
        <RequiredText :plural="false"/>
      </p>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="access_code"
          label="Access Code*"
        >
          <b-form-input
            id="access_code"
            v-model="form.access_code"
            type="text"
            required
            :class="{ 'is-invalid': form.errors.has('access_code') }"
            @keydown="form.errors.clear('access_code')"
          />
          <has-error :form="form" field="access_code"/>
        </b-form-group>
        <div v-show="isLms">
          <b-form-group
            label-cols-sm="4"
            label-cols-lg="3"
            label-for="student_id"
            label="Student ID*"
          >
            <b-form-input
              id="student_id"
              v-model="form.student_id"
              type="text"
              required
              :class="{ 'is-invalid': form.errors.has('student_id') }"
              @keydown="form.errors.clear('student_id')"
            />
            <has-error :form="form" field="student_id"/>
          </b-form-group>
          <b-form-group
            label-cols-sm="4"
            label-cols-lg="3"
            label-for="time_zone"
            label="Time Zone*"
          >
            <b-form-select id="time_zone"
                           v-model="form.time_zone"
                           title="time zone"
                           :options="timeZones"
                           required
                           :class="{ 'is-invalid': form.errors.has('time_zone') }"
                           @change="form.errors.clear('time_zone')"
            />
            <has-error :form="form" field="time_zone"/>
          </b-form-group>
        </div>
      </b-form>
      <template #modal-footer>
        <span v-if="!inIFrame">
        <b-button
          size="sm"
          class="float-right"
          aria-label="Cancel enroll in course"
          @click="$bvModal.hide('modal-enroll-in-course')"
        >
            Cancel
        </b-button>
          </span>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          aria-label="Submit enroll in course"
          @click="submitEnrollInCourse()"
        >
          Submit
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import Form from 'vform'
import { mapGetters } from 'vuex'
import { getTimeZones } from '@vvo/tzdb'
import { populateTimeZoneSelect } from '~/helpers/TimeZones'
import AllFormErrors from './AllFormErrors'
import { fixInvalid } from '../helpers/accessibility/FixInvalid'

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
    submitEnrollInCourse () {
      this.form.is_lms = this.isLms
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
            this.isLms
              ? location.reload()
              : this.resetAll()
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
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-enroll-in-course')
        }
      }
    },
    async  enrollInCourseViaIFrame () {
      try {
        const { data } = await this.form.post('/api/enrollments')
        // they never finished the registration page
        if (parseInt(this.user.role) === 0) {
          alert('You did not complete the previous registration page.')
          window.location = '/finish-sso-registration'
          return false
        }
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        if (data.validated) {
          await this.$store.dispatch('auth/fetchUser')

          location.reload()
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
