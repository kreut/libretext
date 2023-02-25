<template>
  <div>
    <b-modal
      id="modal-question-editor"
      title="New Question"
      size="xl"
      hide-footer
      no-close-on-backdrop
    >
      <CreateQuestion modal-id="modal-createQuestion-question-editor"
                      :assignment-id="+assignmentId"
                      :parent-get-my-questions="reloadAssignmentQuestions"
      />
    </b-modal>
    <div v-if="[2, 4, 5].includes(user.role)">
      <CannotAddAssessmentToBetaAssignmentModal/>
      <b-container>
        <hr>
      </b-container>
      <div class="row">
        <div class="mt-2 mb-2">
          <b-row class="ml-2">
            <b-button v-if="[2,5].includes(user.role)"
                      size="sm"
                      class="mr-2"
                      variant="primary"
                      @click="getAssessmentsForAssignment(assignmentId)"
            >
              Add Questions
            </b-button>
            <b-button v-if="[2,5].includes(user.role)"
                      size="sm"
                      variant="info"
                      @click="openQuestionEditor()"
            >
              New Question
            </b-button>
          </b-row>
          <b-card header-html="<h2 class=&quot;h7&quot;>Assignment Information</h2>" class="properties-card mt-3">
            <ul class="nav flex-column nav-pills">
              <li v-for="(tab,index) in tabs" :key="`tab-${index}`" class="nav-item">
                <router-link
                  v-if="showTab(tab.name)"
                  :key="tab.route"
                  :to="{ name: tab.route }"
                  class="nav-link"
                  active-class="active"
                >
                  <span class="hover-underline"> {{ tab.name }}</span>
                </router-link>
              </li>
              <li v-if="user.role !== 5">
                <a href="" class="nav-link" @click.prevent="gotoAssignmentGrading()">
                  <span class="hover-underline">  Assignment Grading</span>
                </a>
              </li>
              <router-link v-if="user.role !== 5"
                           :to="{ name: 'instructors.assignments.gradebook' }"
                           class="nav-link"
                           active-class="active"
              >
                <span class="hover-underline"> Assignment Gradebook</span>
              </router-link>
              <li v-if="user.role !== 5">
                <a :href="`/courses/${courseId}/gradebook`" class="nav-link">
                  <span class="hover-underline">  Course Gradebook</span>
                </a>
              </li>
            </ul>
          </b-card>
        </div>

        <div class="col-md-9">
          <transition name="fade" mode="out-in">
            <router-view :key="`router-view-${tabKey}`"/>
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'
import CannotAddAssessmentToBetaAssignmentModal from '~/components/CannotAddAssessmentToBetaAssignmentModal'
import CreateQuestion from '~/components/questions/CreateQuestion'
import { updateModalToggleIndex } from '~/helpers/accessibility/fixCKEditor'

export default {
  middleware: 'auth',
  components: {
    CannotAddAssessmentToBetaAssignmentModal,
    CreateQuestion
  },
  data: () => ({
    tabKey: 0,
    assignmentId: 0,
    nursing: false,
    isBetaAssignment: false,
    courseId: 0
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe,
    tabs () {
      return [
        {
          icon: '',
          name: 'Questions',
          route: 'instructors.assignments.questions'
        },
        {
          icon: '',
          name: 'Case Study Notes',
          route: 'instructors.assignments.case.study.notes'
        },
        {
          icon: '',
          name: 'Summary',
          route: 'instructors.assignments.summary'
        },
        {
          icon: '',
          name: 'Properties',
          route: 'instructors.assignments.properties'
        },
        {
          icon: '',
          name: 'Control Panel',
          route: 'instructors.assignments.control_panel'
        },
        {
          icon: '',
          name: 'Submission Overrides',
          route: 'instructors.assignments.submission_overrides'
        },
        {
          icon: '',
          name: 'Auto-Graded Submissions',
          route: 'instructors.assignments.auto_graded_submissions'
        },
        {
          icon: '',
          name: 'Grader Access',
          route: 'instructors.assignments.grader_access'
        },
        {
          icon: '',
          name: 'Statistics',
          route: 'instructors.assignments.statistics'
        },
        {
          icon: '',
          name: 'Mass Grading',
          route: 'assignment.mass_grading.index'
        }
      ]
    }
  },
  mounted () {
    if (![2, 4, 5].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.nursing = [1, 3279, 3280, 6314, 6732].includes(this.user.id)
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentSummary()
  },
  methods:
    {
      reloadAssignmentQuestions () {
        this.$bvModal.hide('modal-question-editor')
        this.getAssignmentSummary()
        this.tabKey++
        this.$forceUpdate()
      },
      openQuestionEditor () {
        this.$bvModal.show('modal-question-editor')
        this.$nextTick(() => {
          updateModalToggleIndex('modal-question-editor')
        })
      },
      showTab (name) {
        if ((!this.nursing && ['Case Study Notes'].includes(name))) {
          return false
        } else {
          return (this.user.role === 5 && ['Questions', 'Properties'].includes(name)) ||
            this.user.role === 2 ||
            (this.user.role === 4 && !['Grader Access', 'Properties'].includes(name))
        }
      },
      gotoAssignmentGrading () {
        this.$router.push(`/assignments/${this.assignmentId}/grading`)
      },
      gotoMassGrading () {
        this.$router.push(`/assignments/${this.assignmentId}/mass-grading`)
      },
      getAssessmentsForAssignment (assignmentId) {
        this.isBetaAssignment
          ? this.$bvModal.show('modal-cannot-add-assessment-to-beta-assignment')
          : this.$router.push(`/assignments/${assignmentId}/${this.assessmentUrlType}/get`)
      },
      getStudentView (assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/view`)
      },
      async getAssignmentSummary () {
        try {
          const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
          console.log(data)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          this.courseId = data.assignment.course_id
          this.isBetaAssignment = data.assignment.is_beta_assignment
          this.assessmentUrlType = data.assignment.assessment_type === 'learning tree' ? 'learning-trees' : 'questions'
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
    }
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
