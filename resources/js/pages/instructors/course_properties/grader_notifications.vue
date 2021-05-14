<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="Grader Notifications">
          <b-card-text>
            <b-container>
              <p>With Grader Notifications, you can optionally let your graders know when assignments are closed and
                when students submit late submissions. These emails will be sent out once per day.</p>
              <b-form-checkbox
                id="when_assignments_are_closed"
                v-model="graderNotificationsForm.when_assignments_are_closed"
                name="when_assignments_are_closed"
                value="1"
                unchecked-value="0"
              >
                Notify graders when assignments are closed
              </b-form-checkbox>
              <b-form-checkbox
                id="for_late_submissions"
                v-model="graderNotificationsForm.for_late_submissions"
                name="for_late_submissions"
                value="1"
                unchecked-value="0"
              >
                Notify graders when students submit after the assignment is closed
              </b-form-checkbox>
              <hr>
              <p>
                Additionally, you can also send your graders reminders to finish grading ungraded assessments with one
                email sent per time period.
              </p>

              <b-form-group
                id="frequency_of_reminders"
                label-cols-sm="4"
                label-cols-lg="3"
                label="Frequency of reminders"
                label-for="Frequency of reminders"
              >
                <b-form-row>
                  <div class="col-md-5">
                    <b-form-select v-model="graderNotificationsForm.num_reminders_per_week"
                                   :options="numRemindersPerWeekOptions"
                                   :class="{ 'is-invalid': graderNotificationsForm.errors.has('num_reminders_per_week') }"
                                   @change="graderNotificationsForm.errors.clear();resetForwardEmails($event)"
                    />
                    <has-error :form="graderNotificationsForm" field="num_reminders_per_week"/>
                  </div>
                </b-form-row>
              </b-form-group>
              <div v-show="showForwardOptions">
                <b-form-checkbox
                  v-show="headGrader"
                  id="copy_grading_reminder_to_head_grader"
                  v-model="graderNotificationsForm.copy_grading_reminder_to_head_grader"
                  name="copy_grading_reminder_to_head_grader"
                  value="1"
                  unchecked-value="0"
                >
                  Forward a copy to the Head Grader
                </b-form-checkbox>
                <b-form-checkbox
                  id="copy_grading_reminder_to_instructor"
                  v-model="graderNotificationsForm.copy_grading_reminder_to_instructor"
                  name="copy_grading_reminder_to_instructor"
                  value="1"
                  unchecked-value="0"
                >
                  Forward a copy to the Instructor
                </b-form-checkbox>
              </div>
              <b-row align-h="end">
                <b-button class="mb-2" variant="primary" size="sm" @click="updateGradingNotifications()">
                  Update
                </b-button>
              </b-row>
            </b-container>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  data: () => ({
    headGrader: false,
    showForwardOptions: false,
    numRemindersPerWeekOptions: [
      { value: 0, text: 'Never' },
      { value: 1, text: 'Once a week' },
      { value: 2, text: 'Twice a week' },
      { value: 3, text: 'Three times a week' },
      { value: 7, text: 'Every day' }
    ],
    isLoading: true,
    graderNotificationsForm: new Form({
      when_assignments_are_closed: 0,
      for_late_submissions: 0,
      copy_grading_reminder_to_head_grader: 0,
      num_reminders_per_week: 0,
      copy_grading_reminder_to_instructor: 0
    }),
    graderNotifications: {}
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getGraderNotifications()
  },
  methods: {
    resetForwardEmails (target) {
      this.showForwardOptions = target !== 0
      if (!this.showForwardOptions) {
        this.graderNotificationsForm.copy_grading_reminder_to_head_grader = 0
        this.graderNotificationsForm.copy_grading_reminder_to_instructor = 0
      }
    },
    async updateGradingNotifications () {
      try {
        const { data } = await this.graderNotificationsForm.patch(`/api/grader-notifications/${this.courseId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async getGraderNotifications () {
      try {
        const { data } = await axios.get(`/api/grader-notifications/${this.courseId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        let graderNotifications = data.grading_notifications
        this.headGrader = data.head_grader
        if (graderNotifications) {
          this.graderNotificationsForm.when_assignments_are_closed = graderNotifications.when_assignments_are_closed
          this.graderNotificationsForm.for_late_submissions = graderNotifications.for_late_submissions
          this.graderNotificationsForm.copy_grading_reminder_to_head_grader = graderNotifications.copy_grading_reminder_to_head_grader
          this.graderNotificationsForm.copy_grading_reminder_to_instructor = graderNotifications.copy_grading_reminder_to_instructor
          this.graderNotificationsForm.num_reminders_per_week = graderNotifications.num_reminders_per_week
          this.showForwardOptions = this.graderNotificationsForm.num_reminders_per_week !== 0
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
