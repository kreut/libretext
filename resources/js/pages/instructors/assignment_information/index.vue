<template>
  <div>
    <div v-if="[2, 4, 5].includes(user.role)">
      <CannotAddAssessmentToBetaAssignmentModal/>
      <b-container>
        <hr>
      </b-container>
      <div class="row">
        <div class="mt-2 mb-2">
          <b-row class="ml-3">
            <b-button v-if="[2,5].includes(user.role)"
                      size="sm"
                      variant="primary"
                      @click="getAssessmentsForAssignment(assignmentId)"
            >
              Add Questions
            </b-button>
          </b-row>
          <b-card header-html="<h2 class=&quot;h7&quot;>Assignment Information</h2>" class="properties-card mt-3">
            <ul class="nav flex-column nav-pills">
              <li v-for="tab in tabs" :key="tab.route" class="nav-item">
                <router-link
                  v-if="showTab(tab.name)"
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
            <router-view/>
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

export default {

  middleware: 'auth',
  components: {
    CannotAddAssessmentToBetaAssignmentModal
  },
  data: () => ({
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
        }
      ]
    }
  },
  mounted () {
    if (![2, 4, 5].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.nursing = [1, 3279, 3280].includes(this.user.id)
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentSummary()
  },
  methods:
    {
      showTab (name) {
        if ((!this.nursing && ['Case Study Notes'].includes(name))) {
          return false
        } else {
          return (this.user.role === 5 && ['Questions', 'Properties'].includes(name)) ||
            this.user.role === 2 ||
            (this.user.role === 4 && !['Grader Access', 'Properties', 'Submission Overrides'].includes(name))
        }
      },
      gotoAssignmentGrading () {
        this.$router.push(`/assignments/${this.assignmentId}/grading`)
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
