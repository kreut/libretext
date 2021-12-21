<template>
  <div>
    <PageTitle title="Submission Overrides" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <div v-if="enrollments.length">
          <b-card header-html="<h2 class=&quot;h7&quot;>Allow Submitting, Uploading PDF, and Assigning (all questions in assignment)</h2>"
                  class="mb-4"
          >
            <b-card-text>
              <p>
                Optionally allow a subset of your class to re-submit any auto-graded or open-ended question
                after an assignment has been closed. Students may also upload a Compiled PDF and Set Pages if the
                Compiled
                PDF option is set for the assignment. If this option is set, it will take precedence over any individual
                override
                set below.
              </p>
              <b-form>
                <RequiredText />
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label-for="assignment_level_apply_to"
                >
                  <template slot="label">
                    Apply To*
                  </template>
                  <b-form-row>
                    <div class="d-flex mt-1">
                      <b-form-select id="assignment_level_apply_to"
                                     v-model="assignmentLevelApplyTo"
                                     cols="5"
                                     aria-required="true"
                                     size="sm"
                                     class="mr-2"
                                     :options="enrollments"
                      />
                      <b-button variant="primary"
                                size="sm"
                                @click="updateOverrides(assignmentLevelOverrides,'assignment-level', assignmentLevelApplyTo)"
                      >
                        Update
                      </b-button>
                    </div>
                  </b-form-row>
                </b-form-group>
              </b-form>
              <div v-if="assignmentLevelOverrides.length">
                <ul v-for="assignmentLevelOverride in assignmentLevelOverrides"
                    :key="`assignment_level_override_${assignmentLevelOverride.value}`"
                >
                  <li>
                    {{ assignmentLevelOverride.text }}
                    <a href="" @click.prevent="removeOverride(assignmentLevelOverride,'assignment-level')">
                      <b-icon-trash class="text-muted" :aria-label="`Remove assignment level override: ${assignmentLevelOverride.text}`" />
                    </a>
                  </li>
                </ul>
                <b-button variant="danger"
                          size="sm"
                          @click="removeOverride({value: -1},'assignment-level')"
                >
                  Remove All Assignment Level Overrides
                </b-button>
              </div>
              <div v-else>
                <b-alert :show="true" variant="info" class="font-weight-bold">
                  No students have been selected.
                </b-alert>
              </div>
            </b-card-text>
          </b-card>
          <div v-if="fileUploadMode !== 'individual_assessment'">
            <b-card header-html="<h2 class=&quot;h7&quot;>Allow Uploading PDF and Assigning (all questions in assignment)</h2>"
                    class="mb-4"
            >
              <b-card-text>
                <p>
                  Optionally allow a subset of your class to upload their compiled PDF and set pages even if
                  an assignment has been closed. This can be useful if individual students with the uploading process.
                </p>
                <b-form>
                  <RequiredText />
                  <b-form-group
                    label-cols-sm="3"
                    label-cols-lg="2"
                    label-for="compiled_pdf_apply_to"
                  >
                    <template slot="label">
                      Apply To*
                    </template>
                    <b-form-row>
                      <div class="d-flex mt-1">
                        <b-form-select id="compiled_pdf_apply_to"
                                       v-model="compiledPDFApplyTo"
                                       aria-required="true"
                                       cols="5"
                                       size="sm"
                                       class="mr-2"
                                       :options="enrollments"
                        />
                        <b-button variant="primary"
                                  size="sm"
                                  @click="updateOverrides(compiledPDFOverrides,'compiled-pdf', compiledPDFApplyTo)"
                        >
                          Update
                        </b-button>
                      </div>
                    </b-form-row>
                  </b-form-group>
                </b-form>
                <div v-if="compiledPDFOverrides.length">
                  <ul v-for="compiledPDFOverride in compiledPDFOverrides"
                      :key="`compiled_pdf_override_${compiledPDFOverride.value}`"
                  >
                    <li>
                      {{ compiledPDFOverride.text }}
                      <a href="" @click.prevent="removeOverride(compiledPDFOverride,'compiled-pdf')">
                        <b-icon-trash class="text-muted" :aria-label="`Remove compiled PDF override: ${compiledPDFOverride.text}`" />
                      </a>
                    </li>
                  </ul>
                  <b-button variant="danger"
                            size="sm"
                            @click="removeOverride({value: -1},'compiled-pdf')"
                  >
                    Remove All Compiled PDF Overrides
                  </b-button>
                </div>
                <div v-else>
                  <b-alert :show="true" variant="info" class="font-weight-bold">
                    No students have been selected.
                  </b-alert>
                </div>
              </b-card-text>
            </b-card>
            <b-card header-html="<h2 class=&quot;h7&quot;>Allow Assigning to Existing Uploaded PDF (all questions in assignment)</h2>" class="mb-4">
              <b-card-text>
                <p>
                  Optionally allow a subset of your class to set pages in their compiled PDF even if
                  an assignment has been closed. Students will not be allowed to upload a new compiled PDF.
                  This can be useful if individual students forgot to do this after
                  uploading their compiled PDF.
                </p>
                <b-form>
                  <RequiredText />
                  <b-form-group
                    label-cols-sm="3"
                    label-cols-lg="2"
                    label-for="set_page_apply_to"
                  >
                    <template slot="label">
                      Apply To*
                    </template>
                    <b-form-row>
                      <div class="d-flex mt-1">
                        <b-form-select id="set_page_apply_to"
                                       v-model="setPageApplyTo"
                                       aria-required="true"
                                       size="sm"
                                       class="mr-2"
                                       :options="enrollments"
                        />
                        <b-button variant="primary"
                                  size="sm"
                                  @click="updateOverrides(setPageOverrides,'set-page-only', setPageApplyTo)"
                        >
                          Update
                        </b-button>
                      </div>
                    </b-form-row>
                  </b-form-group>
                </b-form>
                <div v-if="setPageOverrides.length">
                  <ul v-for="setPageOverride in setPageOverrides"
                      :key="`set_page_override_${setPageOverride.value}`"
                  >
                    <li>
                      {{ setPageOverride.text }}
                      <a href="" @click.prevent="removeOverride(setPageOverride,'set-page-only')">
                        <b-icon-trash class="text-muted" :aria-label="`Remove set page override: ${setPageOverride.text}`" />
                      </a>
                    </li>
                  </ul>
                  <b-button variant="danger"
                            size="sm"
                            @click="removeOverride({value: -1},'set-page-only')"
                  >
                    Remove All Set Page Overrides
                  </b-button>
                </div>
                <div v-else>
                  <b-alert :show="true" variant="info" class="font-weight-bold">
                    No students have been selected.
                  </b-alert>
                </div>
              </b-card-text>
            </b-card>
          </div>
          <b-card header-html="<h2 class=&quot;h7&quot;>Allow Full Submitting (single question in assignment)</h2>" class="mb-4">
            <b-card-text>
              <p>
                Optionally allow a subset of your class to resubmit questions regardless of whether the assignment is
                closed.
              </p>
              <b-form>
                <RequiredText />
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label-for="questions"
                >
                  <template slot="label">
                    Question*
                  </template>
                  <b-form-row>
                    <div class="mt-1">
                      <b-form-select id="questions"
                                     v-model="currentQuestionPage"
                                     aria-required="true"
                                     :options="questionsOptions"
                                     cols="2"
                                     size="sm"
                                     @change="updateQuestionSubmissionTypes"
                      />
                    </div>
                  </b-form-row>
                </b-form-group>
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label="Apply to"
                >
                  <template slot="label">
                    Type<span v-if="showQuestionSubmissionTypes">*</span>
                  </template>
                  <div class="mt-2">
                    <b-form-checkbox-group
                      v-if="showQuestionSubmissionTypes"
                      id="submission_options"
                      v-model="selectedSubmissionTypes"
                      aria-required="true"
                      name="submission options"
                    >
                      <b-form-checkbox value="auto-graded">
                        auto-graded
                      </b-form-checkbox>
                      <b-form-checkbox value="open-ended">
                        open-ended
                      </b-form-checkbox>
                    </b-form-checkbox-group>
                    <span v-if="justAutoGraded">Auto-graded</span>
                    <span v-if="justOpenEnded">Open-ended</span>
                  </div>
                </b-form-group>
                <b-form-group
                  label-cols-sm="3"
                  label-cols-lg="2"
                  label-for="question_level_apply_to"
                >
                  <template slot="label">
                    Apply To*
                  </template>
                  <b-form-row>
                    <div class="d-flex mt-1">
                      <b-form-select id="question_level_apply_to"
                                     v-model="questionLevelApplyTo"
                                     :options="enrollments"
                                     aria-required="true"
                                     size="sm"
                                     cols="5"
                                     class="mr-2"
                      />

                      <b-button variant="primary"
                                size="sm"
                                @click="updateOverrides(questionLevelOverrides,'question-level', questionLevelApplyTo)"
                      >
                        Update
                      </b-button>
                    </div>
                  </b-form-row>
                </b-form-group>
              </b-form>
              <div v-if="questionLevelOverrides.length">
                <ul v-for="questionLevelOverride in questionLevelOverrides"
                    :key="`question_level_override_${questionLevelOverride.value}`"
                >
                  <li>
                    {{ questionLevelOverride.text }}
                    <a href=""
                       @click.prevent="removeOverride(questionLevelOverride,'question-level',questionLevelOverride.question_id)"
                    >
                      <b-icon-trash class="text-muted" :aria-label="`Remove question level override: ${questionLevelOverride.text }`" />
                    </a>
                  </li>
                </ul>
                <b-button variant="danger"
                          size="sm"
                          @click="removeOverride({value: -1},'question-level')"
                >
                  Remove All Question Level Overrides
                </b-button>
              </div>
              <div v-else>
                <b-alert :show="true" variant="info" class="font-weight-bold">
                  No students have been selected.
                </b-alert>
              </div>
            </b-card-text>
          </b-card>
        </div>
      </div>
    </div>
    <div v-if="!enrollments.length">
      <b-alert :show="true" variant="info">
        <span class="font-weight-bold">
          You will be able to provide submission overrides to students once
          students are enrolled in this course.</span>
      </b-alert>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { getQuestions } from '~/helpers/Questions'

export default {
  components: { Loading },
  metaInfo () {
    return { title: 'Assignment Submission Overrides' }
  },
  data: () => ({
    fileUploadMode: '',
    justAutoGraded: false,
    justOpenEnded: false,
    selectedSubmissionTypes: [],
    showQuestionSubmissionTypes: false,
    questionId: 0,
    currentQuestionPage: null,
    questions: [],
    questionsOptions: [],
    assignmentLevelOverrides: [],
    compiledPDFOverrides: [],
    setPageOverrides: [],
    questionLevelOverrides: [],
    assignmentLevelApplyTo: null,
    questionLevelApplyTo: null,
    compiledPDFApplyTo: null,
    setPageApplyTo: null,
    enrollments: [{ 'text': 'Select a student', 'value': null }],
    isLoading: true,
    assignmentId: 0
  }),
  async mounted () {
    this.assignmentId = this.$route.params.assignmentId
    await this.getAssignmentInfo
    this.getQuestions = getQuestions
    await this.getQuestions()
    this.currentQuestionPage = 1
    await this.getEnrolledStudentsFromAssignment()
    if (this.enrollments.length && this.questions.length) {
      await this.getOverrides()
      this.updateQuestionSubmissionTypes()
    }

    this.isLoading = false
  },
  methods: {
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.fileUploadMode = data.assignment.file_upload_mode
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateQuestionSubmissionTypes () {
      let question = this.questions.find(question => question.order === this.currentQuestionPage)
      this.showQuestionSubmissionTypes = question.is_auto_graded && question.is_open_ended
      this.justAutoGraded = question.is_auto_graded && !question.is_open_ended
      if (this.justAutoGraded) {
        this.selectedSubmissionTypes = ['auto-graded']
      }
      this.justOpenEnded = !question.is_auto_graded && question.is_open_ended
      if (this.justOpenEnded) {
        this.selectedSubmissionTypes = ['open-ended']
      }

      this.questionId = question.question_id
    },
    async getOverrides () {
      try {
        const { data } = await axios.get(`/api/submission-overrides/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.compiledPDFOverrides = data.compiled_pdf_overrides
        this.setPageOverrides = data.set_page_overrides
        this.questionLevelOverrides = data.question_level_overrides
        this.assignmentLevelOverrides = data.assignment_level_overrides
        this.assignmentLevelOverride = null
        this.compiledPDFOverride = null
        this.setPageOverride = null
        this.questionLevelOverride = null
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async removeOverride (student, type, questionId = null) {
      try {
        let url = `/api/submission-overrides/${this.assignmentId}/${student.value}/${type}`
        if (questionId) {
          url += `/${questionId}`
        }
        const { data } = await axios.delete(url)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        await this.getOverrides()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async updateOverrides (overrides, type, applyTo) {
      let student = this.enrollments.find(student => student.value === applyTo)
      let alreadyExistsAsOverride = overrides.find(override => student.value === override.value)
      let everybodyChosen = overrides.find(override => override.value === -1)

      if (student.value === null) {
        this.$noty.info('Please choose a student.')
        return false
      }
      if (type !== 'question-level' && alreadyExistsAsOverride) {
        this.$noty.info(`${student.text} is already on your list.`)
        return false
      }
      if (type === 'question-level' && this.showQuestionSubmissionTypes && !this.selectedSubmissionTypes.length) {
        this.$noty.info('Please choose at least one of the submission types.')
        return false
      }

      if (type !== 'question-level' && everybodyChosen) {
        this.$noty.info('All students have already been chosen.')
        return false
      }
      let submissionOverrideData = {
        student: student,
        type: type
      }
      if (type === 'question-level') {
        submissionOverrideData.selected_submission_types = this.selectedSubmissionTypes
        submissionOverrideData.question_id = this.questionId
        submissionOverrideData.question_order = this.currentQuestionPage
      }

      try {
        const { data } = await axios.patch(`/api/submission-overrides/${this.assignmentId}`,
          submissionOverrideData)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        await this.getOverrides()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getEnrolledStudentsFromAssignment () {
      try {
        const { data } = await axios.get(`/api/enrollments/${this.assignmentId}/from-assignment`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.enrollments = data.enrollments.length > 2 ? data.enrollments : []
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
