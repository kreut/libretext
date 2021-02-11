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
          <b-button class="float-right" variant="primary" @click="submitAssignmentGroupWeights">
            Submit
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
    isLoading: true,
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
          formInputs[data.assignment_group_weights[i].id] = data.assignment_group_weights[i].assignment_group_weight
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
