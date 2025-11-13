<template>
  <div>
    <div v-if="hasAccess">
      <div class="row">
        <div v-if="isAdmin" class="mt-2 mb-2">
          <card title="Control Panel" class="properties-card mt-3">
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
            <router-view/>
          </transition>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  data: () => ({
    isLocal: () => window.config.environment === 'local',
    isBetaAssignment: false,
    courseId: 0,
    hasAccess: false
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isAdmin: () => window.config.isAdmin,
    tabs () {
      return [
        {
          icon: '',
          name: 'Login As',
          route: 'login.as'
        },
        {
          icon: '',
          name: 'Update User Info',
          route: 'updateUserInfo'
        },
        {
          icon: '',
          name: 'Refresh Question Requests',
          route: 'refresh.question.requests'
        },
        {
          icon: '',
          name: 'LTI Integrations',
          route: 'lti.integrations'
        },
        {
          icon: '',
          name: 'Instructor Access Codes',
          route: 'instructorAccessCodes'
        },
        {
          icon: '',
          name: 'Non-Instructor Editors',
          route: 'questionEditors'
        },
        {
          icon: '',
          name: 'Tester Access Codes',
          route: 'testerAccessCodes'
        },
        {
          icon: '',
          name: 'Courses To Reset',
          route: 'coursesToReset'
        },
        {
          icon: '',
          name: 'Classification Manager',
          route: 'classificationManager'
        },
        {
          icon: '',
          name: 'Discipline Manager',
          route: 'disciplineManager'
        },
        {
          icon: '',
          name: 'Sub–Chap–Sect Manager',
          route: 'subjectChapterSectionManager'
        },
        {
          icon: '',
          name: 'Metrics',
          route: 'metrics'
        },
        {
          icon: '',
          name: 'Learning Tree Analytics',
          route: 'LearningTreeAnalytics'
        },
        {
          icon: '',
          name: 'Webwork Submission Errors',
          route: 'WebworkSubmissionErrors'
        }
      ]
    }
  },
  mounted () {
    this.hasAccess = this.isAdmin && (this.user !== null)
    if (!this.hasAccess) {
      this.$router.push({ name: 'no.access' })
      return false
    }
  },
  methods:
    {}
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
