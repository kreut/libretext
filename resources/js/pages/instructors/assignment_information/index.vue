<template>
  <div>
    <div v-if="[2, 4].includes(user.role)">
      <b-container>
        <b-row align-h="end">
          <b-button v-if="user.role === 2" class="ml-3 mb-2" variant="primary" @click="getAssessmentsForAssignment(assignmentId)">
            Get Assessments
          </b-button>
          <b-button class="ml-3 mb-2" variant="primary" @click="getStudentView(assignmentId)">
            View Assessments
          </b-button>
        </b-row>
        <hr>
      </b-container>
      <div class="row">
        <div class="col-md-3">
          <card title="Assignment Information" class="properties-card">
            <ul class="nav flex-column nav-pills">
              <li v-for="tab in tabs" :key="tab.route" class="nav-item">
                <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
                  {{ tab.name }}
                </router-link>
              </li>
            </ul>
          </card>
        </div>

        <div class="col-md-9">
          <transition name="fade" mode="out-in">
            <router-view />
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  middleware: 'auth',
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    tabs () {
      return [
        {
          icon: '',
          name: 'Summary',
          route: 'assignment.summary'
        },
        {
          icon: '',
          name: 'Statistics',
          route: 'assignment.statistics'
        },
        {
          icon: '',
          name: 'Questions',
          route: 'assignment.questions'
        },
        {
          icon: '',
          name: 'Gradebook',
          route: 'assignment.gradebook'
        }
      ]
    }
  },
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('sYou do not have access to the assignment properties page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentSummary()
  },
  methods:
    {
      getAssessmentsForAssignment (assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/${this.assessmentUrlType}/get`)
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
