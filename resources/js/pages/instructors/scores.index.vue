<template>
  <div>
    <PageTitle v-if="canViewScores" title="Gradebook"></PageTitle>
    <div v-if="hasAssignments">
      <div v-if="canViewScores">
        <download-excel
          class="float-right mb-2"
          :data="downloadData"
          :fetch="fetchData"
          :fields="downloadFields"
          worksheet="My Worksheet"
          type="csv"
          name="scores.csv">
          <b-button variant="success">Download Scores</b-button>
        </download-excel>
      </div>
      <b-table striped
               hover
               fixed
               :items="items"
               :fields="fields"
               :sort-by.sync="sortBy"
               primary-key="userId"
               :sort-desc.sync="sortDesc"
               sort-icon-left
               responsive="sm"

      >
        <template v-slot:cell()="data"
                  v-for="assignmentIndex in assignmentsArray">
          <span v-html="data.value"
                v-on:click="openStudentAssignmentModal(data.value,data.item.userId, data.field.key)"></span>
        </template>

      </b-table>
    </div>
    <div v-else>
      <b-alert show variant="warning"><a href="#" class="alert-link">Once you have students enrolled in the cousre.</a>
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
      <p>Please use this form to either provide an extension for your student or given an override.</p>
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
            <div>
              <b-form-select v-model="form.score" :options="options"></b-form-select>
            </div>
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
            >        Update this student's score to Completed.
            </b-form-checkbox>


          </div>
      </b-form>
    </b-modal>

  </div>
</template>
<script>
import axios from 'axios'
import Form from "vform"


const now = new Date()

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
let formatDateAndTime = value => {
  let date = new Date(value)
  return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + date.toLocaleTimeString()
}

// get all students enrolled in the course: course_enrollment
// get all assignments for the course
//
export default {
  middleware: 'auth',
  data: () => ({
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
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
    downloadData: [],
    scores: [],
    items: [],
    hasAssignments: true,
    studentUserId: 0,
    assignmentId: 0,
    assignmentsArray: [],
    hasExtension: false,
    canViewScores: false,
    assignmentScoringTypes: [],
    options: [
      {value: null, text: 'Please select an option'},
      {value: 'C', text: 'Completed'},
      {value: '1', text: '1'},
      {value: '2', text: '2'},
      {value: '3', text: '3'},
      {value: '4', text: '4'},
      {value: '5', text: '5'},
    ]
  }),
  mounted() {
    this.courseId = this.$route.params.courseId
    this.getAssignmentScoringTypes()
    this.getScores();
  },
  methods: {
    async getAssignmentScoringTypes() {
      try {
        const {data} = await axios.get(`/api/assignments/courses/${this.courseId}`)
        console.log(data.length)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        for (let i = 0; i < data.length; i++) {
            this.assignmentScoringTypes[data[i].id] = data[i].scoring_type
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
  updateAssignmentByStudent() {
    let isUpdateScore = (this.form.score !== null)
    let isGiveExtension = (this.form.extension_date !== '')
    if (isUpdateScore && isGiveExtension) {
      this.$noty.error("Please either give an extension or provide an override score, but not both.")
      this.form.score = null
      this.form.extension_date = ''
      return false
    }
    if (!(isUpdateScore || isGiveExtension)) {

      this.$noty.error("Please either give an extension or provide an override score.")
    }
    if (isUpdateScore) {
      this.updateScore(this.studentUserId, this.assignmentId)
    }
    if (isGiveExtension) {
      this.hasExtension ? this.updateExtension(this.studentUserId, this.assignmentId)
        : this.addExtension(this.studentUserId, this.assignmentId)
    }
  },
  async updateScore(studentUserId, assignmentId) {
    try {
      const {data} = await this.form.patch(`/api/scores/${assignmentId}/${studentUserId}`)
      this.$noty[data.type](data.message)
      await this.getScores()
      if (data.type === 'success') {
        this.resetAll('modal-update-student-assignment')
      }
    } catch (error) {
      console.log(error)
    }
  },
  async addExtension() {
    try {
      const {data} = await this.form.post(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
      console.log(data)
      this.$noty[data.type](data.message)
      await this.getScores()
      if (data.type === 'success') {
        this.resetAll('modal-update-student-assignment')
      }
    } catch (error) {
      console.log(error)
    }

  },
  async updateExtension() {
    try {
      const {data} = await this.form.patch(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
      console.log(data)
      this.$noty[data.type](data.message)
      await this.getScores()
      if (data.type === 'success') {
        this.resetAll('modal-update-student-assignment')
      }
    } catch (error) {
      console.log(error)
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
  async openStudentAssignmentModal(value, studentUserId, assignmentId) {
    //name shouldn't be clickable
    if (assignmentId === 'name') {
      return false
    }
    this.studentUserId = studentUserId
    this.assignmentId = assignmentId
    this.hasExtension = value === 'Extension'
    this.assignmentScoringType = this.assignmentScoringTypes[ this.assignmentId ]

    if (this.hasExtension) {
      const {data} = await axios.get(`/api/extensions/${this.assignmentId}/${this.studentUserId}`)
      if (data.type === 'success') {
        this.form.extension_date = data.extension_date
        this.form.extension_time = data.extension_time
      } else {
        this.$noty.error(data.message)
        return false
      }
    }
    //get the score and assignment info

    this.$bvModal.show('modal-update-student-assignment')

  },
  async fetchData() {
    const {data} = await axios.get(`/api/scores/${this.courseId}`)
    console.log(data)
    return data.download_data.sort((a, b) => (a.name > b.name) - (a.name < b.name))//sort in ascending order
  },
  async getScores() {

    try {
      const {data} = await axios.get(`/api/scores/${this.courseId}`)
      console.log(data)
      if (data.type === 'error') {
        this.$noty.error(data.message)
        return false
      }

      if (data.hasAssignments) {
        this.items = data.table.rows
        this.fields = data.table.fields  //Name
        this.downloadFields = data.download_fields
        this.downloadData = data.download_data
        console.log(this.downloadFields)
        console.log(this.downloadData)

        //create an array 0 up through the top assignment number index
        this.assignmentsArray = [...Array(this.fields.length).keys()]
        this.hasAssignments = true
        this.canViewScores = true
      }


    } catch (error) {
      alert(error.message)
    }
  }

}
}
</script>
