<template>
  <div class="row">
    <div v-if="user.role === 2" class="col-md-3">
      <b-card v-if="!isLoading" header-html="<h2 class=&quot;h7&quot;>Course Properties</h2>" class="properties-card">
        <ul class="nav flex-column nav-pills">
          <li v-for="tab in filteredTabs()" :key="tab.route" class="nav-item">
            <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
              <span class="hover-underline">{{ tab.name }}</span>
            </router-link>
          </li>
          <li v-if="!formative" class="nav-item">
            <router-link :to="{ name: 'course_properties.analytics' }" class="nav-link">
              <span class="hover-underline">Analytics</span>
            </router-link>
          </li>
          <li v-if="!formative">
            <a :href="`/courses/${courseId}/gradebook`" class="nav-link">
              <span class="hover-underline">Gradebook</span>
            </a>
          </li>
          <li v-if="!formative" class="nav-item">
            <router-link :to="{ name: 'course_properties.reset_course' }" class="nav-link" active-class="active">
              <span class="hover-underline">Reset Course</span>
            </router-link>
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
</template>

<script>

import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
  middleware: 'auth',
  data: () => ({
    lms: false,
    courseId: 0,
    formative: 0,
    isLoading: true
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    tabs () {
      return [
        {
          icon: '',
          name: 'General Information',
          route: 'course_properties.general_info'
        },
        {
          icon: '',
          name: 'Sections',
          route: 'course_properties.sections'
        },
        {
          icon: '',
          name: 'Graders',
          route: 'course_properties.graders'
        },
        {
          icon: '',
          name: 'Students',
          route: 'course_properties.students'
        },
        {
          icon: '',
          name: 'Non-Updated Revisions',
          route: 'course_properties.non_updated_question_revisions',
          params: {courseId: this.courseId}
        },
        {
          icon: '',
          name: 'A11y Redirect',
          route: 'course_properties.a11y_redirect'
        },
        {
          icon: '',
          name: 'Tethered Courses',
          route: 'course_properties.tethered_courses'
        },
        {
          icon: '',
          name: 'Assignment Group Weights',
          route: 'course_properties.assignment_group_weights'
        },
        {
          icon: '',
          name: 'Letter Grades',
          route: 'course_properties.letter_grades'
        },
        {
          icon: '',
          name: 'Ungraded Submissions',
          route: 'course_properties.ungraded_submissions'
        },
        {
          icon: '',
          name: 'Embed Properties',
          route: 'course_properties.iframe_properties'
        },
        {
          icon: '',
          name: 'Testers',
          route: 'course_properties.testers'
        }
      ]
    }
  },
  created () {
    this.courseId = this.$route.params.courseId
  },
  mounted () {
    if (![2, 5].includes(this.user.role)) {
      this.$router.push({ name: 'no.access' })
      return false
    }
    this.getCourseInfo()
  },
  methods: {
    filteredTabs () {
      return this.formative
        ? this.tabs.filter(tab => ['General Information', 'Embed Properties'].includes(tab.name))
        : this.tabs
    },
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.formative = data.course.formative
        this.isLoading = false
        this.$forceUpdate()
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
