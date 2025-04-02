<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-letter-grades'"/>
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
        <b-modal
          id="modal-letter-grades-editor"
          ref="modal"
          title="Letter Grades"
        >
          <p>
            Use the text area below to customize your letter grades, in a comma separated list of
            the form "Minimum score for the group, Letter Grade". As an example, if the three letter grades that you
            offer
            are
            A, B, and C, and students need at least a 60% to pass the course, you might enter 90,A,70,B,60,C,0,F.
          </p>
          <p>The field below is required.</p>

          <b-form-input
            id="letter_grades"
            v-model="letterGradesForm.letter_grades"
            title="Comma separated list of letter grades"
            type="text"
            placeholder=""
            required
            :class="{ 'is-invalid': letterGradesForm.errors.has('letter_grades') }"
            @keydown="letterGradesForm.errors.clear('letter_grades')"
          />
          <has-error :form="letterGradesForm" field="letter_grades"/>
          <div slot="modal-footer">
            <b-btn size="sm" @click="$bvModal.hide('modal-letter-grades-editor')">
              Cancel
            </b-btn>
            <b-btn variant="info" size="sm" @click="resetLetterGradesToDefault">
              Reset to Default
            </b-btn>
            <b-btn variant="primary" size="sm" @click="submitLetterGrades">
              Submit
            </b-btn>
          </div>
        </b-modal>
        <div class="mb-3">
          <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Assignment Group Weights</h2>">
            <b-card-text>
              <div v-if="lms">
                <b-alert variant="info" :show="true">
                <span class="font-weight-bold">
                  This course is run through an LMS.  You should use the LMS to assign weights to assignment groups.
                </span>
                </b-alert>
              </div>
              <div v-else>
                <p>
                  Tell ADAPT how you would like to weight your assignment groups which are currently associated with
                  your
                  assignments.
                </p>
                <p v-if="hasExtraCredit">
                  Your assignment weights must sum to 100. The Extra Credit will be applied after the score is computed
                  using the assignment weights. For example,
                  if a student has an average of 90 and you provide up to 3 points for extra credit, the student can
                  receive
                  up to 93 points total for the course.
                </p>
                <b-alert :show="weightsTotal !== 100 || weightHas0Entry" variant="info">
                <span class="font-weight-bold">
                  <span v-show="weightsTotal !== 100"
                  > The total of your assignment group weights does not sum to 100.</span>
                  <span v-show="weightHas0Entry">  At least one of your weights has a 0 entry.</span>
                </span>
                </b-alert>
                <b-table striped
                         hover
                         aria-label="Assignment group weights"
                         :fields="assignmentGroupWeightsFields"
                         :items="assignmentGroupWeights"
                         class="border border-1 rounded"
                >
                  <template v-slot:cell(assignment_group)="data">
                    <label :for="`assignment_group_id_${data.item.id}`">{{ data.item.assignment_group }}</label>
                  </template>
                  <template v-slot:cell(assignment_group_weight)="data">
                    <b-col lg="5">
                      <b-form-input
                        :id="`assignment_group_id_${data.item.id}`"
                        v-model="assignmentGroupWeightsForm[data.item.id]"
                        type="text"
                        :class="{ 'is-invalid': assignmentGroupWeightsFormWeightError }"
                        @keydown="assignmentGroupWeightsFormWeightError = ''"
                        @keyup="validateWeightsSumTo100"
                      />
                    </b-col>
                  </template>
                </b-table>

                <b-form-group v-if="extraCreditId>0"
                              label-cols-sm="5"
                              label-cols-lg="4"
                              label-for="extra_credit_weight"
                >
                  <template slot="label">
                    <b-icon-star-fill varient="info" variant="warning"/>
                    Extra Credit Weight
                  </template>
                  <b-col lg="3">
                    <b-form-input
                      id="extra_credit_weight"
                      v-model="assignmentGroupWeightsForm[extraCreditId]"
                      type="text"
                      :class="{ 'is-invalid': assignmentGroupWeightsFormWeightError }"
                      @keydown="assignmentGroupWeightsFormWeightError = ''"
                    />
                  </b-col>
                </b-form-group>

                <b-button class="float-right" variant="primary" size="sm" @click="submitAssignmentGroupWeights">
                  Update Assignment Group Weights
                </b-button>
              </div>
            </b-card-text>
          </b-card>
        </div>
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Letter Grades</h2>">
          <b-card-text>
            <div v-if="lms">
              <b-alert variant="info" :show="true">
                <span class="font-weight-bold">
                  This course is run through an LMS.  You should use the LMS to determine how you determine letter grades.
                </span>
              </b-alert>
            </div>
            <div v-else>
              <p>
                Optionally, you can allow your students to view their progress report (assignment group summary of total
                number of points earned in relation
                to total number of points). In addition, we can provide your students with z-scores to give
                them a sense of relative standing.
              </p>
              <p>
                In addition, ADAPT can convert your students' weighted scores into letter grades or grade
                categories.
                Some examples might be "A+, A, A-,..." or
                "Excellent, Good, Unsatisfactory". You can use the
                <b-link @click="openLetterGradesEditorModal">
                  letter grade editor
                </b-link>
                to customize the letter grades.
              </p>
              Show progress report
              <toggle-button
                tabindex="0"
                class="mt-2"
                :width="55"
                :value="showProgressReport"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :aria-label="showProgressReport ? 'Progress report shown' : 'Progress report not shown'"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitShowProgressReport()"
              />
              <br>
              Show z-scores
              <toggle-button
                tabindex="0"
                class="mt-2"
                :width="55"
                :value="showZScores"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :aria-label="showZScores ? 'Z-scores shown' : 'Z-scores not shown'"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitShowZScores()"
              />
              <br>
              Release weighted averages:
              <toggle-button
                tabindex="0"
                class="mt-2"
                :width="55"
                :value="studentsCanViewWeightedAverage"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :aria-label="studentsCanViewWeightedAverage ? 'Students can view weighted averages' : 'Students cannot view weighted averages'"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitShowWeightedAverage()"
              />
              <br>
              <span>Release letter grades </span>
              <toggle-button
                tabindex="0"
                class="mt-2"
                :width="55"
                :value="letterGradesReleased"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :aria-label="letterGradesReleased ? 'Letter grades released' : 'Letter grades not released'"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitReleaseLetterGrades()"
              />
              <br>
              <span>When determining the letter grades, round the weighted scores to the nearest integer:</span>
              <toggle-button
                tabindex="0"
                class="mt-2"
                :width="55"
                :value="Boolean(roundScores)"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="toggleColors"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitRoundScores()"
              />
              <b-table striped
                       hover
                       aria-label="Letter grades"
                       :sticky-header="true"
                       :fields="letterGradeFields" :items="letterGradeItems"
              />
            </div>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading,
    AllFormErrors
  },
  metaInfo () {
    return { title: 'Course Letter Grades' }
  },
  data: () => ({
    lms: false,
    weightHas0Entry: false,
    weightsTotal: 0,
    course: {},
    extraCreditId: 0,
    isLoading: true,
    hasExtraCredit: false,
    letterGradesReleased: false,
    assignmentGroupWeightsFormWeightError: '',
    assignmentGroupWeightsForm: {},
    assignmentGroupWeightsFields: [
      'assignment_group',
      {
        key: 'assignment_group_weight',
        label: 'Weighting Percentage'
      }
    ],
    assignmentGroupWeights: [],
    showProgressReport: false,
    allFormErrors: [],
    toggleColors: window.config.toggleColors,
    showZScores: false,
    letterGradeFields: [
      {
        key: 'letter_grade',
        isRowHeader: true
      },
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
    letterGradesForm: new Form({
      letter_grades: ''
    }),
    studentsCanViewWeightedAverage: false,
    assignmentGroupForm: new Form({
      assignment_group: ''
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    if (this.user.role !== 2) {
      this.isLoading = false
      return false
    }
    this.getCourse(this.courseId)
    this.getLetterGrades()
    this.initAssignmentGroupWeights()
  },
  methods: {
    validateWeightsSumTo100 () {
      this.weightsTotal = 0
      for (let i = 0; i < this.assignmentGroupWeights.length; i++) {
        if (this.assignmentGroupWeights[i]['assignment_group'] !== 'Extra Credit') {
          let id = this.assignmentGroupWeights[i].id
          this.weightsTotal += parseFloat(this.assignmentGroupWeightsForm[id])
        }
      }
    },
    async initAssignmentGroupWeights () {
      try {
        const { data } = await axios.get(`/api/assignmentGroupWeights/${this.courseId}`)
        console.log(data)
        this.isLoading = false
        if (data.error) {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentGroupWeights = data.assignment_group_weights
        this.lms = data.lms

        let formInputs = {}
        for (let i = 0; i < data.assignment_group_weights.length; i++) {
          if (data.assignment_group_weights[i]['assignment_group'] === 'Extra Credit') {
            this.hasExtraCredit = true
            this.extraCreditId = data.assignment_group_weights[i].id
            formInputs[this.extraCreditId] = data.assignment_group_weights[i].assignment_group_weight
            this.extraCreditInput = data.assignment_group_weights[i].assignment_group_weight
            this.assignmentGroupWeights.splice(i, 1)
          } else {
            formInputs[data.assignment_group_weights[i].id] = data.assignment_group_weights[i].assignment_group_weight
          }
        }
        console.log(this.assignmentGroupWeights)
        console.log(formInputs)
        this.assignmentGroupWeightsForm = new Form(formInputs)
        this.validateWeightsSumTo100()
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    },
    async submitAssignmentGroupWeights () {
      try {
        const { data } = await this.assignmentGroupWeightsForm.patch(`/api/assignmentGroupWeights/${this.courseId}`)
        if (data.form_error) {
          this.assignmentGroupWeightsFormWeightError = data.message
          this.$noty.error(data.message)
          return false
        }
        this.$noty[data.type](data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCourse (courseId) {
      const { data } = await axios.get(`/api/courses/${courseId}`)
      this.course = data.course
      this.letterGradesReleased = Boolean(data.course.letter_grades_released)
      this.studentsCanViewWeightedAverage = Boolean(data.course.students_can_view_weighted_average)
      this.showZScores = Boolean(data.course.show_z_scores)
      this.showProgressReport = Boolean(data.course.show_progress_report)
      this.lms = data.course.lms
    },
    async submitShowProgressReport () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/show-progress-report`,
          { 'show_progress_report': this.showProgressReport })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.showProgressReport = !this.showProgressReport
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitShowZScores () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/show-z-scores`,
          { 'show_z_scores': this.showZScores })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.showZScores = !this.showZScores
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
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
    showLetterGradeFormErrors () {
      this.$nextTick(() => fixInvalid())

      this.allFormErrors = this.letterGradesForm.errors.flatten()
      this.$bvModal.show('modal-form-errors-letter-grades')
    },
    async submitLetterGrades () {
      this.letterGradesForm.letter_grades = this.letterGradesForm.letter_grades.replace(/%/g, '')
      if (!this.isValidLetterGrades()) {
        this.$nextTick(() => this.showLetterGradeFormErrors())
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
        } else {
          this.allFormErrors = this.letterGradesForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-letter-grades')
          this.$nextTick(() => {
            fixInvalid()
          })
        }
      }
    },
    async submitShowWeightedAverage () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/students-can-view-weighted-average`,
          { 'students_can_view_weighted_average': this.studentsCanViewWeightedAverage })
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
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
      this.isLoading = false
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
