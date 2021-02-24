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
        <b-card header="default" header-html="Assignment Group Weights">
          <b-card-text>
            <p>
              Tell Adapt how you would like to weight your assignment groups which are currently associated with your
              assignments.
            </p>
            <p v-if="hasExtraCredit">
              Your assignment weights must sum to 100. The Extra Credit will be applied after the score is computed
              using the assignment weights. For example,
              if a student has an average of 90 and you provide up to 3 points for extra credit, the student can receive
              up to 93 points total for the course.
            </p>

            <b-table striped hover :fields="assignmentGroupWeightsFields" :items="assignmentGroupWeights"
                     class="border border-1 rounded"
            >
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

            <b-form-group v-if="extraCreditId>0"
                          id="extra_credit"
                          label-cols-sm="3"
                          label-cols-lg="2"
                          label-for="Extra Credit"
            >
              <template slot="label">
                <b-icon-star-fill varient="info" variant="warning" />
                Extra Credit
              </template>
              <b-col lg="2">
                <b-form-input
                  id="extra_credit"
                  v-model="assignmentGroupWeightsForm[extraCreditId]"
                  type="text"
                  :class="{ 'is-invalid': assignmentGroupWeightsFormWeightError }"
                  @keydown="assignmentGroupWeightsFormWeightError = ''"
                />
              </b-col>
            </b-form-group>

            <div class="ml-5">
              <b-form-invalid-feedback :state="false">
                {{ assignmentGroupWeightsFormWeightError }}
              </b-form-invalid-feedback>
            </div>
          </b-card-text>
          <b-button class="float-right" variant="primary" size="sm" @click="submitAssignmentGroupWeights">
            Update Assignment Group Weights
          </b-button>
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
    assignmentGroupWeights: []
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourse(this.courseId)
    this.initAssignmentGroupWeights()
  },
  methods: {
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
        this.assignmentGroupWeightsForm = new Form(formInputs)
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
