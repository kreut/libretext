<template>
  <div>
    <PageTitle title="Course Properties" />
    {{ course }}
    <b-modal
      id="modal-letter-grades-editor"
      ref="modal"
      title="Letter Grades"
    >
      <p>
        Use the text area below to customize your letter grades, in a comma separated list of
        the form "Minimum score for the group, Letter Grade". As an example, if the three letter grades that you offer
        are
        A, B, and C, and students need at least a 60% to pass the course, you might enter 90,A,70,B,60,C,0,F.
      </p>
      <b-form-input
        id="letter_grades"
        v-model="letterGradesForm.letter_grades"
        type="text"
        placeholder=""
        :class="{ 'is-invalid': letterGradesForm.errors.has('letter_grades') }"
        @keydown="letterGradesForm.errors.clear('letter_grades')"
      />
      <has-error :form="letterGradesForm" field="letter_grades" />
      <div slot="modal-footer">
        <b-btn @click="$bvModal.hide('modal-letter-grades-editor')">
          Cancel
        </b-btn>
        <b-btn variant="info" @click="resetLetterGradesToDefault">
          Reset to Default
        </b-btn>
        <b-btn variant="primary" @click="submitLetterGrades">
          Submit
        </b-btn>
      </div>
    </b-modal>
    <b-card>
      <b-card-text>
        <p>
          Let us know how you would like to convert your students' weighted scores into letter grades or grade
          categories.
          Some examples might be "A+, A, A-,..." or
          "Excellent, Good, Unsatisfactory". You can use the
          <b-link @click="openLetterGradesEditorModal">
            letter grade editor
          </b-link>

          to customize the letter grades.
        </p>
        <p>
          <span class="font-italic">When determining the letter grades, round the weighted scores to the nearest integer:</span>
          <toggle-button
            class="mt-2"
            :width="55"
            :value="Boolean(this.roundScores)"
            :sync="true"
            :font-size="14"
            :margin="4"
            :color="{checked: '#28a745', unchecked: '#6c757d'}"
            :labels="{checked: 'Yes', unchecked: 'No'}"
            @change="submitRoundScores()"
          />
        </p>
        <b-table striped
                 hover
                 :sticky-header="true"
                 :fields="letterGradeFields" :items="letterGradeItems"
        />
      </b-card-text>
    </b-card>

    <b-col cols="6">
      <span class="font-italic">Release letter grades: </span>
      <toggle-button
        class="mt-2"
        :width="55"
        :value="letterGradesReleased"
        :sync="true"
        :font-size="14"
        :margin="4"
        :color="{checked: '#28a745', unchecked: '#6c757d'}"
        :labels="{checked: 'Yes', unchecked: 'No'}"
        @change="submitReleaseLetterGrades()"
      />
    </b-col>

    <span class="font-italic">Release weighted averages: </span>
    <toggle-button
      class="mt-2"
      :width="55"
      :value="studentsCanViewWeightedAverage"
      :sync="true"
      :font-size="14"
      :margin="4"
      :color="{checked: '#28a745', unchecked: '#6c757d'}"
      :labels="{checked: 'Yes', unchecked: 'No'}"
      @change="submitShowWeightedAverage()"
    />
    <b-card>
      <b-card-text>
        <p>
          Tell Adapt how you would like to weight your assignment groups so that it can compute a weighted average of
          all scores.
        </p>
        <b-table striped hover :fields="assignmentGroupWeightsFields" :items="assignmentGroupWeights">
          <template v-slot:cell(assignment_group_weight)="data">
            <b-col lg="5">
              <b-form-input
                :id="`assignment_group_id_${data.item.id}}`"
                v-model="assignmentGroupWeightsForm[data.item.id]"
                type="text"
                :class="{ 'is-invalid': assignmentGroupWeightsFormWeightError }"
                @keydown="assignmentGroupWeightsFormWeightError = ''"
              />
            </b-col>
          </template>
        </b-table>
        <div class="ml-5">
          <b-form-invalid-feedback :state="false">
            {{ assignmentGroupWeightsFormWeightError }}
          </b-form-invalid-feedback>
        </div>
      </b-card-text>
    </b-card>
    <b-card header="default" header-html="Course Access Codes">
      <b-card-text>
        <p>By refreshing your access code, students will no longer be able to sign up using the old access code.</p>
        <p>Current Access code: {{ course.access_code }}</p>
        <b-button class="primary" @click="refreshAccessCode">
          Refresh Access Code
        </b-button>
      </b-card-text>
    </b-card>
    <b-card header="default" header-html="Graders">
      <b-card-text>
        <b-form ref="form">
          <div v-if="course.graders.length">
            Your current graders:<br>
            <ol id="graders">
              <li v-for="grader in course.graders" :key="grader.id">
                {{ grader.first_name }} {{ grader.last_name }} {{ grader.email }}
                <b-icon icon="trash" @click="deleteGrader(grader.id)" />
              </li>
            </ol>
          </div>

          <b-form-group
            id="email"
            label-cols-sm="4"
            label-cols-lg="3"
            label="New Grader"
            label-for="email"
          >
            <b-form-input
              id="email"
              v-model="graderForm.email"
              placeholder="Email Address"
              type="text"
              :class="{ 'is-invalid': graderForm.errors.has('email') }"
              @keydown="graderForm.errors.clear('email')"
            />
            <has-error :form="graderForm" field="email" />
          </b-form-group>
          <b-button class="primary" @click="submitInviteGrader">
            Invite Grader
          </b-button>
          <div v-if="sendingEmail" class="float-right">
            <b-spinner small type="grow" />
            Sending Email..
          </div>
        </b-form>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'

export default {
  middleware: 'auth',
  components: {
    ToggleButton
  },
  data: () => ({
    letterGradeFields: [
      'letter_grade',
      {
        key: 'min',
        label: 'Minimum'
      },
      {
        key: 'max',
        label: 'Maximum'
      }
    ],
    roundScores: false,
    letterGradeItems: [],
    course: {},
    sendingEmail: false,
    graders: {},
    letterGradesForm: new Form({
      letter_grades: ''
    }),
    graderForm: new Form({
      email: ''
    }),
    letterGradesReleased: false,
    studentsCanViewWeightedAverage: false,
    assignmentGroupWeightsFormWeightError: '',
    assignmentGroupWeightsForm: {},
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    async submitReleaseLetterGrades () {
      try {
        const { data } = await axios.patch(`/api/final-grades/${this.courseId}/release-letter-grades/${Number(this.letterGradesReleased)}`)

        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.letterGradesReleased = !this.letterGradesReleased
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitRoundScores () {
      try {
        const { data } = await axios.patch(`/api/final-grades/${this.courseId}/round-scores/${Number(this.roundScores)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.roundScores = !this.roundScores
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async resetLetterGradesToDefault () {
      try {
        const { data } = await axios.get(`/api/final-grades/letter-grades/default`)
        this.letterGradeItems = data.default_letter_grades
        this.letterGradesForm.letter_grades = this.formatLetterGrades(this.letterGradeItems)
        this.letterGradesForm.letter_grades.replace('%', '')
        await this.submitLetterGrades()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    formatLetterGrades (letterGradeItems) {
      let formattedLetterGrades = ''
      for (let i = 0; i < letterGradeItems.length; i++) {
        formattedLetterGrades += `${letterGradeItems[i]['min']},${letterGradeItems[i]['letter_grade']}`
        if (i !== letterGradeItems.length - 1) {
          formattedLetterGrades += ','
        }
      }
      return formattedLetterGrades
    },
    assignmentGroupWeightsFields: [
      'assignment_group',
      {
        key: 'assignment_group_weight',
        label: 'Weighting Percentage'
      }
    ],
    assignmentGroupWeights: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourse(this.courseId)
    this.getLetterGrades()
    this.initAssignmentGroupWeights()
  },
  methods: {
    isValidLetterGrades () {
      let letterGradesArray = this.letterGradesForm.letter_grades.split(',')
      if (letterGradesArray.length === 1) {
        this.letterGradesForm.errors.set('letter_grades', 'Please enter your list of letter grades and associated minimum scores.')
        return false
      }
      if (letterGradesArray.length % 2 !== 0) {
        this.letterGradesForm.errors.set('letter_grades', 'Not every letter grade has a minimum score associated with it.')
        return false
      }
      let usedLetters = []
      let usedCutoffs = []
      let atLeastOneZero = false
      for (let i = 0; i < letterGradesArray.length / 2; i++) {
        if (isNaN(letterGradesArray[2 * i])) {
          this.letterGradesForm.errors.set('letter_grades', `${letterGradesArray[2 * i]} is not a number.`)
          return false
        }
        if (parseInt(letterGradesArray[2 * i]) === 0) {
          atLeastOneZero = true
        }
        if (letterGradesArray[2 * i] < 0) {
          this.letterGradesForm.errors.set('letter_grades', `${letterGradesArray[2 * i]} should be a positive number.`)
          return false
        }
        if (usedLetters.includes(letterGradesArray[2 * i + 1])) {
          this.letterGradesForm.errors.set('letter_grades', `You used the letter grade "${letterGradesArray[2 * i + 1]}" multiple times.`)
          return false
        } else {
          usedLetters.push(letterGradesArray[2 * i + 1])
        }

        if (usedCutoffs.includes(letterGradesArray[2 * i])) {
          this.letterGradesForm.errors.set('letter_grades', `You used the grade cutoff "${letterGradesArray[2 * i]}" multiple times.`)
          return false
        } else {
          usedCutoffs.push(letterGradesArray[2 * i])
        }
      }
      if (!atLeastOneZero) {
        this.letterGradesForm.errors.set('letter_grades', 'At least one of the letter grades should have a minimum score of 0.')
        return false
      }
      return true
    },
    async submitLetterGrades () {
      this.letterGradesForm.letter_grades = this.letterGradesForm.letter_grades.replace(/%/g, '')
      if (!this.isValidLetterGrades()) {
        return false
      }
      try {
        const { data } = await this.letterGradesForm.patch(`/api/final-grades/letter-grades/${this.courseId}`)
        console.log(data)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-letter-grades-editor')
          this.letterGradeItems = data.letter_grades
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async submitShowWeightedAverage () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/students-can-view-weighted-average`,
          { 'students_can_view_weighted_average': this.studentsCanViewWeightedAverage })
        this.$noty[data.type](data.message)
        if (data.error) {
          return false
        }
        this.studentsCanViewWeightedAverage = !this.studentsCanViewWeightedAverage
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getLetterGrades () {
      try {
        const { data } = await axios.get(`/api/final-grades/letter-grades/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.letterGradeItems = data.letter_grades
        this.roundScores = data.round_scores
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async openLetterGradesEditorModal () {
      try {
        const { data } = await axios.get(`/api/final-grades/letter-grades/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.letterGradeItems = data.letter_grades
        this.$bvModal.show('modal-letter-grades-editor')

        this.letterGradesForm.letter_grades = this.formatLetterGrades(this.letterGradeItems).replace(/%/g, '')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initAssignmentGroupWeights () {
      try {
        const { data } = await axios.get(`/api/assignmentGroupWeights/${this.courseId}`)
        if (data.error) {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentGroupWeights = data.assignment_group_weights
        let formInputs = {}
        for (let i = 0; i < data.assignment_group_weights.length; i++) {
          formInputs[data.assignment_group_weights[i].id] = data.assignment_group_weights[i].assignment_group_weight
        }
        console.log(this.assignmentGroupWeights)
        this.assignmentGroupWeightsForm = new Form(formInputs)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitAssignmentGroupWeights (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.assignmentGroupWeightsForm.patch(`/api/assignmentGroupWeights/${this.courseId}`)
        if (data.form_error) {
          this.assignmentGroupWeightsFormWeightError = data.message
          return false
        }
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-assignment-group-weights')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCourse (courseId) {
      const { data } = await axios.get(`/api/courses/${courseId}`)
      this.course = data.course
      this.letterGradesReleased = Boolean(data.course.letter_grades_released)
    },
    async refreshAccessCode () {
      try {
        const { data } = await axios.patch('/api/course-access-codes', { course_id: this.courseId })
        if (data.type === 'error') {
          this.$noty.error('We were not able to update your access code.')
          return false
        }
        this.$noty.success(data.message)
        this.course.access_code = data.access_code
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async inviteGrader (courseId) {
      this.courseId = courseId
      try {
        const { data } = await axios.get(`/api/grader/${this.courseId}`)
        this.graders = data.graders
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error('We were not able to retrieve your graders.')
          return false
        }
        this.$bvModal.show('modal-manage-graders')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async deleteGrader (userId) {
      try {
        const { data } = await axios.delete(`/api/grader/${this.courseId}/${userId}`)

        if (data.type === 'error') {
          this.$noty.error('We were not able to remove the grader from the course.  Please try again or contact us for assistance.')
          return false
        }
        this.$noty.success(data.message)
        // remove the grad
        this.course.graders = this.course.graders.filter(grader => parseFloat(grader.id) !== parseFloat(userId))
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitInviteGrader (bvModalEvt) {
      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }
      bvModalEvt.preventDefault()
      try {
        this.sendingEmail = true
        const { data } = await this.graderForm.post(`/api/invitations/${this.courseId}`)
        this.$noty[data.type](data.message)
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
      this.sendingEmail = false
    }
  }
}
</script>
<style>
body, html {
  overflow: visible;

}

svg:focus, svg:active:focus {
  outline: none !important;
}
</style>
