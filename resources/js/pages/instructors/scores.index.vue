<template>
  <div>
    <PageTitle v-if="canViewScores" title="Gradebook"></PageTitle>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"></loading>
      <div v-if="hasAssignments">
        <div v-if="canViewScores">
          <b-container>
            <div class="font-italic">
              <p>To compute the weighted averages, we first compute the percent score on each assignment, then take a
                straight average of all assignments within an assignment group. Finally, the averages by assignment
                group
                are weighted by the assignment group weights.</p>
              <p>If you prefer a different grading methodology, please download the scores and input them into a
                spreadsheet.</p>
            </div>
            <b-row align-h="end">
              <download-excel
                class="float-right mb-2"
                :data="downloadRows"
                :fetch="fetchData"
                :fields="downloadFields"
                worksheet="My Worksheet"
                type="csv"
                name="scores.csv">
                <b-button variant="success">Download Scores</b-button>
              </download-excel>
            </b-row>
            <b-row>
              <b-table striped
                       hover
                       responsive="true"
                       sticky-header="600px"
                       :no-border-collapse="true"
                       :items="items"
                       :fields="fields"
                       :sort-by.sync="sortBy"
                       primary-key="userId"
                       :sort-desc.sync="sortDesc"
                       sort-icon-left
              >
                <template v-slot:cell()="data"
                          v-for="assignmentIndex in assignmentsArray">
          <span v-html="data.value"
                v-on:click="openStudentAssignmentModal(data.value,data.item.userId, data.field.key)"></span>
                </template>

              </b-table>
            </b-row>
          </b-container>
        </div>
      </div>
      <div v-else>
        <b-alert show variant="warning"><a href="#" class="alert-link">Once you have students enrolled in the
          cousre.</a>
        </b-alert>
      </div>
      <b-modal
        id="modal-update-student-assignment"
        ref="modal"
        title="Update Student Assignment"
        @ok="submitUpdateAssignmentByStudent"
        @hidden="resetModalForms"
        ok-title="Submit"
        size="lg"
      >
        <p>Please use this form to either provide an extension for your student or an override score.</p>
        <b-form ref="form" @submit="updateAssignmentByStudent">
          <b-form-group
            id="extension"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Extension"
            label-for="Extension"
          >
            <b-form-row>
              <b-col lg="7">
                <b-form-datepicker
                  v-model="form.extension_date"
                  :min="min"
                  :class="{ 'is-invalid': form.errors.has('extension_date') }"
                  v-on:shown="form.errors.clear('extension_date')">
                </b-form-datepicker>
                <has-error :form="form" field="extension_date"></has-error>
              </b-col>
              <b-col>
                <b-form-timepicker v-model="form.extension_time"
                                   locale="en"
                                   :class="{ 'is-invalid': form.errors.has('extension_time') }"
                                   v-on:shown="form.errors.clear('extension_time')">

                </b-form-timepicker>
                <has-error :form="form" field="extension_time"></has-error>
              </b-col>
            </b-form-row>
          </b-form-group>
          <div v-if="assignmentScoringType === 'p'">
            <b-form-group
              id="score"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Override Score"
              label-for="Override Score"
            >
              <b-form-row>
                <b-col lg="3">
                  <b-form-input
                    id="score"
                    v-model="form.score"
                    type="text"
                    placeholder=""
                    :class="{ 'is-invalid': form.errors.has('score') }"
                    @keydown="form.errors.clear('score')"
                  >
                  </b-form-input>
                  <has-error :form="form" field="score"></has-error>
                </b-col>
              </b-form-row>


            </b-form-group>
          </div>
          <div v-if="assignmentScoringType === 'c'">

            <b-form-checkbox
              id="score"
              v-model="form.score"
              name="score"
              value="c"
              unchecked-value="i"
            > Update this student's score to Completed.
            </b-form-checkbox>


          </div>
        </b-form>
      </b-modal>

    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Form from "vform"
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'


// get all students enrolled in the course: course_enrollment
// get all assignments for the course
//
export default {
  components: {
    Loading
  },
  middleware: 'auth',
  data: () => ({
    weightedAverageAssignmentId: 0,
    isLoading: false,
    min: '',
    form: new Form({
      extension_date: '',
      extension_time: '',
      score: null
    }),
    assignmentScoringType: null,
    sortBy: 'name',
    sortDesc: false,
    courseId: '',
    fields: [],
    downloadFields: {},
    downloadRows: [],
    scores: [],
    items: [],
    hasAssignments: true,
    studentUserId: 0,
    assignmentId: 0,
    assignmentsArray: [],
    hasExtension: false,
    canViewScores: false,
    assignmentScoringTypes: [],
    currentExtensionDate: '',
    currentExtensionTime: '',
    currentScore: null
  }),
  mounted() {
    this.courseId = this.$route.params.courseId
    this.getAssignmentScoringTypes()
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
    this.isLoading = true
    this.getScores()
  },
  methods: {
    async getAssignmentScoringTypes() {
      try {
        const {data} = await axios.get(`/api/assignments/courses/${this.courseId}`)


        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < data.assignments.length; i++) {
          this.assignmentScoringTypes[data.assignments[i].id] = data.assignments[i].scoring_type
        }

      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    submitUpdateAssignmentByStudent(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault()
      // Trigger submit handler
      this.updateAssignmentByStudent()
    },
    async updateAssignmentByStudent() {
      let isUpdateScore = (this.currentScore !== this.form.score)
      let isUpdateExtension = ((this.currentExtensionDate !== this.form.extension_date)
        || (this.currentExtensionTime !== this.form.extension_time))

      if (!(isUpdateScore || isUpdateExtension)) {

        this.$noty.error("Please either give an extension or provide an override score.")
      }
      let success = true
      if (isUpdateScore) {
        success = await this.updateScore()
      }
      if (success) {
        if (isUpdateExtension) {
          success = await this.updateExtension()
        }
        if (success) {
          this.getScores()
          this.resetAll('modal-update-student-assignment')
        }
      }

    },
    async updateScore() {
      try {
        const {data} = await this.form.patch(`/api/scores/${this.assignmentId}/${this.studentUserId}`)
        this.$noty[data.type](data.message)
        return (data.type === 'success')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
        return false
      }
    },
    async updateExtension() {

      try {
        const {data} = await this.form.post(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
        this.$noty[data.type](data.message)
        return (data.type === 'success')

      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
        return false
      }

    },
    resetAll(modalId) {
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetModalForms() {
      this.form.extension_date = ''
      this.form.extension_time = ''
      this.form.score = null
      this.form.errors.clear()
    },
    initStudentAssignmentCell(key) {
      console.log(key)
      return `cell(${key})`; // simple string interpolation
    },
    async getExtensionByAssignmentAndStudent() {
      const {data} = await axios.get(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
      console.log(data)
      if (data.type === 'success') {
        this.currentExtensionDate = data.extension_date
        this.currentExtensionTime = data.extension_time
        if (data.extension_date) {
          this.form.extension_date = data.extension_date
          this.form.extension_time = data.extension_time
        }
      } else {
        this.$noty.error(data.message)
        return false

      }
    },
    async getScoreByAssignmentAndStudent() {
      const {data} = await axios.get(`/api/scores/${this.assignmentId}/${this.studentUserId}`)
      console.log(data)
      if (data.type === 'success') {
        this.currentScore = data.score
        this.form.score = data.score
      } else {
        this.$noty.error(data.message)
        return false
      }
    },
    async openStudentAssignmentModal(value, studentUserId, assignmentId) {
      //name shouldn't be clickable

      if (assignmentId === 'name' || parseInt(assignmentId) === parseInt(this.weightedAverageAssignmentId)) {
        return false
      }

      this.studentUserId = studentUserId
      this.assignmentId = assignmentId
      this.assignmentScoringType = this.assignmentScoringTypes[this.assignmentId]

      try {
        await this.getScoreByAssignmentAndStudent()
        await this.getExtensionByAssignmentAndStudent()

        this.$bvModal.show('modal-update-student-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }

    },
    async fetchData() {
      const {data} = await axios.get(`/api/scores/${this.courseId}`)
      console.log(data)
      return data.download_rows.sort((a, b) => (a.name > b.name) - (a.name < b.name))//sort in ascending order
    },
    async getScores() {

      try {
        const {data} = await axios.get(`/api/scores/${this.courseId}`)
        this.isLoading = false
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        if (data.hasAssignments) {
          this.items = data.table.rows
          console.log(this.items)
          this.fields = data.table.fields  //Name
          console.log(this.fields)
          console.log(this.fields)
          this.downloadFields = data.download_fields
          this.downloadRows = data.download_rows


          //create an array 0 up through the top assignment number index
          this.assignmentsArray = [...Array(this.fields.length).keys()]
          console.log(this.fields)
          console.log(this.assignmentsArray)
          this.hasAssignments = true
          this.canViewScores = true
          this.weightedAverageAssignmentId = data.weighted_score_assignment_id
        }


      } catch (error) {
        alert(error.message)
      }
    }

  }
}
</script>
