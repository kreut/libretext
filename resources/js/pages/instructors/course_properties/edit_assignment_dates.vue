<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-shift-dates'"/>
    <b-modal id="modal-shifting-period-examples"
             title="Shifting Period Examples"
             hide-footer
    >
      <p style="font-size: 0.8rem">
        The shifting period should be a "human readable" period of time. Negative signs
        indicate moving the time backwards.
      </p>
      <table class="table table-striped table-sm" style="font-size: 0.8rem">
        <thead>
        <tr>
          <th scope="col">
            Shifting Period
          </th>
          <th scope="col">
            Result
          </th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td>
            3 days and 1 hour
          </td>
          <td> 3 days and 1 hour forward</td>
        </tr>
        <tr>
          <td>
            2 days and -1 hour
          </td>
          <td> 3 days and 1 hour back</td>
        </tr>
        <tr>
          <td>
            -3 days and -1 hour
          </td>
          <td> 3 days back and 1 hour back</td>
        </tr>
        </tbody>
      </table>
    </b-modal>
    <b-modal id="modal-shift-dates"
             title="Shift Dates"
             size="xl"
             no-close-on-backdrop
    >
      <b-form-radio-group
        v-model="shiftDatesForm.shifting_method"
        class="mb-3"
        label="Shifting Method"
        stacked
        @change="resetShiftDatesForm"
      >
        <b-form-radio
          name="shifting-method"
          value="first available on"
        >
          Provide a new First Available On and shift all other assignments based on the difference between the two
          Available Ons
        </b-form-radio>
        <b-form-radio
          name="shifting-method"
          value="period of time"
        >
          A period of time such as 1 hour or -10 days
        </b-form-radio>
      </b-form-radio-group>

      <b-form-group
        v-show="shiftDatesForm.shifting_method === 'first available on'"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="date"
        label-align="end"
      >
        <template v-slot:label>
          First Available On
        </template>
        <b-form-row>
          <b-col lg="7">
            <b-form-datepicker
              id="date"
              v-model="firstAvailableOnForm.date"
              required
              tabindex="0"
              :class="{ 'is-invalid': firstAvailableOnForm.errors.has('date') }"
              class="datepicker"
              @shown="firstAvailableOnForm.errors.clear('date')"
            />
            <has-error :form="firstAvailableOnForm" field="date"/>
          </b-col>
          <b-col>
            <vue-timepicker id="time"
                            v-model="firstAvailableOnForm.time"
                            format="h:mm A"
                            manual-input
                            drop-direction="up"
                            :class="{ 'is-invalid': firstAvailableOnForm.errors.has('date') }"
                            input-class="custom-timepicker-class"
            >
              <template v-slot:icon>
                <b-icon-clock/>
              </template>
            </vue-timepicker>
            <has-error :form="firstAvailableOnForm" field="time"/>
          </b-col>
          <b-col>
            <div class="mt-1">
              <b-button variant="info" size="sm" @click="previewShiftDates">
                Preview
              </b-button>
              <b-button variant="info" size="sm" @click="resetDates">
                Reset
              </b-button>
              <b-button variant="primary" size="sm" @click="saveShiftDates">
                Save
              </b-button>
            </div>
          </b-col>
        </b-form-row>
      </b-form-group>
      <b-form-group
        v-show="shiftDatesForm.shifting_method === 'period of time'"
        label-cols-sm="3"
        label-cols-lg="2"
        label-for="shift-by"
        label-align="center"
        label="Shift by"
      >
        <template v-slot:label>
          Shifting Period
          <span @mouseover="showShiftingPeriodExamplesModal()" @mouseout="mouseOverShiftingPeriodExamples = false">
            <QuestionCircleTooltip/>
          </span>
        </template>
        <b-form-row>
          <b-col lg="3">
            <b-form-input
              id="shift-by"
              v-model="shiftDatesForm.shift_by"
              required
              type="text"
              :class="{ 'is-invalid': shiftDatesForm.errors.has('shift_by') }"
              @keydown="shiftDatesForm.errors.clear('shift_by')"
            />
            <has-error :form="shiftDatesForm" field="shift_by"/>
          </b-col>
          <b-col>
            <div class="mt-1">
              <b-button variant="info" size="sm" @click="previewShiftDates">
                Preview
              </b-button>
              <b-button variant="info" size="sm" @click="resetDates">
                Reset
              </b-button>
              <b-button variant="primary" size="sm" @click="saveShiftDates">
                Save
              </b-button>
            </div>
          </b-col>
        </b-form-row>
      </b-form-group>
      <table class="table table-striped table-sm" style="font-size: 0.8rem">
        <thead>
        <tr>
          <th scope="col">
            Name
          </th>
          <th scope="col">
            Available On
          </th>
          <th scope="col">
            Due Date
          </th>
          <th scope="col">
            Final Submission Deadline
          </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(assignmentDate, assignmentDateIndex) in chosenAssignments"
            :key="`assignment-dates-${assignmentDateIndex}`"
        >
          <td>
            {{ assignmentDate.name }}
          </td>
          <td>{{ formatDate(assignmentDate.available_from) }}</td>
          <td>{{ formatDate(assignmentDate.due) }}</td>
          <td>{{ formatDate(assignmentDate.final_submission_deadline) }}</td>
        </tr>
        </tbody>
      </table>
      <template #modal-footer>
        <b-button variant="primary" size="sm" class="float-right" @click="saveShiftDates">
          Save
        </b-button>
        <b-button size="sm" class="float-right" @click="$bvModal.hide('modal-shift-dates')">
          Cancel
        </b-button>
      </template>
    </b-modal>
    <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Edit Assignment Dates</h2>">
      <b-card-text>
        <p class="mb-2">
          You can shift dates in bulk by selecting any of the assignments below. The assigments listed below are ones
          which have been
          assigned to Everybody and are non-clicker assignments.
        </p>
        <div class="float-right mb-2">
          <b-button size="sm" variant="primary" @click="openShiftDatesModal">
            Shift Dates
          </b-button>
        </div>
        <div>
          <table class="table table-striped table-sm" style="font-size: 0.8rem">
            <thead>
            <tr>
              <th scope="col" style="width:300px">
                <b-form-checkbox id="select-all"
                                 @input="selectAll()"
                >
                  Name
                </b-form-checkbox>
              </th>
              <th scope="col">
                Available On
              </th>
              <th scope="col">
                Due Date
              </th>
              <th scope="col">
                Final Submission Deadline
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(assignmentDate, assignmentDateIndex) in assignmentDates"
                :key="`assignment-dates-${assignmentDateIndex}`"
            >
              <td>
                <input v-model="selectedAssignmentIds"
                       type="checkbox"
                       :value="assignmentDate.assignment_id"
                       class="selected-assignment-id"
                > {{ assignmentDate.name }}
              </td>
              <td>{{ formatDate(assignmentDate.available_from) }}</td>
              <td>{{ formatDate(assignmentDate.due) }}</td>
              <td>{{ formatDate(assignmentDate.final_submission_deadline) }}</td>
            </tr>
            </tbody>
          </table>
        </div>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors.vue'
import VueTimepicker from 'vue2-timepicker'
import 'vue2-timepicker/dist/VueTimepicker.css'
import { mapGetters } from 'vuex'

export default {
  components: { AllFormErrors, VueTimepicker },
  data: () => ({
    offsetDifference: 0,
    firstAvailableOnForm: new Form({
      date: '',
      time: ''
    }),
    allFormErrors: [],
    courseId: 0,
    mouseOverShiftingPeriodExamples: false,
    chosenAssignments: [],
    assignmentDates: [],
    selectedAssignmentIds: [],
    shiftDatesForm: new Form({
      shift_by: '',
      offset_difference: 0,
      shifting_method: 'first available on'
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = parseInt(this.$route.params.courseId)
    this.getAssignmentDates()
  },
  methods: {
    resetShiftDatesForm (option) {
      this.shiftDatesForm = new Form({
        shift_by: '',
        offset_difference: 0,
        shifting_method: option
      })
      this.firstAvailableOnForm =
        new Form({
          date: '',
          time: ''
        })
    },
    showShiftingPeriodExamplesModal () {
      this.mouseOverShiftingPeriodExamples = true
      setTimeout(() => {
        if (this.mouseOverShiftingPeriodExamples) {
          this.$bvModal.show('modal-shifting-period-examples')
        }
      }, 500)
    },
    resetDates () {
      this.shiftDatesForm.shift_by = null
      this.chosenAssignments = this.assignmentDates.filter(assignment => this.selectedAssignmentIds.includes(assignment.assignment_id))
      this.$noty.success('The dates have been reset.')
    },
    async saveShiftDates () {
      if (this.shiftDatesForm.shifting_method === 'first available on') {
        if (!this.computeShiftByForDate()) {
          return
        }
      }
      try {
        this.shiftDatesForm.assignment_ids = this.selectedAssignmentIds
        const { data } = await this.shiftDatesForm.post(`/api/assignments/${this.courseId}/shift-dates`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getAssignmentDates()
          this.$bvModal.hide('modal-shift-dates')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.shiftDatesForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-shift-dates')
        }
      }
    },
    computeShiftByForDate () {
      const { date, time } = this.firstAvailableOnForm
      const time24 = this.$moment(time, ['h:mm A']).format('HH:mm')
      const dateTimeString = `${date} ${time24}`
      if (this.$moment(dateTimeString).isValid() && this.$moment(this.assignmentDates[0].available_from).isValid()) {
        const firstAvailableMoment = this.$moment(dateTimeString)
        const availableFromMoment = this.$moment(this.assignmentDates[0].available_from)
        const firstAvailableOffset = firstAvailableMoment.utcOffset()
        const availableFromOffset = availableFromMoment.utcOffset()

        this.shiftDatesForm.offset_difference = firstAvailableOffset - availableFromOffset
        this.differenceInMinutes = firstAvailableMoment.diff(availableFromMoment, 'minutes')
        if (this.shiftDatesForm.offset_difference === 60 || this.shiftDatesForm.offset_difference === -60) {
          this.differenceInMinutes = firstAvailableMoment.diff(availableFromMoment, 'minutes') + this.shiftDatesForm.offset_difference
        } else {
          this.differenceInMinutes = firstAvailableMoment.diff(availableFromMoment, 'minutes')
        }
        this.shiftDatesForm.shift_by = `${this.differenceInMinutes} minutes`
      } else {
        const errorMessage = 'Please be sure to enter a valid date and time.'
        this.firstAvailableOnForm.errors.set('date', errorMessage)
        this.allFormErrors = [errorMessage]
        this.$bvModal.show('modal-form-errors-shift-dates')
        return false
      }
      return true
    },
    async previewShiftDates () {
      if (this.shiftDatesForm.shifting_method === 'first available on') {
        if (!this.computeShiftByForDate()) {
          return
        }
      }
      this.chosenAssignments = this.assignmentDates.filter(assignment => this.selectedAssignmentIds.includes(assignment.assignment_id))
      try {
        const { data } = await axios.patch('/api/assignments/preview-shift-dates', {
          shift_by: this.shiftDatesForm.shift_by,
          chosen_assignments: this.chosenAssignments
        })
        if (data.type === 'success') {
          this.chosenAssignments = data.preview_shift_dates
          this.$noty.success('The dates have been updated.')
        } else {
          this.shiftDatesForm.errors.set('shift_by', data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    openShiftDatesModal () {
      this.shiftDatesForm.shift_by = null
      this.chosenAssignments = this.assignmentDates.filter(assignment => this.selectedAssignmentIds.includes(assignment.assignment_id))
      if (!this.chosenAssignments.length) {
        this.$noty.info('Please first choose at least one assignment.')
        return
      }
      this.$bvModal.show('modal-shift-dates')
      const option = 'first available on'
      this.resetShiftDatesForm(option)
    },
    selectAll () {
      this.selectedAssignmentIds = []
      let checkboxes = document.getElementsByClassName('selected-assignment-id')
      if (document.getElementById('select-all').checked) {
        for (let checkbox of checkboxes) {
          this.selectedAssignmentIds.push(parseInt(checkbox.value))
          checkbox.checked = true
        }
      }
    },
    formatDate (date) {
      if (date) {
        return this.$moment(date).format('ddd, M/D/YY h:mm A')
      }
      return 'N/A'
    },
    async getAssignmentDates () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.courseId}/dates`)
        if (data.type === 'success') {
          this.assignmentDates = data.assignment_dates
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
