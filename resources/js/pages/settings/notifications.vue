<template>
  <div>
    <b-card header="default" header-html="Assignment Reminders">
      <b-card-text>
        <p>If you like, we can send you a reminder email notifying you when your next assignment is due.</p>
        <b-form-group
          id="hours_until_due"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Please email me"
          label-for="Please email me"
        >
          <b-form-radio-group v-model="hoursUntilDue" stacked>
            <b-form-radio name="hours_until_due" value="0">
              I don't need any reminder
            </b-form-radio>
            <b-form-radio name="hours_until_due" value="1">
              1 hour before it's due
            </b-form-radio>
            <b-form-radio name="hours_until_due" value="6">
              6 hours before it's due
            </b-form-radio>
            <b-form-radio name="hours_until_due" value="12">
              12 hours before it's due
            </b-form-radio>
            <b-form-radio name="hours_until_due" value="24">
              24 hours before it's due
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <hr>
        <div class="float-right">
          <b-button variant="primary" @click="submitAssignmentNotificationForm">
            Update
          </b-button>
        </div>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  scrollToTop: false,

  data: () => ({
    hoursUntilDue: 0
  }),
  methods: {
    async submitAssignmentNotificationForm () {
      try {
        const { data } = await axios.patch(`/api/notifications/assignments`, { 'hours_until_due': this.hoursUntilDue })
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
